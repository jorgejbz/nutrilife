{{-- Campos del modo del drawer de alimento (los llena diet-plans.js).
     Deshabilitados = no se envían: el drawer funciona igual que antes
     al agregar a la comida. _drawer_key reabre el mismo botón si falla
     la validación. --}}
<input type="hidden" name="_method" value="PATCH" data-method-input disabled>
<input type="hidden" name="diet_plan_dish_id" value="" data-dish-input disabled>
<input type="hidden" name="_drawer_key" value="" data-key-input>
