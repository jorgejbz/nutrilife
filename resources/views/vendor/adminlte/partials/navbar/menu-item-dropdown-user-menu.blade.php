@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

{{--
    Resolve the data of the authenticated user. Note the 'adminlte_*' methods
    are optional additions to the user model, so they are only called when the
    model provides them, otherwise enabling one of the related options would
    break every page of the panel.
--}}
@php
    $umUser = Auth::user();

    $umValueOf = static function ($method) use ($umUser) {
        return is_object($umUser) && method_exists($umUser, $method)
            ? $umUser->{$method}()
            : null;
    };

    $umName = $umUser->name ?? '';
    $umImage = config('adminlte.usermenu_image') ? $umValueOf('adminlte_image') : null;
    $umDesc = config('adminlte.usermenu_desc') ? $umValueOf('adminlte_desc') : null;

    $logout_url = View::getSection('logout_url') ?? config('adminlte.logout_url', 'logout');
    $profile_url = View::getSection('profile_url') ?? config('adminlte.profile_url', false);

    if (config('adminlte.usermenu_profile_url', false)) {
        $profile_url = $umValueOf('adminlte_profile_url');
    }

    $profile_url = $layoutHelper->makeUrl($profile_url);
    $logout_url = $layoutHelper->makeUrl($logout_url);
@endphp

{{--
    Setup the user menu header classes. The legacy 'bg-{color}' values are
    translated to the Bootstrap 5.3 'text-bg-{color}' helpers, so the header
    text keeps a proper contrast color on AdminLTE v4.
--}}
@php
    $umBsColors = [
        'primary', 'secondary', 'success', 'danger', 'warning', 'info',
        'light', 'dark',
    ];

    $umHeaderCfg = config('adminlte.usermenu_header_class', 'bg-primary');
    $umHeaderClass = collect(preg_split('/\s+/', trim((string) $umHeaderCfg)))
        ->map(function ($class) use ($umBsColors) {
            $color = str_starts_with($class, 'bg-') ? substr($class, 3) : null;

            return in_array($color, $umBsColors, true) ? "text-bg-{$color}" : $class;
        })
        ->filter()
        ->implode(' ');
@endphp

{{-- =========================================================
     MENÚ DE USUARIO (NutriLife)
     - Cabecera con nombre, correo y tipo de cuenta.
     - "Cerrar sesión" es un <form method="POST"> con @csrf y un
       botón real (antes era un enlace con onclick a un form oculto):
       funciona con teclado y sin depender de JavaScript.
     ========================================================= --}}
@php
    $umInitials = collect(preg_split('/\s+/', trim(preg_replace('/^(Dra?\.|Lic\.|Mtra?\.)\s*/u', '', $umName))))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $umAccount = \App\Models\Subscription::accountTypeFor($umUser);
@endphp

<li class="nav-item dropdown user-menu nl-user-menu">

    {{-- Botón del menú --}}
    <a href="#" class="nav-link dropdown-toggle nl-user-toggle" data-bs-toggle="dropdown"
       aria-expanded="false" aria-label="Menú de {{ $umName }}">
        <span class="nl-user-avatar" aria-hidden="true">{{ $umInitials ?: 'NL' }}</span>
        <span class="nl-user-name" title="{{ $umName }}">{{ $umName }}</span>
    </a>

    <ul class="dropdown-menu dropdown-menu-end nl-user-dropdown">

        <li class="nl-user-dropdown__header">
            <span class="nl-user-avatar nl-user-avatar--lg" aria-hidden="true">{{ $umInitials ?: 'NL' }}</span>
            <div>
                <strong>{{ $umName }}</strong>
                <small>{{ $umUser->email ?? '' }}</small>
                @if($umAccount)
                    <span class="nl-user-dropdown__plan">{{ $umAccount['label'] }}</span>
                @endif
            </div>
        </li>

        {{-- Enlaces configurados en adminlte.menu (navbar-user) --}}
        @each('adminlte::partials.navbar.dropdown-item', $adminlte->menu("navbar-user"), 'item')

        @if(Route::has('perfil'))
            <li>
                <a class="dropdown-item nl-user-dropdown__link" href="{{ route('perfil') }}">
                    <i class="fas fa-user-circle fa-fw" aria-hidden="true"></i>
                    Mi perfil
                </a>
            </li>
        @endif

        @if(Route::has('subscription.plans') && $umUser?->isNutritionist() && ! $umUser->isClinicMember())
            <li>
                <a class="dropdown-item nl-user-dropdown__link" href="{{ route('subscription.plans') }}">
                    <i class="fas fa-box-open fa-fw" aria-hidden="true"></i>
                    Paquetes
                </a>
            </li>
        @endif

        <li><hr class="dropdown-divider"></li>

        <li class="nl-user-dropdown__footer">
            <form action="{{ $logout_url }}" method="POST" data-no-submit-guard>
                @if(config('adminlte.logout_method'))
                    {{ method_field(config('adminlte.logout_method')) }}
                @endif
                @csrf
                <button type="submit" class="nl-logout-button" data-logout>
                    <i class="fas fa-sign-out-alt fa-fw" aria-hidden="true"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </li>

    </ul>

</li>
