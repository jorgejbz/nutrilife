<?php

namespace App\Http\Controllers;

use App\Models\DietPlan;
use App\Models\DietPlanMeal;
use App\Models\DietPlanItem;
use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DietPlanItemController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ALIMENTO DEL CATÁLOGO
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        DietPlan $plan,
        DietPlanMeal $meal
    ) {
        $this->validateOwnership(
            $plan,
            $meal
        );


        $validated = $request->validate([
            'food_id' => [
                'required',
                'integer',
            ],

            'grams' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ]);


        $food = Food::where(
            'id',
            $validated['food_id']
        )
            ->where('active', true)
            ->first();


        if (!$food) {

            return back()
                ->withErrors([
                    'food_id' =>
                        'El alimento seleccionado no es válido.',
                ])
                ->withInput();
        }


        $portionGrams =
            (float) $food->portion_grams;


        if ($portionGrams <= 0) {

            return back()
                ->withErrors([
                    'food_id' =>
                        'El alimento seleccionado no tiene una porción válida.',
                ])
                ->withInput();
        }


        $grams =
            (float) $validated['grams'];


        DietPlanItem::create([

            'diet_plan_meal_id' =>
                $meal->id,

            'food_id' =>
                $food->id,

            'grams' =>
                $grams,

            'equivalents' =>
                round(
                    $grams / $portionGrams,
                    2
                ),

        ]);


        return redirect()
            ->route(
                'diet-plans.show',
                $plan
            )
            ->with(
                'success',
                'Alimento agregado correctamente.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | ALIMENTO PERSONALIZADO
    |--------------------------------------------------------------------------
    */

    public function storeCustom(
        Request $request,
        DietPlan $plan,
        DietPlanMeal $meal
    ) {
        $this->validateOwnership(
            $plan,
            $meal
        );


        $validated = $request->validate([

            'custom_name' => [
                'required',
                'string',
                'max:150',
            ],

            'grams' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'custom_kcal' => [
                'required',
                'numeric',
                'min:0',
            ],

            'custom_protein_g' => [
                'required',
                'numeric',
                'min:0',
            ],

            'custom_carbs_g' => [
                'required',
                'numeric',
                'min:0',
            ],

            'custom_lipids_g' => [
                'required',
                'numeric',
                'min:0',
            ],

            'custom_portion_description' => [
                'nullable',
                'string',
                'max:150',
            ],

        ]);


        DietPlanItem::create([

            'diet_plan_meal_id' =>
                $meal->id,

            /*
             * IMPORTANTE:
             * No se guarda en foods.
             */
            'food_id' =>
                null,

            'custom_name' =>
                $validated['custom_name'],

            'custom_kcal' =>
                $validated['custom_kcal'],

            'custom_protein_g' =>
                $validated['custom_protein_g'],

            'custom_carbs_g' =>
                $validated['custom_carbs_g'],

            'custom_lipids_g' =>
                $validated['custom_lipids_g'],

            'custom_portion_description' =>
                $validated[
                    'custom_portion_description'
                ] ?? null,

            'grams' =>
                $validated['grams'],

            /*
             * Para manuales tomamos lo capturado
             * como una sola porción.
             */
            'equivalents' =>
                1,

        ]);


        return redirect()
            ->route(
                'diet-plans.show',
                $plan
            )
            ->with(
                'success',
                'Alimento personalizado agregado correctamente.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | ELIMINAR
    |--------------------------------------------------------------------------
    */

    public function destroy(
        DietPlan $plan,
        DietPlanMeal $meal,
        DietPlanItem $item
    ) {
        $this->validateOwnership(
            $plan,
            $meal
        );


        abort_if(
            $item->diet_plan_meal_id
                !== $meal->id,
            404
        );


        $item->delete();


        return redirect()
            ->route(
                'diet-plans.show',
                $plan
            )
            ->with(
                'success',
                'Alimento eliminado correctamente.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD
    |--------------------------------------------------------------------------
    */

    private function validateOwnership(
        DietPlan $plan,
        DietPlanMeal $meal
    ): void {

        abort_if(
            $plan->user_id !== Auth::id(),
            403
        );


        abort_if(
            $meal->diet_plan_id
                !== $plan->id,
            404
        );
    }
}