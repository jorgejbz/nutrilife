/**
 * ==========================================================
 * EVALUATIONS CREATE
 * Vista previa en vivo de las medidas y cálculo automático
 * del IMC (peso / estatura²) mientras se llena el formulario.
 * ==========================================================
 */

document.addEventListener('DOMContentLoaded', function () {

    const weight = document.getElementById('weight');
    const height = document.getElementById('height');
    const bodyFat = document.getElementById('body_fat');
    const muscle = document.getElementById('muscle_mass');
    const notes = document.getElementById('notes');

    const waist = document.getElementById('waist');
    const hip = document.getElementById('hip');
    const chest = document.getElementById('chest');
    const arm = document.getElementById('arm');
    const thigh = document.getElementById('thigh');

    if (!weight) {
        return;
    }

    function displayValue(value, fallback = '—') {
        return value && value.trim() !== '' ? value : fallback;
    }

    function updatePreview() {
        document.getElementById('previewWeight').textContent = displayValue(weight.value);
        document.getElementById('previewFat').textContent = displayValue(bodyFat.value);
        document.getElementById('previewMuscle').textContent = displayValue(muscle.value);
        document.getElementById('previewWaist').textContent = displayValue(waist.value);
        document.getElementById('previewHip').textContent = displayValue(hip.value);
        document.getElementById('previewChest').textContent = displayValue(chest.value);
        document.getElementById('previewArm').textContent = displayValue(arm.value);
        document.getElementById('previewThigh').textContent = displayValue(thigh.value);

        /* --- Calcular IMC --- */
        const weightValue = parseFloat(weight.value);
        const heightValue = parseFloat(height.value);

        const bmiElement = document.getElementById('previewBmi');
        const statusElement = document.getElementById('bmiStatus');
        const descriptionElement = document.getElementById('bmiDescription');

        if (weightValue > 0 && heightValue > 0) {
            const heightMeters = heightValue / 100;
            const bmi = weightValue / (heightMeters * heightMeters);

            bmiElement.textContent = bmi.toFixed(1);
            descriptionElement.textContent = 'IMC preliminar calculado con peso y estatura.';

            if (bmi < 18.5) {
                statusElement.textContent = 'Bajo peso';
            } else if (bmi < 25) {
                statusElement.textContent = 'Rango normal';
            } else if (bmi < 30) {
                statusElement.textContent = 'Sobrepeso';
            } else {
                statusElement.textContent = 'IMC elevado';
            }
        } else {
            bmiElement.textContent = '—';
            statusElement.textContent = 'Esperando datos';
            descriptionElement.textContent = 'Ingresa peso y estatura para obtener el cálculo.';
        }
    }

    function updateNotesCounter() {
        document.getElementById('notesCount').textContent = notes.value.length;
    }

    [weight, height, bodyFat, muscle, waist, hip, chest, arm, thigh].forEach(function (input) {
        if (input) {
            input.addEventListener('input', updatePreview);
        }
    });

    if (notes) {
        notes.addEventListener('input', updateNotesCounter);
    }

    updatePreview();
    updateNotesCounter();

});
