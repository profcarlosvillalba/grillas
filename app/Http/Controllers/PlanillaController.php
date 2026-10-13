<?php
namespace App\Http\Controllers;
use App\Http\Requests\StorePlanillaRequest;
use App\Http\Requests\UpdatePlanillaRequest;
use App\Models\Planilla;
use App\Models\Tecnico;
use App\Services\PlanillaService;
use Illuminate\Http\Request;
class PlanillaController extends Controller {
    public function create(Request $request) {
        $request->validate(['fecha'=>['nullable','date_format:Y-m-d']]);
        return view('planillas.form',[
            'planilla'=>new Planilla(['fecha'=>$request->input('fecha',now()->format('Y-m-d'))]),
            'tecnicos'=>Tecnico::where('activo',true)->orderBy('apellido')->orderBy('nombre')->get(),
        ]);
    }
    public function store(StorePlanillaRequest $request,PlanillaService $service) {
        $p=$service->guardar($request->validated(),$request->user()->id);
        return redirect(route('grilla',['fecha'=>$p->fecha->format('Y-m-d')]).'#planilla-'.$p->id)->with('success','Grilla creada. Seleccioná el equipo y escaneá su MAC o serie.');
    }
    public function edit(Planilla $planilla) {
        return view('planillas.form',[
            'planilla'=>$planilla,
            'tecnicos'=>Tecnico::where('activo',true)->orWhereIn('id',array_filter([$planilla->responsable_id,$planilla->dupla_id]))->orderBy('apellido')->get(),
        ]);
    }
    public function update(UpdatePlanillaRequest $request,Planilla $planilla,PlanillaService $service) {
        $planilla=$service->guardar($request->validated(),$request->user()->id,$planilla);
        return redirect(route('grilla',['fecha'=>$planilla->fecha->format('Y-m-d')]).'#planilla-'.$planilla->id)->with('success','Técnicos de la grilla actualizados.');
    }
    public function destroy(Planilla $planilla) {
        if($planilla->registros()->exists()) { return back()->withErrors(['grilla'=>'Solo se puede eliminar una grilla vacía.']); }
        try { $planilla->delete(); } catch(\Illuminate\Database\QueryException) { return back()->withErrors(['grilla'=>'La grilla contiene equipos y no se puede eliminar.']); }
        return redirect()->route('grilla',['fecha'=>$planilla->fecha->format('Y-m-d')])->with('success','Grilla vacía eliminada.');
    }
}
