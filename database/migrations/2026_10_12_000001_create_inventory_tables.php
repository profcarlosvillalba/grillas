<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function(Blueprint $t): void { $t->string('role',20)->default('operador'); $t->boolean('active')->default(true); });
        Schema::create('application_setup', function(Blueprint $t): void { $t->unsignedInteger('id')->primary(); });
        Schema::create('tecnicos', function(Blueprint $t): void {
            $t->id(); $t->string('apellido',120); $t->string('nombre',120)->nullable(); $t->boolean('activo')->default(true); $t->timestamps();
        });
        Schema::create('moviles', function(Blueprint $t): void {
            $t->id(); $t->string('codigo',20)->unique(); $t->string('descripcion',120)->nullable(); $t->boolean('activo')->default(true); $t->timestamps();
        });
        Schema::create('tipos_equipo', function(Blueprint $t): void {
            $t->id(); $t->string('nombre',40)->unique(); $t->boolean('activo')->default(true); $t->timestamps();
        });
        Schema::create('planillas', function(Blueprint $t): void {
            $t->id(); $t->date('fecha');
            $t->foreignId('movil_id')->constrained('moviles')->restrictOnDelete();
            $t->foreignId('responsable_id')->constrained('tecnicos')->restrictOnDelete();
            $t->foreignId('dupla_id')->nullable()->constrained('tecnicos')->restrictOnDelete();
            $t->string('movil_codigo',20); $t->string('responsable_nombre',250); $t->string('dupla_nombre',250)->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['fecha','movil_id']);
        });
        Schema::create('registros', function(Blueprint $t): void {
            $t->id(); $t->foreignId('planilla_id')->constrained('planillas')->restrictOnDelete();
            $t->foreignId('tipo_equipo_id')->constrained('tipos_equipo')->restrictOnDelete();
            $t->string('tipo_nombre',40); $t->date('fecha_registro'); $t->string('mac',64);
            $t->string('ot',40)->nullable(); $t->date('fecha_ot')->nullable(); $t->unsignedInteger('version')->default(1);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('updated_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['fecha_registro','mac']); $t->index('mac'); $t->index('ot');
        });
    }
    public function down(): void {
        foreach(['registros','planillas','tipos_equipo','moviles','tecnicos','application_setup'] as $table) { Schema::dropIfExists($table); }
        Schema::table('users', function(Blueprint $t): void { $t->dropColumn(['role','active']); });
    }
};
