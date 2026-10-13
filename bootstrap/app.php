<?php
use App\Http\Middleware\ActiveUser;
use App\Http\Middleware\Administrator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
return Application::configure(basePath:dirname(__DIR__))
    ->withRouting(web:__DIR__.'/../routes/web.php',commands:__DIR__.'/../routes/console.php',health:'/up')
    ->withMiddleware(function(Middleware $m): void {
        $m->alias(['active'=>ActiveUser::class,'admin'=>Administrator::class]);
        $m->redirectUsersTo('/grilla');
    })
    ->withExceptions(function(Exceptions $e): void {
        $e->shouldRenderJsonWhen(fn(Request $r)=>$r->is('api/*') || $r->expectsJson());
    })->create();
