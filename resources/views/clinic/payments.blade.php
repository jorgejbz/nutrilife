@extends('adminlte::page')

{{-- =========================================================
     PAGOS DE LA CLÍNICA (/clinica/pagos)
     Solo lectura: referencia NutriLife, método y estado de los
     pagos de la cuenta principal y sus subperfiles.
     Datos: ClinicPaymentController@index.
     ========================================================= --}}

@section('title', 'Pagos de la clínica | NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · CLÍNICA
            </span>
            <h1>Pagos de la clínica</h1>
            <p>Pagos de pacientes registrados por los nutriólogos de {{ $clinic->name }}, con su referencia NutriLife.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    <section class="nl-profile-card">
        <form method="get" action="{{ route('clinic.payments.index') }}" class="nl-toolbar" role="search">
            <div class="nl-field nl-field--wide">
                <label for="q">Buscar</label>
                <div class="nl-input">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Referencia (p. ej. EFE-2026…), paciente o concepto">
                </div>
            </div>
            <div class="nl-field">
                <label for="nutriologo">Nutriólogo</label>
                <div class="nl-input">
                    <i class="fas fa-user-doctor" aria-hidden="true"></i>
                    <select id="nutriologo" name="nutriologo">
                        <option value="">Todos</option>
                        @foreach ($nutritionists as $nutritionist)
                            <option value="{{ $nutritionist->id }}" @selected($filters['nutriologo'] === $nutritionist->id)>
                                {{ $nutritionist->name }}{{ $nutritionist->id === $clinic->id ? ' (cuenta principal)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-field">
                <label for="metodo">Método</label>
                <div class="nl-input">
                    <i class="fas fa-wallet" aria-hidden="true"></i>
                    <select id="metodo" name="metodo">
                        <option value="">Todos</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method }}" @selected($filters['metodo'] === $method)>{{ $method }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-field">
                <label for="estado">Estado</label>
                <div class="nl-input">
                    <i class="fas fa-toggle-on" aria-hidden="true"></i>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($filters['estado'] === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                <a href="{{ route('clinic.payments.index') }}" class="nl-btn nl-btn--ghost{{ $hasFilters ? '' : ' is-disabled' }}" @unless ($hasFilters) aria-disabled="true" @endunless>
                    <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar filtros
                </a>
            </div>
        </form>

        <p class="nl-result-summary" role="status">
            {{ $payments->total() }} {{ $payments->total() === 1 ? 'pago' : 'pagos' }}
        </p>

        @if ($payments->isEmpty())
            <p class="nl-empty"><i class="fas fa-receipt" aria-hidden="true"></i>No hay pagos con esos filtros.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Referencia y método</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Paciente</th>
                            <th scope="col">Nutriólogo</th>
                            <th scope="col">Concepto</th>
                            <th scope="col" class="is-num">Monto</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td data-label="Referencia" class="payment-reference-cell">
                                    @include('partials.payment-reference', ['payment' => $payment, 'external' => true])
                                </td>
                                <td data-label="Fecha">
                                    <strong>{{ $payment->paid_at?->format('d/m/Y') }}</strong>
                                    <small>Registro {{ $payment->created_at?->format('d/m/Y') }}</small>
                                </td>
                                <td data-label="Paciente">{{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}</td>
                                <td data-label="Nutriólogo">
                                    {{ $payment->user?->name }}
                                    <small>{{ $payment->user_id === $clinic->id ? 'Cuenta principal' : 'Subperfil' }}</small>
                                </td>
                                <td data-label="Concepto">{{ $payment->concept ?: '—' }}</td>
                                <td data-label="Monto" class="is-num">${{ number_format((float) $payment->amount, 2) }}</td>
                                <td data-label="Estado">
                                    <span class="nl-status nl-status--{{ \Illuminate\Support\Str::slug($payment->status) }}">{{ $payment->status }}</span>
                                    @if ($payment->user_id === $clinic->id)
                                        <small><a href="{{ route('pagos.show', $payment) }}">Ver detalle</a></small>
                                    @endif
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
