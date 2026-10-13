<?php
namespace App\Services;

use App\Models\Planilla;
use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class XlsxExporter
{
    public function descargar(iterable $registros,string $nombre,iterable $planillas=[]): BinaryFileResponse
    {
        $groups=[];
        foreach($planillas as $p) { $groups[$p->id]=$this->grupo($p); }
        foreach($registros as $r) {
            $p=$r->planilla;
            $groups[$p->id] ??= $this->grupo($p);
            $groups[$p->id]['registros'][]=[
                'orden'=>$r->id,'equipo'=>$r->tipo_nombre,'mac'=>$r->mac,'ot'=>$r->ot,
                'fecha_ot'=>$r->fecha_ot?->format('Y-m-d'),
            ];
        }
        $groups=array_values($groups);
        usort($groups,fn($a,$b)=>strcmp($b['fecha'],$a['fecha']) ?: ($a['id']<=>$b['id']));
        $layout=json_decode(File::get(resource_path('excel/layout.json')),true,512,JSON_THROW_ON_ERROR);
        File::ensureDirectoryExists(storage_path('app/exports'));
        $path=tempnam(storage_path('app/exports'),'grilla-');
        if($path===false || !copy(resource_path('excel/plantilla_grillas.xlsx'),$path)) {
            throw new RuntimeException('No se pudo preparar la plantilla de Excel.');
        }
        $zip=new ZipArchive();
        if($zip->open($path)!==true) { @unlink($path); throw new RuntimeException('No se pudo abrir la plantilla de Excel.'); }
        try {
            $rows=''; $merges=[]; $cursor=1; $dateCounts=[]; $totalHeight=0.0;
            foreach($groups as $group) {
                $styles=$layout['styles'];
                if(($dateCounts[$group['fecha']] ?? 0)<2) {
                    $rows.=$this->fila($cursor++,[$this->fechaExcel($group['fecha'])],[$styles['date']],$layout['dateHeight'],[0]);
                    $totalHeight+=$layout['dateHeight'];
                }
                $dateCounts[$group['fecha']]=($dateCounts[$group['fecha']] ?? 0)+1;
                $heading=$cursor;
                $team=mb_strtoupper($group['responsable'].($group['dupla']?' - '.$group['dupla']:''));
                $height=max($layout['rowHeight'],ceil(mb_strlen($team)/80)*$layout['rowHeight']);
                $rows.=$this->fila($cursor++,[$group['movil'],$team,null,null],$styles['team'],$height);
                $merges[]='B'.$heading.':D'.$heading;
                $totalHeight+=$height+$layout['rowHeight'];
                $rows.=$this->fila($cursor++,['EQUIPOS','MAC','OT','FECHA'],$styles['headers'],$layout['rowHeight']);
                $items=$group['registros'];
                usort($items,fn($a,$b)=>$a['orden']<=>$b['orden']);
                if(!$items) { $items=[['equipo'=>null,'mac'=>null,'ot'=>null,'fecha_ot'=>null]]; }
                foreach($items as $i=>$item) {
                    $last=$i===count($items)-1;
                    $height=max($layout['rowHeight'],ceil(strlen($item['mac'] ?? '')/22)*$layout['rowHeight']);
                    $totalHeight+=$height;
                    $rows.=$this->fila($cursor++,[
                        $item['equipo'],$item['mac'],$item['ot'],
                        $item['fecha_ot']!==null?$this->fechaExcel($item['fecha_ot']):null,
                    ],$styles[$last?'last':'body'],$height,[3]);
                }
                $totalHeight+=$layout['gapHeight'];
                $rows.='<row r="'.$cursor++.'" ht="'.$layout['gapHeight'].'" customHeight="1"/>';
            }
            $lastRow=max(1,$cursor-2);
            $fitHeight=count($dateCounts)<=1 && $totalHeight-$layout['gapHeight']<=805?1:0;
            $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:D'.$lastRow.'"/><sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="'.$layout['rowHeight'].'"/><cols>';
            foreach($layout['widths'] as $i=>$width) { $xml.='<col min="'.($i+1).'" max="'.($i+1).'" width="'.$width.'" customWidth="1"/>'; }
            $xml.='</cols><sheetData>'.$rows.'</sheetData>';
            if($merges) {
                $xml.='<mergeCells count="'.count($merges).'">';
                foreach($merges as $merge) { $xml.='<mergeCell ref="'.$merge.'"/>'; }
                $xml.='</mergeCells>';
            }
            $xml.='<printOptions horizontalCentered="1" gridLines="0" headings="0"/><pageMargins left="0.3" right="0.3" top="0.25" bottom="0.25" header="0" footer="0"/><pageSetup paperSize="9" orientation="portrait" fitToWidth="1" fitToHeight="'.$fitHeight.'"/></worksheet>';
            $zip->addFromString('xl/worksheets/sheet1.xml',$xml);
            // El Excel exportado contiene únicamente los datos elegidos, también en las cadenas internas.
            if($zip->locateName('xl/sharedStrings.xml')!==false) {
                $zip->addFromString('xl/sharedStrings.xml','<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0"/>');
            }
            $book=new DOMDocument();
            $book->loadXML($zip->getFromName('xl/workbook.xml'),LIBXML_NONET);
            $xp=new DOMXPath($book); $xp->registerNamespace('s','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $names=$xp->query('/s:workbook/s:definedNames')->item(0);
            if(!$names) { $names=$book->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main','definedNames'); $book->documentElement->appendChild($names); }
            foreach(iterator_to_array($xp->query('s:definedName[@name="_xlnm.Print_Area"]',$names)) as $old) { $names->removeChild($old); }
            $print=$book->createElementNS('http://schemas.openxmlformats.org/spreadsheetml/2006/main','definedName');
            $print->setAttribute('name','_xlnm.Print_Area'); $print->setAttribute('localSheetId','0');
            $print->appendChild($book->createTextNode("'Grillas'!\$A\$1:\$D\$".$lastRow)); $names->appendChild($print);
            $zip->addFromString('xl/workbook.xml',$book->saveXML());
            $zip->close();
        } catch(\Throwable $e) { $zip->close(); @unlink($path); throw $e; }
        return response()->download($path,$nombre,['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function grupo(Planilla $p): array
    {
        return ['id'=>$p->id,'fecha'=>$p->fecha->format('Y-m-d'),'movil'=>$p->movil_codigo,
            'responsable'=>$p->responsable_nombre,'dupla'=>$p->dupla_nombre,'registros'=>[]];
    }

    private function fechaExcel(string $date): int
    {
        return (int)(new DateTimeImmutable('1899-12-30',new DateTimeZone('UTC')))->diff(new DateTimeImmutable($date,new DateTimeZone('UTC')))->format('%r%a');
    }

    private function fila(int $row,array $values,array $styles,float $height,array $numeric=[]): string
    {
        $xml='<row r="'.$row.'" ht="'.$height.'" customHeight="1">';
        foreach($values as $i=>$value) {
            $ref=chr(65+$i).$row; $style=$styles[$i];
            if($value===null || $value==='') { $xml.='<c r="'.$ref.'" s="'.$style.'"/>'; continue; }
            if(in_array($i,$numeric,true)) { $xml.='<c r="'.$ref.'" s="'.$style.'"><v>'.(int)$value.'</v></c>'; continue; }
            $safe=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/','',(string)$value) ?? '';
            $safe=htmlspecialchars($safe,ENT_XML1 | ENT_QUOTES,'UTF-8');
            $xml.='<c r="'.$ref.'" t="inlineStr" s="'.$style.'"><is><t xml:space="preserve">'.$safe.'</t></is></c>';
        }
        return $xml.'</row>';
    }
}
