<?php
namespace Database\Seeders;
use App\Models\TipoEquipo;
use Illuminate\Database\Seeder;
class TiposEquipoSeeder extends Seeder {
    public function run(): void {
        foreach(['HD','WIFI','DCT','ISDBT','EXTENSOR','USB','ONT'] as $nombre) {
            TipoEquipo::firstOrCreate(['nombre'=>$nombre],['activo'=>true]);
        }
    }
}

