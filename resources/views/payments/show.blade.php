@extends('adminlte::page')

{{-- =========================================================
     DETALLE DE UN PAGO (/pagos/{id})
     Fechas de registro y de pago, monto, concepto, quién cambió el
     estado y acciones para un pago pendiente. PaymentController@show.
     ========================================================= --}}

@section('title', 'Pago | NutriLife')

@php
    $fecha = fn ($date) => $date ? $date->format('d/m/Y H:i') : '—';
    $statusClass = \Illuminate\Support\Str::slug($payment->status);
@endphp

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · PAGOS
            </span>
            <h1>${{ number_format((float) $payment->amount, 2) }} · {{ $payment->concept ?: 'Pago' }}</h1>
            <p>{{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}</p>
        </div>
        <div class="nl-page-actions">
            <a href="{{ route('pagos.index') }}" class="nl-btn nl-btn--ghost">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Pagos
            </a>
            <a href="{{ route('pagos.edit', $payment) }}" class="nl-btn nl-btn--ghost">
                <i class="fas fa-pen" aria-hidden="true"></i> Editar
            </a>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    <section class="nl-profile-card">
        <header class="nl-section-head">
            <div>
                <span class="nl-eyebrow">Detalle del pago</span>
                <h2>{{ $payment->concept ?: 'Pago' }}</h2>
            </div>
            <span class="nl-status nl-status--{{ $statusClass }}">{{ $payment->status }}</span>
        </header>

        <dl class="nl-facts nl-facts--grid">
            <div><dt>Monto</dt><dd>${{ number_format((float) $payment->amount, 2) }} <small>MXN</small></dd></div>
            <div class="nl-facts__wide">
                <dt>Referencia NutriLife</dt>
                <dd>@include('partials.payment-reference', ['payment' => $payment])</dd>
            </div>
            <div><dt>Fecha de pago</dt><dd>{{ $fecha($payment->paid_at) }}</dd></div>
            <div><dt>Fecha de registro</dt><dd>{{ $fecha($payment->created_at) }}</dd></div>
            <div><dt>Paciente</dt><dd>{{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}</dd></div>
            <div><dt>Cita relacionada</dt><dd>{{ $payment->appointment ? $payment->appointment->appointment_at->format('d/m/Y H:i') : 'Ninguna' }}</dd></div>
            <div>
                <dt>Referencia externa</dt>
                <dd>
                    @if (filled($payment->reference))
                        <span translate="no">{{ $payment->reference }}</span>
                    @else
                        <small>Sin referencia del banco o terminal</small>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Último cambio de estado</dt>
                <dd>
                    @if ($payment->status_changed_at)
                        {{ $fecha($payment->status_changed_at) }}
                        <small>· {{ $payment->statusChangedBy?->name ?? 'Usuario eliminado' }}</small>
                    @else
                        <small>Sin cambios desde el registro</small>
                    @endif
                </dd>
            </div>
        </dl>

        @if ($payment->notes)
            <p class="nl-muted nl-mt-16"><strong>Notas:</strong> {{ $payment->notes }}</p>
        @endif

        @if ($payment->isPending())
            <div class="nl-alert nl-alert--info nl-mt-20">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                <div>
                    Pago manual pendiente. Cuando recibas el dinero, márcalo como completado: se guardará la fecha
                    de pago y quién lo confirmó. Si el paciente ya no pagará este cargo, cancélalo.
                </div>
            </div>

            <div class="nl-inline-form nl-mt-16">
                <form method="post" action="{{ route('pagos.complete', $payment) }}" class="nl-inline-form"
                      data-confirm="¿Marcar este pago como completado? Se registrará como recibido en la fecha indicada."
                      data-confirm-title="Marcar pago como completado" data-confirm-accept="Marcar como completado" data-confirm-icon="fa-circle-check">
                    @csrf
                    @method('patch')
                    <div class="nl-field">
                        <label for="paid_at">Fecha en que se recibió</label>
                        <div class="nl-input @error('paid_at') has-error @enderror">
                            <i class="far fa-calendar" aria-hidden="true"></i>
                            <input id="paid_at" name="paid_at" type="datetime-local" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->endOfDay()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                    <button type="submit" class="nl-btn nl-btn--ok">
                        <i class="fas fa-check" aria-hidden="true"></i> Marcar como completado
                    </button>
                </form>

                <form method="post" action="{{ route('pagos.cancel', $payment) }}"
                      data-confirm="¿Cancelar este pago pendiente? Ya no contará como ingreso pendiente."
                      data-confirm-title="Cancelar pago" data-confirm-accept="Cancelar pago" data-confirm-variant="danger" data-confirm-icon="fa-ban">
                    @csrf
                    @method('patch')
                    <button type="submit" class="nl-btn nl-btn--danger-soft">
                        <i class="fas fa-ban" aria-hidden="true"></i> Cancelar pago
                    </button>
                </form>
            </div>
        @endif
    </section>

    @if ($history->isNotEmpty())
        <section class="nl-profile-card">
            <header class="nl-section-head">
                <div>
                    <span class="nl-eyebrow">Bitácora</span>
                    <h2>Historial del pago</h2>
                </div>
            </header>
            <ul class="nl-list-plain">
                @foreach ($history as $log)
                    <li>
                        <div>
                            <strong>{{ $log->actionLabel() }}</strong>
                            <small>
                                {{ $log->user?->name ?? 'Sistema' }}
                                @if (! empty($log->details['from'])) · {{ $log->details['from'] }} → {{ $log->details['to'] }} @endif
                                @if (! empty($log->details['fields'])) · {{ implode(', ', $log->details['fields']) }} @endif
                            </small>
                        </div>
                        <small>{{ $log->created_at?->format('d/m/Y H:i') }}</small>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
