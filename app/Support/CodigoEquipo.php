<?php
namespace App\Support;
final class CodigoEquipo {
    public static function normalizar(?string $codigo): string {
        return strtoupper(preg_replace('/[\s:.\-]+/u','',trim($codigo ?? '')) ?? '');
    }
}
