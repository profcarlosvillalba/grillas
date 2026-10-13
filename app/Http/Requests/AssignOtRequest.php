<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AssignOtRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return [
        'ot'=>['nullable','required_with:fecha_ot','string','max:40'],
        'fecha_ot'=>['nullable','required_with:ot','date_format:Y-m-d'],
        'version'=>['required','integer','min:1'],
    ]; }
    public function messages(): array { return ['fecha_ot.required_with'=>'Ingresá también la fecha de la OT.','ot.required_with'=>'Ingresá también el número de OT.']; }
}
