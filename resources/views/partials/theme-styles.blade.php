{{-- =========================================================
     HOJAS DE ESTILO POR SECCIÓN + MODO CLARO/OSCURO

     Uso:
         @include('partials.theme-styles', ['section' => 'admin'])

     Secciones: 'app' (sitio público), 'admin' (panel), 'login'
     (login, registro y recuperación de contraseña).

     Carga, en este orden (lista completa en config/stylesheets.php):
       1. base/tokens.css + módulos base de la sección
       2. themes/{section}-light.css y themes/{section}-dark.css:
          solo una queda activa; theme.js las intercambia
       3. responsive/{section}/*.css → todas las @media, de mayor
          a menor ancho, para que puedan ajustar lo anterior
     ========================================================= --}}
@php
    $section = in_array($section ?? 'app', ['app', 'admin', 'login'], true) ? ($section ?? 'app') : 'app';
    $nlSheets = config('stylesheets.sections.'.$section);

    // ?v=<fecha de modificación> evita que el navegador use una versión vieja en caché
    $nlCss = static function (string $file): string {
        $path = public_path('css/'.$file);

        return asset('css/'.$file).(is_file($path) ? '?v='.filemtime($path) : '');
    };
@endphp

@include('partials.theme-init-script')

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('images/brand/nutrilife-emblem-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/brand/nutrilife-emblem-192.png') }}">

@foreach ($nlSheets['base'] as $nlSheet)
    <link rel="stylesheet" href="{{ $nlCss($nlSheet) }}">
@endforeach
@foreach ($nlSheets['themes'] as $nlMode => $nlSheet)
    <link rel="stylesheet" href="{{ $nlCss($nlSheet) }}" data-theme-stylesheet="{{ $nlMode }}">
@endforeach
@foreach ($nlSheets['responsive'] as $nlSheet)
    <link rel="stylesheet" href="{{ $nlCss($nlSheet) }}">
@endforeach

<script>
    window.nutrilifeSyncThemeStylesheets(document.documentElement.getAttribute('data-theme'));
</script>

{{-- Tipografía e iconos comunes a todo NutriLife --}}
@if ($section !== 'app')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@endif
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

{{-- Botón claro/oscuro (sincroniza data-theme y las hojas *-light / *-dark) --}}
@vite(['resources/js/theme.js'])

{{-- Panel y autenticación: evita envíos dobles de formularios (resources/js/forms.js) --}}
@if ($section !== 'app')
    @vite(['resources/js/forms.js'])
@endif

{{-- Panel: calendario de NutriLife para los campos de fecha (resources/js/date-picker.js) --}}
@if ($section === 'admin')
    @vite(['resources/js/date-picker.js'])
@endif
