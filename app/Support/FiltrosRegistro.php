<?php
namespace App\Support;
use App\Models\Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
class FiltrosRegistro {
    public static function validar(Request $request,bool $historial=false): array {
        $rules=['movil_id'=>['nullable','integer','exists:moviles,id'],'tipo_equipo_id'=>['nullable','integer','exists:tipos_equipo,id'],'estado'=>['nullable','in:pendiente,asignado'],'q'=>['nullable','string','max:80']];
        $rules += $historial ? ['desde'=>['nullable','date_format:Y-m-d'],'hasta'=>['nullable','date_format:Y-m-d']] : ['fecha'=>['nullable','date_format:Y-m-d']];
        if($historial && $request->filled('desde')) { $rules['hasta'][]='after_or_equal:desde'; }
        return $request->validate($rules);
    }
    public static function aplicar(Builder $q,array $f): Builder {
        if(!empty($f['movil_id'])) { $q->whereHas('planilla',fn($p)=>$p->where('movil_id',$f['movil_id'])); }
        if(!empty($f['tipo_equipo_id'])) { $q->where('tipo_equipo_id',$f['tipo_equipo_id']); }
        if(($f['estado'] ?? '')==='pendiente') { $q->whereNull('ot'); }
        if(($f['estado'] ?? '')==='asignado') { $q->whereNotNull('ot'); }
        if(!empty($f['q'])) {
            $raw=str_replace(['%','_'], '', trim($f['q']));
            $mac=CodigoEquipo::normalizar($raw);
            $q->where(fn($w)=>$w->where('mac','like','%'.$mac.'%')->orWhere('ot','like','%'.$raw.'%'));
        }
        return $q;
    }
    public static function historial(array $f): Builder {
        $q=self::aplicar(Registro::with('planilla'),$f);
        if(!empty($f['desde'])) { $q->where('fecha_registro','>=',$f['desde']); }
        if(!empty($f['hasta'])) { $q->where('fecha_registro','<=',$f['hasta']); }
        return $q->orderByDesc('fecha_registro')->orderByDesc('id');
    }
}
