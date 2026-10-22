@extends('adminlte::page')

{{-- =========================================================
     COBROS DE LA PLATAFORMA (/admin/cobros) — subscription_payments
     Solo el administrador confirma cobros pendientes, y solo los
     manuales. AdminSubscriptionPaymentController.
     ========================================================= --}}

@section('title', 'Cobros de la plataforma | Administración NutriLife')

@php
    $dinero = fn ($amount) => '$'.number_format((float) $amount, 2);
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · ADMINISTRACIÓN GLOBAL
            </span>
            <h1>Cobros de la plataforma</h1>
            <p>Pagos de suscripción de nutriólogos y clínicas.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    <div class="nl-kpis">
        <div class="nl-kpi"><i class="fas fa-circle-check" aria-hidden="true"></i><strong>{{ $totals['paid'] }}</strong><span>Pagados</span></div>
        <div class="nl-kpi is-warn"><i class="fas fa-hourglass-half" aria-hidden="true"></i><strong>{{ $totals['pending'] }}</strong><span>Pendientes</span></div>
        <div class="nl-kpi is-info"><i class="fas fa-coins" aria-hidden="true"></i><strong>{{ $dinero($totals['revenue']) }}</strong><span>Total cobrado (MXN)</span></div>
        <div class="nl-kpi"><i class="fas fa-circle-info" aria-hidden="true"></i><span>Cobros "Simulado"</span><small>Contrataciones de prueba sin pasarela: ya nacen pagados.</small></div>
    </div>

    <section class="nl-profile-card">
        <form method="get" action="{{ route('admin.payments.index') }}" class="nl-toolbar" role="search">
            <div class="nl-field nl-field--wide">
                <label for="q">Buscar</label>
                <div class="nl-input">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Referencia (p. ej. TAR-2026…), usuario, correo o plan">
                </div>
            </div>
            <div class="nl-field">
                <label for="estado">Estado</label>
                <div class="nl-input">
                    <i class="fas fa-toggle-on" aria-hidden="true"></i>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="pendiente" @selected($filters['estado'] === 'pendiente')>Pendientes</option>
                        <option value="pagado" @selected($filters['estado'] === 'pagado')>Pagados</option>
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                <a href="{{ route('admin.payments.index') }}" class="nl-btn nl-btn--ghost">Limpiar</a>
            </div>
        </form>

        @if ($payments->isEmpty())
            <p class="nl-empty"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>No hay cobros con esos filtros.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Cuenta</th>
                            <th scope="col">Plan</th>
                            <th scope="col" class="is-num">Monto</th>
                            <th scope="col">Referencia y método</th>
                            <th scope="col">Estado</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            @php $owner = $payment->subscription?->user; @endphp
                            <tr>
                                <td data-label="Fecha">{{ $payment->paid_at?->format('d/m/Y H:i') }}<small>Registro {{ $payment->created_at?->format('d/m/Y') }}</small></td>
                                <td data-label="Cuenta">
                                    @if ($owner)
                                        <a href="{{ route('admin.accounts.show', $owner) }}"><strong>{{ $owner->name }}</strong></a>
                                        <small>{{ $owner->email }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Plan">{{ $payment->subscription?->plan?->name ?? '—' }}</td>
                                <td data-label="Monto" class="is-num">{{ $dinero($payment->amount) }}</td>
                                <td data-label="Referencia" class="payment-reference-cell">@include('partials.payment-reference', ['payment' => $payment, 'external' => true, 'externalLabel' => 'Código de origen'])</td>
                                <td data-label="Estado">
                                    <span class="nl-status nl-status--{{ $payment->status }}">{{ ucfirst($payment->status) }}</span>
                                    @if ($payment->statusChangedBy)
                                        <small>Confirmó {{ $payment->statusChangedBy->name }} · {{ $payment->status_changed_at?->format('d/m/Y H:i') }}</small>
                                    @endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="nl-row-actions">
                                        @if ($payment->status === 'pendiente' && in_array($payment->payment_method, $manualMethods, true))
                                            <form method="post" action="{{ route('admin.payments.complete', $payment) }}"
                                                  data-confirm="¿Confirmar que se recibió el cobro {{ $payment->internal_reference }} por {{ $dinero($payment->amount) }}? Quedará registrado a tu nombre."
                                                  data-confirm-title="Marcar cobro como pagado" data-confirm-accept="Marcar como pagado" data-confirm-icon="fa-circle-check">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="nl-btn nl-btn--ok nl-btn--sm"><i class="fas fa-check" aria-hidden="true"></i> Marcar como pagado</button>
                                            </form>
                                        @elseif ($payment->status === 'pendiente')
                                            <small>Lo confirma la pasarela</small>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('partials.pager', ['paginator' => $payments])
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
