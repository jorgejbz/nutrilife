@extends('adminlte::page')

{{-- Alta (PaymentController@create) y edición (@edit, con $payment).
     Al editar, el paciente queda fijo y el estado no se cambia aquí:
     tiene sus propias acciones (Marcar como completado / Cancelar). --}}
@php
    $payment = $payment ?? null;
    $editing = $payment !== null;
    $val = fn (string $field, $default = null) => old($field, $editing ? $payment->{$field} : $default);
@endphp

@section('title', ($editing ? 'Editar pago' : 'Registrar pago').' | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

<div class="payments-header">

    <div>

        <span class="header-eyebrow">
            <i class="fas fa-leaf me-1"></i>
            NUTRILIFE
        </span>

        <h1>
            {{ $editing ? 'Editar pago' : 'Registrar pago' }}
        </h1>

        <p>
            {{ $editing ? 'Corrige los datos del pago. El estado se cambia desde el listado o el detalle.' : 'Registra un nuevo ingreso de tus pacientes.' }}
        </p>

    </div>


    <a href="{{ $editing ? route('pagos.show', $payment) : route('pagos.index') }}"
       class="btn-back-glass">

        <i class="fas fa-arrow-left"></i>

        <span>
            Regresar
        </span>

    </a>

</div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

<div class="payment-page">

    {{-- LUCES AMBIENTALES --}}
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>


    <form method="POST"
          action="{{ $editing ? route('pagos.update', $payment) : route('pagos.store') }}">

        @csrf
        @if($editing)
            @method('PUT')
        @endif


        <div class="payment-glass-card">


            {{-- CABECERA --}}
            <div class="payment-card-header">

                <div class="payment-icon">

                    <i class="fas fa-wallet"></i>

                </div>


                <div>

                    <span class="payment-eyebrow">
                        {{ $editing ? 'EDICIÓN · ESTADO: '.mb_strtoupper($payment->status) : 'NUEVO INGRESO' }}
                    </span>

                    <h2>
                        Información del pago
                    </h2>

                    <p>
                        Captura los datos correspondientes al pago realizado.
                    </p>

                </div>

            </div>



            {{-- FORMULARIO --}}
            <div class="payment-form-body">

                <div class="row">


                    {{-- PACIENTE --}}
                    <div class="col-md-6">

                        <div class="nutri-form-group">

                            <label for="patient_id">
                                <i class="far fa-user"></i>
                                Paciente
                                <span>*</span>
                            </label>

                            @if($editing)
                            {{-- id="patient_id" (sin name): payments-create.js filtra las citas por este paciente --}}
                            <input type="hidden" id="patient_id" value="{{ $payment->patient_id }}">
                            <input type="text" class="nutri-input" readonly aria-label="Paciente"
                                   value="{{ $payment->patient?->first_name }} {{ $payment->patient?->last_name }}">
                            <small class="input-help">El paciente de un pago no se cambia.</small>
                            @else
                            <select
                                id="patient_id"
                                name="patient_id"
                                class="nutri-input @error('patient_id') input-error @enderror"
                                required
                            >

                                <option value="">
                                    Selecciona un paciente
                                </option>

                                @foreach($patients as $patient)

                                    <option
                                        value="{{ $patient->id }}"
                                        {{ old('patient_id') == $patient->id ? 'selected' : '' }}
                                    >

                                        {{ $patient->first_name }}
                                        {{ $patient->last_name }}

                                    </option>

                                @endforeach

                            </select>
                            @endif


                            @error('patient_id')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>



                    {{-- MONTO --}}
                    <div class="col-md-6">

                        <div class="nutri-form-group">

                            <label for="amount">
                                <i class="fas fa-dollar-sign"></i>
                                Monto
                                <span>*</span>
                            </label>


                            <div class="amount-wrapper">

                                <span class="amount-symbol">
                                    $
                                </span>

                                <input
                                    type="number"
                                    step="0.01"
                                    max="99999999.99"
                                    min="0.01"
                                    id="amount"
                                name="amount"
                                    value="{{ $val('amount') }}"
                                    class="nutri-input amount-input @error('amount') input-error @enderror"
                                    placeholder="600.00"
                                    required
                                >

                                <span class="amount-currency">
                                    MXN
                                </span>

                            </div>


                            @error('amount')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>



                    {{-- FECHA --}}
                    <div class="col-md-6">

                        <div class="nutri-form-group">

                            <label for="paid_at">
                                <i class="far fa-calendar-alt"></i>
                                Fecha y hora
                                <span>*</span>
                            </label>

                            <input
                                type="datetime-local"
                                id="paid_at"
                                name="paid_at"
                                value="{{ old('paid_at', $editing ? $payment->paid_at?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                                max="{{ now()->format('Y-m-d') }}T23:59"
                                class="nutri-input @error('paid_at') input-error @enderror"
                                required
                            >

                            @error('paid_at')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>



                    {{-- CONCEPTO --}}
                    <div class="col-md-6">

                        <div class="nutri-form-group">

                            <label for="concept">
                                <i class="fas fa-receipt"></i>
                                Concepto
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="concept"
                                name="concept"
                                maxlength="255"
                                value="{{ $val('concept') }}"
                                class="nutri-input @error('concept') input-error @enderror"
                                placeholder="Ej. Consulta nutricional"
                                required
                            >

                            @error('concept')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>



                    {{-- MÉTODO --}}
                    <div class="col-md-6">

                        <div class="nutri-form-group">

                            <label for="payment_method">
                                <i class="far fa-credit-card"></i>
                                Método de pago
                                <span>*</span>
                            </label>

                            <select
                                id="payment_method"
                                name="payment_method"
                                class="nutri-input @error('payment_method') input-error @enderror"
                                required
                            >

                                <option
                                    value="Efectivo"
                                    data-prefix="{{ \App\Services\PaymentReferenceService::prefixFor('Efectivo') }}"
                                    {{ $val('payment_method', 'Efectivo') === 'Efectivo' ? 'selected' : '' }}
                                >
                                    Efectivo
                                </option>

                                <option
                                    value="Transferencia"
                                    data-prefix="{{ \App\Services\PaymentReferenceService::prefixFor('Transferencia') }}"
                                    {{ $val('payment_method') === 'Transferencia' ? 'selected' : '' }}
                                >
                                    Transferencia
                                </option>

                                <option
                                    value="Tarjeta"
                                    data-prefix="{{ \App\Services\PaymentReferenceService::prefixFor('Tarjeta') }}"
                                    {{ $val('payment_method') === 'Tarjeta' ? 'selected' : '' }}
                                >
                                    Tarjeta
                                </option>

                                <option
                                    value="Otro"
                                    data-prefix="{{ \App\Services\PaymentReferenceService::prefixFor('Otro') }}"
                                    {{ $val('payment_method') === 'Otro' ? 'selected' : '' }}
                                >
                                    Otro
                                </option>

                            </select>

                            {{-- Solo informativo: la referencia definitiva la genera
                                 el servidor al guardar (PaymentReferenceService). --}}
                            @if ($editing)
                                <div class="payment-ref-preview" id="paymentRefPreview"
                                     data-current-prefix="{{ $payment->referencePrefix() }}"
                                     data-pending="{{ $payment->isPending() ? '1' : '0' }}">
                                    <span>Referencia NutriLife</span>
                                    @include('partials.payment-reference', ['payment' => $payment, 'method' => false])
                                    <small id="paymentRefPreviewNote">
                                        {{ $payment->isPending()
                                            ? 'Si cambias a un método con otro prefijo, al guardar se genera una referencia nueva; la anterior queda en el historial.'
                                            : 'El pago ya no está pendiente: su referencia se conserva aunque corrijas el método.' }}
                                    </small>
                                </div>
                            @else
                                <div class="payment-ref-preview" id="paymentRefPreview" aria-live="polite">
                                    <span>Referencia NutriLife (automática)</span>
                                    <code id="paymentRefPreviewCode" translate="no">{{ \App\Services\PaymentReferenceService::prefixFor($val('payment_method', 'Efectivo')) }}-{{ now()->format('Ymd') }}-······</code>
                                    <small>Se genera al guardar según el método. El número final lo asigna el sistema.</small>
                                </div>
                            @endif

                        </div>

                    </div>



                    {{-- ESTADO --}}
                    @unless($editing)
                    <div class="col-md-6">

                        <div class="nutri-form-group">

                            <label for="status">
                                <i class="fas fa-circle-check"></i>
                                Estado
                                <span>*</span>
                            </label>

                            <select
                                id="status"
                                name="status"
                                class="nutri-input @error('status') input-error @enderror"
                                required
                            >

                                <option
                                    value="Pagado"
                                    {{ old('status', 'Pagado') === 'Pagado' ? 'selected' : '' }}
                                >
                                    Pagado
                                </option>

                                <option
                                    value="Pendiente"
                                    {{ old('status') === 'Pendiente' ? 'selected' : '' }}
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="Cancelado"
                                    {{ old('status') === 'Cancelado' ? 'selected' : '' }}
                                >
                                    Cancelado
                                </option>

                            </select>

                        </div>

                    </div>
                    @endunless



                    {{-- CITA RELACIONADA (payments.appointment_id, opcional) --}}
                    <div class="col-md-12">

                        <div class="nutri-form-group">

                            <label for="appointment_id">
                                <i class="far fa-calendar-check"></i>
                                Cita relacionada
                            </label>

                            {{-- data-patient-id: payments-create.js muestra solo
                                 las citas del paciente elegido --}}
                            <select
                                id="appointment_id"
                                name="appointment_id"
                                class="nutri-input @error('appointment_id') input-error @enderror"
                            >

                                <option value="">
                                    Ninguna
                                </option>

                                @foreach($appointments as $appointment)

                                    <option
                                        value="{{ $appointment->id }}"
                                        data-patient-id="{{ $appointment->patient_id }}"
                                        {{ $val('appointment_id') == $appointment->id ? 'selected' : '' }}
                                    >
                                        {{ $appointment->appointment_at->format('d/m/Y H:i') }}
                                        — {{ $appointment->reason ?: 'Sin motivo' }}
                                        ({{ $appointment->status }})
                                    </option>

                                @endforeach

                            </select>

                            <small class="input-help">
                                Opcional. Vincula el cobro con una cita del paciente.
                            </small>

                            @error('appointment_id')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>



                    {{-- REFERENCIA --}}
                    <div class="col-md-12">

                        <div class="nutri-form-group">

                            <label for="reference">
                                <i class="fas fa-hashtag"></i>
                                Referencia externa
                            </label>

                            <input
                                type="text"
                                id="reference"
                                name="reference"
                                value="{{ $val('reference') }}"
                                maxlength="255"
                                class="nutri-input @error('reference') input-error @enderror"
                                placeholder="Ej. clave de rastreo SPEI, folio de la terminal..."
                            >

                            <small class="input-help">
                                Opcional. La que te da el banco o la terminal; no sustituye a la referencia de NutriLife.
                            </small>

                            @error('reference')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>



                    {{-- NOTAS --}}
                    <div class="col-md-12">

                        <div class="nutri-form-group mb-0">

                            <label for="notes">
                                <i class="far fa-note-sticky"></i>
                                Notas
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                maxlength="2000"
                                class="nutri-input nutri-textarea @error('notes') input-error @enderror"
                                placeholder="Agrega alguna observación relacionada con el pago..."
                            >{{ $val('notes') }}</textarea>

                            @error('notes')
                                <span class="error-message">
                                    {{ $message }}
                                </span>
                            @enderror

                        </div>

                    </div>


                </div>

            </div>



            {{-- FOOTER --}}
            <div class="payment-footer">


                <a href="{{ $editing ? route('pagos.show', $payment) : route('pagos.index') }}"
                   class="btn-cancel-glass">

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="btn-payment-primary"
                >

                    <i class="fas fa-check"></i>

                    {{ $editing ? 'Guardar cambios' : 'Registrar pago' }}

                </button>


            </div>

        </div>

    </form>

</div>

@stop


{{-- =========================================================
     ESTILOS
     ========================================================= --}}
@section('css')

    {{-- Estilos del panel: tokens + public/css/admin/* + temas + responsive (config/stylesheets.php),
         anti-parpadeo del tema, tipografía e iconos --}}
    @include('partials.theme-styles', ['section' => 'admin'])

@stop


{{-- =========================================================
     SCRIPTS
     ========================================================= --}}
@section('js')

    @vite(['resources/js/pages/payments-create.js'])

@stop
