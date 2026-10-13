@extends('layout')
@section('title','Historial de equipos')
@section('section','HISTORIAL')
@section('content')
<header class="page-header"><div><span class="eyebrow">TRAZABILIDAD POR MAC O SERIE</span><h1>Historial de equipos</h1><p>Encontrá en qué días y móviles se registró cada equipo.</p></div><a class="btn secondary" href="{{ route('exportar',request()->only(['desde','hasta','movil_id','tipo_equipo_id','estado','q'])+['historial'=>1]) }}">Exportar Excel</a></header>
<form class="filters panel" action="{{ route('historial') }}" method="get"><label>Desde<input type="date" name="desde" value="{{ request('desde') }}"></label><label>Hasta<input type="date" name="hasta" value="{{ request('hasta') }}"></label>@include('partials.filtros')<button class="btn primary">Buscar</button><a class="clear-link" href="{{ route('historial') }}">Limpiar</a></form>
<div class="results-line"><strong>{{ $registros->total() }} registros encontrados</strong><span>Hasta 40 por página</span></div>
<section class="panel"><div class="table-scroll"><table><thead><tr><th>Fecha registro</th><th>Móvil</th><th>Responsable / Dupla</th><th>Equipo</th><th>MAC / Serie</th><th>OT</th><th>Fecha OT</th><th class="actions-column"></th></tr></thead><tbody>
@forelse($registros as $registro)<tr><td>{{ $registro->fecha_registro->format('d/m/Y') }}</td><td><span class="mobile-pill">{{ $registro->planilla->movil_codigo }}</span></td><td><strong>{{ $registro->planilla->responsable_nombre }}</strong><small class="block">{{ $registro->planilla->dupla_nombre ?? 'Sin dupla' }}</small></td><td><span class="equipment-badge">{{ $registro->tipo_nombre }}</span></td><td class="mac-cell">{{ $registro->mac }}</td><td>{{ $registro->ot ?? '—' }}</td><td>{{ $registro->fecha_ot?->format('d/m/Y') ?? '—' }}</td><td><a class="text-link" href="{{ route('grilla',['fecha'=>$registro->fecha_registro->format('Y-m-d'),'movil_id'=>$registro->planilla->movil_id]).'#planilla-'.$registro->planilla_id }}">Ver grilla</a></td></tr>
@empty<tr><td colspan="8"><div class="empty-row">No se encontraron registros. Probá con otra fecha o código.</div></td></tr>@endforelse
</tbody></table></div></section>
@include('partials.pagination',['paginator'=>$registros])
@endsection
