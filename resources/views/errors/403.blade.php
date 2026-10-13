@extends('layout')
@section('title','Acceso restringido')
@section('content')<section class="panel empty-state"><h1>Acceso restringido</h1><p>Tu cuenta no tiene permisos para esta sección.</p><a class="btn primary" href="{{ route('grilla') }}">Volver a la grilla</a></section>@endsection
