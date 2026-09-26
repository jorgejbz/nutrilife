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

                            <label>
                                <i class="far fa-user"></i>
                                Paciente
                                <span>*</span>
                            </label>

                            <select
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

                            <label>
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
                                    min="0.01"
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

                            <label>
                                <i class="far fa-calendar-alt"></i>
                                Fecha y hora
                                <span>*</span>
                            </label>

                            <input
                                type="datetime-local"
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

                            <label>
                                <i class="fas fa-receipt"></i>
                                Concepto
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                name="concept"
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

                            <label>
                                <i class="far fa-credit-card"></i>
                                Método de pago
                                <span>*</span>
                            </label>

                            <select
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

                            <label>
                                <i class="fas fa-circle-check"></i>
                                Estado
                                <span>*</span>
                            </label>

                            <select
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



                    {{-- REFERENCIA --}}
                    <div class="col-md-12">

                        <div class="nutri-form-group">

                            <label>
                                <i class="fas fa-hashtag"></i>
                                Referencia
                            </label>

                            <input
                                type="text"
                                name="reference"
                                value="{{ old('reference') }}"
                                class="nutri-input"
                                placeholder="Ej. SPEI, folio, número de operación..."
                            >

                            <small class="input-help">
                                Opcional. Útil para transferencias o pagos con tarjeta.
                            </small>

                        </div>

                    </div>



                    {{-- NOTAS --}}
                    <div class="col-md-12">

                        <div class="nutri-form-group mb-0">

                            <label>
                                <i class="far fa-note-sticky"></i>
                                Notas
                            </label>

                            <textarea
                                name="notes"
                                rows="4"
                                class="nutri-input nutri-textarea"
                                placeholder="Agrega alguna observación relacionada con el pago..."
                            >{{ old('notes') }}</textarea>

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


@stop
