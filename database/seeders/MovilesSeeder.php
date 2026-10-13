<?php
namespace Database\Seeders;
use App\Models\Movil;
use Illuminate\Database\Seeder;
class MovilesSeeder extends Seeder {
    public function run(): void {
        foreach(PlanillaFoto::datos()['grillas'] as $grilla) {
            Movil::firstOrCreate(['codigo'=>$grilla['movil']],['descripcion'=>'Planilla de ejemplo del 26/07/2025','activo'=>true]);
        }
    }
}

