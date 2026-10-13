<?php
namespace Database\Seeders;
final class PlanillaFoto {
    public static function datos(): array {
        return json_decode(file_get_contents(database_path('seeders/data/planilla_foto.json')),true,512,JSON_THROW_ON_ERROR);
    }
}

