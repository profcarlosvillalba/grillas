<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#0f766e">
<title>@yield('title','Control de equipos') · Grillas</title>
<link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('assets/app.css') }}?v=20261013">
<script src="{{ asset('assets/app.js') }}?v=20261013" defer></script>
</head>
<body class="@auth app-body @else auth-body @endauth">
<svg class="sprite" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
<symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 9v12M15 9v12"/></symbol>
<symbol id="i-scan" viewBox="0 0 24 24"><path d="M3 8V3h5m8 0h5v5M3 16v5h5m8 0h5v-5M4 12h16M7 8v8m4-8v8m3-8v8m3-8v8"/></symbol>
<symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m2-17a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 5v2"/></symbol>
<symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/></symbol>
<symbol id="i-truck" viewBox="0 0 24 24"><path d="M3 6h12v11H3zM15 10h4l3 4v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></symbol>
<symbol id="i-box" viewBox="0 0 24 24"><path d="m12 3 9 5v9l-9 5-9-5V8zM3 8l9 5 9-5M12 13v9M7 5l10 6"/></symbol>
</svg>
@auth
<aside class="sidebar" id="sidebar">
<a class="brand" href="{{ route('grilla') }}"><span class="brand-icon"><svg><use href="#i-grid"/></svg></span><span>Control de equipos<small>GRILLAS DIARIAS</small></span></a>
<div class="nav-caption">OPERACIÓN</div>
<nav aria-label="Navegación principal">
<a class="{{ request()->routeIs('grilla','planillas.*','registros.*')?'active':'' }}" href="{{ route('grilla') }}"><svg><use href="#i-grid"/></svg>Grilla diaria</a>
<a class="{{ request()->routeIs('historial')?'active':'' }}" href="{{ route('historial') }}"><svg><use href="#i-clock"/></svg>Historial de equipos</a>
@if(auth()->user()->esAdmin())
<div class="nav-caption">CATÁLOGOS</div>
@foreach(['tecnicos'=>['Técnicos y legajos','users'],'equipos'=>['Tipos de equipo','box']] as $key=>$menu)
<a class="{{ request()->segment(2)===$key?'active':'' }}" href="{{ route('catalogos.index',$key) }}"><svg><use href="#i-{{ $menu[1] }}"/></svg>{{ $menu[0] }}</a>
@endforeach
<a class="{{ request()->routeIs('usuarios.*')?'active':'' }}" href="{{ route('usuarios.index') }}"><svg><use href="#i-users"/></svg>Usuarios</a>
@endif
<div class="nav-caption">AYUDA</div>
<a class="{{ request()->routeIs('ayuda')?'active':'' }}" href="{{ route('ayuda') }}"><svg><use href="#i-scan"/></svg>Cómo usar el sistema</a>
</nav>
<div class="sidebar-note"><span class="dot"></span> Registro diario de equipos<small>MAC, serie y asignación de OT</small></div>
<div class="account"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->esAdmin()?'Administrador':'Operador' }}</small></div><form action="{{ route('logout') }}" method="post">@csrf<button class="signout" title="Cerrar sesión">Salir</button></form></div>
</aside>
<main class="main">
<div class="mobile-bar"><button type="button" class="btn secondary" data-menu aria-controls="sidebar" aria-expanded="false">Menú</button><strong>Control de equipos</strong></div>
<div class="topline"><span>OPERACIONES / @yield('section','REGISTRO DE EQUIPOS')</span><span>{{ now()->translatedFormat('l d/m/Y') }}</span></div>
@else
<main class="auth-main">
@endauth
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert danger" role="alert"><strong>Revisá estos datos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
<div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
</main>
</body></html>
