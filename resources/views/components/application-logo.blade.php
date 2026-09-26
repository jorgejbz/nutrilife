{{-- Emblema de NutriLife (antes: logotipo SVG de Laravel).
     Acepta atributos, p. ej. <x-application-logo class="h-9 w-auto" />;
     object-fit evita que se deforme con cualquier tamaño. --}}
<img
    src="{{ asset('images/brand/nutrilife-emblem-192.png') }}"
    alt="NutriLife"
    width="192"
    height="192"
    style="object-fit: contain;"
    {{ $attributes }}
>
