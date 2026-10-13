@extends('layout')
@section('title','Corregir equipo')
@section('content')
<header class="page-header"><div><span class="eyebrow">MÓVIL {{ $registro->planilla->movil_codigo }} · {{ $registro->fecha_registro->format('d/m/Y') }}</span><h1>Corregir equipo</h1><p>Modificá el tipo, el código o su asignación.</p></div></header>
<section class="panel form-panel"><form method="post" action="{{ route('registros.update',$registro) }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $registro->version }}">
<div class="form-grid"><label>Equipo<select name="tipo_equipo_id" required>@foreach($tipos as $tipo)<option value="{{ $tipo->id }}" @selected((string)old('tipo_equipo_id',$registro->tipo_equipo_id)===(string)$tipo->id)>{{ $tipo->nombre }}</option>@endforeach</select></label><label>MAC / Serie<input name="mac" value="{{ old('mac',$registro->mac) }}" maxlength="100" required spellcheck="false" autocomplete="off"></label><label>OT<input name="ot" value="{{ old('ot',$registro->ot) }}" maxlength="40" placeholder="Puede quedar vacía"></label><label>Fecha OT<input type="date" name="fecha_ot" value="{{ old('fecha_ot',$registro->fecha_ot?->format('Y-m-d')) }}"></label></div>
<p class="muted">Para dejar el equipo sin asignación, borrá tanto la OT como su fecha.</p>
<div class="form-footer"><a class="btn secondary" href="{{ route('grilla',['fecha'=>$registro->fecha_registro->format('Y-m-d')]) }}">Volver</a><button class="btn primary">Guardar cambios</button></div></form></section>
@endsection
