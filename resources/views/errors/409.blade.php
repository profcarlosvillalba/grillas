@extends('layout')
@section('title','Registro actualizado')
@section('content')<section class="panel empty-state"><h1>El registro cambió</h1><p>Otra persona lo modificó mientras lo estabas editando. Abrí nuevamente la grilla para trabajar con los datos actuales.</p><a class="btn primary" href="{{ route('grilla') }}">Volver a la grilla</a></section>@endsection
