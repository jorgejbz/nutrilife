@extends('adminlte::page')

@section('title', 'Pagos | NutriLife')


{{-- =========================================================
     CONTENT HEADER
     ========================================================= --}}
@section('content_header')

<div class="payments-header">

    <div>

        <span class="payments-eyebrow">

            <i class="fas fa-leaf me-1"></i>
            NUTRILIFE

        </span>

        <h1>Pagos</h1>

        <p>
            Controla los ingresos y pagos de tus pacientes.
        </p>

    </div>


    <a href="{{ route('pagos.create') }}"
       class="btn-payment-primary">

        <i class="fas fa-plus"></i>

        Registrar pago

    </a>

</div>

@stop


{{-- =========================================================
     CONTENT
     ========================================================= --}}
@section('content')

<div class="payments-page">


    @if(session('success'))

        <div class="payment-alert">

            <i class="fas fa-check-circle"></i>

            {{ session('success') }}

        </div>

    @endif



    {{-- MÉTRICAS --}}

    <div class="payment-stats">


        <div class="payment-stat">

            <div class="stat-payment-icon">

                <i class="fas fa-wallet"></i>

            </div>

            <div>

                <span>Ingresos del mes</span>

                <h3>
                    ${{ number_format($monthIncome, 2) }}
                </h3>

            </div>

        </div>



        <div class="payment-stat">

            <div class="stat-payment-icon green">

                <i class="fas fa-check"></i>

            </div>

            <div>

                <span>Pagos recibidos</span>

                <h3>
                    {{ $paidPayments }}
                </h3>

            </div>

        </div>



        <div class="payment-stat">

            <div class="stat-payment-icon yellow">

                <i class="far fa-clock"></i>

            </div>

            <div>

                <span>Pendientes</span>

                <h3>
                    {{ $pendingPayments }}
                </h3>

            </div>

        </div>



        <div class="payment-stat">

            <div class="stat-payment-icon">

                <i class="fas fa-coins"></i>

            </div>

            <div>

                <span>Ingreso histórico</span>

                <h3>
                    ${{ number_format($totalIncome, 2) }}
                </h3>

            </div>

        </div>


    </div>



    {{-- LISTADO --}}

    <div class="payments-card">


        <div class="payments-card-header">

            <div>

                <span>HISTORIAL</span>

                <h4>Movimientos recientes</h4>

            </div>


            <i class="fas fa-receipt"></i>

        </div>



        @if($payments->isEmpty())


            <div class="empty-payments">

                <i class="fas fa-wallet"></i>

                <h5>
                    Sin pagos registrados
                </h5>

                <p>
                    Los pagos de tus pacientes aparecerán aquí.
                </p>

                <a href="{{ route('pagos.create') }}">
                    Registrar primer pago
                </a>

            </div>


        @else


            <div class="table-responsive">

                <table class="table payment-table">

                    <thead>

                        <tr>

                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th class="text-right">Monto</th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($payments as $payment)

                            <tr>

                                <td>

                                    {{ $payment->paid_at->format('d/m/Y') }}

                                </td>


                                <td>

                                    <strong>

                                        {{ $payment->patient->first_name }}
                                        {{ $payment->patient->last_name }}

                                    </strong>

                                </td>


                                <td>

                                    {{ $payment->concept }}

                                </td>


                                <td>

                                    {{ $payment->payment_method }}

                                </td>


                                <td>

                                    @if($payment->status === 'Pagado')

                                        <span class="payment-status paid">
                                            Pagado
                                        </span>

                                    @elseif($payment->status === 'Pendiente')

                                        <span class="payment-status pending">
                                            Pendiente
                                        </span>

                                    @else

                                        <span class="payment-status cancelled">
                                            Cancelado
                                        </span>

                                    @endif

                                </td>


                                <td class="text-right payment-amount">

                                    ${{ number_format($payment->amount, 2) }}

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


        @endif


    </div>


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
