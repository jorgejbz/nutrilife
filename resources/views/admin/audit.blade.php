@extends('adminlte::page')

{{-- =========================================================
     BITÁCORA DE ACCIONES SENSIBLES (/admin/auditoria) — audit_logs
     ========================================================= --}}

@section('title', 'Bitácora | Administración NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · ADMINISTRACIÓN GLOBAL
            </span>
            <h1>Bitácora</h1>
            <p>Quién cambió qué y cuándo: cuentas, planes, subperfiles y pagos.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">
    <section class="nl-profile-card">
        <form method="get" action="{{ route('admin.audit.index') }}" class="nl-toolbar">
            <div class="nl-field nl-field--wide">
                <label for="accion">Acción</label>
                <div class="nl-input">
                    <i class="fas fa-list-check" aria-hidden="true"></i>
                    <select id="accion" name="accion">
                        <option value="">Todas</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" @selected($action === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
            </div>
        </form>

        @if ($logs->isEmpty())
            <p class="nl-empty"><i class="fas fa-clipboard-list" aria-hidden="true"></i>Todavía no hay acciones registradas.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Fecha</th>
                            <th scope="col">Acción</th>
                            <th scope="col">Realizó</th>
                            <th scope="col">Registro afectado</th>
                            <th scope="col">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td data-label="Fecha">{{ $log->created_at?->format('d/m/Y H:i') }}<small>{{ $log->ip_address }}</small></td>
                                <td data-label="Acción"><strong>{{ $log->actionLabel() }}</strong></td>
                                <td data-label="Realizó">{{ $log->user?->name ?? 'Sistema' }}<small>{{ $log->user?->email }}</small></td>
                                <td data-label="Registro">
                                    @if ($log->subject_type === 'User' && $log->subject_id)
                                        <a href="{{ route('admin.accounts.show', $log->subject_id) }}">Cuenta #{{ $log->subject_id }}</a>
                                    @else
                                        {{ $log->subject_type === 'Payment' ? 'Pago de paciente' : ($log->subject_type === 'SubscriptionPayment' ? 'Cobro de plataforma' : $log->subject_type) }} #{{ $log->subject_id }}
                                    @endif
                                </td>
                                <td data-label="Detalle">
                                    @foreach ((array) $log->details as $key => $value)
                                        <small>{{ $key }}: {{ is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'sí' : 'no') : $value) }}</small>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('partials.pager', ['paginator' => $logs])
    </section>
</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
