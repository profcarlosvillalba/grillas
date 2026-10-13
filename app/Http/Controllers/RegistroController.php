<?php
namespace App\Http\Controllers;
use App\Http\Requests\AssignOtRequest;
use App\Http\Requests\StoreRegistroRequest;
use App\Models\Planilla;
use App\Models\Registro;
use App\Models\TipoEquipo;
use App\Services\RegistroService;
use Illuminate\Http\Request;
class RegistroController extends Controller {
    private function resultado(Request $request,Registro $r,string $message) {
        if($request->expectsJson()) {
            $q=Registro::where('fecha_registro',$r->fecha_registro->format('Y-m-d'));
            $total=(clone $q)->count(); $asignados=(clone $q)->whereNotNull('ot')->count();
            return response()->json([
                'message'=>$message,'id'=>$r->id,'html'=>view('partials.registro-row',['registro'=>$r])->render(),
                'total'=>$total,'asignados'=>$asignados,'pendientes'=>$total-$asignados,
                'planilla_total'=>$r->planilla->registros()->count(),
            ]);
        }
        return redirect(route('grilla',['fecha'=>$r->fecha_registro->format('Y-m-d')]).'#planilla-'.$r->planilla_id)->with('success',$message);
    }
    public function store(StoreRegistroRequest $request,Planilla $planilla,RegistroService $service) {
        $r=$service->crear($planilla,$request->validated(),$request->user()->id);
        return $this->resultado($request,$r,'Equipo registrado. Podés escanear el siguiente.');
    }
    public function asignar(AssignOtRequest $request,Registro $registro,RegistroService $service) {
        $r=$service->actualizar($registro,$request->validated(),$request->user()->id);
        return $this->resultado($request,$r,$r->ot ? 'OT y fecha guardadas.' : 'El equipo quedó sin OT asignada.');
    }
    public function edit(Registro $registro) {
        return view('registros.form',['registro'=>$registro,'tipos'=>TipoEquipo::where('activo',true)->orWhere('id',$registro->tipo_equipo_id)->orderBy('nombre')->get()]);
    }
    public function update(StoreRegistroRequest $request,Registro $registro,RegistroService $service) {
        $r=$service->actualizar($registro,$request->validated(),$request->user()->id);
        return $this->resultado($request,$r,'Equipo corregido.');
    }
    public function destroy(Request $request,Registro $registro) {
        $data=$request->validate(['version'=>['required','integer','min:1']]);
        $deleted=Registro::whereKey($registro->id)->where('version',$data['version'])->delete();
        abort_if($deleted!==1,409,'El registro cambió. Actualizá la página antes de eliminar.');
        return redirect(route('grilla',['fecha'=>$registro->fecha_registro->format('Y-m-d')]).'#planilla-'.$registro->planilla_id)->with('success','Registro eliminado.');
    }
}
