<?php
namespace App\Http\Requests;
use App\Support\CodigoEquipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreRegistroRequest extends FormRequest {
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void {
        $this->merge(['mac'=>CodigoEquipo::normalizar(is_string($this->input('mac')) ? $this->input('mac') : '')]);
    }
    public function rules(): array {
        $r=$this->route('registro'); $p=$this->route('planilla') ?? $r?->planilla;
        $rules=[
            'tipo_equipo_id'=>['required',Rule::exists('tipos_equipo','id')->where(fn($q)=>$q->where('activo',true)->when($r,fn($q)=>$q->orWhere('id',$r->tipo_equipo_id)))],
            'mac'=>['required','regex:/^[A-Z0-9]{4,64}$/',Rule::unique('registros','mac')->where('fecha_registro',$p->fecha->format('Y-m-d'))->ignore($r?->id)],
        ];
        if($r) { $rules += [
            'ot'=>['nullable','required_with:fecha_ot','string','max:40'],
            'fecha_ot'=>['nullable','required_with:ot','date_format:Y-m-d'],
            'version'=>['required','integer','min:1'],
        ]; }
        return $rules;
    }
    public function messages(): array { return [
        'mac.regex'=>'Ingresá una MAC o serie de 4 a 64 letras o números. Los separadores se eliminan al guardar.',
        'mac.unique'=>'Este equipo ya fue escaneado en esta fecha, posiblemente en otro móvil. Buscá su MAC en la grilla.',
        'fecha_ot.required_with'=>'Completá también la fecha de la OT.',
        'ot.required_with'=>'Completá también el número de OT.',
    ]; }
    public function attributes(): array { return ['mac'=>'MAC / serie','tipo_equipo_id'=>'tipo de equipo','fecha_ot'=>'fecha OT','ot'=>'OT']; }
}
