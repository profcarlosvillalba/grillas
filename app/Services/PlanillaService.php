<?php
namespace App\Services;
use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Tecnico;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanillaService {
    public function guardar(array $data,int $userId,?Planilla $planilla=null): Planilla {
        try {
            return DB::transaction(function() use($data,$userId,$planilla): Planilla {
                $p=$planilla ? Planilla::whereKey($planilla->id)->lockForUpdate()->firstOrFail() : new Planilla();
                $tecnicos=Tecnico::whereIn('id',array_filter([$data['responsable_id'],$data['dupla_id'] ?? null]))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $work=$tecnicos->get($data['responsable_id']);
                $dupla=!empty($data['dupla_id']) ? $tecnicos->get($data['dupla_id']) : null;
                $cambiaWork=!$p->exists || (int)$p->responsable_id!==(int)$data['responsable_id'];
                $fecha=$p->exists ? $p->fecha->format('Y-m-d') : $data['fecha'];
                if(!$work || ($cambiaWork && (!$work->activo || !$work->legajo))) {
                    throw ValidationException::withMessages(['responsable_id'=>'El productivo debe estar activo y tener su legajo cargado.']);
                }
                if(!empty($data['dupla_id']) && (!$dupla || (!$dupla->activo && (!$p->exists || (int)$p->dupla_id!==$dupla->id)))) {
                    throw ValidationException::withMessages(['dupla_id'=>'Seleccioná una dupla activa.']);
                }
                if($dupla?->id===$work->id) {
                    throw ValidationException::withMessages(['dupla_id'=>'El productivo y la dupla deben ser personas distintas.']);
                }
                if($cambiaWork) {
                    if(Planilla::where('fecha',$fecha)->where('responsable_id',$work->id)->when($p->exists,fn($q)=>$q->where('id','<>',$p->id))->exists()) {
                        throw ValidationException::withMessages(['responsable_id'=>'Este productivo ya tiene una grilla en esa fecha.']);
                    }
                    // Los códigos de móvil pasan a ser el legajo del productivo.
                    // La tabla existente se conserva para los filtros y el historial.
                    $movil=Movil::firstOrCreate(['codigo'=>$work->legajo],['activo'=>true]);
                    $p->fill(['movil_id'=>$movil->id,'movil_codigo'=>$work->legajo,'responsable_nombre'=>$work->etiqueta()]);
                }
                if(!$p->exists || (int)$p->dupla_id!==(int)($dupla?->id)) { $p->dupla_nombre=$dupla?->etiqueta(); }
                $p->fill(['responsable_id'=>$work->id,'dupla_id'=>$dupla?->id]);
                if(!$p->exists) { $p->fill(['fecha'=>$fecha,'created_by'=>$userId]); }
                $p->save();
                return $p;
            });
        } catch(UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['responsable_id'=>'Ese legajo ya tiene una grilla en esa fecha. Abrí la grilla existente.']);
        }
    }
}
