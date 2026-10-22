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


    {{-- Éxito / error de la última acción (registrar, editar, completar) --}}
    @include('partials.flash')



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


        {{-- FILTROS: estado y búsqueda por paciente, concepto o referencia --}}
        <form method="get" action="{{ route('pagos.index') }}" class="nl-toolbar nl-p-0-4" role="search">
            <div class="nl-field nl-field--wide">
                <label for="q">Buscar</label>
                <div class="nl-input">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Referencia (p. ej. TRA-2026…), paciente o concepto">
                </div>
            </div>
            <div class="nl-field">
                <label for="estado">Estado</label>
                <div class="nl-input">
                    <i class="fas fa-toggle-on" aria-hidden="true"></i>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="Pendiente" @selected($status === 'Pendiente')>Pendientes</option>
                        <option value="Pagado" @selected($status === 'Pagado')>Completados (pagados)</option>
                        <option value="Cancelado" @selected($status === 'Cancelado')>Cancelados</option>
                    </select>
                </div>
            </div>
            <div class="nl-field">
                <label for="metodo">Método</label>
                <div class="nl-input">
                    <i class="fas fa-wallet" aria-hidden="true"></i>
                    <select id="metodo" name="metodo">
                        <option value="">Todos</option>
                        @foreach (\App\Http\Controllers\PaymentController::METHODS as $option)
                            <option value="{{ $option }}" @selected($method === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                @php $hasFilters = $status || $method || $search !== ''; @endphp
                <a href="{{ route('pagos.index') }}" class="nl-btn nl-btn--ghost{{ $hasFilters ? '' : ' is-disabled' }}" @unless ($hasFilters) aria-disabled="true" @endunless>
                    <i class="fas fa-eraser" aria-hidden="true"></i> Limpiar filtros
                </a>
            </div>
        </form>



        @if($payments->isEmpty())


            <div class="empty-payments">

                <i class="fas fa-wallet"></i>

                <h5>
                    {{ $status || $method || $search ? 'Sin resultados' : 'Sin pagos registrados' }}
                </h5>

                <p>
                    {{ $status || $method || $search ? 'No hay pagos con esos filtros.' : 'Los pagos de tus pacientes aparecerán aquí.' }}
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
                            <th>Referencia y método</th>
                            <th>Estado</th>
                            <th class="text-right">Monto</th>
                            <th class="text-right">Acciones</th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($payments as $payment)

                            <tr>

                                <td>

                                    {{ $payment->paid_at->format('d/m/Y') }}
                                    <small class="d-block text-muted">Registrado {{ $payment->created_at?->format('d/m/Y') }}</small>

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


                                <td class="payment-reference-cell">

                                    @include('partials.payment-reference', ['payment' => $payment, 'external' => true])

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


                                {{-- Acciones: el estado solo cambia con estas acciones
                                     (PaymentController@complete / @cancel), con confirmación --}}
                                <td class="text-right">

                                    <div class="nl-row-actions">

                                        @if($payment->isPending())
                                            <form method="POST" action="{{ route('pagos.complete', $payment) }}"
                                                  data-confirm="¿Marcar como completado el pago de {{ $payment->patient->first_name }} {{ $payment->patient->last_name }} por ${{ number_format($payment->amount, 2) }}? La fecha de pago será hoy."
                                                  data-confirm-title="Marcar pago como completado"
                                                  data-confirm-accept="Marcar como completado"
                                                  data-confirm-icon="fa-circle-check">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="nl-btn nl-btn--ok nl-btn--sm">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    Marcar como completado
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('pagos.show', $payment) }}" class="nl-btn nl-btn--ghost nl-btn--sm" title="Ver detalle">
                                            <i class="far fa-eye" aria-hidden="true"></i>
                                            <span class="visually-hidden">Ver detalle</span>
                                        </a>

                                        <a href="{{ route('pagos.edit', $payment) }}" class="nl-btn nl-btn--ghost nl-btn--sm" title="Editar">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                            <span class="visually-hidden">Editar</span>
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


        @endif


        <div class="nl-p-0-4-6">
            @include('partials.pager', ['paginator' => $payments])
        </div>


    </div>


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


@stop
