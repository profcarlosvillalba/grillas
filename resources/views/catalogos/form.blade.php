@extends('layout')
@section('title',($item->exists?'Editar':'Agregar').' · '.$titulo)
@section('section','CATÁLOGOS')
@section('content')
<header class="page-header"><div><span class="eyebrow">CATÁLOGOS / {{ mb_strtoupper($titulo) }}</span><h1>{{ $item->exists?'Editar dato':'Agregar dato' }}</h1></div></header>
<section class="panel form-panel"><form method="post" action="{{ $item->exists?route('catalogos.update',[$catalogo,$item->id]):route('catalogos.store',$catalogo) }}">@csrf @if($item->exists) @method('PUT') @endif
<div class="form-grid">
@if($catalogo==='tecnicos')<label>Legajo<input name="legajo" value="{{ old('legajo',$item->legajo) }}" required maxlength="20" placeholder="Ejemplo: 207" pattern="[A-Za-z0-9\-]+" autofocus><small>Único por técnico. Conserva los ceros iniciales.</small></label><label>Apellido / Identificación del técnico<input name="apellido" value="{{ old('apellido',$item->apellido) }}" required maxlength="120" placeholder="Ejemplo: RAUL"></label><label>Nombre · Opcional<input name="nombre" value="{{ old('nombre',$item->nombre) }}" maxlength="120"></label>
@elseif($catalogo==='moviles')<label>Código de móvil / Work<input name="codigo" value="{{ old('codigo',$item->codigo) }}" required maxlength="20" placeholder="Ejemplo: 207" autofocus><small>Se conserva como texto, incluidos los ceros iniciales.</small></label><label>Descripción · Opcional<input name="descripcion" value="{{ old('descripcion',$item->descripcion) }}" maxlength="120"></label>
@else<label>Tipo de equipo<input name="nombre" value="{{ old('nombre',$item->nombre) }}" required maxlength="40" placeholder="Ejemplo: EXTENSOR" autofocus></label>@endif
</div><input type="hidden" name="activo" value="0"><label class="checkbox"><input type="checkbox" name="activo" value="1" @checked(old('activo',$item->exists?$item->activo:true))>Activo · Disponible para nuevas cargas</label>
<div class="form-footer"><a class="btn secondary" href="{{ route('catalogos.index',$catalogo) }}">Volver</a><button class="btn primary">Guardar</button></div>
</form></section>
@endsection
