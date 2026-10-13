<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Movil extends Model {
    protected $table='moviles';
    protected $fillable=['codigo','descripcion','activo'];
    protected function casts(): array { return ['activo'=>'boolean']; }
}
