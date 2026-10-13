@extends('layout')
@section('title','Grilla diaria')
@section('content')
<header class="page-header"><div><span class="eyebrow">REGISTRO POR DÍA Y MÓVIL</span><h1>Grilla diaria</h1><p>Escaneá equipos y completá su orden de trabajo cuando se asignen.</p></div><div class="actions"><a class="btn secondary" href="{{ route('exportar',request()->only(['fecha','movil_id','tipo_equipo_id','estado','q'])) }}">Exportar Excel</a><button class="btn secondary" type="button" data-print>Imprimir</button><a class="btn primary" href="{{ route('planillas.create',['fecha'=>$fecha]) }}">＋ Nueva grilla</a></div></header>
<div class="stats"><div class="stat"><span>Móviles del día</span><strong>{{ $cantidadMoviles }}</strong><small>Legajo del productivo</small></div><div class="stat"><span>Equipos registrados</span><strong data-count="total">{{ $total }}</strong><small>Escaneados en esta fecha</small></div><div class="stat warning"><span>Pendientes de OT</span><strong data-count="pendientes">{{ $pendientes }}</strong><small>Sin asignación</small></div><div class="stat positive"><span>Con OT asignada</span><strong data-count="asignados">{{ $asignados }}</strong><small>OT y fecha completas</small></div></div>
<form class="filters panel" action="{{ route('grilla') }}" method="get">
<label>Fecha del registro<input type="date" name="fecha" value="{{ $fecha }}" required></label>
@include('partials.filtros')
<button class="btn primary">Ver grilla</button><a class="clear-link" href="{{ route('grilla',['fecha'=>$fecha]) }}">Limpiar</a></form>
<div class="day-line"><div class="day-nav"><a aria-label="Día anterior" href="{{ route('grilla',['fecha'=>\Carbon\Carbon::parse($fecha)->subDay()->format('Y-m-d')]) }}">‹</a><strong>{{ \Carbon\Carbon::parse($fecha)->translatedFormat('l d \d\e F \d\e Y') }}</strong><a aria-label="Día siguiente" href="{{ route('grilla',['fecha'=>\Carbon\Carbon::parse($fecha)->addDay()->format('Y-m-d')]) }}">›</a></div><span>Totales de todo el día · {{ $planillas->count() }} grillas visibles</span></div>
<div id="daily-grid" data-filtered="{{ $filtered?'true':'false' }}">
@forelse($planillas as $planilla)
<section class="sheet panel" id="planilla-{{ $planilla->id }}">
<div class="sheet-head"><div class="mobile-id"><svg><use href="#i-truck"/></svg><span><small>LEGAJO WORK</small><strong>{{ $planilla->movil_codigo }}</strong></span></div><div class="team"><strong class="team-title">{{ $planilla->encabezado() }}</strong><div><span class="role-label">PRODUCTIVO · WORK</span><strong>{{ $planilla->responsable_nombre }}</strong></div><div><span class="role-label">DUPLA</span><strong>{{ $planilla->dupla_nombre ?? 'Sin dupla' }}</strong></div></div><div class="sheet-meta"><span><b data-sheet-total="{{ $planilla->id }}">{{ $planilla->registros_count }}</b> equipos</span><a class="text-link" href="{{ route('planillas.edit',$planilla) }}">Editar técnicos</a>@if($planilla->registros_count===0)<form method="post" action="{{ route('planillas.destroy',$planilla) }}" data-confirm="¿Eliminar esta grilla vacía?">@csrf @method('DELETE')<button class="text-link danger-text">Eliminar grilla</button></form>@endif</div></div>
<div class="table-scroll"><table class="equipment-table"><thead><tr><th>Equipo</th><th>MAC / Serie</th><th>OT</th><th>Fecha OT</th><th>Estado</th><th class="actions-column">Acciones</th></tr></thead><tbody data-rows="{{ $planilla->id }}">
@forelse($planilla->registros as $registro) @include('partials.registro-row')
@empty<tr data-empty><td colspan="6"><div class="empty-row">Todavía no hay equipos. Registrá el primero con el lector o escribiendo su código.</div></td></tr>@endforelse
</tbody></table></div>
@if(!$filtered)
<form class="scan-form" action="{{ route('registros.store',$planilla) }}" method="post" data-ajax="scan" data-planilla="{{ $planilla->id }}">
@csrf
<span class="scan-icon"><svg><use href="#i-scan"/></svg></span>
<label>Equipo<select name="tipo_equipo_id" required><option value="">Elegir tipo</option>@foreach($tiposActivos as $tipo)<option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>@endforeach</select></label>
<label class="scan-code">MAC / Serie<input name="mac" placeholder="Escaneá o escribí el código" maxlength="100" required autocomplete="off" autocapitalize="characters" spellcheck="false" data-mac></label>
<button class="btn primary" @disabled($tiposActivos->isEmpty())>Registrar equipo</button>
<p class="scan-help">Lector USB: seleccioná el tipo, enfocá el código y escaneá. Tab o Enter guarda y deja listo el campo para el siguiente código. No hace falta tocar el botón.</p>
<p class="scan-status" data-scan-status role="status" aria-live="polite">Listo para el siguiente código.</p>
</form>
@else<div class="filter-note">Hay filtros activos. Limpiá los filtros para escanear equipos en esta grilla.</div>@endif
</section>
@empty
<section class="empty-state panel"><svg><use href="#i-grid"/></svg><h2>{{ $filtered?'No hay equipos que coincidan':'Comenzá la jornada' }}</h2><p>{{ $filtered?'Probá otra MAC, OT o quitá los filtros.':'Creá una grilla, elegí el productivo y la dupla, y registrá los equipos del día.' }}</p><a class="btn primary" href="{{ route('planillas.create',['fecha'=>$fecha]) }}">Crear una grilla</a>
@if(auth()->user()->esAdmin())<p class="small-note">Primero podés cargar <a href="{{ route('catalogos.index','tecnicos') }}">técnicos con su legajo</a>.</p>@endif</section>
@endforelse
</div>
<p class="footnote">Fecha del registro: día del escaneo. Fecha OT: día en que se asignó el equipo a esa orden.</p>
@endsection
