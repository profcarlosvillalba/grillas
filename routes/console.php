<?php
use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\RegistroService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
Artisan::command('usuario:clave {email}',function(): int {
    $user=User::where('email',$this->argument('email'))->first();
    if(!$user) { $this->error('No existe un usuario con ese correo.'); return 1; }
    $clave=$this->secret('Nueva contraseña (mínimo 8 caracteres)');
    $confirmacion=$this->secret('Repetí la contraseña');
    if(!is_string($clave) || mb_strlen($clave)<8 || mb_strlen($clave)>128 || $clave!==$confirmacion) {
        $this->error('Las contraseñas deben coincidir y tener entre 8 y 128 caracteres.'); return 1;
    }
    $user->update(['password'=>$clave,'remember_token'=>null]);
    $this->info('Contraseña actualizada.'); return 0;
})->purpose('Cambiar una contraseña desde la terminal, sin mostrarla.');
Artisan::command('datos:respaldar',function(): int {
    if(config('database.default')!=='sqlite') { $this->error('Este comando respalda SQLite. Para MySQL usá su herramienta de respaldo.'); return 1; }
    File::ensureDirectoryExists(storage_path('app/backups'));
    $path=storage_path('app/backups/grillas-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite');
    DB::connection()->getPdo()->exec("VACUUM INTO '".str_replace("'","''",$path)."'");
    $this->info('Respaldo creado: '.$path); return 0;
})->purpose('Crear un respaldo consistente de la base SQLite.');
Artisan::command('demo:cargar',function(): int {
    if(Movil::exists() || Tecnico::exists() || Planilla::exists()) { $this->error('La demostración solo se carga si no existen técnicos, móviles ni grillas.'); return 1; }
    $user=User::where('role','admin')->first();
    if(!$user) { $this->error('Primero creá tu cuenta administradora desde el navegador.'); return 1; }
    DB::transaction(function() use($user): void {
        $a=Tecnico::create(['legajo'=>'207','apellido'=>'Gómez','nombre'=>'Ana']);
        $b=Tecnico::create(['legajo'=>'211','apellido'=>'Pérez','nombre'=>'Luis']);
        $c=Tecnico::create(['legajo'=>'213','apellido'=>'Díaz','nombre'=>'Lucía']);
        $service=app(RegistroService::class);
        foreach([['207',$a,$b],['213',$c,null]] as [$codigo,$responsable,$dupla]) {
            $m=Movil::create(['codigo'=>$codigo,'descripcion'=>'Ejemplo ficticio']);
            $p=Planilla::create(['fecha'=>now()->format('Y-m-d'),'movil_id'=>$m->id,'responsable_id'=>$responsable->id,'dupla_id'=>$dupla?->id,'movil_codigo'=>$codigo,'responsable_nombre'=>$responsable->etiqueta(),'dupla_nombre'=>$dupla?->etiqueta(),'created_by'=>$user->id]);
            foreach(['HD','WIFI','USB'] as $i=>$tipo) {
                $r=$service->crear($p,['tipo_equipo_id'=>TipoEquipo::where('nombre',$tipo)->firstOrFail()->id,'mac'=>$tipo==='HD'?'FZ32406013'.$codigo:($tipo==='WIFI'?'48575443F'.$codigo:'USB00000'.$codigo)],$user->id);
                if($i===1) { $service->actualizar($r,['ot'=>'00'.$codigo.'01','fecha_ot'=>now()->format('Y-m-d'),'version'=>1],$user->id); }
            }
        }
    });
    $this->info('Demostración cargada: 2 móviles y 6 equipos ficticios para hoy.'); return 0;
})->purpose('Cargar una demostración opcional en una instalación vacía.');
