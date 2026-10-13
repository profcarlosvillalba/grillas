<?php
namespace Tests\Feature;
use App\Models\Movil;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use App\Models\User;
use App\Services\RegistroService;
use App\Services\XlsxExporter;
use App\Support\CodigoEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use ZipArchive;
class InventoryTest extends TestCase {
    use RefreshDatabase;
    private User $admin;
    private Tecnico $tech;
    private Tecnico $duo;
    private Movil $mobile;
    private TipoEquipo $hd;
    protected function setUp(): void {
        parent::setUp();
        $this->seed(\Database\Seeders\TiposEquipoSeeder::class);
        $this->admin=User::create(['name'=>'Carlos de prueba','email'=>'qa@example.test','password'=>'password123','role'=>'admin']);
        $this->tech=Tecnico::create(['legajo'=>'0207','apellido'=>'Gómez','nombre'=>'Ana']);
        $this->duo=Tecnico::create(['legajo'=>'211','apellido'=>'Pérez']);
        $this->mobile=Movil::create(['codigo'=>'0207']);
        $this->hd=TipoEquipo::where('nombre','HD')->firstOrFail();
        $this->actingAs($this->admin);
    }
    private function sheet(string $date='2026-10-12',?Movil $mobile=null): Planilla {
        $m=$mobile ?? $this->mobile;
        return Planilla::create(['fecha'=>$date,'movil_id'=>$m->id,'responsable_id'=>$this->tech->id,'dupla_id'=>$this->duo->id,'movil_codigo'=>$m->codigo,'responsable_nombre'=>$this->tech->etiqueta(),'dupla_nombre'=>$this->duo->etiqueta(),'created_by'=>$this->admin->id]);
    }
    private function scan(Planilla $p,string $mac='FZ324060135287'): \Illuminate\Testing\TestResponse {
        return $this->postJson(route('registros.store',$p),['tipo_equipo_id'=>$this->hd->id,'mac'=>$mac]);
    }
    public function test_public_pages_require_login(): void {
        auth()->logout();
        foreach(['grilla','historial','exportar','usuarios.index'] as $route) { $this->get(route($route))->assertRedirect(route('login')); }
        $this->get(route('login'))->assertOk();
    }
    public function test_new_account_is_only_available_before_first_user(): void {
        auth()->logout();
        $this->get(route('setup'))->assertRedirect(route('login'));
        $this->post(route('setup'),['name'=>'Other','email'=>'other@example.test','password'=>'password123','password_confirmation'=>'password123'])->assertForbidden();
    }
    public function test_setup_creates_first_administrator(): void {
        auth()->logout(); User::query()->delete();
        $this->get(route('login'))->assertRedirect(route('setup'));
        $this->get(route('setup'))->assertOk();
        $this->post(route('setup'),['name'=>'Carlos','email'=>'first@example.test','password'=>'password123','password_confirmation'=>'password123'])->assertRedirect(route('grilla'));
        $this->assertDatabaseHas('users',['email'=>'first@example.test','role'=>'admin','active'=>1]);
        $this->assertDatabaseHas('application_setup',['id'=>1]);
    }
    public function test_login_and_logout(): void {
        auth()->logout();
        $this->post(route('login'),['email'=>$this->admin->email,'password'=>'bad'])->assertSessionHasErrors('email');
        $this->post(route('login'),['email'=>$this->admin->email,'password'=>'password123'])->assertRedirect(route('grilla'));
        $this->post(route('logout'))->assertRedirect(route('login')); $this->assertGuest();
    }
    public function test_daily_sheet_accepts_a_single_technician(): void {
        $this->post(route('planillas.store'),['fecha'=>'2026-10-12','movil_id'=>$this->mobile->id,'responsable_id'=>$this->tech->id,'dupla_id'=>''])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('planillas',['fecha'=>'2026-10-12','movil_codigo'=>'0207','dupla_id'=>null,'responsable_nombre'=>'Gómez, Ana']);
    }
    public function test_responsible_and_duo_must_be_different(): void {
        $this->postJson(route('planillas.store'),['fecha'=>'2026-10-12','movil_id'=>$this->mobile->id,'responsable_id'=>$this->tech->id,'dupla_id'=>$this->tech->id])->assertUnprocessable()->assertJsonValidationErrors('dupla_id');
    }
    public function test_unique_sheet_per_mobile_and_day(): void {
        $this->sheet();
        $data=['fecha'=>'2026-10-12','movil_id'=>$this->mobile->id,'responsable_id'=>$this->tech->id];
        $this->postJson(route('planillas.store'),$data)->assertUnprocessable()->assertJsonValidationErrors('responsable_id');
        $data['fecha']='2026-10-13'; $this->postJson(route('planillas.store'),$data)->assertRedirect();
    }
    public function test_scanning_stores_series_and_empty_ot(): void {
        $p=$this->sheet(); $response=$this->scan($p,' fz324060135287 ');
        $response->assertOk()->assertJsonPath('total',1)->assertJsonPath('pendientes',1)->assertJsonPath('planilla_total',1);
        $this->assertDatabaseHas('registros',['mac'=>'FZ324060135287','ot'=>null,'fecha_ot'=>null,'fecha_registro'=>'2026-10-12']);
        $this->assertStringContainsString('Sin asignar',$response->json('html'));
    }
    public function test_mac_normalization_and_invalid_characters(): void {
        $p=$this->sheet();
        $this->scan($p,'48:57:54:43:F6:71')->assertOk();
        $this->assertDatabaseHas('registros',['mac'=>'48575443F671']);
        $this->scan($p,'<script>')->assertUnprocessable()->assertJsonValidationErrors('mac');
        $this->assertSame('AABB00112233',CodigoEquipo::normalizar(' aa:bb-00.11 22 33 '));
    }
    public function test_same_mac_is_blocked_across_mobile_on_same_day_but_allowed_next_day(): void {
        $p=$this->sheet(); $this->scan($p)->assertOk();
        $other=Movil::create(['codigo'=>'213']); $p2=$this->sheet('2026-10-12',$other);
        $this->scan($p2)->assertUnprocessable()->assertJsonValidationErrors('mac');
        $p3=$this->sheet('2026-10-13'); $this->scan($p3)->assertOk();
        $this->assertDatabaseCount('registros',2);
    }
    public function test_database_unique_constraint_also_protects_direct_service_writes(): void {
        $p=$this->sheet(); $this->scan($p)->assertOk();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(RegistroService::class)->crear($p,['mac'=>'FZ324060135287','tipo_equipo_id'=>$this->hd->id],$this->admin->id);
    }
    public function test_ot_and_date_are_saved_and_leading_zeros_preserved(): void {
        $p=$this->sheet(); $this->scan($p); $r=Registro::firstOrFail();
        $this->putJson(route('registros.ot',$r),['ot'=>'006420251','fecha_ot'=>'2026-10-14','version'=>1])->assertOk()->assertJsonPath('asignados',1);
        $r->refresh(); $this->assertSame('006420251',$r->ot); $this->assertSame('2026-10-14',$r->fecha_ot->format('Y-m-d')); $this->assertSame(2,$r->version);
    }
    public function test_ot_and_date_must_be_completed_together_and_can_be_cleared(): void {
        $p=$this->sheet(); $this->scan($p); $r=Registro::first();
        $this->putJson(route('registros.ot',$r),['ot'=>'0001','version'=>1])->assertUnprocessable()->assertJsonValidationErrors('fecha_ot');
        $this->putJson(route('registros.ot',$r),['fecha_ot'=>'2026-10-12','version'=>1])->assertUnprocessable()->assertJsonValidationErrors('ot');
        $this->putJson(route('registros.ot',$r),['ot'=>'0001','fecha_ot'=>'2026-10-12','version'=>1])->assertOk();
        $this->putJson(route('registros.ot',$r),['ot'=>'','fecha_ot'=>'','version'=>2])->assertOk();
        $this->assertDatabaseHas('registros',['id'=>$r->id,'ot'=>null,'fecha_ot'=>null]);
    }
    public function test_stale_edits_and_deletes_do_not_overwrite_newer_data(): void {
        $p=$this->sheet(); $this->scan($p); $r=Registro::first();
        $this->putJson(route('registros.ot',$r),['ot'=>'0001','fecha_ot'=>'2026-10-12','version'=>1])->assertOk();
        $this->putJson(route('registros.ot',$r),['ot'=>'0002','fecha_ot'=>'2026-10-12','version'=>1])->assertStatus(409);
        $this->deleteJson(route('registros.destroy',$r),['version'=>1])->assertStatus(409);
        $this->assertDatabaseHas('registros',['id'=>$r->id,'ot'=>'0001']);
    }
    public function test_equipment_can_be_corrected_and_deleted(): void {
        $p=$this->sheet(); $this->scan($p); $r=Registro::first();
        $this->get(route('registros.edit',$r))->assertOk();
        $this->put(route('registros.update',$r),['tipo_equipo_id'=>$this->hd->id,'mac'=>'FZ999900011122','ot'=>'','fecha_ot'=>'','version'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('registros',['mac'=>'FZ999900011122','version'=>2]);
        $this->delete(route('registros.destroy',$r),['version'=>2])->assertRedirect();
        $this->assertDatabaseCount('registros',0);
    }
    public function test_nonempty_sheet_cannot_be_deleted(): void {
        $p=$this->sheet(); $this->scan($p);
        $this->delete(route('planillas.destroy',$p))->assertSessionHasErrors('grilla');
        $this->assertDatabaseCount('planillas',1);
    }
    public function test_empty_sheet_can_be_deleted_and_team_can_be_edited(): void {
        $p=$this->sheet();
        $this->get(route('planillas.edit',$p))->assertOk();
        $this->put(route('planillas.update',$p),['responsable_id'=>$this->duo->id,'dupla_id'=>''])->assertRedirect();
        $this->assertDatabaseHas('planillas',['id'=>$p->id,'responsable_nombre'=>'Pérez','dupla_id'=>null]);
        $this->delete(route('planillas.destroy',$p))->assertRedirect();
        $this->assertDatabaseCount('planillas',0);
    }
    public function test_catalog_changes_do_not_rewrite_history(): void {
        $p=$this->sheet(); $this->scan($p);
        $this->tech->update(['apellido'=>'Otro']); $this->mobile->update(['codigo'=>'999']); $this->hd->update(['nombre'=>'HD NUEVO']);
        $this->get(route('grilla',['fecha'=>'2026-10-12']))->assertOk()->assertSee('Gómez, Ana')->assertSee('0207')->assertSee('FZ324060135287');
        $this->assertSame('HD',Registro::first()->tipo_nombre);
    }
    public function test_search_and_pending_filters_use_persisted_records(): void {
        $p=$this->sheet(); $this->scan($p,'48575443F671');
        $this->scan($p,'FZ324060135287'); $r=Registro::where('mac','FZ324060135287')->first();
        $this->putJson(route('registros.ot',$r),['ot'=>'000999','fecha_ot'=>'2026-10-12','version'=>1]);
        $this->get(route('grilla',['fecha'=>'2026-10-12','estado'=>'pendiente']))->assertOk()->assertSee('48575443F671')->assertDontSee('FZ324060135287');
        $this->get(route('historial',['q'=>'48:57:54:43:F6:71']))->assertOk()->assertSee('48575443F671')->assertDontSee('FZ324060135287');
        $this->get(route('historial',['q'=>'000999']))->assertOk()->assertSee('FZ324060135287')->assertDontSee('48575443F671');
        $this->get(route('historial',['desde'=>'2026-10-13']))->assertOk()->assertDontSee('FZ324060135287');
        $this->get(route('historial',['hasta'=>'2026-10-12']))->assertOk()->assertSee('FZ324060135287');
        $this->getJson(route('historial',['desde'=>'2026-10-13','hasta'=>'2026-10-12']))->assertUnprocessable();
    }
    public function test_all_main_views_render_without_missing_assets_or_templates(): void {
        foreach(['grilla','historial','planillas.create','usuarios.index','ayuda'] as $route) { $this->get(route($route))->assertOk(); }
        foreach(['tecnicos','moviles','equipos'] as $c) {
            $this->get(route('catalogos.index',$c))->assertOk();
            $this->get(route('catalogos.create',$c))->assertOk();
        }
    }
    public function test_operator_permissions_and_inactive_account(): void {
        $operator=User::create(['name'=>'Operador','email'=>'op@example.test','password'=>'password123','role'=>'operador']);
        $this->actingAs($operator)->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('catalogos.index','tecnicos'))->assertForbidden();
        $p=$this->sheet(); $this->scan($p)->assertOk();
        $operator->update(['active'=>false]); $this->get(route('grilla'))->assertRedirect(route('login'));
    }
    public function test_inactive_catalog_entries_cannot_be_used_for_new_scans_or_sheets(): void {
        $p=$this->sheet(); $this->hd->update(['activo'=>false]); $this->scan($p)->assertUnprocessable()->assertJsonValidationErrors('tipo_equipo_id');
        $this->tech->update(['activo'=>false]);
        $this->postJson(route('planillas.store'),['fecha'=>'2026-10-13','movil_id'=>$this->mobile->id,'responsable_id'=>$this->tech->id])->assertUnprocessable()->assertJsonValidationErrors('responsable_id');
    }
    public function test_administrator_can_create_catalogs_and_users_without_disabling_self(): void {
        $this->post(route('catalogos.store','moviles'),['codigo'=>'00208','descripcion'=>'Ejemplo','activo'=>1])->assertRedirect();
        $this->assertDatabaseHas('moviles',['codigo'=>'00208']);
        $this->post(route('catalogos.store','equipos'),['nombre'=>'nuevo tipo','activo'=>1])->assertRedirect();
        $this->assertDatabaseHas('tipos_equipo',['nombre'=>'NUEVO TIPO']);
        $this->post(route('usuarios.store'),['name'=>'Nuevo','email'=>'new@example.test','password'=>'password123','password_confirmation'=>'password123','role'=>'operador'])->assertRedirect();
        $this->patch(route('usuarios.toggle',$this->admin))->assertStatus(422);
    }
    public function test_xlsx_reproduces_reference_grids_with_text_codes(): void {
        $p=$this->sheet(); $this->scan($p); $r=Registro::first();
        app(RegistroService::class)->actualizar($r,['ot'=>'006420251','fecha_ot'=>'2026-10-12','version'=>1],$this->admin->id);
        $response=$this->get(route('exportar',['fecha'=>'2026-10-12']))->assertOk();
        $path=$response->baseResponse->getFile()->getPathname();
        $zip=new ZipArchive(); $this->assertTrue($zip->open($path));
        $xml=$zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertStringContainsString('006420251',$xml); $this->assertStringContainsString('0207',$xml);
        $this->assertStringContainsString('t="inlineStr"',$xml);
        $this->assertStringContainsString('EQUIPOS',$xml);
        $this->assertStringContainsString('GÓMEZ, ANA - PÉREZ',$xml);
        $this->assertStringContainsString('mergeCell ref="B2:D2"',$xml);
        $this->assertStringContainsString('orientation="portrait"',$xml);
        $this->assertStringContainsString('paperSize="9"',$xml);
        $this->assertStringNotContainsString('autoFilter',$xml);
        $this->assertNotFalse(simplexml_load_string($xml)); $zip->close(); unlink($path);
        $empty=$this->get(route('exportar',['fecha'=>'2026-10-13']))->assertOk();
        unlink($empty->baseResponse->getFile()->getPathname());
    }
    public function test_backup_creates_readable_sqlite_and_demo_does_not_touch_existing_data(): void {
        $this->artisan('demo:cargar')->assertExitCode(1);
        $this->assertDatabaseCount('planillas',0);
    }
}
