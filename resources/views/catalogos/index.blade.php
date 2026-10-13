@extends('layout')
@section('title',$titulo)
@section('section','CATÁLOGOS')
@section('content')
<header class="page-header"><div><span class="eyebrow">CONFIGURACIÓN</span><h1>{{ $titulo }}</h1><p>{{ $catalogo==='tecnicos'?'Legajo e identificación de cada productivo y dupla.':($catalogo==='moviles'?'Códigos conservados para las grillas y el historial.':'HD, WIFI, DCT, ISDBT y todos los tipos que utilices.') }}</p></div><a class="btn primary" href="{{ route('catalogos.create',$catalogo) }}">＋ Agregar</a></header>
<section class="panel"><div class="table-scroll"><table><thead><tr>@if($catalogo==='tecnicos')<th>Legajo</th>@endif<th>{{ $catalogo==='moviles'?'Código':($catalogo==='tecnicos'?'Apellido / Identificación':'Tipo de equipo') }}</th>@if($catalogo!=='equipos')<th>{{ $catalogo==='tecnicos'?'Nombre':'Descripción' }}</th>@endif<th>Estado</th><th>Acciones</th></tr></thead><tbody>
@forelse($items as $item)<tr>@if($catalogo==='tecnicos')<td><strong>{{ $item->legajo ?? 'Pendiente de completar' }}</strong></td>@endif<td><strong>{{ $catalogo==='moviles'?$item->codigo:($catalogo==='tecnicos'?$item->apellido:$item->nombre) }}</strong></td>@if($catalogo!=='equipos')<td>{{ $catalogo==='tecnicos'?($item->nombre ?? '—'):($item->descripcion ?? '—') }}</td>@endif<td><span class="badge {{ $item->activo?'assigned':'inactive' }}">{{ $item->activo?'Activo':'Inactivo' }}</span></td><td><a class="btn tiny secondary" href="{{ route('catalogos.edit',[$catalogo,$item->id]) }}">Editar</a></td></tr>
@empty<tr><td colspan="{{ $catalogo==='tecnicos'?5:4 }}"><div class="empty-row">Todavía no hay datos. Agregá el primero.</div></td></tr>@endforelse
</tbody></table></div></section>@include('partials.pagination',['paginator'=>$items])
<p class="footnote">Desactivá los datos que ya no utilices. Los registros históricos conservan sus nombres y códigos.</p>
@endsection
