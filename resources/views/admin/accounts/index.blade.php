@extends('adminlte::page')

{{-- =========================================================
     CUENTAS DE LA PLATAFORMA (/admin/cuentas)
     Búsqueda, filtros (tipo, plan, estado) y paginación.
     Datos: AdminAccountController@index.
     ========================================================= --}}

@section('title', 'Cuentas | Administración NutriLife')

@section('content_header')
    <div class="patients-header">
        <div>
            <span class="header-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE · ADMINISTRACIÓN GLOBAL
            </span>
            <h1>Cuentas</h1>
            <p>Todas las cuentas de NutriLife: nutriólogos, clínicas, subperfiles y administradores.</p>
        </div>
    </div>
@stop

@section('content')
<div class="nl-profile">

    @include('partials.flash')

    <section class="nl-profile-card">
        <form method="get" action="{{ route('admin.accounts.index') }}" class="nl-toolbar" role="search">
            <div class="nl-field nl-field--wide">
                <label for="q">Buscar</label>
                <div class="nl-input">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Nombre, clínica, correo o cédula">
                </div>
            </div>
            <div class="nl-field">
                <label for="tipo">Tipo de cuenta</label>
                <div class="nl-input">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                    <select id="tipo" name="tipo">
                        <option value="">Todos</option>
                        @foreach ($typeFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filters['tipo'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-field">
                <label for="plan">Plan</label>
                <div class="nl-input">
                    <i class="fas fa-crown" aria-hidden="true"></i>
                    <select id="plan" name="plan">
                        <option value="">Todos</option>
                        @foreach ($planFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filters['plan'] === $value)>{{ $label }}</option>
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
                        @foreach ($statusFilters as $value => $label)
                            <option value="{{ $value }}" @selected($filters['estado'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="nl-toolbar-actions">
                <button type="submit" class="nl-btn nl-btn--primary" data-loading-text="Filtrando…"><i class="fas fa-filter" aria-hidden="true"></i> Filtrar</button>
                <a href="{{ route('admin.accounts.index') }}" class="nl-btn nl-btn--ghost">Limpiar</a>
            </div>
        </form>

        @if ($accounts->isEmpty())
            <p class="nl-empty"><i class="fas fa-users-slash" aria-hidden="true"></i>No hay cuentas con esos filtros.</p>
        @else
            <div class="table-responsive">
                <table class="nl-table">
                    <thead>
                        <tr>
                            <th scope="col">Nombre / clínica</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Plan</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Registro</th>
                            <th scope="col" class="is-num">Pacientes</th>
                            <th scope="col" class="is-num">Nutriólogos</th>
                            <th scope="col">Último cobro</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            @php
                                $type = \App\Services\AdminAccounts::typeOf($account);
                                $accountType = \App\Models\Subscription::accountTypeFor($account);
                                $lastPayment = $account->latestSubscription?->payments->sortByDesc('paid_at')->first();
                            @endphp
                            <tr>
                                <td data-label="Nombre">
                                    <strong>{{ $account->name }}</strong>
                                    <small>{{ $account->email }}</small>
                                </td>
                                <td data-label="Tipo">
                                    <span class="nl-status nl-status--{{ $type['key'] }}">{{ $type['label'] }}</span>
                                    @if ($account->clinic)
                                        <small>de {{ $account->clinic->name }}</small>
                                    @endif
                                </td>
                                <td data-label="Plan">{{ $accountType['label'] ?? 'Sin suscripción' }}</td>
                                <td data-label="Estado">
                                    <span class="nl-status nl-status--{{ $account->account_status }}">{{ $account->accountStatusLabel() }}</span>
                                </td>
                                <td data-label="Registro">{{ $account->created_at?->format('d/m/Y') }}</td>
                                <td data-label="Pacientes" class="is-num">{{ $account->isAdmin() ? '—' : $account->patients_count }}</td>
                                <td data-label="Nutriólogos" class="is-num">{{ $account->clinic_members_count ?: '—' }}</td>
                                <td data-label="Último cobro">
                                    @if ($lastPayment)
                                        <span class="nl-status nl-status--{{ $lastPayment->status }}">{{ ucfirst($lastPayment->status) }}</span>
                                        <small>${{ number_format((float) $lastPayment->amount, 2) }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Acciones">
                                    <div class="nl-row-actions">
                                        <a href="{{ route('admin.accounts.show', $account) }}" class="nl-btn nl-btn--ghost nl-btn--sm">
                                            <i class="far fa-eye" aria-hidden="true"></i> Detalle
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('partials.pager', ['paginator' => $accounts])
    </section>

</div>
@stop

@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop
