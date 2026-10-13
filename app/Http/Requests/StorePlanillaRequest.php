<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StorePlanillaRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'fecha'=>['required','date_format:Y-m-d'],
            'responsable_id'=>['required',Rule::exists('tecnicos','id')->where(fn($q)=>$q->where('activo',true)->whereNotNull('legajo')->where('legajo','<>','')),Rule::unique('planillas','responsable_id')->where('fecha',$this->input('fecha'))],
            'dupla_id'=>['nullable','different:responsable_id',Rule::exists('tecnicos','id')->where('activo',true)],
        ];
    }
    public function messages(): array { return ['responsable_id.exists'=>'Seleccioná un productivo activo con legajo cargado.','responsable_id.unique'=>'Este productivo ya tiene una grilla en esa fecha. Abrí la grilla existente.','dupla_id.different'=>'El productivo y la dupla deben ser personas distintas.']; }
    public function attributes(): array { return ['fecha'=>'fecha del registro','responsable_id'=>'productivo (work)','dupla_id'=>'dupla']; }
}
