<?php
namespace Tests\Feature;

use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ProductivoTest extends TestCase {
    use RefreshDatabase;
    private User $admin;
    private Tecnico $raul;
    private Tecnico $exequiel;
    protected function setUp(): void {
        parent::setUp();
        $this->admin=User::create(['name'=>'Admin','email'=>'work@example.test','password'=>'Clave12345!','role'=>'admin']);
        $this->raul=Tecnico::create(['legajo'=>'207','apellido'=>'RAUL','activo'=>true]);
        $this->exequiel=Tecnico::create(['legajo'=>'211','apellido'=>'EXEQUIEL','activo'=>true]);
        $this->actingAs($this->admin);
    }
    private function crear(string $fecha='2026-10-13'): Planilla {
        $this->post(route('planillas.store'),['fecha'=>$fecha,'responsable_id'=>$this->raul->id,'dupla_id'=>$this->exequiel->id])->assertRedirect()->assertSessionHasNoErrors();
        return Planilla::where('fecha',$fecha)->firstOrFail();
    }
    public function test_legajo_is_required_unique_and_preserves_leading_zeros(): void {
        $this->postJson(route('catalogos.store','tecnicos'),['apellido'=>'Otro','activo'=>1])->assertUnprocessable()->assertJsonValidationErrors('legajo');
        $this->postJson(route('catalogos.store','tecnicos'),['legajo'=>'207','apellido'=>'Otro','activo'=>1])->assertUnprocessable()->assertJsonValidationErrors('legajo');
        $this->post(route('catalogos.store','tecnicos'),['legajo'=>' 00215 ','apellido'=>'Nuevo','activo'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tecnicos',['legajo'=>'00215','apellido'=>'Nuevo']);
        $this->put(route('catalogos.update',['tecnicos',$this->raul->id]),['legajo'=>'207','apellido'=>'RAUL','activo'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->putJson(route('catalogos.update',['tecnicos',$this->exequiel->id]),['legajo'=>'207','apellido'=>'EXEQUIEL','activo'=>1])->assertUnprocessable()->assertJsonValidationErrors('legajo');
    }
    public function test_database_also_rejects_a_duplicate_legajo(): void {
        $this->expectException(UniqueConstraintViolationException::class);
        Tecnico::create(['legajo'=>'207','apellido'=>'Otro']);
    }
    public function test_work_code_is_derived_and_cannot_be_overridden_in_a_request(): void {
        $other=Movil::create(['codigo'=>'999']);
        $this->post(route('planillas.store'),['fecha'=>'2026-10-13','responsable_id'=>$this->raul->id,'dupla_id'=>$this->exequiel->id,'movil_id'=>$other->id,'movil_codigo'=>'999'])->assertRedirect()->assertSessionHasNoErrors();
        $p=Planilla::firstOrFail();
        $this->assertSame('207',$p->movil_codigo);
        $this->assertSame('207 RAUL - EXEQUIEL',$p->encabezado());
        $this->assertDatabaseHas('moviles',['id'=>$p->movil_id,'codigo'=>'207']);
        $this->get(route('grilla',['fecha'=>'2026-10-13']))->assertOk()->assertSee('207 RAUL - EXEQUIEL')->assertSee('PRODUCTIVO');
    }
    public function test_swapping_roles_changes_the_code_and_keeps_equipment_and_ot(): void {
        $p=$this->crear();
        $type=TipoEquipo::create(['nombre'=>'HD','activo'=>true]);
        $this->postJson(route('registros.store',$p),['tipo_equipo_id'=>$type->id,'mac'=>'FZ324060135287'])->assertOk();
        $r=Registro::firstOrFail();
        $this->putJson(route('registros.ot',$r),['ot'=>'00123','fecha_ot'=>'2026-10-14','version'=>1])->assertOk();
        $this->put(route('planillas.update',$p),['responsable_id'=>$this->exequiel->id,'dupla_id'=>$this->raul->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('211 EXEQUIEL - RAUL',$p->fresh()->encabezado());
        $this->assertDatabaseHas('registros',['id'=>$r->id,'planilla_id'=>$p->id,'mac'=>'FZ324060135287','ot'=>'00123','fecha_ot'=>'2026-10-14','version'=>2]);
        $response=$this->get(route('exportar',['fecha'=>'2026-10-13']))->assertOk();
        $path=$response->baseResponse->getFile()->getPathname();
        $z=new ZipArchive(); $z->open($path); $xml=$z->getFromName('xl/worksheets/sheet1.xml');
        $doc=new \DOMDocument(); $doc->loadXML($xml); $xpath=new \DOMXPath($doc); $xpath->registerNamespace('s','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $this->assertSame('211',$xpath->evaluate('string(//s:c[@r="A2"]/s:is/s:t)'));
        $this->assertSame('EXEQUIEL - RAUL',$xpath->evaluate('string(//s:c[@r="B2"]/s:is/s:t)'));
        $this->assertStringContainsString('00123',$xml);
        $this->assertStringContainsString('mergeCell ref="B2:D2"',$xml);
        $this->assertSame(4,$xpath->query('//s:row[@r="3"]/s:c')->length);
        $z->close(); unlink($path);
    }
    public function test_single_work_header_and_no_independent_mobile_selection(): void {
        $this->post(route('planillas.store'),['fecha'=>'2026-10-13','responsable_id'=>$this->exequiel->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('211 EXEQUIEL',Planilla::firstOrFail()->encabezado());
        $this->get(route('planillas.create'))->assertOk()->assertSee('211 · EXEQUIEL')->assertDontSee('name="movil_id"',false)->assertSee('data-team-preview',false);
    }
    public function test_same_work_cannot_get_two_grids_in_a_day_even_if_legajo_changes(): void {
        $p=$this->crear();
        $this->raul->update(['legajo'=>'307']);
        $this->postJson(route('planillas.store'),['fecha'=>'2026-10-13','responsable_id'=>$this->raul->id])->assertUnprocessable()->assertJsonValidationErrors('responsable_id');
        $later=$this->crear('2026-10-14');
        $this->assertSame('307 RAUL - EXEQUIEL',$later->encabezado());
        $this->assertSame('207 RAUL - EXEQUIEL',$p->fresh()->encabezado());
    }
    public function test_swapping_to_an_existing_work_grid_is_rejected_without_changes(): void {
        $p=$this->crear();
        $this->post(route('planillas.store'),['fecha'=>'2026-10-13','responsable_id'=>$this->exequiel->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->putJson(route('planillas.update',$p),['responsable_id'=>$this->exequiel->id,'dupla_id'=>$this->raul->id])->assertUnprocessable()->assertJsonValidationErrors('responsable_id');
        $this->assertSame('207 RAUL - EXEQUIEL',$p->fresh()->encabezado());
        $this->assertDatabaseCount('planillas',2);
    }
    public function test_legacy_technician_without_legajo_must_complete_it_before_becoming_work(): void {
        $legacy=Tecnico::create(['apellido'=>'Anterior','activo'=>true]);
        $this->postJson(route('planillas.store'),['fecha'=>'2026-10-13','responsable_id'=>$legacy->id])->assertUnprocessable()->assertJsonValidationErrors('responsable_id');
        $p=$this->crear();
        $this->putJson(route('planillas.update',$p),['responsable_id'=>$legacy->id,'dupla_id'=>$this->raul->id])->assertUnprocessable()->assertJsonValidationErrors('responsable_id');
        $this->put(route('catalogos.update',['tecnicos',$legacy->id]),['legajo'=>'0218','apellido'=>'Anterior','activo'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('planillas.store'),['fecha'=>'2026-10-13','responsable_id'=>$legacy->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('planillas',['responsable_id'=>$legacy->id,'movil_codigo'=>'0218']);
    }
    public function test_editing_only_dupla_preserves_original_photo_codes_and_snapshots(): void {
        $this->seed();
        $p=Planilla::where('movil_codigo','213')->firstOrFail();
        $work=Tecnico::findOrFail($p->responsable_id);
        $work->update(['legajo'=>'317','apellido'=>'Nombre cambiado']);
        $this->put(route('planillas.update',$p),['responsable_id'=>$p->responsable_id,'dupla_id'=>''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('213 RAUL',$p->fresh()->encabezado());
        $this->assertSame(5,$p->registros()->count());
        $this->assertDatabaseCount('registros',35);
    }
    public function test_scanner_is_below_rows_with_tab_and_enter_instructions(): void {
        $p=$this->crear();
        $html=$this->get(route('grilla',['fecha'=>'2026-10-13']))->assertOk()->getContent();
        $this->assertLessThan(strpos($html,'class="scan-form"'),strpos($html,'data-rows="'.$p->id.'"'));
        $this->assertStringContainsString('Tab o Enter guarda',$html);
        $this->assertStringContainsString('data-scan-status',$html);
    }
    public function test_seeds_fill_known_legajos_without_overwriting_user_changes(): void {
        $this->seed();
        $this->assertSame('207',$this->raul->fresh()->legajo);
        $this->assertSame('211',$this->exequiel->fresh()->legajo);
        $this->assertSame(12,Tecnico::whereNotNull('legajo')->count());
        $this->raul->update(['legajo'=>'000207','activo'=>false]);
        $this->seed();
        $this->assertSame('000207',$this->raul->fresh()->legajo);
        $this->assertFalse($this->raul->fresh()->activo);
        $this->assertDatabaseCount('registros',35);
    }
}
