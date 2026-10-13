<?php
namespace Tests\Feature;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegajoMigrationTest extends TestCase {
    use RefreshDatabase;
    public function test_standard_migrate_upgrades_an_existing_database_without_losing_history(): void {
        $this->seed();
        $r=Registro::firstOrFail(); $r->update(['ot'=>'000567','fecha_ot'=>'2025-07-27']);
        $password=User::firstOrFail()->password;
        $headers=Planilla::orderBy('id')->get(['id','movil_codigo','responsable_nombre','dupla_nombre'])->toArray();
        $name='2026_10_13_000001_add_legajo_to_tecnicos';
        $migration=require database_path('migrations/'.$name.'.php');
        $migration->down();
        DB::table('migrations')->where('migration',$name)->delete();
        $this->assertFalse(Schema::hasColumn('tecnicos','legajo'));
        $this->artisan('migrate',['--path'=>'database/migrations/'.$name.'.php','--force'=>true])->assertExitCode(0);
        $this->assertTrue(Schema::hasColumn('tecnicos','legajo'));
        $this->assertSame(12,Tecnico::whereNull('legajo')->count());
        $this->assertSame($password,User::firstOrFail()->password);
        $this->assertSame($headers,Planilla::orderBy('id')->get(['id','movil_codigo','responsable_nombre','dupla_nombre'])->toArray());
        $this->assertDatabaseHas('registros',['id'=>$r->id,'mac'=>$r->mac,'ot'=>'000567','fecha_ot'=>'2025-07-27']);
        $this->artisan('db:seed',['--force'=>true])->assertExitCode(0);
        $this->assertSame('207',Tecnico::where('apellido','RAUL')->firstOrFail()->legajo);
        $this->assertSame('211',Tecnico::where('apellido','EXEQUIEL')->firstOrFail()->legajo);
        $this->assertSame($headers,Planilla::orderBy('id')->get(['id','movil_codigo','responsable_nombre','dupla_nombre'])->toArray());
        $this->assertDatabaseCount('registros',35);
        $this->assertSame($password,User::firstOrFail()->password);
    }
}
