<?php
namespace Database\Seeders;
use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;
class PlanillasSeeder extends Seeder {
    public function run(): void {
        $user=User::where('role','admin')->where('active',true)->orderBy('id')->first();
        if(!$user) { throw new RuntimeException('Se necesita una cuenta administradora activa para cargar las grillas de ejemplo.'); }
        $datos=PlanillaFoto::datos();
        foreach($datos['grillas'] as $grilla) {
            $movil=Movil::where('codigo',$grilla['movil'])->firstOrFail();
            $responsable=Tecnico::where('apellido',$grilla['responsable'])->whereNull('nombre')->firstOrFail();
            $dupla=Tecnico::where('apellido',$grilla['dupla'])->whereNull('nombre')->firstOrFail();
            $planilla=Planilla::firstOrCreate(['fecha'=>$datos['fecha'],'movil_id'=>$movil->id],[
                'responsable_id'=>$responsable->id,'dupla_id'=>$dupla->id,'movil_codigo'=>$movil->codigo,
                'responsable_nombre'=>$responsable->etiqueta(),'dupla_nombre'=>$dupla->etiqueta(),'created_by'=>$user->id,
            ]);
            foreach($grilla['equipos'] as [$tipoNombre,$mac]) {
                $tipo=TipoEquipo::where('nombre',$tipoNombre)->firstOrFail();
                // Una asignación modificada por el usuario nunca se vuelve a dejar vacía.
                Registro::firstOrCreate(['fecha_registro'=>$datos['fecha'],'mac'=>$mac],[
                    'planilla_id'=>$planilla->id,'tipo_equipo_id'=>$tipo->id,'tipo_nombre'=>$tipo->nombre,
                    'ot'=>null,'fecha_ot'=>null,'version'=>1,'created_by'=>$user->id,'updated_by'=>$user->id,
                ]);
            }
        }
        $this->command?->info('Ejemplo cargado: 7 grillas y 35 equipos. Fecha: 26/07/2025. Repetir db:seed no duplica estos datos.');
    }
}

