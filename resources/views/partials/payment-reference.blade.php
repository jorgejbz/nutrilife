{{-- =========================================================
     REFERENCIA DE UN PAGO (Payment o SubscriptionPayment)
     Distintivo del método (icono + nombre, no solo color), la
     referencia interna de NutriLife y un botón para copiarla
     (resources/js/forms.js → [data-copy]).

     @include('partials.payment-reference', [
         'payment' => $payment,
         'external' => true,     // muestra también la referencia externa
         'method' => false,      // oculta el distintivo del método
         'externalLabel' => 'Código de origen',  // texto de la referencia externa
     ])
     ========================================================= --}}
@php
    $nlRefCode = $payment->internal_reference;
    // El color sigue al método ACTUAL; si un pago ya pagado se corrigió, el
    // prefijo de su referencia histórica puede ser otro (se conserva).
    $nlRefPrefix = \App\Services\PaymentReferenceService::prefixFor($payment->payment_method);
    $nlRefIcon = [
        'EFE' => 'fa-money-bill-wave',
        'TAR' => 'fa-credit-card',
        'TRA' => 'fa-building-columns',
        'SIM' => 'fa-flask',
        'PAS' => 'fa-globe',
        'OTR' => 'fa-circle-question',
    ][$nlRefPrefix];
    $nlRefMethod = $payment->payment_method ?: \App\Services\PaymentReferenceService::labelFor($nlRefPrefix);
@endphp
<span class="nl-ref nl-ref--{{ strtolower($nlRefPrefix) }}">
    @if ($method ?? true)
        <span class="nl-ref__method">
            <i class="fas {{ $nlRefIcon }}" aria-hidden="true"></i>
            {{ $nlRefMethod }}{{ $nlRefPrefix === 'OTR' && ! in_array($payment->payment_method, ['Otro'], true) ? ' · no especificado' : '' }}
        </span>
    @endif
    @if ($nlRefCode)
        <span class="nl-ref__line">
            <code class="nl-ref__code" translate="no">{{ $nlRefCode }}</code>
            <button type="button" class="nl-ref__copy" data-copy="{{ $nlRefCode }}"
                    aria-label="Copiar referencia {{ $nlRefCode }}" title="Copiar referencia">
                <i class="far fa-copy" aria-hidden="true"></i>
            </button>
        </span>
    @else
        <span class="nl-ref__code is-missing">Sin referencia</span>
    @endif
    @if (($external ?? false) && filled($payment->reference))
        <small class="nl-ref__external">{{ $nlRefPrefix === 'PAS' ? 'Id. de la pasarela' : ($externalLabel ?? 'Ref. externa') }}: <span translate="no">{{ $payment->reference }}</span></small>
    @endif
</span>
