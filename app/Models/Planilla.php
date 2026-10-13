<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Planilla extends Model {
    protected $fillable=['fecha','movil_id','responsable_id','dupla_id','movil_codigo','responsable_nombre','dupla_nombre','created_by'];
    protected function casts(): array { return ['fecha'=>'date']; }
    protected function fecha(): Attribute { return Attribute::make(set: fn($value)=>Carbon::parse($value)->format('Y-m-d')); }
    public function registros(): HasMany { return $this->hasMany(Registro::class); }
    public function encabezado(): string { return $this->movil_codigo.' '.mb_strtoupper($this->responsable_nombre.($this->dupla_nombre?' - '.$this->dupla_nombre:'')); }
}
