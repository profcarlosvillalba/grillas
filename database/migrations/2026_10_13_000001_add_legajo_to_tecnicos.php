<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Nullable permite actualizar una instalación con técnicos ya cargados.
        // El formulario exige un legajo para las altas y para elegir un nuevo work.
        Schema::table('tecnicos',function(Blueprint $table): void {
            $table->string('legajo',20)->nullable()->unique();
        });
    }
    public function down(): void {
        Schema::table('tecnicos',function(Blueprint $table): void {
            $table->dropUnique(['legajo']);
            $table->dropColumn('legajo');
        });
    }
};
