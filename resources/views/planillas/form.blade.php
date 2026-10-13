@extends('layout')
@section('title',$planilla->exists?'Editar equipo de técnicos':'Nueva grilla')
@section('content')
<header class="page-header"><div><span class="eyebrow">FECHA + PRODUCTIVO + DUPLA</span><h1>{{ $planilla->exists?'Editar equipo de técnicos':'Nueva grilla diaria' }}</h1><p>El legajo del productivo identifica la grilla. La dupla es opcional.</p></div></header>
<section class="panel form-panel">
@if(!$tecnicos->contains(fn($t)=>!empty($t->legajo)))<div class="alert info">Necesitás un técnico activo con legajo.@if(auth()->user()->esAdmin()) Completalo desde <a href="{{ route('catalogos.index','tecnicos') }}">Técnicos</a>.@else Pedile al administrador que lo cargue.@endif</div>@endif
@if($tecnicos->contains(fn($t)=>empty($t->legajo)))<p class="muted">Los técnicos anteriores sin legajo pueden seguir como dupla. Completá su legajo en Técnicos para poder elegirlos como productivos.</p>@endif
<form method="post" action="{{ $planilla->exists?route('planillas.update',$planilla):route('planillas.store') }}" data-team-form data-original-work="{{ $planilla->responsable_id }}" data-original-code="{{ $planilla->movil_codigo }}" data-original-name="{{ $planilla->responsable_nombre }}">@csrf @if($planilla->exists) @method('PUT') @endif
<div class="form-grid">
@if($planilla->exists)<div class="readonly-field"><span>Fecha del registro</span><strong>{{ $planilla->fecha->format('d/m/Y') }}</strong></div>
@else<label>Fecha del registro<input type="date" name="fecha" value="{{ old('fecha',$planilla->fecha->format('Y-m-d')) }}" required></label>@endif
<div class="readonly-field"><span>Código · Legajo del productivo</span><strong data-work-code>Se completa al elegir el productivo</strong></div>
<label>Productivo · Work<select name="responsable_id" required data-work><option value="">Seleccionar productivo</option>@foreach($tecnicos as $tecnico)<option value="{{ $tecnico->id }}" data-legajo="{{ $tecnico->legajo }}" data-name="{{ $tecnico->etiqueta() }}" @disabled(!$tecnico->legajo && $tecnico->id!==$planilla->responsable_id) @selected((string)old('responsable_id',$planilla->responsable_id)===(string)$tecnico->id)>{{ $tecnico->etiquetaConLegajo() }}</option>@endforeach</select><small>Su legajo será el código de la planilla.</small></label>
<label>Dupla · Opcional<select name="dupla_id" data-dupla><option value="">Sin dupla</option>@foreach($tecnicos as $tecnico)<option value="{{ $tecnico->id }}" data-name="{{ $tecnico->etiqueta() }}" @selected((string)old('dupla_id',$planilla->dupla_id)===(string)$tecnico->id)>{{ $tecnico->etiquetaConLegajo() }}</option>@endforeach</select></label>
</div>
<div class="team-preview"><span>Así queda el encabezado</span><strong data-team-preview aria-live="polite">{{ $planilla->exists?$planilla->encabezado():'Seleccioná el productivo y la dupla' }}</strong><button class="btn secondary tiny" type="button" data-swap-team>Intercambiar productivo y dupla</button></div>
<div class="form-footer"><a class="btn secondary" href="{{ route('grilla',['fecha'=>$planilla->fecha->format('Y-m-d')]) }}">Volver</a><button class="btn primary" @disabled($tecnicos->isEmpty())>{{ $planilla->exists?'Guardar técnicos':'Crear grilla' }}</button></div>
</form></section>
@endsection
