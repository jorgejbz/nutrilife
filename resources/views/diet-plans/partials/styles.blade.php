{{-- =========================================================
     PLANES ALIMENTICIOS — HOJAS DE ESTILO
     Tema del panel (claro/oscuro) + estilos propios del módulo
     (public/css/diet-plans/*, todo bajo .dp, y sus @media en
     public/css/responsive/diet-plans/*). Orden: config/stylesheets.php.
     ========================================================= --}}
@include('partials.theme-styles', ['section' => 'admin'])

@foreach (config('stylesheets.modules.diet-plans') as $dpSheet)
    @php $dpCss = public_path('css/'.$dpSheet); @endphp
    <link rel="stylesheet" href="{{ asset('css/'.$dpSheet) }}{{ is_file($dpCss) ? '?v='.filemtime($dpCss) : '' }}">
@endforeach
