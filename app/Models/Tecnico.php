<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Tecnico extends Model {
    protected $fillable=['legajo','apellido','nombre','activo'];
    protected function casts(): array { return ['activo'=>'boolean']; }
    public function etiqueta(): string { return $this->apellido.($this->nombre ? ', '.$this->nombre : ''); }
    public function etiquetaConLegajo(): string { return ($this->legajo ?: 'Sin legajo').' · '.$this->etiqueta(); }
}
