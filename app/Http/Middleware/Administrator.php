<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class Administrator {
    public function handle(Request $request, Closure $next): Response {
        abort_unless($request->user()?->esAdmin(),403,'Esta sección requiere una cuenta administradora.');
        return $next($request);
    }
}
