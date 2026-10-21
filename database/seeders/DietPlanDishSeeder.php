<?php

namespace Database\Seeders;

use App\Models\DietPlanDish;
use App\Models\DietPlanDishItem;
use App\Models\DietPlanItem;
use App\Models\Food;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Platillos del sistema (diet_plan_dishes con user_id NULL) con su receta
 * completa en diet_plan_dish_items.
 *
 *   php artisan db:seed --class=DietPlanDishSeeder
 *
 * Requiere el catálogo de alimentos: FoodSeeder (SMAE) e
 * IncmnszCatalogSeeder (INCMNSZ 2015). Los ingredientes se ligan a `foods`
 * por nombre, así kcal y macros salen del catálogo y los avisos de alergias
 * y patologías funcionan. Lo que no existe en ningún catálogo va como
 * ingrediente personalizado con valores de referencia por 100 g
 * (CUSTOM_FOODS) escalados a sus gramos.
 *
 * Se puede ejecutar varias veces:
 *  - Crea el platillo si no existe (busca por nombre).
 *  - Si sus ingredientes ya son la receta, no toca nada.
 *  - Si son distintos (p. ej. la receta incompleta de la versión anterior),
 *    los reemplaza por la receta. Los planes NO cambian: al insertar un
 *    platillo sus ingredientes se copiaron al plan.
 *  - Solo platillos del sistema; nunca toca los propios de un nutriólogo.
 *  - Si falta algún alimento del catálogo, deja ese platillo como estaba y
 *    lo avisa.
 *
 * Porciones: las usuales de un tiempo de comida, en gramos de alimento
 * listo para comer (cocido cuando aplica).
 */
class DietPlanDishSeeder extends Seeder
{
    /** Las fotos viven en public/images/platillos (créditos en PHOTOS). */
    private const IMAGE = 'images/platillos/%s';

    /**
     * Ingredientes que no están en `foods` (ni SMAE ni INCMNSZ), por 100 g:
     * [kcal, proteína, carbohidratos, grasas]. Referencia: USDA FoodData
     * Central (SR Legacy). Valores aproximados para revisión del nutriólogo.
     */
    public const CUSTOM_FOODS = [
        'Tofu firme' => [144, 17.3, 2.8, 8.7],
        'Espárragos (cocidos)' => [22, 2.4, 4.1, 0.2],
        'Queso feta' => [264, 14.2, 4.1, 21.3],
        'Pechuga de pavo rebanada' => [104, 17.1, 4.2, 1.7],
        'Pavo molido magro (cocido)' => [213, 27.1, 0, 11.6],
        'Hummus' => [166, 7.9, 14.3, 9.6],
        'Galletas integrales' => [443, 8.7, 68.6, 17.2],
    ];

    /**
     * Crédito de cada foto (Wikimedia Commons): [autor, licencia, página].
     * Se guarda en diet_plan_dishes.image_credit como «Autor · Licencia ·
     * Wikimedia Commons». Lista completa: docs/CREDITOS_FOTOS_PLATILLOS.md
     */
    public const PHOTOS = [
        'Omelette de Claras con Espinacas y Queso' => ['Ewan Munro from London, UK', 'CC BY-SA 2.0', 'https://commons.wikimedia.org/wiki/File:Artillery_Arms,_St_Lukes,_London_(3599600661).jpg'],
        'Pechuga a la Plancha con Papa al Horno y Ensalada' => ['Isaacvp', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Pechuga_a_la_plancha_con_guarniciones_en_VIPs_Tlaxcala.jpg'],
        'Sincronizadas de Pavo con Queso Panela' => ['Nsaum75', 'CC BY-SA 3.0', 'https://commons.wikimedia.org/wiki/File:Sincronizada_mexicana.jpg'],
        'Ensalada Griega con Pollo y Aceitunas' => ['zone41 (Flickr)', 'CC BY 2.0', 'https://commons.wikimedia.org/wiki/File:Greek_Salad_Choriatiki.jpg'],
        'Bowl de Avena con Plátano y Crema de Maní' => ['Shisma', 'CC BY 4.0', 'https://commons.wikimedia.org/wiki/File:Banana_oatmeal_2.jpg'],
        'Licuado Proteico de Avena, Plátano y Almendras' => ['Ourlibrary', 'CC0', 'https://commons.wikimedia.org/wiki/File:Banana_Smoothie.jpg'],
        'Hot Cakes de Avena y Plátano con Frutos Rojos' => ['Nightcrafter', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Pancakes_with_fruit.jpg'],
        'Tostadas de Aguacate con Huevo Pochado' => ['Stacy Spensley', 'CC BY 2.0', 'https://commons.wikimedia.org/wiki/File:T%C3%ADpico_desayuno_that_girl.jpg'],
        'Smoothie Bowl de Frutos Rojos y Semillas de Chía' => ['Ella Olsson from Stockholm, Sweden', 'CC BY 2.0', 'https://commons.wikimedia.org/wiki/File:Berries_Galore_Acai_Bowl_(30276166867).jpg'],
        'Chilaquiles Verdes Ligeros con Pollo y Queso Panela' => ['Engirare', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Chilaquiles_verdes_con_pollo.jpg'],
        'Filete de Salmón al Horno con Espárragos y Camote' => ['HaJunkiyada', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Liat_Portal_for_Foodie_Disorder_-_Grilled_Salmon_with_Roasted_Potatoes_and_Asparagus.jpg'],
        'Fajitas de Res con Pimientos y Cebolla' => ['Elisa Arteaga from Caracas, Venezuela', 'CC BY-SA 2.0', 'https://commons.wikimedia.org/wiki/File:Flickr_elisart_324248450--Beef_and_chicken_fajitas.jpg'],
        'Ceviche de Pescado Blanco con Tostadas Horneadas' => ['Wotancito', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Ceviche_de_pescado_con_tostadas.jpg'],
        'Pechuga Empanizada con Amaranto y Ensalada Verde' => ['Maor X', 'CC BY-SA 3.0', 'https://commons.wikimedia.org/wiki/File:Schnitzel_with_baked_potatoes_and_salad.jpg'],
        'Tacos de Pescado a la Mexicana con Tortilla de Maíz' => ['Alejandra Mendoza Santillan', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Tacos_de_pescado_estilo_Ensenada.jpg'],
        'Bowl de Quinoa con Verduras Salteadas y Tofu' => ['PizzaMan', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:BuddhaBowlLot.jpg'],
        'Ensalada de Atún con Verduras y Galletas Integrales' => ['Daderot', 'CC0', 'https://commons.wikimedia.org/wiki/File:Tuna_salad_on_mixed_greens,_with_corn,_carrots,_tomatoes,_olive_oil,_red_vinegar,_and_black_pepper_-_Massachusetts.jpg'],
        'Wrap de Pechuga de Pavo con Lechuga y Hummus' => ['U.S. Department of Agriculture', 'Public domain', 'https://commons.wikimedia.org/wiki/File:20111012-FNCS-LSC-0225_-_Flickr_-_USDAgov.jpg'],
        'Tacos de Lechuga con Guisado de Pavo y Aguacate' => ['A Healthier Michigan from Detroit, United States', 'CC BY-SA 2.0', 'https://commons.wikimedia.org/wiki/File:Slow_Cooker_Asian_Chicken_Lettuce_Wraps_(36167537862).jpg'],
        'Crema de Calabacita con Pechuga Deshebrada' => ['Krista', 'CC BY 2.0', 'https://commons.wikimedia.org/wiki/File:Crema_de_calabac%C3%ADn_-_Krista.jpg'],
        'Enfrijoladas de Pollo con Queso Fresco' => ['Dahyana Yasada R. R.', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Enfrijoladas_con_queso.jpg'],
        'Pozole Rojo de Pollo' => ['Diegosalcido', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Pozole_Rojo.jpg'],
        'Sopa de Lentejas con Verduras' => ['Abraham', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:20221217_lentil_soup_Katowice_Panewniki.jpg'],
        'Tostadas de Tinga de Pollo' => ['Jennifer Woodard Maderazo from San Francisco, USA', 'CC BY 2.0', 'https://commons.wikimedia.org/wiki/File:Tostada_de_tinga.jpg'],
        'Caldo Tlalpeño' => ['Distrito Medico Monterrey', 'CC BY 2.0', 'https://commons.wikimedia.org/wiki/File:Caldo_tlalpe%C3%B1o_con_arroz_rojo_y_tortillas.jpg'],
        'Huevos a la Mexicana con Frijoles' => ['Connie Ma from United States of America', 'CC BY-SA 2.0', 'https://commons.wikimedia.org/wiki/File:Huevos_%C3%A0_la_Mexicana._(6571912625).jpg'],
        'Pescado a la Veracruzana con Arroz' => ['HuachinangoALaVeracruzana', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Pescado_blanco_a_la_veracruzana.jpg'],
        'Entomatadas con Queso Panela' => ['Dahyana Yasada R. R.', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Enchiladas_con_salsa_roja.jpg'],
        'Albóndigas en Caldillo de Jitomate' => ['Juan Emilio Prades Bel', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Alb%C3%B3ndigas_con_chanfaina.jpg'],
        'Arroz con Pollo y Verduras' => ['Kobako', 'CC BY-SA 2.5', 'https://commons.wikimedia.org/wiki/File:Arroz-con-Pollo.jpg'],
        'Yogur Griego con Granola y Frutos Rojos' => ['Missvain', 'CC0', 'https://commons.wikimedia.org/wiki/File:Granola_and_yogurt_-_Bubbles_%2B_Brunch_2018_-_Sarah_Stierch_02.jpg'],
        'Avena Nocturna con Manzana y Nueces' => ['Nervy8924', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Protein_overnight_oats.jpg'],
        'Sándwich Integral de Pollo y Aguacate' => ['Andy Li', 'CC0', 'https://commons.wikimedia.org/wiki/File:Vg_Toasted_Club_Sandwich_-_T_@_The_Dials_2025-04-12.jpg'],
        'Brochetas de Pollo con Verduras' => ['Home Cooking Secrets', 'CC BY-SA 3.0', 'https://commons.wikimedia.org/wiki/File:Dawn%27s_Kabob_Skewers!.jpg'],
        'Salpicón de Res con Tostadas' => ['German matus', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:Salpic%C3%B3n_de_res.jpg'],
        'Ensalada de Garbanzos con Pepino y Jitomate' => ['Juan Emilio Prades Bel', 'CC BY 4.0', 'https://commons.wikimedia.org/wiki/File:Ensalada_de_garbanzos.jpg'],
        'Pasta con Pollo y Brócoli' => ['Famartin', 'CC BY-SA 4.0', 'https://commons.wikimedia.org/wiki/File:2020-08-30_06_01_03_Close_view_of_Healthy_Choice_Cafe_Steamers_Grilled_Chicken_Marinara_with_Parmesan_(Grilled_Chicken_Breast_with_Penne_Pasta_%26_Broccoli)_in_the_Dulles_section_of_Sterling,_Loudoun_County,_Virginia.jpg'],
        'Tacos de Bistec con Nopales' => ['ProtoplasmaKid', 'CC BY 4.0', 'https://commons.wikimedia.org/wiki/File:Tacos_de_bistec_de_Tacos_Franc_-_1.jpg'],
        'Ensalada César con Pollo' => ['Daderot', 'CC0', 'https://commons.wikimedia.org/wiki/File:Chicken_Caesar_salad_-_Auckland,_New_Zealand.jpg'],
    ];

    /**
     * nombre => [archivo de la foto en public/images/platillos, [[ingrediente, gramos], …]]
     * El ingrediente es el nombre exacto en `foods` o una clave de CUSTOM_FOODS.
     */
    public const DISHES = [
        'Omelette de Claras con Espinacas y Queso' => ['omelette-de-claras-con-espinacas-y-queso.jpg', [
            ['Claras de huevo (crudas)', 132],
            ['Espinaca (cruda)', 60],
            ['Queso panela', 40],
            ['Jitomate', 60],
            ['Aceite de oliva', 5],
        ]],
        'Pechuga a la Plancha con Papa al Horno y Ensalada' => ['pechuga-a-la-plancha-con-papa-al-horno-y-ensalada.jpg', [
            ['Pechuga de pollo sin piel (cocida)', 150],
            ['Papa sin cáscara (cocida)', 120],
            ['Lechuga romana', 47],
            ['Jitomate', 60],
            ['Aceite de oliva', 5],
        ]],
        'Sincronizadas de Pavo con Queso Panela' => ['sincronizadas-de-pavo-con-queso-panela.jpg', [
            ['Tortilla de harina de trigo', 60],
            ['Pechuga de pavo rebanada', 60],
            ['Queso panela', 40],
        ]],
        'Ensalada Griega con Pollo y Aceitunas' => ['ensalada-griega-con-pollo-y-aceitunas.jpg', [
            ['Pechuga de pollo sin piel (cocida)', 120],
            ['Lechuga romana', 94],
            ['Pepino con cáscara', 52],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Queso feta', 30],
            ['Aceituna, fruto', 20],
            ['Aceite de oliva', 5],
        ]],
        'Bowl de Avena con Plátano y Crema de Maní' => ['bowl-de-avena-con-platano-y-crema-de-mani.jpg', [
            ['Avena en hojuelas (cruda)', 40],
            ['Leche descremada', 244],
            ['Plátano', 54],
            ['Crema de cacahuate natural', 16],
        ]],
        'Licuado Proteico de Avena, Plátano y Almendras' => ['licuado-proteico-de-avena-platano-y-almendras.jpg', [
            ['Leche descremada', 244],
            ['Yogur griego natural sin grasa', 150],
            ['Avena en hojuelas (cruda)', 27],
            ['Plátano', 54],
            ['Almendras', 12],
        ]],
        'Hot Cakes de Avena y Plátano con Frutos Rojos' => ['hot-cakes-de-avena-y-platano-con-frutos-rojos.jpg', [
            ['Avena en hojuelas (cruda)', 54],
            ['Plátano', 54],
            ['Huevo entero (crudo)', 50],
            ['Claras de huevo (crudas)', 66],
            ['Leche descremada', 61],
            ['Fresas', 75],
            ['Arándanos', 55],
            ['Miel de abeja', 7],
            ['Aceite de canola', 3],
        ]],
        'Tostadas de Aguacate con Huevo Pochado' => ['tostadas-de-aguacate-con-huevo-pochado.jpg', [
            ['Pan integral', 64],
            ['Aguacate Hass', 60],
            ['Huevo entero (crudo)', 100],
            ['Jitomate', 60],
        ]],
        'Smoothie Bowl de Frutos Rojos y Semillas de Chía' => ['smoothie-bowl-de-frutos-rojos-y-semillas-de-chia.jpg', [
            ['Yogur griego natural sin grasa', 150],
            ['Fresas', 150],
            ['Arándanos', 55],
            ['Plátano', 54],
            ['Semillas de chía', 12],
        ]],
        'Chilaquiles Verdes Ligeros con Pollo y Queso Panela' => ['chilaquiles-verdes-ligeros-con-pollo-y-queso-panela.jpg', [
            ['Tortilla de maíz', 60], // horneada, sin freír
            ['Tomate Verde', 100],    // salsa verde
            ['Pechuga de pollo sin piel (cocida)', 60],
            ['Queso panela', 30],
            ['Cebolla blanca (cruda)', 20],
        ]],
        'Filete de Salmón al Horno con Espárragos y Camote' => ['filete-de-salmon-al-horno-con-esparragos-y-camote.jpg', [
            ['Salmón (cocido)', 150],
            ['Espárragos (cocidos)', 80],
            ['Camote (cocido)', 100],
            ['Aceite de oliva', 5],
        ]],
        'Fajitas de Res con Pimientos y Cebolla' => ['fajitas-de-res-con-pimientos-y-cebolla.jpg', [
            ['Bistec de res magro (cocido)', 120],
            ['Pimiento morrón rojo (crudo)', 75],
            ['Cebolla blanca (cruda)', 40],
            ['Aceite de oliva', 5],
            ['Tortilla de maíz', 60],
        ]],
        'Ceviche de Pescado Blanco con Tostadas Horneadas' => ['ceviche-de-pescado-blanco-con-tostadas-horneadas.jpg', [
            ['Pescado blanco, tilapia (cocido)', 120],
            ['Jitomate', 60],
            ['Pepino con cáscara', 52],
            ['Cebolla blanca (cruda)', 20],
            ['Chile jalapeño (crudo)', 7],
            ['Limón', 30],
            ['Aguacate Hass', 30],
            ['Tortilla de maíz', 60], // 2 tostadas horneadas
        ]],
        'Pechuga Empanizada con Amaranto y Ensalada Verde' => ['pechuga-empanizada-con-amaranto-y-ensalada-verde.jpg', [
            ['Pechuga de pollo sin piel (cocida)', 120],
            ['Amaranto, grano', 15],
            ['Claras de huevo (crudas)', 33],
            ['Aceite de oliva', 5],
            ['Lechuga romana', 94],
            ['Espinaca (cruda)', 30],
            ['Pepino con cáscara', 52],
        ]],
        'Tacos de Pescado a la Mexicana con Tortilla de Maíz' => ['tacos-de-pescado-a-la-mexicana-con-tortilla-de-maiz.jpg', [
            ['Pescado blanco, tilapia (cocido)', 120],
            ['Tortilla de maíz', 90],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Chile jalapeño (crudo)', 7],
            ['Col (cruda)', 45],
            ['Aguacate Hass', 30],
        ]],
        'Bowl de Quinoa con Verduras Salteadas y Tofu' => ['bowl-de-quinoa-con-verduras-salteadas-y-tofu.jpg', [
            ['Quinoa (cocida)', 92],
            ['Tofu firme', 100],
            ['Brócoli (cocido)', 78],
            ['Pimiento morrón rojo (crudo)', 75],
            ['Calabacita (cocida)', 90],
            ['Aceite de oliva', 5],
        ]],
        'Ensalada de Atún con Verduras y Galletas Integrales' => ['ensalada-de-atun-con-verduras-y-galletas-integrales.jpg', [
            ['Atún en agua (escurrido)', 90],
            ['Jitomate', 60],
            ['Pepino con cáscara', 52],
            ['Zanahoria (cruda)', 32],
            ['Cebolla blanca (cruda)', 20],
            ['Limón', 15],
            ['Aceite de oliva', 5],
            ['Galletas integrales', 20],
        ]],
        'Wrap de Pechuga de Pavo con Lechuga y Hummus' => ['wrap-de-pechuga-de-pavo-con-lechuga-y-hummus.jpg', [
            ['Tortilla de harina de trigo', 45],
            ['Pechuga de pavo rebanada', 60],
            ['Hummus', 30],
            ['Lechuga romana', 47],
            ['Jitomate', 40],
        ]],
        'Tacos de Lechuga con Guisado de Pavo y Aguacate' => ['tacos-de-lechuga-con-guisado-de-pavo-y-aguacate.jpg', [
            ['Lechuga romana', 94],
            ['Pavo molido magro (cocido)', 90],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Aguacate Hass', 30],
            ['Aceite de oliva', 5],
        ]],
        'Crema de Calabacita con Pechuga Deshebrada' => ['crema-de-calabacita-con-pechuga-deshebrada.jpg', [
            ['Calabacita (cocida)', 180],
            ['Cebolla blanca (cruda)', 20],
            ['Leche descremada', 122],
            ['Aceite de oliva', 5],
            ['Pechuga de pollo sin piel (cocida)', 90],
        ]],

        // ---- Platillos agregados el 21/10/2026 (cocina mexicana y básicos) ----
        'Enfrijoladas de Pollo con Queso Fresco' => ['enfrijoladas-de-pollo-con-queso-fresco.jpg', [
            ['Tortilla de maíz', 90],
            ['Frijol Negro, puré', 120],
            ['Pechuga de pollo sin piel (cocida)', 60],
            ['Queso, fresco ranchero', 20],
            ['Cebolla blanca (cruda)', 15],
        ]],
        'Pozole Rojo de Pollo' => ['pozole-rojo-de-pollo.jpg', [
            ['Maiz, pozole', 150],
            ['Pechuga de pollo sin piel (cocida)', 90],
            ['Chile Guajillo', 5],
            ['Col (cruda)', 45],
            ['Rábano', 30],
            ['Cebolla blanca (cruda)', 20],
            ['Limón', 15],
        ]],
        'Sopa de Lentejas con Verduras' => ['sopa-de-lentejas-con-verduras.jpg', [
            ['Lenteja (cocida)', 198],
            ['Zanahoria (cruda)', 64],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Aceite de oliva', 5],
        ]],
        'Tostadas de Tinga de Pollo' => ['tostadas-de-tinga-de-pollo.jpg', [
            ['Tortilla de maíz', 60], // 2 tostadas horneadas
            ['Pechuga de pollo sin piel (cocida)', 90],
            ['Jitomate', 120],
            ['Cebolla blanca (cruda)', 40],
            ['Chile Chipotle, adobado con semilla', 10],
            ['Lechuga romana', 47],
            ['Queso, fresco ranchero', 15],
        ]],
        'Caldo Tlalpeño' => ['caldo-tlalpeno.jpg', [
            ['Pechuga de pollo sin piel (cocida)', 90],
            ['Garbanzo (cocido)', 82],
            ['Zanahoria (cruda)', 64],
            ['Calabacita (cocida)', 90],
            ['Chile Chipotle, adobado con semilla', 10],
            ['Aguacate Hass', 30],
        ]],
        'Huevos a la Mexicana con Frijoles' => ['huevos-a-la-mexicana-con-frijoles.jpg', [
            ['Huevo entero (crudo)', 100],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Chile Serrano, crudo', 7],
            ['Aceite de canola', 5],
            ['Frijol negro (cocido)', 86],
            ['Tortilla de maíz', 60],
        ]],
        'Pescado a la Veracruzana con Arroz' => ['pescado-a-la-veracruzana-con-arroz.jpg', [
            ['Pescado blanco, tilapia (cocido)', 120],
            ['Jitomate', 120],
            ['Cebolla blanca (cruda)', 20],
            ['Aceituna, fruto', 15],
            ['Aceite de oliva', 5],
            ['Arroz blanco (cocido)', 80],
        ]],
        'Entomatadas con Queso Panela' => ['entomatadas-con-queso-panela.jpg', [
            ['Tortilla de maíz', 90],
            ['Jitomate', 180],
            ['Cebolla blanca (cruda)', 20],
            ['Ajo', 3],
            ['Queso panela', 40],
            ['Aceite de canola', 3],
        ]],
        'Ensalada César con Pollo' => ['ensalada-cesar-con-pollo.jpg', [
            ['Lechuga romana', 94],
            ['Pechuga de pollo sin piel (cocida)', 120],
            ['Pan integral', 16],                      // crutones
            ['Queso mozzarella semidescremado', 15],
            ['Aceite de oliva', 10],
            ['Limón', 10],
        ]],
        'Albóndigas en Caldillo de Jitomate' => ['albondigas-en-caldillo-de-jitomate.jpg', [
            ['Carne molida de res 90 % magra (cocida)', 90],
            ['Arroz blanco (cocido)', 40],
            ['Claras de huevo (crudas)', 33],
            ['Jitomate', 180],
            ['Calabacita (cocida)', 90],
            ['Zanahoria (cruda)', 32],
        ]],
        'Arroz con Pollo y Verduras' => ['arroz-con-pollo-y-verduras.jpg', [
            ['Arroz integral (cocido)', 98],
            ['Pechuga de pollo sin piel (cocida)', 120],
            ['Zanahoria (cruda)', 32],
            ['Ejotes (cocidos)', 62],
            ['Elote amarillo (cocido)', 41],
            ['Aceite de canola', 5],
        ]],
        'Yogur Griego con Granola y Frutos Rojos' => ['yogur-griego-con-granola-y-frutos-rojos.jpg', [
            ['Yogur griego natural sin grasa', 150],
            ['Amaranto, granola', 30],
            ['Fresas', 75],
            ['Arándanos', 55],
            ['Miel de abeja', 7],
        ]],
        'Avena Nocturna con Manzana y Nueces' => ['avena-nocturna-con-manzana-y-nueces.jpg', [
            ['Avena en hojuelas (cruda)', 40],
            ['Leche descremada', 244],
            ['Manzana', 106],
            ['Semillas de chía', 12],
            ['Nueces', 9],
        ]],
        'Sándwich Integral de Pollo y Aguacate' => ['sandwich-integral-de-pollo-y-aguacate.jpg', [
            ['Pan integral', 64],
            ['Pechuga de pollo sin piel (cocida)', 90],
            ['Aguacate Hass', 30],
            ['Jitomate', 40],
            ['Lechuga romana', 23],
        ]],
        'Brochetas de Pollo con Verduras' => ['brochetas-de-pollo-con-verduras.jpg', [
            ['Pechuga de pollo sin piel (cocida)', 150],
            ['Pimiento morrón rojo (crudo)', 75],
            ['Cebolla blanca (cruda)', 40],
            ['Calabacita (cocida)', 90],
            ['Champiñones (crudos)', 70],
            ['Aceite de oliva', 5],
        ]],
        'Salpicón de Res con Tostadas' => ['salpicon-de-res-con-tostadas.jpg', [
            ['Bistec de res magro (cocido)', 90],
            ['Lechuga romana', 47],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Rábano', 20],
            ['Aguacate Hass', 30],
            ['Limón', 10],
            ['Tortilla de maíz', 60], // 2 tostadas horneadas
        ]],
        'Ensalada de Garbanzos con Pepino y Jitomate' => ['ensalada-de-garbanzos-con-pepino-y-jitomate.jpg', [
            ['Garbanzo (cocido)', 164],
            ['Pepino con cáscara', 104],
            ['Jitomate', 60],
            ['Cebolla blanca (cruda)', 20],
            ['Queso panela', 40],
            ['Aceite de oliva', 5],
            ['Limón', 10],
        ]],
        'Pasta con Pollo y Brócoli' => ['pasta-con-pollo-y-brocoli.jpg', [
            ['Pasta (cocida)', 140],
            ['Pechuga de pollo sin piel (cocida)', 90],
            ['Brócoli (cocido)', 78],
            ['Queso mozzarella semidescremado', 15],
            ['Aceite de oliva', 5],
        ]],
        'Tacos de Bistec con Nopales' => ['tacos-de-bistec-con-nopales.jpg', [
            ['Bistec de res magro (cocido)', 90],
            ['Nopales (cocidos)', 150],
            ['Tortilla de maíz', 90],
            ['Cebolla blanca (cruda)', 20],
            ['Cilantro', 5],
            ['Aguacate Hass', 30],
        ]],
    ];

    public function run(): void
    {
        $foodNames = collect(self::DISHES)->flatMap(fn ($dish) => array_column($dish[1], 0))
            ->reject(fn ($name) => isset(self::CUSTOM_FOODS[$name]))
            ->unique()->values();

        $foods = Food::whereIn('name', $foodNames)->where('active', true)
            ->orderBy('id')->get()->unique('name')->keyBy('name');

        $stats = ['creados' => 0, 'fotos actualizadas' => 0, 'con receta nueva' => 0, 'sin cambios' => 0, 'omitidos' => 0];

        foreach (self::DISHES as $name => [$photo, $recipe]) {
            $photoAttributes = [
                'image_url' => sprintf(self::IMAGE, $photo),
                'image_credit' => $this->credit($name),
            ];

            $dish = DietPlanDish::firstOrCreate(['user_id' => null, 'name' => $name], $photoAttributes);

            $stats['creados'] += $dish->wasRecentlyCreated ? 1 : 0;

            // La foto debe corresponder al platillo (antes eran fotos genéricas).
            // Los renglones de planes guardan una copia de la foto al insertar el
            // platillo: las copias de la foto anterior se cambian por la nueva
            // (solo la foto; cantidades y valores del plan no se tocan).
            if (! $dish->wasRecentlyCreated && $dish->only(array_keys($photoAttributes)) !== $photoAttributes) {
                $previousImage = $dish->image_url;

                DB::transaction(function () use ($dish, $photoAttributes, $previousImage) {
                    $dish->update($photoAttributes);

                    if ($previousImage && $previousImage !== $photoAttributes['image_url']) {
                        DietPlanItem::where('custom_image_url', $previousImage)
                            ->update(['custom_image_url' => $photoAttributes['image_url']]);
                    }
                });

                $stats['fotos actualizadas']++;
            }

            $missing = collect($recipe)->pluck(0)
                ->reject(fn ($ingredient) => isset(self::CUSTOM_FOODS[$ingredient]) || $foods->has($ingredient));

            if ($missing->isNotEmpty()) {
                $stats['omitidos']++;
                $this->command?->warn("«{$name}»: faltan en el catálogo {$missing->join(', ')}. Ejecuta FoodSeeder e IncmnszCatalogSeeder; el platillo queda como estaba.");

                continue;
            }

            $rows = array_map(fn (array $ingredient) => $this->row($ingredient, $foods), $recipe);

            if ($this->signature($dish->items()->get()) === $this->signature(collect($rows))) {
                $stats['sin cambios']++;

                continue;
            }

            DB::transaction(function () use ($dish, $rows) {
                $dish->items()->delete();
                $dish->items()->createMany($rows);
            });

            $stats['con receta nueva']++;
        }

        $this->command?->info('Platillos: '.collect($stats)->map(fn ($count, $label) => "$count $label")->join(', ').'.');
    }

    /** «Autor · Licencia · Wikimedia Commons» (null si la foto no tiene crédito registrado). */
    private function credit(string $dish): ?string
    {
        if (! isset(self::PHOTOS[$dish])) {
            return null;
        }

        [$author, $license] = self::PHOTOS[$dish];

        return mb_substr($author.' · '.$license.' · Wikimedia Commons', 0, 255);
    }

    /** Fila de diet_plan_dish_items: del catálogo (food_id) o personalizada (custom_* = aporte de sus gramos). */
    private function row(array $ingredient, $foods): array
    {
        [$name, $grams] = $ingredient;

        if (! isset(self::CUSTOM_FOODS[$name])) {
            return ['food_id' => $foods[$name]->id, 'grams' => $grams];
        }

        [$kcal, $protein, $carbs, $lipids] = self::CUSTOM_FOODS[$name];
        $factor = $grams / 100;

        return [
            'custom_name' => $name,
            'custom_kcal' => round($kcal * $factor, 2),
            'custom_protein_g' => round($protein * $factor, 2),
            'custom_carbs_g' => round($carbs * $factor, 2),
            'custom_lipids_g' => round($lipids * $factor, 2),
            'grams' => $grams,
        ];
    }

    /** Huella de una receta para saber si ya está cargada tal cual. */
    private function signature($items): string
    {
        return collect($items)
            ->map(function ($item) {
                $item = $item instanceof DietPlanDishItem ? $item->getAttributes() : $item;

                return implode('|', [
                    $item['food_id'] ?? '',
                    $item['custom_name'] ?? '',
                    number_format((float) $item['grams'], 2, '.', ''),
                    isset($item['custom_kcal']) ? number_format((float) $item['custom_kcal'], 2, '.', '') : '',
                ]);
            })
            ->sort()
            ->implode(';');
    }
}
