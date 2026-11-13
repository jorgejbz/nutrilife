{{--
    Plantilla base de los PDF de NutriLife (barryvdh/laravel-dompdf).
    dompdf solo entiende CSS 2.1: nada de flex ni grid, el acomodo se hace
    con tablas. La fuente DejaVu Sans trae acentos y la ñ.
    Variables: $docTitle, $docSubtitle (opcional), $nutritionist (User).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $docTitle }}</title>
<style>
    @page { margin: 110px 42px 64px 42px; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1B3625; line-height: 1.45; }

    header { position: fixed; top: -86px; left: 0; right: 0; height: 70px; border-bottom: 2px solid #459C4D; }
    header table { width: 100%; border-collapse: collapse; }
    header td { vertical-align: middle; padding: 0; }
    .brand-logo { width: 40px; height: 40px; }
    .brand-name { font-size: 20px; font-weight: bold; color: #1B3625; padding-left: 8px; }
    .brand-name span { color: #459C4D; }
    .brand-tag { font-size: 8.5px; color: #6F876F; padding-left: 8px; }
    .doc-meta { text-align: right; font-size: 9px; color: #6F876F; }
    .doc-meta strong { display: block; font-size: 12px; color: #1B3625; }

    footer { position: fixed; bottom: -44px; left: 0; right: 0; height: 30px; border-top: 1px solid #E1EEDC; font-size: 8.5px; color: #8F958B; padding-top: 6px; }

    h1 { font-size: 19px; margin: 0 0 2px; color: #1B3625; }
    h2 { font-size: 13px; margin: 18px 0 8px; color: #2F7A38; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #E1EEDC; padding-bottom: 4px; }
    h3 { font-size: 11.5px; margin: 0 0 4px; color: #1B3625; }
    p { margin: 0 0 6px; }
    .muted { color: #6F876F; }
    .small { font-size: 9px; }
    .subtitle { color: #6F876F; font-size: 11px; margin-bottom: 12px; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.data th { background: #1B3625; color: #FEFBF4; font-size: 9px; text-align: left; padding: 5px 6px; font-weight: bold; }
    table.data td { padding: 5px 6px; border-bottom: 1px solid #E1EEDC; vertical-align: top; }
    table.data tr.alt td { background: #F6FAF3; }
    table.data .num { text-align: right; white-space: nowrap; }
    table.data tr.total td { font-weight: bold; background: #E6FCE5; border-top: 1px solid #97D7A5; }

    table.cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 6px; }
    table.cards td { background: #F6FAF3; border: 1px solid #E1EEDC; border-radius: 8px; padding: 8px 10px; vertical-align: top; }
    .card-label { font-size: 8.5px; color: #6F876F; text-transform: uppercase; letter-spacing: .5px; }
    .card-value { font-size: 16px; font-weight: bold; color: #1B3625; }
    .card-note { font-size: 8.5px; color: #6F876F; }

    .pill { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 8.5px; background: #E6FCE5; color: #2F7A38; margin: 0 3px 3px 0; }
    .pill.danger { background: #FBE9E9; color: #9E3B3B; }
    .pill.warn { background: #FBF3E1; color: #8A6A1F; }
    .up { color: #2F7A38; }
    .down { color: #9E3B3B; }

    .bar { height: 7px; background: #E1EEDC; border-radius: 4px; }
    .bar div { height: 7px; background: #459C4D; border-radius: 4px; }

    .box { border: 1px solid #E1EEDC; border-radius: 8px; padding: 8px 10px; margin-bottom: 8px; }
    .keep { page-break-inside: avoid; }
</style>
</head>
<body>
@php($logo = public_path('images/brand/nutrilife-emblem-192.png'))
<header>
    <table>
        <tr>
            <td style="width: 44px;">
                @if (is_file($logo))
                    <img class="brand-logo" src="data:image/png;base64,{{ base64_encode(file_get_contents($logo)) }}" alt="NutriLife">
                @endif
            </td>
            <td>
                <div class="brand-name">nutri<span>life</span></div>
                <div class="brand-tag">Tu clínica de nutrición digital</div>
            </td>
            <td class="doc-meta">
                <strong>{{ $docTitle }}</strong>
                {{ $nutritionist->name }}@if ($nutritionist->professional_license) · Céd. prof. {{ $nutritionist->professional_license }}@endif<br>
                Generado el {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</header>

<footer>
    <table style="width:100%; border-collapse: collapse;">
        <tr>
            <td>NutriLife · {{ $docTitle }}@isset($docSubtitle) · {{ $docSubtitle }}@endisset</td>
        </tr>
    </table>
</footer>

<main>
    @yield('content')
</main>
</body>
</html>
