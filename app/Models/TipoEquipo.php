<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TipoEquipo extends Model {
    protected $table='tipos_equipo';
    protected $fillable=['nombre','activo'];
    protected function casts(): array { return ['activo'=>'boolean']; }
}
