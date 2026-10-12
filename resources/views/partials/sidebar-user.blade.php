{{-- =========================================================
     TARJETA DEL NUTRIÓLOGO EN EL SIDEBAR
     Nombre y tipo de cuenta (plan / prueba / administrador).
     Lleva a "Mi perfil". Se oculta con el sidebar colapsado.
     ========================================================= --}}
@auth
    @php
        $nlSideUser = auth()->user();
        $nlSideAccount = \App\Models\Subscription::accountTypeFor($nlSideUser);
        $nlSideInitials = collect(preg_split('/\s+/', trim($nlSideUser->name)))
            ->reject(fn ($part) => in_array(mb_strtolower(rtrim($part, '.')), ['dr', 'dra', 'lic', 'mtra', 'mtro'], true))
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <a href="{{ route('profile.edit') }}" class="nl-sidebar-user" title="Mi perfil">
        <span class="nl-sidebar-avatar" aria-hidden="true">{{ $nlSideInitials ?: 'N' }}</span>
        <span class="nl-sidebar-user-info">
            <strong>{{ $nlSideUser->name }}</strong>
            @if ($nlSideAccount)
                <small class="nl-sidebar-plan nl-sidebar-plan--{{ $nlSideAccount['type'] }}">
                    {{ $nlSideAccount['label'] }}
                </small>
            @endif
        </span>
    </a>
@endauth
