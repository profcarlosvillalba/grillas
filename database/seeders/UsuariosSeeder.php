<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class UsuariosSeeder extends Seeder {
    public function run(): void {
        // Las cuentas existentes conservan sus correos, permisos y contraseñas.
        if(User::exists()) { return; }
        User::create(['name'=>'Administrador','email'=>'admin@grillas.test','password'=>'Admin12345!','role'=>'admin','active'=>true]);
        DB::table('application_setup')->insertOrIgnore(['id'=>1]);
        $this->command?->info('Usuario inicial: admin@grillas.test | Contraseña: Admin12345!');
    }
}

