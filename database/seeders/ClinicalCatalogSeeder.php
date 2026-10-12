<?php

namespace Database\Seeders;

use App\Models\Allergen;
use App\Models\Pathology;
use Illuminate\Database\Seeder;

/**
 * Catálogos clínicos globales: pathologies y allergens.
 *
 * Solo INSERTA filas (no cambia la estructura). Sin ellos, la sección
 * "Historial clínico" del formulario de pacientes no tiene opciones,
 * porque patient_pathologies y patient_allergens requieren un id del
 * catálogo. Se puede ejecutar varias veces: busca por nombre.
 *
 *   php artisan db:seed --class=ClinicalCatalogSeeder
 *
 * (Mismo contenido en database/sql/catalogos_clinicos.sql para phpMyAdmin.)
 */
class ClinicalCatalogSeeder extends Seeder
{
    public const PATHOLOGIES = [
        'Diabetes mellitus tipo 1' => 'Deficiencia de producción de insulina; requiere control de carbohidratos y horarios de comida.',
        'Diabetes mellitus tipo 2' => 'Resistencia a la insulina con glucosa elevada; control de carbohidratos e índice glucémico.',
        'Prediabetes / resistencia a la insulina' => 'Glucosa alterada en ayuno o curva; etapa reversible con alimentación y actividad física.',
        'Hipertensión arterial' => 'Presión arterial elevada; limitar sodio y favorecer patrón tipo DASH.',
        'Dislipidemia' => 'Colesterol y/o triglicéridos elevados; cuidar grasas saturadas, trans y azúcares.',
        'Obesidad' => 'Exceso de grasa corporal (IMC ≥ 30) con riesgo metabólico asociado.',
        'Síndrome de ovario poliquístico (SOP)' => 'Trastorno endocrino frecuentemente asociado a resistencia a la insulina.',
        'Hipotiroidismo' => 'Función tiroidea disminuida; puede afectar el gasto energético.',
        'Hipertiroidismo' => 'Función tiroidea aumentada; suele elevar el gasto energético.',
        'Enfermedad renal crónica' => 'Función renal disminuida; control de proteína, sodio, potasio y fósforo según etapa.',
        'Hígado graso (esteatosis hepática)' => 'Acumulación de grasa en el hígado; asociada a resistencia a la insulina.',
        'Hiperuricemia / gota' => 'Ácido úrico elevado; limitar purinas, alcohol y fructosa añadida.',
        'Gastritis' => 'Inflamación de la mucosa gástrica; evitar irritantes según tolerancia.',
        'Enfermedad por reflujo gastroesofágico (ERGE)' => 'Reflujo ácido frecuente; cuidar volumen de comidas, grasas e irritantes.',
        'Síndrome de intestino irritable' => 'Trastorno digestivo funcional; puede requerir dieta baja en FODMAP.',
        'Enfermedad celíaca' => 'Reacción autoinmune al gluten; requiere dieta estricta sin gluten.',
        'Anemia' => 'Hemoglobina baja; revisar aporte de hierro, B12 y folato.',
        'Enfermedad cardiovascular' => 'Antecedente cardíaco o vascular; patrón cardioprotector.',
    ];

    public const ALLERGENS = [
        'Gluten (trigo, cebada, centeno)',
        'Leche (proteína de la leche)',
        'Lactosa (intolerancia)',
        'Huevo',
        'Cacahuate',
        'Nueces y frutos de cáscara',
        'Pescado',
        'Mariscos (crustáceos y moluscos)',
        'Soya',
        'Ajonjolí (sésamo)',
        'Sulfitos',
    ];

    public function run(): void
    {
        foreach (self::PATHOLOGIES as $name => $description) {
            Pathology::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        foreach (self::ALLERGENS as $name) {
            Allergen::firstOrCreate(['name' => $name]);
        }
    }
}
