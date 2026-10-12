-- =====================================================================
-- NutriLife · Catálogos clínicos (pathologies y allergens)
-- Solo INSERTA datos; no cambia la estructura. Se puede ejecutar varias
-- veces: no duplica (busca por nombre).
-- Equivale a: php artisan db:seed --class=ClinicalCatalogSeeder
-- =====================================================================

START TRANSACTION;

-- Patologías (18)
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Diabetes mellitus tipo 1', 'Deficiencia de producción de insulina; requiere control de carbohidratos y horarios de comida.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Diabetes mellitus tipo 1');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Diabetes mellitus tipo 2', 'Resistencia a la insulina con glucosa elevada; control de carbohidratos e índice glucémico.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Diabetes mellitus tipo 2');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Prediabetes / resistencia a la insulina', 'Glucosa alterada en ayuno o curva; etapa reversible con alimentación y actividad física.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Prediabetes / resistencia a la insulina');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Hipertensión arterial', 'Presión arterial elevada; limitar sodio y favorecer patrón tipo DASH.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Hipertensión arterial');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Dislipidemia', 'Colesterol y/o triglicéridos elevados; cuidar grasas saturadas, trans y azúcares.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Dislipidemia');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Obesidad', 'Exceso de grasa corporal (IMC ≥ 30) con riesgo metabólico asociado.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Obesidad');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Síndrome de ovario poliquístico (SOP)', 'Trastorno endocrino frecuentemente asociado a resistencia a la insulina.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Síndrome de ovario poliquístico (SOP)');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Hipotiroidismo', 'Función tiroidea disminuida; puede afectar el gasto energético.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Hipotiroidismo');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Hipertiroidismo', 'Función tiroidea aumentada; suele elevar el gasto energético.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Hipertiroidismo');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Enfermedad renal crónica', 'Función renal disminuida; control de proteína, sodio, potasio y fósforo según etapa.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Enfermedad renal crónica');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Hígado graso (esteatosis hepática)', 'Acumulación de grasa en el hígado; asociada a resistencia a la insulina.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Hígado graso (esteatosis hepática)');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Hiperuricemia / gota', 'Ácido úrico elevado; limitar purinas, alcohol y fructosa añadida.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Hiperuricemia / gota');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Gastritis', 'Inflamación de la mucosa gástrica; evitar irritantes según tolerancia.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Gastritis');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Enfermedad por reflujo gastroesofágico (ERGE)', 'Reflujo ácido frecuente; cuidar volumen de comidas, grasas e irritantes.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Enfermedad por reflujo gastroesofágico (ERGE)');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Síndrome de intestino irritable', 'Trastorno digestivo funcional; puede requerir dieta baja en FODMAP.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Síndrome de intestino irritable');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Enfermedad celíaca', 'Reacción autoinmune al gluten; requiere dieta estricta sin gluten.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Enfermedad celíaca');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Anemia', 'Hemoglobina baja; revisar aporte de hierro, B12 y folato.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Anemia');
INSERT INTO pathologies (name, description, created_at, updated_at)
SELECT 'Enfermedad cardiovascular', 'Antecedente cardíaco o vascular; patrón cardioprotector.', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM pathologies WHERE name = 'Enfermedad cardiovascular');

-- Alérgenos e intolerancias (11)
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Gluten (trigo, cebada, centeno)', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Gluten (trigo, cebada, centeno)');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Leche (proteína de la leche)', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Leche (proteína de la leche)');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Lactosa (intolerancia)', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Lactosa (intolerancia)');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Huevo', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Huevo');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Cacahuate', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Cacahuate');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Nueces y frutos de cáscara', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Nueces y frutos de cáscara');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Pescado', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Pescado');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Mariscos (crustáceos y moluscos)', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Mariscos (crustáceos y moluscos)');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Soya', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Soya');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Ajonjolí (sésamo)', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Ajonjolí (sésamo)');
INSERT INTO allergens (name, created_at, updated_at)
SELECT 'Sulfitos', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM allergens WHERE name = 'Sulfitos');

COMMIT;

SELECT (SELECT COUNT(*) FROM pathologies) AS patologias, (SELECT COUNT(*) FROM allergens) AS alergenos;
