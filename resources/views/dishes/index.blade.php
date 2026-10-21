@extends('adminlte::page')

@section('title', 'Catálogo de platillos | NutriLife')

{{-- =========================================================
     CATÁLOGO DE PLATILLOS (solo consulta)
     Controlador: App\Http\Controllers\DishCatalogController
     Reutiliza los componentes del Catálogo de alimentos (food-*)
     y agrega imagen, ingredientes y detalle por platillo (dish-*).
     El aporte es la suma de los ingredientes del platillo.
     ========================================================= --}}

@php
    // 1 decimal sin ceros sobrantes (12.0 → 12); con 0 decimales no se recorta.
    $fmt = function ($value, int $decimals = 1) {
        $text = number_format((float) $value, $decimals, '.', ',');

        return $decimals > 0 ? rtrim(rtrim($text, '0'), '.') : $text;
    };

    $hasFilters = ! $loadError && ($search !== '' || $origin || $dataFilter || $withoutAllergen || $sort !== 'nombre');
    $activeFilterCount = $loadError ? 0 : (int) ($origin !== null) + (int) ($dataFilter !== null) + (int) ($withoutAllergen !== null) + (int) ($sort !== 'nombre');
@endphp


@section('content_header')
    <div class="consultations-header food-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf me-1" aria-hidden="true"></i>
                NUTRICIÓN
            </span>
            <h1>Catálogo de platillos</h1>
            <p>
                Consulta recetas completas con sus ingredientes y aporte nutricional para insertarlas en los planes.
                @unless($loadError)
                    <span class="food-count-pill">
                        <i class="fas fa-bowl-food" aria-hidden="true"></i>
                        {{ number_format($totalDishes) }} {{ $totalDishes === 1 ? 'platillo' : 'platillos' }}
                        @if($ownDishes > 0)
                            · {{ $ownDishes }} {{ $ownDishes === 1 ? 'propio' : 'propios' }}
                        @endif
                    </span>
                @endunless
            </p>
        </div>

        <a href="{{ route('diet-plans.index') }}" class="btn-consultation-primary">
            <i class="fas fa-utensils" aria-hidden="true"></i>
            Planes alimenticios
        </a>
    </div>
@stop


@section('content')
<div class="consultations-page diet-page food-page dish-page dp">

    @if($loadError)

        <section class="consultations-card food-state food-state--error" role="alert">
            <span class="food-state-icon" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></span>
            <h2>No se pudo cargar el catálogo</h2>
            <p>Hubo un problema al consultar la base de datos. Tus datos no se modificaron. Intenta de nuevo en un momento.</p>
            <a href="{{ request()->fullUrl() }}" class="diet-btn diet-btn--soft">
                <i class="fas fa-redo" aria-hidden="true"></i>
                Reintentar
            </a>
        </section>

    @elseif($totalDishes === 0)

        <section class="consultations-card food-state">
            <span class="food-state-icon" aria-hidden="true"><i class="fas fa-bowl-food"></i></span>
            <h2>El catálogo aún no tiene platillos</h2>
            <p>Cuando se carguen platillos podrás consultarlos aquí e insertarlos en las comidas de tus planes.</p>
            @if(app()->isLocal())
                <p class="food-state-hint">
                    Entorno local: <code>php artisan db:seed --class=DietPlanDishSeeder</code>
                    (no borra ni duplica datos).
                </p>
            @endif
        </section>

    @else

        {{-- BÚSQUEDA Y FILTROS (GET) --}}
        <section class="consultations-card food-toolbar" aria-label="Buscar y filtrar platillos">
            <form method="GET" action="{{ route('dishes.index') }}" id="dishFilters" class="food-filters" role="search">

                <div class="food-search-row">
                    <div class="diet-search food-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <label for="dishQuery" class="visually-hidden">Buscar platillo o ingrediente</label>
                        <input type="search"
                               name="q"
                               id="dishQuery"
                               value="{{ $search }}"
                               maxlength="100"
                               placeholder="Buscar platillo o ingrediente, p. ej. salmón"
                               autocomplete="off">
                    </div>

                    <button type="submit" class="diet-btn diet-btn--primary food-search-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>Buscar</span>
                    </button>

                    <button type="button"
                            class="diet-btn diet-btn--ghost food-filters-toggle"
                            aria-expanded="false"
                            aria-controls="dishFilterPanel"
                            hidden>
                        <i class="fas fa-sliders-h" aria-hidden="true"></i>
                        Filtros
                        @if($activeFilterCount > 0)
                            <span class="food-badge" aria-label="{{ $activeFilterCount }} activos">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                </div>

                <div class="food-filter-panel" id="dishFilterPanel">
                    <div class="food-field">
                        <label for="dishOrigin">Origen</label>
                        <div class="diet-filter-select">
                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                            <select name="origen" id="dishOrigin" data-auto-submit>
                                <option value="">Todos los platillos</option>
                                @foreach($origins as $value => $label)
                                    <option value="{{ $value }}" @selected($origin === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="food-field">
                        <label for="dishData">Datos nutricionales</label>
                        <div class="diet-filter-select">
                            <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                            <select name="datos" id="dishData" data-auto-submit>
                                <option value="">Todos</option>
                                @foreach($dataFilters as $value => $label)
                                    <option value="{{ $value }}" @selected($dataFilter === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($allergens->isNotEmpty())
                        <div class="food-field">
                            <label for="dishAllergen">Excluir alérgeno</label>
                            <div class="diet-filter-select">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <select name="sin" id="dishAllergen" data-auto-submit>
                                    <option value="">Mostrar todos</option>
                                    @foreach($allergens as $allergen)
                                        <option value="{{ $allergen->id }}" @selected($withoutAllergen === $allergen->id)>
                                            Sin {{ \Illuminate\Support\Str::lower($allergen->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="food-field">
                        <label for="dishSort">Ordenar por</label>
                        <div class="diet-filter-select">
                            <i class="fas fa-sort-amount-down" aria-hidden="true"></i>
                            <select name="orden" id="dishSort" data-auto-submit>
                                @foreach($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            @if($hasFilters)
                <div class="food-active-filters" aria-label="Filtros activos">
                    <span class="food-active-label">Filtros activos:</span>

                    @if($search !== '')
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['q', 'page']) }}">
                            Búsqueda: «{{ $search }}»
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="visually-hidden">(quitar)</span>
                        </a>
                    @endif
                    @if($origin)
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['origen', 'page']) }}">
                            {{ $origins[$origin] }}
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="visually-hidden">(quitar)</span>
                        </a>
                    @endif
                    @if($dataFilter)
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['datos', 'page']) }}">
                            {{ $dataFilters[$dataFilter] }}
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="visually-hidden">(quitar)</span>
                        </a>
                    @endif
                    @if($withoutAllergen)
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['sin', 'page']) }}">
                            Sin {{ \Illuminate\Support\Str::lower($allergens->firstWhere('id', $withoutAllergen)?->name) }}
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="visually-hidden">(quitar)</span>
                        </a>
                    @endif
                    @if($sort !== 'nombre')
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['orden', 'page']) }}">
                            Orden: {{ $sorts[$sort] }}
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="visually-hidden">(quitar)</span>
                        </a>
                    @endif

                    <a class="food-clear" href="{{ route('dishes.index') }}">
                        <i class="fas fa-eraser" aria-hidden="true"></i>
                        Limpiar filtros
                    </a>
                </div>
            @endif
        </section>

        {{-- RESULTADOS --}}
        <section class="food-results" id="dishResults" aria-labelledby="dishResultsTitle">
            <div class="food-results-head">
                <h2 id="dishResultsTitle" class="food-results-title">
                    @if($dishes->total() === 0)
                        Sin resultados
                    @elseif($hasFilters)
                        {{ $dishes->total() }} {{ $dishes->total() === 1 ? 'platillo coincide' : 'platillos coinciden' }}
                    @else
                        Todos los platillos
                    @endif
                </h2>
                @if($dishes->total() > 0)
                    <p class="food-results-range" aria-live="polite">
                        Mostrando {{ $dishes->firstItem() }}–{{ $dishes->lastItem() }} de {{ $dishes->total() }}
                        · aporte total del platillo
                    </p>
                @endif
            </div>

            <div class="food-loading" role="status" aria-live="polite" hidden>
                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                Cargando platillos…
            </div>

            @if($dishes->total() === 0)

                <div class="consultations-card food-state food-state--compact">
                    <span class="food-state-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                    <h2>Ningún platillo coincide</h2>
                    <p>
                        @if($search !== '')
                            No encontramos «{{ $search }}» con los filtros actuales. Prueba con el nombre de un ingrediente o una palabra más corta.
                        @else
                            Ningún platillo cumple con los filtros seleccionados.
                        @endif
                    </p>
                    <a href="{{ route('dishes.index') }}" class="diet-btn diet-btn--soft">
                        <i class="fas fa-eraser" aria-hidden="true"></i>
                        Limpiar filtros
                    </a>
                </div>

            @else

                <ul class="food-grid dish-grid" role="list">
                    @foreach($dishes as $row)
                        @php
                            $dish = $row['dish'];
                            $empty = $row['count'] === 0;
                        @endphp
                        <li class="food-card dish-card {{ $empty ? 'is-empty' : '' }}">
                            <div class="dish-card-media">
                                @if($dish->image_url)
                                    <img src="{{ $dish->imageSrc() }}" alt="{{ $dish->name }}" loading="lazy">
                                @else
                                    <i class="fas fa-bowl-food" aria-hidden="true"></i>
                                @endif
                                <span class="dish-origin {{ $dish->isSystem() ? '' : 'is-own' }}">
                                    {{ $dish->isSystem() ? 'Receta del sistema' : 'Mi platillo' }}
                                </span>
                            </div>

                            <div class="food-card-top">
                                <h3 class="food-name">{{ $dish->name }}</h3>
                                <p class="food-portion">
                                    <i class="fas fa-list-ul" aria-hidden="true"></i>
                                    @if($empty)
                                        Sin ingredientes registrados
                                    @else
                                        {{ $row['count'] }} {{ $row['count'] === 1 ? 'ingrediente' : 'ingredientes' }}
                                        · {{ $fmt($row['grams'], 0) }} g en total
                                    @endif
                                </p>
                            </div>

                            @unless($empty)
                                <div class="food-kcal">
                                    <strong>{{ $fmt($row['totals']['kcal'], 0) }}</strong>
                                    <span>kcal</span>
                                </div>

                                <dl class="food-macros">
                                    <div class="food-macro is-protein">
                                        <dt>Proteína</dt>
                                        <dd>{{ $fmt($row['totals']['protein_g']) }} g</dd>
                                    </div>
                                    <div class="food-macro is-carbs">
                                        <dt><span aria-hidden="true">Carbohid.</span><span class="visually-hidden">Carbohidratos</span></dt>
                                        <dd>{{ $fmt($row['totals']['carbs_g']) }} g</dd>
                                    </div>
                                    <div class="food-macro is-lipids">
                                        <dt>Grasas</dt>
                                        <dd>{{ $fmt($row['totals']['lipids_g']) }} g</dd>
                                    </div>
                                </dl>

                                <p class="dish-ingredients-line">{{ $row['ingredients']->pluck('name')->join(', ') }}</p>
                            @endunless

                            <div class="food-card-foot">
                                @if($empty)
                                    <span class="dp-warning is-limited"><i class="fas fa-ban" aria-hidden="true"></i> No se puede insertar en un plan</span>
                                @elseif($row['missing'] > 0)
                                    <span class="dp-warning is-limited">
                                        <i class="fas fa-question-circle" aria-hidden="true"></i>
                                        {{ $row['missing'] }} sin datos nutricionales
                                    </span>
                                @elseif($row['allergens']->isNotEmpty())
                                    <span class="food-allergen-note">
                                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                        Contiene: {{ $row['allergens']->pluck('name')->join(', ') }}
                                    </span>
                                @else
                                    <span></span>
                                @endif

                                <button type="button"
                                        class="diet-btn diet-btn--ghost food-detail-btn"
                                        data-dish-detail="{{ $dish->id }}"
                                        aria-haspopup="dialog"
                                        aria-controls="dishDetailDialog">
                                    Ver detalle
                                    <span class="visually-hidden">de {{ $dish->name }}</span>
                                </button>
                            </div>

                            {{-- Contenido del detalle (lo copia dishes-index.js al diálogo) --}}
                            <template id="dish-detail-{{ $dish->id }}">
                                <header class="diet-dialog-head dish-detail-head">
                                    @if($dish->image_url)
                                        <img src="{{ $dish->imageSrc() }}" alt="" class="dish-detail-img">
                                    @endif
                                    <div>
                                        <span class="food-group-tag"><i class="fas fa-bowl-food" aria-hidden="true"></i> {{ $dish->isSystem() ? 'Receta del sistema' : 'Mi platillo' }}</span>
                                        <h2 id="dishDetailName">{{ $dish->name }}</h2>
                                        <p class="food-portion">
                                            @if($empty)
                                                Sin ingredientes registrados
                                            @else
                                                {{ $row['count'] }} {{ $row['count'] === 1 ? 'ingrediente' : 'ingredientes' }} · {{ $fmt($row['grams'], 0) }} g
                                                · {{ $fmt($row['totals']['kcal'], 0) }} kcal
                                            @endif
                                        </p>
                                    </div>
                                    <button type="button" class="diet-icon-btn" data-close-dialog aria-label="Cerrar detalle">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </button>
                                </header>

                                <div class="diet-dialog-body food-detail-body">
                                    @if($empty)
                                        <p class="diet-muted">
                                            Este platillo aún no tiene ingredientes, así que no aporta datos nutricionales
                                            y no se puede insertar en un plan.
                                        </p>
                                    @else
                                        <div class="dish-detail-scroll">
                                            <table class="food-detail-table dish-detail-table">
                                                <caption class="visually-hidden">Ingredientes y aporte</caption>
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Ingrediente</th>
                                                        <th scope="col">Cantidad</th>
                                                        <th scope="col">kcal</th>
                                                        <th scope="col">P</th>
                                                        <th scope="col">C</th>
                                                        <th scope="col">G</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($row['ingredients'] as $ingredient)
                                                        <tr>
                                                            <th scope="row">
                                                                {{ $ingredient['name'] }}
                                                                <small>
                                                                    {{ $ingredient['custom'] ? 'Personalizado' : ($ingredient['group'] ?? 'Catálogo') }}
                                                                    @unless($ingredient['complete'])
                                                                        · sin datos
                                                                    @endunless
                                                                </small>
                                                            </th>
                                                            <td>{{ $fmt($ingredient['grams']) }} g</td>
                                                            @if($ingredient['complete'])
                                                                <td>{{ $fmt($ingredient['nutrients']['kcal'], 0) }}</td>
                                                                <td>{{ $fmt($ingredient['nutrients']['protein_g']) }}</td>
                                                                <td>{{ $fmt($ingredient['nutrients']['carbs_g']) }}</td>
                                                                <td>{{ $fmt($ingredient['nutrients']['lipids_g']) }}</td>
                                                            @else
                                                                <td class="is-empty" colspan="4">Sin dato</td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th scope="row">Total</th>
                                                        <td>{{ $fmt($row['grams'], 0) }} g</td>
                                                        <td>{{ $fmt($row['totals']['kcal'], 0) }}</td>
                                                        <td>{{ $fmt($row['totals']['protein_g']) }}</td>
                                                        <td>{{ $fmt($row['totals']['carbs_g']) }}</td>
                                                        <td>{{ $fmt($row['totals']['lipids_g']) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <dl class="food-detail-facts">
                                            <div>
                                                <dt>Alérgenos (ingredientes del catálogo)</dt>
                                                <dd>{{ $row['allergens']->isNotEmpty() ? $row['allergens']->pluck('name')->join(', ') : 'Ninguno registrado' }}</dd>
                                            </div>
                                        </dl>

                                        @if($dish->image_credit)
                                            <p class="dish-photo-credit">
                                                <i class="fas fa-camera" aria-hidden="true"></i>
                                                Foto: {{ $dish->image_credit }}
                                            </p>
                                        @endif

                                        <p class="diet-muted food-detail-note">
                                            P = proteína, C = carbohidratos, G = grasas (g).
                                            @if($row['missing'] > 0)
                                                Los ingredientes sin datos cuentan 0 en el total.
                                            @endif
                                            Los personalizados no se revisan contra alergias del paciente.
                                        </p>
                                    @endif
                                </div>

                                <footer class="diet-dialog-foot diet-dialog-foot--simple">
                                    <div class="diet-dialog-actions">
                                        <a href="{{ route('diet-plans.index') }}" class="diet-btn diet-btn--soft">
                                            <i class="fas fa-utensils" aria-hidden="true"></i>
                                            Ir a planes
                                        </a>
                                        <button type="button" class="diet-btn diet-btn--primary" data-close-dialog>Cerrar</button>
                                    </div>
                                </footer>
                            </template>
                        </li>
                    @endforeach
                </ul>

                @if($dishes->hasPages())
                    <nav class="food-pager" aria-label="Páginas del catálogo">
                        @if($dishes->onFirstPage())
                            <span class="diet-btn diet-btn--ghost is-disabled" aria-disabled="true">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                            </span>
                        @else
                            <a class="diet-btn diet-btn--ghost" href="{{ $dishes->previousPageUrl() }}" rel="prev" data-dish-nav>
                                <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                            </a>
                        @endif

                        <span class="food-pager-status">Página {{ $dishes->currentPage() }} de {{ $dishes->lastPage() }}</span>

                        @if($dishes->hasMorePages())
                            <a class="diet-btn diet-btn--ghost" href="{{ $dishes->nextPageUrl() }}" rel="next" data-dish-nav>
                                Siguiente <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        @else
                            <span class="diet-btn diet-btn--ghost is-disabled" aria-disabled="true">
                                Siguiente <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </span>
                        @endif
                    </nav>
                @endif

            @endif
        </section>

        {{-- DETALLE DE UN PLATILLO --}}
        <dialog class="diet-dialog diet-dialog--sm food-detail dish-detail" id="dishDetailDialog" aria-labelledby="dishDetailName">
            <div class="diet-dialog-inner" data-dish-detail-body></div>
        </dialog>

    @endif
</div>
@stop


@section('css')
    @include('diet-plans.partials.styles')
@stop


@section('js')
    @vite(['resources/js/pages/dishes-index.js'])
@stop
