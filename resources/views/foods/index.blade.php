@extends('adminlte::page')

@section('title', 'Catálogo de alimentos | NutriLife')

{{-- =========================================================
     CATÁLOGO DE ALIMENTOS (solo consulta)
     Controlador: App\Http\Controllers\FoodCatalogController
     Los nutrientes de `foods` son POR PORCIÓN (portion_grams).
     ========================================================= --}}

@php
    // 1 decimal sin ceros sobrantes (12.0 → 12, 2.5 → 2.5); con 0 decimales no se recorta nada.
    $fmt = function ($value, int $decimals = 1) {
        $text = number_format((float) $value, $decimals, '.', ',');

        return $decimals > 0 ? rtrim(rtrim($text, '0'), '.') : $text;
    };

    // Icono por grupo: solo apoyo visual; el nombre del grupo siempre se escribe.
    $groupIcon = function (?string $name) {
        $name = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $name));

        return match (true) {
            str_contains($name, 'fruta') => 'fa-apple-alt',
            str_contains($name, 'verdura') => 'fa-carrot',
            str_contains($name, 'cereal') => 'fa-bread-slice',
            str_contains($name, 'leguminosa') => 'fa-seedling',
            str_contains($name, 'animal') => 'fa-drumstick-bite',
            str_contains($name, 'leche') => 'fa-cheese',
            str_contains($name, 'grasa') => 'fa-tint',
            str_contains($name, 'azucar') => 'fa-cookie',
            default => 'fa-tag',
        };
    };

    $hasFilters = ! $loadError && ($search !== '' || $groupId || $withoutAllergen || $sort !== 'nombre');
    $activeFilterCount = $loadError ? 0 : (int) ($groupId !== null) + (int) ($withoutAllergen !== null) + (int) ($sort !== 'nombre');

    // Datos para el detalle (diálogo): solo los alimentos de esta página
    $detail = $loadError ? collect() : $foods->getCollection()->map(fn ($food) => [
        'id' => $food->id,
        'name' => $food->name,
        'group' => $food->foodGroup?->name,
        'icon' => $groupIcon($food->foodGroup?->name),
        'portion' => $food->portion_description,
        'portion_grams' => (float) $food->portion_grams,
        'kcal' => (float) $food->kcal,
        'protein' => (float) $food->protein_g,
        'carbs' => (float) $food->carbs_g,
        'lipids' => (float) $food->lipids_g,
        'fiber' => $food->fiber_g !== null ? (float) $food->fiber_g : null,
        'sodium' => $food->sodium_mg !== null ? (float) $food->sodium_mg : null,
        'glycemic_index' => $food->glycemic_index,
        'allergens' => $food->allergens->pluck('name')->values(),
        'source' => $food->source,
    ])->values();
@endphp


@section('content_header')
    <div class="consultations-header food-header">
        <div>
            <span class="consultation-eyebrow">
                <i class="fas fa-leaf me-1" aria-hidden="true"></i>
                NUTRICIÓN
            </span>
            <h1>Catálogo de alimentos</h1>
            <p>
                Consulta porciones y aporte nutricional para armar los planes de tus pacientes.
                @unless($loadError)
                    <span class="food-count-pill">
                        <i class="fas fa-database" aria-hidden="true"></i>
                        {{ number_format($totalActive) }} {{ $totalActive === 1 ? 'alimento' : 'alimentos' }}
                        · {{ $groups->count() }} {{ $groups->count() === 1 ? 'grupo' : 'grupos' }}
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
<div class="consultations-page diet-page food-page">

    @if($loadError)

        {{-- ERROR AL CARGAR --}}
        <section class="consultations-card food-state food-state--error" role="alert">
            <span class="food-state-icon" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></span>
            <h2>No se pudo cargar el catálogo</h2>
            <p>Hubo un problema al consultar la base de datos. Tus datos no se modificaron. Intenta de nuevo en un momento; si continúa, revisa la conexión a la base de datos.</p>
            <a href="{{ request()->fullUrl() }}" class="diet-btn diet-btn--soft">
                <i class="fas fa-redo" aria-hidden="true"></i>
                Reintentar
            </a>
        </section>

    @elseif($totalActive === 0)

        {{-- CATÁLOGO VACÍO (no es un error: aún no se cargan alimentos) --}}
        <section class="consultations-card food-state">
            <span class="food-state-icon" aria-hidden="true"><i class="fas fa-apple-alt"></i></span>
            <h2>El catálogo aún no tiene alimentos</h2>
            <p>
                Cuando se cargue el catálogo podrás consultarlo aquí y agregar alimentos a las comidas de tus planes.
                La carga la hace el administrador del sistema.
            </p>
            @if(app()->isLocal())
                <p class="food-state-hint">
                    Entorno local: <code>php artisan db:seed --class=FoodSeeder</code>
                    (no borra ni duplica datos).
                </p>
            @endif
        </section>

    @else

        {{-- BÚSQUEDA Y FILTROS (GET: funcionan con datos reales) --}}
        <section class="consultations-card food-toolbar" aria-label="Buscar y filtrar alimentos">
            <form method="GET" action="{{ route('foods.index') }}" id="foodFilters" class="food-filters" role="search">

                <div class="food-search-row">
                    <div class="diet-search food-search">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <label for="foodQuery" class="visually-hidden">Buscar alimento por nombre</label>
                        <input type="search"
                               name="q"
                               id="foodQuery"
                               value="{{ $search }}"
                               maxlength="100"
                               placeholder="Buscar alimento, p. ej. pollo"
                               autocomplete="off">
                    </div>

                    <button type="submit" class="diet-btn diet-btn--primary food-search-btn">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>Buscar</span>
                    </button>

                    <button type="button"
                            class="diet-btn diet-btn--ghost food-filters-toggle"
                            aria-expanded="false"
                            aria-controls="foodFilterPanel"
                            hidden>
                        <i class="fas fa-sliders-h" aria-hidden="true"></i>
                        Filtros
                        @if($activeFilterCount > 0)
                            <span class="food-badge" aria-label="{{ $activeFilterCount }} activos">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                </div>

                <div class="food-filter-panel" id="foodFilterPanel">
                    <div class="food-field">
                        <label for="foodGroup">Grupo de alimentos</label>
                        <div class="diet-filter-select">
                            <i class="fas fa-layer-group" aria-hidden="true"></i>
                            <select name="grupo" id="foodGroup" data-auto-submit>
                                <option value="">Todos los grupos</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" @selected($groupId === $group->id)>
                                        {{ $group->name }} ({{ $group->foods_count }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($allergens->isNotEmpty())
                        <div class="food-field">
                            <label for="foodAllergen">Excluir alérgeno</label>
                            <div class="diet-filter-select">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                <select name="sin" id="foodAllergen" data-auto-submit>
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
                        <label for="foodSort">Ordenar por</label>
                        <div class="diet-filter-select">
                            <i class="fas fa-sort-amount-down" aria-hidden="true"></i>
                            <select name="orden" id="foodSort" data-auto-submit>
                                @foreach($sorts as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            @if($hasFilters)
                {{-- Filtros activos: cada uno se quita con su propio enlace --}}
                <div class="food-active-filters" aria-label="Filtros activos">
                    <span class="food-active-label">Filtros activos:</span>

                    @if($search !== '')
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['q', 'page']) }}">
                            Nombre: «{{ $search }}»
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span class="visually-hidden">(quitar)</span>
                        </a>
                    @endif
                    @if($groupId)
                        <a class="food-filter-tag" href="{{ request()->fullUrlWithoutQuery(['grupo', 'page']) }}">
                            Grupo: {{ $groups->firstWhere('id', $groupId)?->name }}
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

                    <a class="food-clear" href="{{ route('foods.index') }}">
                        <i class="fas fa-eraser" aria-hidden="true"></i>
                        Limpiar filtros
                    </a>
                </div>
            @endif
        </section>

        {{-- RESULTADOS --}}
        <section class="food-results" id="foodResults" aria-labelledby="foodResultsTitle">
            <div class="food-results-head">
                <h2 id="foodResultsTitle" class="food-results-title">
                    @if($foods->total() === 0)
                        Sin resultados
                    @elseif($hasFilters)
                        {{ $foods->total() }} {{ $foods->total() === 1 ? 'alimento coincide' : 'alimentos coinciden' }}
                    @else
                        Todos los alimentos
                    @endif
                </h2>
                @if($foods->total() > 0)
                    <p class="food-results-range" aria-live="polite">
                        Mostrando {{ $foods->firstItem() }}–{{ $foods->lastItem() }} de {{ $foods->total() }}
                        · valores por porción
                    </p>
                @endif
            </div>

            {{-- Se muestra mientras se recarga la página con nuevos filtros --}}
            <div class="food-loading" role="status" aria-live="polite" hidden>
                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                Cargando alimentos…
            </div>

            @if($foods->total() === 0)

                <div class="consultations-card food-state food-state--compact">
                    <span class="food-state-icon" aria-hidden="true"><i class="fas fa-search"></i></span>
                    <h2>Ningún alimento coincide</h2>
                    <p>
                        @if($search !== '')
                            No encontramos «{{ $search }}» con los filtros actuales. Revisa la ortografía o prueba con una palabra más corta.
                        @else
                            Ningún alimento cumple con los filtros seleccionados.
                        @endif
                    </p>
                    <a href="{{ route('foods.index') }}" class="diet-btn diet-btn--soft">
                        <i class="fas fa-eraser" aria-hidden="true"></i>
                        Limpiar filtros
                    </a>
                </div>

            @else

                <ul class="food-grid" role="list">
                    @foreach($foods as $food)
                        @php($groupName = $food->foodGroup?->name ?? 'Sin grupo')
                        <li class="food-card">
                            <div class="food-card-top">
                                <span class="food-group-tag">
                                    <i class="fas {{ $groupIcon($food->foodGroup?->name) }}" aria-hidden="true"></i>
                                    {{ $groupName }}
                                </span>
                                <h3 class="food-name">{{ $food->name }}</h3>
                                <p class="food-portion">
                                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                                    Porción:
                                    {{ $food->portion_description ? $food->portion_description.' · ' : '' }}{{ $fmt($food->portion_grams) }} g
                                </p>
                            </div>

                            <div class="food-kcal">
                                <strong>{{ $fmt($food->kcal, 0) }}</strong>
                                <span>kcal</span>
                            </div>

                            <dl class="food-macros">
                                <div class="food-macro is-protein">
                                    <dt>Proteína</dt>
                                    <dd>{{ $fmt($food->protein_g) }} g</dd>
                                </div>
                                <div class="food-macro is-carbs">
                                    <dt><span aria-hidden="true">Carbohid.</span><span class="visually-hidden">Carbohidratos</span></dt>
                                    <dd>{{ $fmt($food->carbs_g) }} g</dd>
                                </div>
                                <div class="food-macro is-lipids">
                                    <dt>Grasas</dt>
                                    <dd>{{ $fmt($food->lipids_g) }} g</dd>
                                </div>
                            </dl>

                            <div class="food-card-foot">
                                @if($food->allergens->isNotEmpty())
                                    <span class="food-allergen-note">
                                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                        Contiene: {{ $food->allergens->pluck('name')->join(', ') }}
                                    </span>
                                @elseif($food->fiber_g !== null)
                                    <span class="food-extra-note">Fibra: {{ $fmt($food->fiber_g) }} g</span>
                                @else
                                    <span></span>
                                @endif

                                <button type="button"
                                        class="diet-btn diet-btn--ghost food-detail-btn"
                                        data-food-detail="{{ $food->id }}"
                                        aria-haspopup="dialog"
                                        aria-controls="foodDetailDialog">
                                    Ver detalle
                                    <span class="visually-hidden">de {{ $food->name }}</span>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- PAGINACIÓN (solo si hay más de una página) --}}
                @if($foods->hasPages())
                    <nav class="food-pager" aria-label="Páginas del catálogo">
                        @if($foods->onFirstPage())
                            <span class="diet-btn diet-btn--ghost is-disabled" aria-disabled="true">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                            </span>
                        @else
                            <a class="diet-btn diet-btn--ghost" href="{{ $foods->previousPageUrl() }}" rel="prev" data-food-nav>
                                <i class="fas fa-chevron-left" aria-hidden="true"></i> Anterior
                            </a>
                        @endif

                        <span class="food-pager-status">Página {{ $foods->currentPage() }} de {{ $foods->lastPage() }}</span>

                        @if($foods->hasMorePages())
                            <a class="diet-btn diet-btn--ghost" href="{{ $foods->nextPageUrl() }}" rel="next" data-food-nav>
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

        {{-- DETALLE DE UN ALIMENTO (lo llena foods.js con los datos de esta página) --}}
        <dialog class="diet-dialog diet-dialog--sm food-detail" id="foodDetailDialog" aria-labelledby="foodDetailName">
            <div class="diet-dialog-inner">
                <header class="diet-dialog-head">
                    <div>
                        <span class="food-group-tag" data-detail="group"></span>
                        <h2 id="foodDetailName" data-detail="name"></h2>
                        <p class="food-portion" data-detail="portion"></p>
                    </div>
                    <button type="button" class="diet-icon-btn" data-close-dialog aria-label="Cerrar detalle">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </header>

                <div class="diet-dialog-body food-detail-body">
                    <table class="food-detail-table">
                        <caption class="visually-hidden">Aporte nutricional</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nutriente</th>
                                <th scope="col" data-detail="portion-head">Por porción</th>
                                <th scope="col">Por 100 g</th>
                            </tr>
                        </thead>
                        <tbody data-detail="rows"></tbody>
                    </table>

                    <dl class="food-detail-facts">
                        <div>
                            <dt>Índice glucémico</dt>
                            <dd data-detail="gi"></dd>
                        </div>
                        <div>
                            <dt>Alérgenos</dt>
                            <dd data-detail="allergens"></dd>
                        </div>
                        <div>
                            <dt>Fuente</dt>
                            <dd data-detail="source"></dd>
                        </div>
                    </dl>

                    <p class="diet-muted food-detail-note">
                        Valores de referencia aproximados. «Por 100 g» se calcula a partir de la porción.
                    </p>
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
            </div>
        </dialog>

        <script type="application/json" id="foodDetailData">@json($detail)</script>

    @endif
</div>
@stop


@section('css')
    @include('partials.theme-styles', ['section' => 'admin'])
@stop


@section('js')
    @vite(['resources/js/pages/foods-index.js'])
@stop
