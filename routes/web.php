<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\GrillaController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
Route::get('/',fn()=>redirect()->route('grilla'));
Route::middleware('guest')->group(function(): void {
    Route::get('/ingresar',[AuthController::class,'login'])->name('login');
    Route::post('/ingresar',[AuthController::class,'authenticate'])->middleware('throttle:5,1');
    Route::get('/configurar',[AuthController::class,'setup'])->name('setup');
    Route::post('/configurar',[AuthController::class,'initialize'])->middleware('throttle:5,1');
});
Route::middleware(['auth','active'])->group(function(): void {
    Route::post('/salir',[AuthController::class,'logout'])->name('logout');
    Route::get('/grilla',[GrillaController::class,'index'])->name('grilla');
    Route::get('/historial',[GrillaController::class,'historial'])->name('historial');
    Route::get('/exportar',[GrillaController::class,'exportar'])->name('exportar');
    Route::get('/grillas/nueva',[PlanillaController::class,'create'])->name('planillas.create');
    Route::post('/grillas',[PlanillaController::class,'store'])->name('planillas.store');
    Route::get('/grillas/{planilla}/editar',[PlanillaController::class,'edit'])->name('planillas.edit');
    Route::put('/grillas/{planilla}',[PlanillaController::class,'update'])->name('planillas.update');
    Route::delete('/grillas/{planilla}',[PlanillaController::class,'destroy'])->name('planillas.destroy');
    Route::post('/grillas/{planilla}/equipos',[RegistroController::class,'store'])->name('registros.store');
    Route::put('/registros/{registro}/ot',[RegistroController::class,'asignar'])->name('registros.ot');
    Route::get('/registros/{registro}/editar',[RegistroController::class,'edit'])->name('registros.edit');
    Route::put('/registros/{registro}',[RegistroController::class,'update'])->name('registros.update');
    Route::delete('/registros/{registro}',[RegistroController::class,'destroy'])->name('registros.destroy');
    Route::view('/ayuda','ayuda')->name('ayuda');
    Route::middleware('admin')->group(function(): void {
        Route::get('/catalogos/{catalogo}',[CatalogoController::class,'index'])->name('catalogos.index');
        Route::get('/catalogos/{catalogo}/nuevo',[CatalogoController::class,'create'])->name('catalogos.create');
        Route::post('/catalogos/{catalogo}',[CatalogoController::class,'store'])->name('catalogos.store');
        Route::get('/catalogos/{catalogo}/{id}/editar',[CatalogoController::class,'edit'])->name('catalogos.edit');
        Route::put('/catalogos/{catalogo}/{id}',[CatalogoController::class,'update'])->name('catalogos.update');
        Route::get('/usuarios',[UsuarioController::class,'index'])->name('usuarios.index');
        Route::post('/usuarios',[UsuarioController::class,'store'])->name('usuarios.store');
        Route::patch('/usuarios/{usuario}/estado',[UsuarioController::class,'toggle'])->name('usuarios.toggle');
    });
});
