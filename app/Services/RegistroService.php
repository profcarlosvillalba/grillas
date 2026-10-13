<?php
namespace App\Services;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\TipoEquipo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class RegistroService {
    public function crear(Planilla $planilla,array $data,int $userId): Registro {
        try {
            return Registro::create([
                'planilla_id'=>$planilla->id,'tipo_equipo_id'=>$data['tipo_equipo_id'],
                'tipo_nombre'=>TipoEquipo::findOrFail($data['tipo_equipo_id'])->nombre,
                'fecha_registro'=>$planilla->fecha,'mac'=>$data['mac'],'ot'=>null,'fecha_ot'=>null,
                'created_by'=>$userId,'updated_by'=>$userId,
            ]);
        } catch(UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['mac'=>'Este equipo ya está registrado en esa fecha. Actualizá la grilla.']);
        }
    }
    public function actualizar(Registro $registro,array $data,int $userId): Registro {
        try {
            return DB::transaction(function() use($registro,$data,$userId): Registro {
                $version=(int)$data['version']; unset($data['version']);
                if(isset($data['tipo_equipo_id'])) { $data['tipo_nombre']=TipoEquipo::findOrFail($data['tipo_equipo_id'])->nombre; }
                $changed=Registro::whereKey($registro->id)->where('version',$version)->update($data+['updated_by'=>$userId,'version'=>$version+1,'updated_at'=>now()]);
                abort_if($changed!==1,409,'Otra persona modificó este registro. Actualizá la página antes de guardar.');
                return $registro->fresh();
            });
        } catch(UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['mac'=>'Este equipo ya está registrado en esa fecha.']);
        }
    }
}
