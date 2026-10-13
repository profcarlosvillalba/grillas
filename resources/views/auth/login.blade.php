@extends('layout')
@section('title','Ingresar')
@section('content')
<div class="auth-intro"><span class="brand-icon large"><svg><use href="#i-grid"/></svg></span><h1>Control de equipos</h1><p>Volvé a tu grilla diaria.</p></div>
<section class="auth-card"><span class="eyebrow">BIENVENIDO</span><h2>Ingresar al sistema</h2>
<form action="{{ route('login') }}" method="post">@csrf
<label>Correo electrónico<input type="email" name="email" required autocomplete="username" value="{{ old('email') }}" autofocus></label>
<label>Contraseña<input type="password" name="password" required autocomplete="current-password"></label>
<button class="btn primary full">Ingresar</button></form></section>
<p class="auth-footer">Usá los datos que elegiste al configurar tu cuenta.</p>
@endsection
