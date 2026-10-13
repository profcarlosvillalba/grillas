<?php
namespace Tests\Feature;

use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\User;
use Database\Seeders\PlanillaFoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_artisan_commands_load_the_photo_example(): void
    {
        $this->artisan('migrate',['--force'=>true])->assertExitCode(0);
        $this->artisan('db:seed',['--force'=>true])->assertExitCode(0);
        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseCount('tecnicos',12);
        $this->assertDatabaseCount('moviles',7);
        $this->assertDatabaseCount('tipos_equipo',7);
        $this->assertDatabaseCount('planillas',7);
        $this->assertDatabaseCount('registros',35);
        $this->assertSame(35,Registro::whereNull('ot')->whereNull('fecha_ot')->count());
        $this->assertSame(['2025-07-26'],Registro::distinct()->pluck('fecha_registro')->map(fn($d)=>$d->format('Y-m-d'))->all());
        $user=User::firstOrFail();
        $this->assertTrue($user->esAdmin());
        $this->assertTrue(Hash::check('Admin12345!',$user->password));
        $this->post(route('login'),['email'=>'admin@grillas.test','password'=>'Admin12345!'])->assertRedirect(route('grilla'));
        $this->get(route('grilla',['fecha'=>'2025-07-26']))->assertOk()->assertSee('RAUL')->assertSee('FZ324060135287');
    }

    public function test_repeating_seeds_preserves_password_ot_and_inactive_catalogs(): void
    {
        $this->seed();
        $user=User::firstOrFail(); $user->update(['password'=>'OtraClave123!']);
        $r=Registro::firstOrFail();
        $r->update(['ot'=>'000123','fecha_ot'=>'2025-07-27','version'=>9]);
        Movil::firstOrFail()->update(['activo'=>false]);
        TipoEquipo::where('nombre','HD')->firstOrFail()->update(['activo'=>false]);
        $this->travel(5)->days();
        $this->seed();
        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseCount('tecnicos',12);
        $this->assertDatabaseCount('moviles',7);
        $this->assertDatabaseCount('planillas',7);
        $this->assertDatabaseCount('registros',35);
        $this->assertDatabaseHas('registros',['id'=>$r->id,'ot'=>'000123','fecha_ot'=>'2025-07-27','version'=>9]);
        $this->assertTrue(Hash::check('OtraClave123!',$user->fresh()->password));
        $this->assertFalse(Movil::firstOrFail()->activo);
        $this->assertFalse(TipoEquipo::where('nombre','HD')->firstOrFail()->activo);
    }

    public function test_an_existing_administrator_is_used_without_adding_or_resetting_credentials(): void
    {
        $admin=User::create(['name'=>'Carlos','email'=>'carlos@example.test','password'=>'ClavePersonal123!','role'=>'admin','active'=>true]);
        $this->seed();
        $this->assertDatabaseCount('users',1);
        $this->assertDatabaseMissing('users',['email'=>'admin@grillas.test']);
        $this->assertTrue(Hash::check('ClavePersonal123!',$admin->fresh()->password));
        $this->assertSame(35,Registro::where('created_by',$admin->id)->count());
    }

    public function test_the_photo_export_contains_seven_grids_and_all_35_codes(): void
    {
        $this->seed(); $this->actingAs(User::firstOrFail());
        $response=$this->get(route('exportar',['fecha'=>'2025-07-26']))->assertOk();
        $path=$response->baseResponse->getFile()->getPathname();
        $z=new ZipArchive(); $this->assertTrue($z->open($path));
        $xml=$z->getFromName('xl/worksheets/sheet1.xml');
        $this->assertSame(7,substr_count($xml,'<mergeCell ref='));
        $this->assertSame(7,substr_count($xml,'>EQUIPOS<'));
        $this->assertStringNotContainsString('Responsable (work)',$xml);
        $this->assertStringNotContainsString('autoFilter',$xml);
        foreach(PlanillaFoto::datos()['grillas'] as $g) {
            $this->assertStringContainsString($g['responsable'].' - '.$g['dupla'],$xml);
            foreach($g['equipos'] as [$type,$mac]) { $this->assertSame(1,substr_count($xml,'>'.$mac.'<')); }
        }
        $this->assertStringContainsString('orientation="portrait"',$xml);
        $this->assertStringContainsString('paperSize="9"',$xml);
        $this->assertStringContainsString('fitToHeight="1"',$xml);
        $this->assertStringContainsString('Print_Area',$z->getFromName('xl/workbook.xml'));
        $z->close(); unlink($path);
    }

    public function test_filtered_excel_does_not_retain_other_rows_in_shared_strings(): void
    {
        $this->seed(); $this->actingAs(User::firstOrFail());
        $response=$this->get(route('exportar',['fecha'=>'2025-07-26','q'=>'FZ324060135287']))->assertOk();
        $path=$response->baseResponse->getFile()->getPathname();
        $z=new ZipArchive(); $z->open($path);
        $xml=$z->getFromName('xl/worksheets/sheet1.xml');
        $this->assertSame(1,substr_count($xml,'<mergeCell ref='));
        $this->assertStringContainsString('FZ324060135287',$xml);
        $this->assertStringNotContainsString('FZ324060135053',$xml.($z->getFromName('xl/sharedStrings.xml') ?: ''));
        $z->close(); unlink($path);
    }

    public function test_empty_created_grid_exports_a_blank_row_with_its_header(): void
    {
        $this->seed(); $user=User::firstOrFail(); $this->actingAs($user);
        $source=Planilla::firstOrFail();
        $p=$source->replicate(); $p->fecha='2026-10-13'; $p->save();
        $response=$this->get(route('exportar',['fecha'=>'2026-10-13']))->assertOk();
        $path=$response->baseResponse->getFile()->getPathname();
        $z=new ZipArchive(); $z->open($path);
        $xml=$z->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('EQUIPOS',$xml);
        $this->assertStringContainsString($source->responsable_nombre.' - '.$source->dupla_nombre,$xml);
        $this->assertStringNotContainsString('FZ324060135287',$xml);
        $z->close(); unlink($path);
    }
}

