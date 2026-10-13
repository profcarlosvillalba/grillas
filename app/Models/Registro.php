<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Registro extends Model {
    protected $attributes=['version'=>1];
    protected $fillable=['planilla_id','tipo_equipo_id','tipo_nombre','fecha_registro','mac','ot','fecha_ot','created_by','updated_by','version'];
    protected function casts(): array { return ['fecha_registro'=>'date','fecha_ot'=>'date','version'=>'integer']; }
    protected function fechaRegistro(): Attribute { return Attribute::make(set: fn($value)=>Carbon::parse($value)->format('Y-m-d')); }
    protected function fechaOt(): Attribute { return Attribute::make(set: fn($value)=>$value===null?null:Carbon::parse($value)->format('Y-m-d')); }
    public function planilla(): BelongsTo { return $this->belongsTo(Planilla::class); }
}
