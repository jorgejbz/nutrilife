<?php

namespace App\Http\Controllers;

use App\Models\Allergen;
use App\Models\Food;
use App\Models\FoodGroup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * Catálogo de alimentos (solo consulta).
 *
 * Lee la tabla `foods` (catálogo global, no pertenece a ningún nutriólogo)
 * con sus grupos (`food_groups`) y alérgenos (`food_allergens`). Mismo
 * acceso que Planes alimenticios: middleware feature:diet_plans en la ruta.
 *
 * Filtros por GET (se pueden compartir/guardar como enlace):
 *   q      → nombre del alimento (sin importar acentos ni mayúsculas)
 *   grupo  → food_group_id
 *   sin    → allergen_id: oculta los alimentos que contienen ese alérgeno
 *   orden  → nombre | kcal | proteina | fibra
 *
 * Los nutrientes de la tabla son POR PORCIÓN (portion_grams); la vista
 * muestra esa porción y, en el detalle, el equivalente por 100 g.
 */
class FoodCatalogController extends Controller
{
    public const PER_PAGE = 24;

    public const SORTS = [
        'nombre' => 'Nombre (A-Z)',
        'kcal' => 'Menos kcal por porción',
        'proteina' => 'Más proteína por porción',
        'fibra' => 'Más fibra por porción',
    ];

    public function index(Request $request)
    {
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $sort = array_key_exists($request->query('orden'), self::SORTS) ? $request->query('orden') : 'nombre';

        try {
            $groups = FoodGroup::query()
                ->whereHas('foods', fn ($query) => $query->where('active', true))
                ->withCount(['foods' => fn ($query) => $query->where('active', true)])
                ->orderBy('name')
                ->get();

            // Solo alérgenos que de verdad aparecen en algún alimento activo
            $allergens = Allergen::query()
                ->whereIn('id', fn ($query) => $query->select('allergen_id')
                    ->from('food_allergens')
                    ->join('foods', 'foods.id', '=', 'food_allergens.food_id')
                    ->where('foods.active', true))
                ->orderBy('name')
                ->get(['id', 'name']);

            $groupId = $request->integer('grupo') ?: null;
            $groupId = $groups->contains('id', $groupId) ? $groupId : null;

            $withoutAllergen = $request->integer('sin') ?: null;
            $withoutAllergen = $allergens->contains('id', $withoutAllergen) ? $withoutAllergen : null;

            $totalActive = Food::where('active', true)->count();

            $foods = Food::query()
                ->where('active', true)
                ->with(['foodGroup:id,name', 'allergens:id,name'])
                ->when($search !== '', fn ($query) => $this->whereNameMatches($query, $search))
                ->when($groupId, fn ($query) => $query->where('food_group_id', $groupId))
                ->when($withoutAllergen, fn ($query) => $query->whereDoesntHave(
                    'allergens',
                    fn ($allergen) => $allergen->where('allergens.id', $withoutAllergen)
                ))
                ->when($sort === 'kcal', fn ($query) => $query->orderBy('kcal'))
                ->when($sort === 'proteina', fn ($query) => $query->orderByDesc('protein_g'))
                ->when($sort === 'fibra', fn ($query) => $query->orderByRaw('fiber_g IS NULL')->orderByDesc('fiber_g'))
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(self::PER_PAGE)
                ->withQueryString();
        } catch (QueryException $exception) {
            // Error de BD (tabla faltante, conexión caída…): se registra y se
            // muestra un estado de error en lugar de un 500.
            report($exception);

            return response()->view('foods.index', [
                'loadError' => true,
                'search' => $search,
                'sort' => $sort,
                'sorts' => self::SORTS,
            ], 503);
        }

        return view('foods.index', [
            'loadError' => false,
            'foods' => $foods,
            'groups' => $groups,
            'allergens' => $allergens,
            'totalActive' => $totalActive,
            'search' => $search,
            'groupId' => $groupId,
            'withoutAllergen' => $withoutAllergen,
            'sort' => $sort,
            'sorts' => self::SORTS,
        ]);
    }

    /**
     * Cada palabra debe aparecer en el nombre ("pollo pechuga" encuentra
     * "Pechuga de pollo"). Los acentos se comparan tal cual en SQL, así que
     * también se busca la variante sin acento escrita por el usuario.
     */
    private function whereNameMatches($query, string $search): void
    {
        $words = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY);

        foreach (array_slice($words, 0, 6) as $word) {
            $query->where(function ($query) use ($word) {
                foreach (self::accentVariants($word) as $variant) {
                    $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $variant).'%';
                    $query->orWhere('name', 'like', $like);
                }
            });
        }
    }

    /**
     * "platano" → ["platano", "plátano"…]. MySQL (utf8mb4_unicode_ci) ya
     * ignora acentos; SQLite no, por eso se agregan las variantes.
     *
     * @return list<string>
     */
    private static function accentVariants(string $word): array
    {
        $map = ['a' => 'á', 'e' => 'é', 'i' => 'í', 'o' => 'ó', 'u' => 'ú', 'n' => 'ñ'];
        $plain = mb_strtolower(strtr($word, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'ü' => 'u']));
        $variants = [$word, $plain];

        // Una sola letra acentuada por variante (cubre los nombres del catálogo)
        $chars = mb_str_split($plain);
        foreach ($chars as $index => $char) {
            if (isset($map[$char])) {
                $copy = $chars;
                $copy[$index] = $map[$char];
                $variants[] = implode('', $copy);
            }
        }

        return array_values(array_unique(array_slice($variants, 0, 12)));
    }
}
