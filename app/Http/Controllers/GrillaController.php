<?php
namespace App\Http\Controllers;
use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\TipoEquipo;
use App\Services\XlsxExporter;
use App\Support\FiltrosRegistro;
use Illuminate\Http\Request;
class GrillaController extends Controller {
    public function index(Request $request) {
        $f=FiltrosRegistro::validar($request);
        $fecha=$f['fecha'] ?? now()->format('Y-m-d');
        $filtered=!empty($f['q']) || !empty($f['estado']) || !empty($f['tipo_equipo_id']);
        $query=Planilla::where('fecha',$fecha)->when(!empty($f['movil_id']),fn($q)=>$q->where('movil_id',$f['movil_id']));
        if($filtered) { $query->whereHas('registros',fn($q)=>FiltrosRegistro::aplicar($q,$f)); }
        $planillas=$query->with(['registros'=>fn($q)=>FiltrosRegistro::aplicar($q->getQuery(),$f)->orderBy('id')])->withCount('registros')->orderBy('movil_codigo')->get();
        $total=Registro::where('fecha_registro',$fecha)->count();
        $asignados=Registro::where('fecha_registro',$fecha)->whereNotNull('ot')->count();
        return view('grilla.index',[
            'fecha'=>$fecha,'planillas'=>$planillas,'filtered'=>$filtered,
            'moviles'=>Movil::orderBy('codigo')->get(),'tipos'=>TipoEquipo::orderBy('nombre')->get(),
            'tiposActivos'=>TipoEquipo::where('activo',true)->orderBy('nombre')->get(),
            'total'=>$total,'asignados'=>$asignados,'pendientes'=>$total-$asignados,
            'cantidadMoviles'=>Planilla::where('fecha',$fecha)->count(),
        ]);
    }
    public function historial(Request $request) {
        $f=FiltrosRegistro::validar($request,true);
        return view('grilla.historial',[
            'registros'=>FiltrosRegistro::historial($f)->paginate(40)->withQueryString(),
            'moviles'=>Movil::orderBy('codigo')->get(),'tipos'=>TipoEquipo::orderBy('nombre')->get(),
        ]);
    }
    public function exportar(Request $request,XlsxExporter $excel) {
        $historial=$request->boolean('historial');
        $planillas=[];
        $f=FiltrosRegistro::validar($request,$historial);
        if($historial) { $q=FiltrosRegistro::historial($f); $nombre='historial-equipos.xlsx'; }
        else {
            $fecha=$f['fecha'] ?? now()->format('Y-m-d');
            $q=FiltrosRegistro::aplicar(Registro::with('planilla')->where('fecha_registro',$fecha),$f)->orderBy('planilla_id')->orderBy('id');
            $nombre='grilla-'.$fecha.'.xlsx';
            if(empty($f['q']) && empty($f['estado']) && empty($f['tipo_equipo_id'])) {
                $planillas=Planilla::where('fecha',$fecha)->when(!empty($f['movil_id']),fn($p)=>$p->where('movil_id',$f['movil_id']))->orderBy('id')->get();
            }
        }
        return $excel->descargar($q->lazy(500),$nombre,$planillas);
    }
}
