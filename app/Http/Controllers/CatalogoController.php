<?php
namespace App\Http\Controllers;
use App\Models\Movil;
use App\Models\Tecnico;
use App\Models\TipoEquipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class CatalogoController extends Controller {
    private const MAP=['tecnicos'=>[Tecnico::class,'Técnicos'],'moviles'=>[Movil::class,'Móviles'],'equipos'=>[TipoEquipo::class,'Tipos de equipo']];
    private function modelo(string $catalogo): string { abort_unless(isset(self::MAP[$catalogo]),404); return self::MAP[$catalogo][0]; }
    public function index(string $catalogo) {
        $model=$this->modelo($catalogo);
        return view('catalogos.index',['catalogo'=>$catalogo,'titulo'=>self::MAP[$catalogo][1],'items'=>$model::orderBy($catalogo==='tecnicos'?'apellido':($catalogo==='moviles'?'codigo':'nombre'))->paginate(30)]);
    }
    public function create(string $catalogo) { $m=$this->modelo($catalogo); return view('catalogos.form',['catalogo'=>$catalogo,'titulo'=>self::MAP[$catalogo][1],'item'=>new $m]); }
    public function edit(string $catalogo,int $id) { $m=$this->modelo($catalogo); return view('catalogos.form',['catalogo'=>$catalogo,'titulo'=>self::MAP[$catalogo][1],'item'=>$m::findOrFail($id)]); }
    private function datos(Request $request,string $catalogo,?int $id=null): array {
        if($catalogo==='tecnicos') {
            $request->merge(['legajo'=>mb_strtoupper(trim((string)$request->input('legajo')))]);
            $rules=[
                'legajo'=>['required','string','max:20','regex:/^[A-Z0-9\-]+$/',Rule::unique('tecnicos','legajo')->ignore($id)],
                'apellido'=>['required','string','max:120'],'nombre'=>['nullable','string','max:120'],
            ];
        }
        elseif($catalogo==='moviles') {
            $request->merge(['codigo'=>strtoupper(trim((string)$request->input('codigo')))]);
            $rules=['codigo'=>['required','string','max:20','regex:/^[A-Z0-9\-]+$/',Rule::unique('moviles','codigo')->ignore($id)],'descripcion'=>['nullable','string','max:120']];
        } else {
            $request->merge(['nombre'=>mb_strtoupper(trim((string)$request->input('nombre')))]);
            $rules=['nombre'=>['required','string','max:40',Rule::unique('tipos_equipo','nombre')->ignore($id)]];
        }
        $data=$request->validate($rules+['activo'=>['required','boolean']]);
        return $data;
    }
    public function store(Request $request,string $catalogo) { $m=$this->modelo($catalogo); $m::create($this->datos($request,$catalogo)); return redirect()->route('catalogos.index',$catalogo)->with('success','Dato creado.'); }
    public function update(Request $request,string $catalogo,int $id) { $m=$this->modelo($catalogo); $item=$m::findOrFail($id); $item->update($this->datos($request,$catalogo,$id)); return redirect()->route('catalogos.index',$catalogo)->with('success','Dato actualizado. Las grillas anteriores conservan sus nombres originales.'); }
}
