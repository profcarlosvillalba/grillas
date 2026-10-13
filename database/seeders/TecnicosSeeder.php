<?php
namespace Database\Seeders;
use App\Models\Tecnico;
use Illuminate\Database\Seeder;
class TecnicosSeeder extends Seeder {
    private const LEGAJOS=[
        'RAUL'=>'207','EXEQUIEL'=>'211','HIPOLITO'=>'205','RICHARD'=>'208','HUGO'=>'209',
        'AUGUSTO'=>'DEMO-AUGUSTO','DAVID'=>'DEMO-DAVID','JAIRO'=>'DEMO-JAIRO','SERGIO'=>'DEMO-SERGIO',
        'RICARDO'=>'DEMO-RICARDO','JULIO'=>'DEMO-JULIO','WALTER'=>'DEMO-WALTER',
    ];
    public function run(): void {
        $nombres=[];
        foreach(PlanillaFoto::datos()['grillas'] as $grilla) {
            $nombres[]=$grilla['responsable']; $nombres[]=$grilla['dupla'];
        }
        foreach(array_unique($nombres) as $nombre) {
            // Se conservan las etiquetas de los técnicos que aparecen en la foto.
            $tecnico=Tecnico::firstOrCreate(['apellido'=>$nombre,'nombre'=>null],['activo'=>true]);
            $legajo=self::LEGAJOS[$nombre];
            // No se reemplazan legajos reales ni se ocupa un código ya asignado.
            if(!$tecnico->legajo && !Tecnico::where('legajo',$legajo)->exists()) { $tecnico->update(['legajo'=>$legajo]); }
        }
    }
}

