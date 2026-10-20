{{-- =========================================================
     DASHBOARD SUMMARY — próximas citas y pendientes (datos reales)
     ========================================================= --}}
<section class="dashboard-summary">
    <div class="dashboard-summary__header">
        <div>
            <span class="dashboard-summary__eyebrow">Agenda</span>
            <h2 class="dashboard-summary__title">Próximas citas</h2>
        </div>
        <i class="fas fa-clock"></i>
    </div>

    @if($upcomingAppointments->isEmpty())
        <p class="dashboard-upcoming-empty">No tienes citas pendientes ni confirmadas.</p>
    @else
        <ul class="dashboard-upcoming">
            @foreach($upcomingAppointments as $appointment)
                @php $at = \Illuminate\Support\Carbon::parse($appointment->appointment_at); @endphp
                <li>
                    <span class="dashboard-upcoming__date">
                        <strong>{{ $at->format('d') }}</strong>
                        {{ mb_strtoupper(rtrim($at->locale('es')->translatedFormat('M'), '.')) }}
                    </span>
                    <span class="dashboard-upcoming__info">
                        <strong>{{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}</strong>
                        <small>
                            {{ $at->isToday() ? 'Hoy' : ($at->isTomorrow() ? 'Mañana' : ucfirst($at->locale('es')->translatedFormat('l'))) }}
                            · {{ $at->format('H:i') }} · {{ $appointment->reason ?? 'Consulta' }}
                        </small>
                    </span>
                    <span class="dashboard-upcoming__status is-{{ \Illuminate\Support\Str::lower($appointment->status) }}">{{ $appointment->status }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="dashboard-summary__items">
        <a class="dashboard-summary__item" href="{{ route('pagos.index') }}">
            <span class="dashboard-summary__item-icon"><i class="fas fa-hourglass-half"></i></span>
            <div>
                <strong>{{ $pendingPaymentsCount }}</strong>
                <span>Pagos pendientes</span>
            </div>
        </a>
        <a class="dashboard-summary__item" href="{{ route('consultas.index') }}">
            <span class="dashboard-summary__item-icon"><i class="fas fa-notes-medical"></i></span>
            <div>
                <strong>{{ $monthConsultations }}</strong>
                <span>Consultas este mes</span>
            </div>
        </a>
        @if($activeDietPlans !== null)
            <a class="dashboard-summary__item" href="{{ route('diet-plans.index', ['estado' => 'activos']) }}">
                <span class="dashboard-summary__item-icon"><i class="fas fa-utensils"></i></span>
                <div>
                    <strong>{{ $activeDietPlans }}</strong>
                    <span>Planes alimenticios activos</span>
                </div>
            </a>
        @endif
    </div>
</section>
