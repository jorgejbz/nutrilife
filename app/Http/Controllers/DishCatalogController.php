<?php

namespace App\Http\Controllers;

use App\Models\Allergen;
use App\Models\DietPlanDish;
use App\Models\DietPlanDishItem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Catálogo de platillos (solo consulta), hermano del Catálogo de alimentos.
 *
 * Lee `diet_plan_dishes` + `diet_plan_dish_items` (actualización 3.2):
 * platillos del sistema (user_id NULL) y los propios del nutriólogo
 * (DietPlanDish::visibleTo). Mismo acceso que Planes alimenticios:
 * middleware feature:diet_plans en la ruta.
 *
 * El aporte de un platillo es la suma de sus ingredientes
 * (DietPlanDish::totals()): los del catálogo se calculan de `foods`; los
 * personalizados usan sus custom_* (NULL = sin dato, cuenta 0).
 *
 * Filtros por GET:
 *   q      → nombre del platillo o de alguno de sus ingredientes
 *   origen → sistema | propios
 *   datos  → completos | incompletos | sin_ingredientes
 *   sin    → allergen_id: oculta platillos con un ingrediente del catálogo
 *            que contenga ese alérgeno
 *   orden  → nombre | kcal | proteina | ingredientes
 *
 * Son pocos registros (decenas): se filtran y ordenan en PHP porque el
 * aporte se calcula, no está guardado.
 */
class DishCatalogController extends Controller
{
    public const PER_PAGE = 12;

    public const SORTS = [
        'nombre' => 'Nombre (A-Z)',
        'kcal' => 'Menos kcal',
        'proteina' => 'Más proteína',
        'ingredientes' => 'Más ingredientes',
    ];

    public const ORIGINS = [
        'sistema' => 'Recetas del sistema',
        'propios' => 'Mis platillos',
    ];

    public const DATA_FILTERS = [
        'completos' => 'Con datos completos',
        'incompletos' => 'Con datos incompletos',
        'sin_ingredientes' => 'Sin ingredientes',
    ];

    public function index(Request $request)
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $sort = array_key_exists($request->query('orden'), self::SORTS) ? $request->query('orden') : 'nombre';
        $origin = array_key_exists($request->query('origen'), self::ORIGINS) ? $request->query('origen') : null;
        $dataFilter = array_key_exists($request->query('datos'), self::DATA_FILTERS) ? $request->query('datos') : null;

        try {
            $all = DietPlanDish::visibleTo(Auth::id())
                ->with(['items.food.foodGroup:id,name', 'items.food.allergens:id,name'])
                ->orderBy('name')
                ->get();

            // Solo alérgenos presentes en algún ingrediente del catálogo
            $allergenIds = $all->flatMap->items->pluck('food')->filter()
                ->flatMap->allergens->pluck('id')->unique();
            $allergens = Allergen::whereIn('id', $allergenIds)->orderBy('name')->get(['id', 'name']);

            $withoutAllergen = $request->integer('sin') ?: null;
            $withoutAllergen = $allergens->contains('id', $withoutAllergen) ? $withoutAllergen : null;
        } catch (QueryException $exception) {
            report($exception);

            return response()->view('dishes.index', [
                'loadError' => true,
                'search' => $search,
            ], 503);
        }

        $rows = $all->map(fn (DietPlanDish $dish) => $this->row($dish));

        $filtered = $rows
            ->when($search !== '', fn (Collection $rows) => $rows->filter(fn (array $row) => $this->matches($row, $search)))
            ->when($origin === 'sistema', fn (Collection $rows) => $rows->filter(fn (array $row) => $row['dish']->isSystem()))
            ->when($origin === 'propios', fn (Collection $rows) => $rows->reject(fn (array $row) => $row['dish']->isSystem()))
            ->when($dataFilter === 'completos', fn (Collection $rows) => $rows->filter(fn (array $row) => $row['count'] > 0 && $row['missing'] === 0))
            ->when($dataFilter === 'incompletos', fn (Collection $rows) => $rows->filter(fn (array $row) => $row['count'] > 0 && $row['missing'] > 0))
            ->when($dataFilter === 'sin_ingredientes', fn (Collection $rows) => $rows->filter(fn (array $row) => $row['count'] === 0))
            ->when($withoutAllergen, fn (Collection $rows) => $rows->reject(fn (array $row) => $row['allergens']->contains('id', $withoutAllergen)));

        // Los platillos sin ingredientes van al final en cualquier orden
        $sorted = match ($sort) {
            'kcal' => $filtered->sortBy([fn ($a, $b) => ($a['count'] === 0) <=> ($b['count'] === 0), fn ($a, $b) => $a['totals']['kcal'] <=> $b['totals']['kcal']]),
            'proteina' => $filtered->sortBy([fn ($a, $b) => ($a['count'] === 0) <=> ($b['count'] === 0), fn ($a, $b) => $b['totals']['protein_g'] <=> $a['totals']['protein_g']]),
            'ingredientes' => $filtered->sortBy([fn ($a, $b) => $b['count'] <=> $a['count'], fn ($a, $b) => strcmp($a['sort_name'], $b['sort_name'])]),
            default => $filtered->sortBy([fn ($a, $b) => ($a['count'] === 0) <=> ($b['count'] === 0), fn ($a, $b) => strcmp($a['sort_name'], $b['sort_name'])]),
        };

        $page = max(1, $request->integer('page', 1));

        $dishes = new LengthAwarePaginator(
            $sorted->values()->forPage($page, self::PER_PAGE)->values(),
            $sorted->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dishes.index', [
            'loadError' => false,
            'dishes' => $dishes,
            'allergens' => $allergens,
            'totalDishes' => $rows->count(),
            'ownDishes' => $rows->reject(fn (array $row) => $row['dish']->isSystem())->count(),
            'search' => $search,
            'origin' => $origin,
            'dataFilter' => $dataFilter,
            'withoutAllergen' => $withoutAllergen,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'origins' => self::ORIGINS,
            'dataFilters' => self::DATA_FILTERS,
        ]);
    }

    /**
     * Datos calculados de un platillo para la tarjeta y el detalle.
     *
     * @return array<string, mixed>
     */
    private function row(DietPlanDish $dish): array
    {
        return [
            'dish' => $dish,
            'sort_name' => Str::lower(Str::ascii($dish->name)),
            'count' => $dish->items->count(),
            'missing' => $dish->missingNutrientsCount(),
            'totals' => $dish->totals(),
            'grams' => (float) $dish->items->sum(fn (DietPlanDishItem $item) => (float) $item->grams),
            'allergens' => $dish->items->pluck('food')->filter()->flatMap->allergens->unique('id')->sortBy('name')->values(),
            'ingredients' => $dish->items->map(fn (DietPlanDishItem $item) => [
                'name' => $item->displayName(),
                'grams' => (float) $item->grams,
                'custom' => $item->isCustom(),
                'group' => $item->food?->foodGroup?->name,
                'complete' => $item->hasNutrients(),
                'nutrients' => $item->nutrients(),
            ]),
            'search_text' => Str::lower(Str::ascii($dish->name.' '.$dish->items->map->displayName()->join(' '))),
        ];
    }

    /** Cada palabra debe aparecer en el nombre o en algún ingrediente (sin acentos). */
    private function matches(array $row, string $search): bool
    {
        $words = preg_split('/\s+/u', Str::lower(Str::ascii($search)), -1, PREG_SPLIT_NO_EMPTY);

        foreach (array_slice($words, 0, 6) as $word) {
            if (! str_contains($row['search_text'], $word)) {
                return false;
            }
        }

        return true;
    }
}
