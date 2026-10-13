<tr id="registro-{{ $registro->id }}">
<td><span class="equipment-badge">{{ $registro->tipo_nombre }}</span></td>
<td class="mac-cell">{{ $registro->mac }}</td>
<td><label class="sr-only" for="ot-{{ $registro->id }}-value">OT de {{ $registro->mac }}</label><input class="cell-input" id="ot-{{ $registro->id }}-value" form="ot-{{ $registro->id }}" name="ot" value="{{ $registro->ot }}" maxlength="40" placeholder="Sin asignar" autocomplete="off"><span class="print-value">{{ $registro->ot }}</span></td>
<td><label class="sr-only" for="date-{{ $registro->id }}">Fecha OT de {{ $registro->mac }}</label><input class="cell-input date-input" id="date-{{ $registro->id }}" form="ot-{{ $registro->id }}" type="date" name="fecha_ot" value="{{ $registro->fecha_ot?->format('Y-m-d') }}"><span class="print-value">{{ $registro->fecha_ot?->format('d/m/Y') }}</span></td>
<td><span class="badge {{ $registro->ot?'assigned':'pending' }}">{{ $registro->ot?'Asignado':'Sin OT' }}</span></td>
<td class="row-actions">
<form id="ot-{{ $registro->id }}" action="{{ route('registros.ot',$registro) }}" method="post" data-ajax="ot">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $registro->version }}"><button class="btn tiny secondary">Guardar</button></form>
<details class="row-menu"><summary aria-label="Más acciones para {{ $registro->mac }}">•••</summary><div><a href="{{ route('registros.edit',$registro) }}">Corregir equipo</a><form action="{{ route('registros.destroy',$registro) }}" method="post" data-confirm="¿Eliminar el registro de {{ $registro->mac }} de este día?">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $registro->version }}"><button class="danger-text">Eliminar registro</button></form></div></details>
</td></tr>
