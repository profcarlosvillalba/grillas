@extends('layout')
@section('title','Primera configuración')
@section('content')
<div class="auth-intro"><span class="brand-icon large"><svg><use href="#i-grid"/></svg></span><h1>Tu planilla, organizada.</h1><p>Equipos, técnicos y órdenes de trabajo en un solo lugar.</p></div>
<section class="auth-card"><span class="eyebrow">PRIMER INGRESO</span><h2>Creá tu cuenta administradora</h2><p>Elegí el correo y la contraseña con los que vas a ingresar.</p>
<form action="{{ route('setup') }}" method="post">@csrf
<label>Tu nombre<input name="name" required maxlength="120" autocomplete="name" value="{{ old('name') }}" autofocus></label>
<label>Correo electrónico<input type="email" name="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}"></label>
<label>Contraseña<input type="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password"><small>Mínimo 8 caracteres.</small></label>
<label>Repetir contraseña<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
<button class="btn primary full">Crear cuenta y comenzar</button></form></section>
<p class="auth-footer">Los datos se guardan en la base de datos del sistema.</p>
@endsection
