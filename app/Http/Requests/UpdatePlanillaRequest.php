<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdatePlanillaRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $p=$this->route('planilla');
        $responsable=['required',Rule::exists('tecnicos','id')->where(fn($q)=>$q->where('activo',true)->orWhere('id',$p->responsable_id))];
        if((string)$this->input('responsable_id')!==(string)$p->responsable_id) {
            $responsable[]=Rule::exists('tecnicos','id')->where(fn($q)=>$q->whereNotNull('legajo')->where('legajo','<>',''));
            $responsable[]=Rule::unique('planillas','responsable_id')->where('fecha',$p->fecha->format('Y-m-d'))->ignore($p->id);
        }
        return [
            'responsable_id'=>$responsable,
            'dupla_id'=>['nullable','different:responsable_id',Rule::exists('tecnicos','id')->where(fn($q)=>$q->where('activo',true)->orWhere('id',$p->dupla_id))],
        ];
    }
    public function messages(): array { return ['responsable_id.exists'=>'Seleccioná un productivo activo con legajo cargado.','responsable_id.unique'=>'Este productivo ya tiene una grilla en esa fecha.','dupla_id.different'=>'El productivo y la dupla deben ser personas distintas.']; }
    public function attributes(): array { return ['responsable_id'=>'productivo (work)','dupla_id'=>'dupla']; }
}
