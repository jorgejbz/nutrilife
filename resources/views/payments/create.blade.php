@extends('adminlte::page')

@section('title', 'Registrar pago | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

<div class="payments-header">

    <div>

        <span class="header-eyebrow">
            <i class="fas fa-leaf mr-1"></i>
            NUTRILIFE
        </span>

        <h1>
            Registrar pago
        </h1>

        <p>
            Registra un nuevo ingreso de tus pacientes.
        </p>

    </div>


    <a href="{{ route('pagos.index') }}"
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
          action="{{ route('pagos.store') }}">

        @csrf


        <div class="payment-glass-card">


            {{-- CABECERA --}}
            <div class="payment-card-header">

                <div class="payment-icon">

                    <i class="fas fa-wallet"></i>

                </div>


                <div>

                    <span class="payment-eyebrow">
                        NUEVO INGRESO
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
                                    value="{{ old('amount') }}"
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
                                value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}"
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
                                value="{{ old('concept') }}"
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
                                    {{ old('payment_method', 'Efectivo') === 'Efectivo' ? 'selected' : '' }}
                                >
                                    Efectivo
                                </option>

                                <option
                                    value="Transferencia"
                                    {{ old('payment_method') === 'Transferencia' ? 'selected' : '' }}
                                >
                                    Transferencia
                                </option>

                                <option
                                    value="Tarjeta"
                                    {{ old('payment_method') === 'Tarjeta' ? 'selected' : '' }}
                                >
                                    Tarjeta
                                </option>

                                <option
                                    value="Otro"
                                    {{ old('payment_method') === 'Otro' ? 'selected' : '' }}
                                >
                                    Otro
                                </option>

                            </select>

                        </div>

                    </div>



                    {{-- ESTADO --}}
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
                                        {{ old('appointment_id') == $appointment->id ? 'selected' : '' }}
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
                                Referencia
                            </label>

                            <input
                                type="text"
                                id="reference"
                                name="reference"
                                value="{{ old('reference') }}"
                                maxlength="255"
                                class="nutri-input @error('reference') input-error @enderror"
                                placeholder="Ej. SPEI, folio, número de operación..."
                            >

                            <small class="input-help">
                                Opcional. Útil para transferencias o pagos con tarjeta.
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
                            >{{ old('notes') }}</textarea>

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


                <a href="{{ route('pagos.index') }}"
                   class="btn-cancel-glass">

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="btn-payment-primary"
                >

                    <i class="fas fa-check"></i>

                    Registrar pago

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

    {{-- Estilos del panel: tokens + admin.css + admin-light/dark.css,
         anti-parpadeo del tema, tipografía e iconos --}}
    @include('partials.theme-styles', ['section' => 'admin'])

@stop


{{-- =========================================================
     SCRIPTS
     ========================================================= --}}
@section('js')

    @vite(['resources/js/pages/payments-create.js'])

@stop
