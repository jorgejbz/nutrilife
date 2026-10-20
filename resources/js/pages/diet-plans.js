/**
 * ==========================================================
 * NUTRILIFE — PLANES ALIMENTICIOS
 * index / create / edit / show (resources/views/diet-plans)
 *
 *  - (La confirmación de borrado, data-confirm, vive en forms.js.)
 *  - Filtros del listado que se aplican al cambiar.
 *  - Formulario: kcal que aportan los macros y texto del switch.
 *  - Constructor: diálogo para agregar alimentos del catálogo
 *    (búsqueda, grupos, vista previa del aporte) y edición
 *    rápida de gramos.
 *
 * Todo cálculo que se guarda lo hace el servidor; aquí solo hay
 * ayudas visuales.
 * ==========================================================
 */

(function () {

    const KCAL_PER_GRAM = { protein: 4, carbs: 4, lipids: 9 };

    const numberFormat = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 });
    const decimalFormat = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });

    const normalize = (text) => (text || '')
        .toString()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();

    /* ------------------------------------------------------
     | Listado: filtros automáticos
     * ------------------------------------------------------ */
    document.querySelectorAll('form[data-auto-submit]').forEach((form) => {
        // El buscador se envía con Enter; selects y botones al cambiar.
        form.addEventListener('change', (event) => {
            if (event.target.type !== 'search') {
                form.submit();
            }
        });
    });

    /* ------------------------------------------------------
     | Formulario crear / editar
     * ------------------------------------------------------ */
    const planForm = document.getElementById('dietPlanForm');

    if (planForm) {
        const kcalInput = planForm.querySelector('[data-macro="target_kcal"]');
        const macroInputs = {
            protein: planForm.querySelector('[data-macro="target_protein_g"]'),
            carbs: planForm.querySelector('[data-macro="target_carbs_g"]'),
            lipids: planForm.querySelector('[data-macro="target_lipids_g"]'),
        };
        const check = document.getElementById('macroCheck');
        const checkText = document.getElementById('macroCheckText');

        const updateMacroCheck = () => {
            const kcalFromMacros = {};
            let total = 0;

            Object.entries(macroInputs).forEach(([key, input]) => {
                const grams = parseFloat(input?.value) || 0;
                kcalFromMacros[key] = grams * KCAL_PER_GRAM[key];
                total += kcalFromMacros[key];
            });

            if (total <= 0) {
                check.hidden = true;
                return;
            }

            check.hidden = false;

            Object.entries(kcalFromMacros).forEach(([key, kcal]) => {
                const bar = check.querySelector(`[data-split="${key}"]`);
                bar.style.width = `${(kcal / total) * 100}%`;
            });

            const pct = (key) => Math.round((kcalFromMacros[key] / total) * 100);
            const target = parseFloat(kcalInput?.value) || 0;
            let message = `Los macros aportan ${numberFormat.format(total)} kcal `
                + `(proteína ${pct('protein')}%, carbohidratos ${pct('carbs')}%, grasas ${pct('lipids')}%).`;

            if (target > 0) {
                const diff = total - target;
                check.classList.toggle('is-off', Math.abs(diff) > target * 0.05);
                message += Math.abs(diff) <= target * 0.05
                    ? ' Coinciden con las calorías objetivo.'
                    : ` Difieren ${numberFormat.format(Math.abs(diff))} kcal de las calorías objetivo.`;
            }

            checkText.textContent = message;
        };

        [kcalInput, ...Object.values(macroInputs)].forEach((input) => {
            input?.addEventListener('input', updateMacroCheck);
        });

        updateMacroCheck();

        // Fin no puede ser antes del inicio
        const startsAt = planForm.querySelector('#starts_at');
        const endsAt = planForm.querySelector('#ends_at');

        startsAt?.addEventListener('change', () => {
            if (endsAt) {
                endsAt.min = startsAt.value;
            }
        });

        // Texto del switch Activo / Finalizado
        const activeSwitch = planForm.querySelector('#planActive');
        const activeText = planForm.querySelector('#planActiveText');

        activeSwitch?.addEventListener('change', () => {
            activeText.textContent = activeSwitch.checked ? activeText.dataset.on : activeText.dataset.off;
        });
    }

    /* ------------------------------------------------------
     | Constructor: edición rápida de gramos
     * ------------------------------------------------------ */
    document.querySelectorAll('.diet-grams-form').forEach((form) => {
        const input = form.querySelector('input[name="grams"]');

        const sync = () => {
            form.classList.toggle('is-dirty', input.value !== input.dataset.original);
        };

        input.addEventListener('input', sync);
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                input.value = input.dataset.original;
                sync();
            }
        });
    });

    /* ------------------------------------------------------
     | Diálogos genéricos (p. ej. "Duplicar / asignar")
     |   <button data-open-dialog="idDelDialog">
     |   <button data-close-dialog> dentro del <dialog>
     * ------------------------------------------------------ */
    // Mientras hay un diálogo abierto la página de atrás no se desplaza
    // (antes la rueda del ratón movía el plan detrás del diálogo).
    const lockPage = () => document.documentElement.classList.add('nl-dialog-open');
    const unlockPage = () => {
        if (!document.querySelector('dialog.diet-dialog[open]')) {
            document.documentElement.classList.remove('nl-dialog-open');
        }
    };

    document.querySelectorAll('dialog.diet-dialog').forEach((target) => {
        target.addEventListener('close', unlockPage);
    });

    const showDialog = (target) => {
        lockPage();
        if (typeof target.showModal === 'function') {
            target.showModal();
        } else {
            target.setAttribute('open', '');
        }
    };

    document.querySelectorAll('[data-open-dialog]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.openDialog);
            if (target) {
                showDialog(target);
            }
        });
    });

    document.querySelectorAll('dialog.diet-dialog:not(#foodDialog)').forEach((target) => {
        target.querySelectorAll('[data-close-dialog]').forEach((button) => {
            button.addEventListener('click', () => (typeof target.close === 'function' ? target.close() : target.removeAttribute('open')));
        });

        target.addEventListener('click', (event) => {
            if (event.target === target && typeof target.close === 'function') {
                target.close();
            }
        });

        if (target.hasAttribute('data-reopen')) {
            showDialog(target);
        }
    });

    /* ------------------------------------------------------
     | Constructor: editar nombre / horario / indicación de una comida
     * ------------------------------------------------------ */
    document.querySelectorAll('[data-toggle-meal-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById(button.dataset.toggleMealEdit);
            if (!form) {
                return;
            }

            form.hidden = !form.hidden;
            document.querySelectorAll(`[data-toggle-meal-edit="${form.id}"][aria-expanded]`).forEach((toggle) => {
                toggle.setAttribute('aria-expanded', String(!form.hidden));
            });

            if (!form.hidden) {
                form.querySelector('input[name="meal_name"]')?.focus();
            }
        });
    });

    /* ------------------------------------------------------
     | Constructor: diálogo "Agregar alimento"
     * ------------------------------------------------------ */
    const dialog = document.getElementById('foodDialog');

    if (!dialog) {
        return;
    }

    const dialogForm = document.getElementById('foodDialogForm');
    const mealLabel = dialog.querySelector('[data-meal-label]');
    const mealField = document.getElementById('dialogMeal');
    const search = document.getElementById('foodSearch');
    const options = Array.from(dialog.querySelectorAll('[data-food-option]'));
    const noResults = document.getElementById('foodNoResults');
    const gramsInput = document.getElementById('dialogGrams');
    const preview = document.getElementById('foodPreview');
    const groupChips = Array.from(dialog.querySelectorAll('.diet-chip[data-group]'));
    const groupSelect = document.getElementById('foodGroupSelect');
    const resultCount = document.getElementById('foodResultCount');
    const clearButtons = [document.getElementById('foodClearFilters'), ...dialog.querySelectorAll('[data-clear-food-filters]')].filter(Boolean);
    const optionList = document.getElementById('foodOptions');

    let catalog = [];

    try {
        catalog = JSON.parse(document.getElementById('foodCatalogData')?.textContent || '[]');
    } catch (error) {
        catalog = [];
    }

    const foodsById = new Map(catalog.map((food) => [String(food.id), food]));
    let activeGroup = '';
    let gramsTouched = false;

    const selectedFood = () => {
        const checked = dialog.querySelector('input[name="food_id"]:checked');
        return checked ? foodsById.get(checked.value) : null;
    };

    const applyFilters = () => {
        const term = normalize(search.value);
        let visible = 0;

        options.forEach((option) => {
            const matches = (!term || option.dataset.name.includes(term))
                && (!activeGroup || option.dataset.group === activeGroup);

            option.hidden = !matches;
            visible += matches ? 1 : 0;
        });

        noResults.hidden = visible > 0;

        if (resultCount) {
            const label = visible === 1 ? 'alimento' : 'alimentos';
            resultCount.textContent = (term || activeGroup)
                ? `${visible} de ${options.length} ${options.length === 1 ? 'alimento' : 'alimentos'}`
                : `${visible} ${label}`;
        }

        const filtered = Boolean(term || activeGroup);
        clearButtons.forEach((button) => {
            if (button.id === 'foodClearFilters') {
                button.hidden = !filtered;
            }
        });
    };

    const setGroup = (value, { scroll = true } = {}) => {
        activeGroup = value;
        groupChips.forEach((chip) => {
            const selected = chip.dataset.group === value;
            chip.classList.toggle('is-selected', selected);
            chip.setAttribute('aria-pressed', String(selected));
        });
        if (groupSelect) {
            groupSelect.value = value;
        }
        applyFilters();
        if (scroll && optionList) {
            optionList.scrollTop = 0;
        }
    };

    const updatePreview = () => {
        const food = selectedFood();
        const grams = parseFloat(gramsInput.value);

        options.forEach((option) => {
            option.classList.toggle('is-selected', option.querySelector('input').checked);
        });

        if (!food) {
            preview.textContent = 'Elige un alimento para ver su aporte.';
            return;
        }

        if (!grams || grams <= 0 || !food.portion_grams) {
            preview.textContent = `${food.name}: indica la cantidad en gramos.`;
            return;
        }

        const factor = grams / food.portion_grams;

        preview.innerHTML = '';
        const strong = document.createElement('strong');
        strong.textContent = `${numberFormat.format(food.kcal * factor)} kcal`;
        preview.append(
            strong,
            ` · P ${decimalFormat.format(food.protein * factor)} g`
            + ` · C ${decimalFormat.format(food.carbs * factor)} g`
            + ` · G ${decimalFormat.format(food.lipids * factor)} g`
            + ` · ${decimalFormat.format(factor)} ${factor === 1 ? 'porción' : 'porciones'}`
        );
    };

    const openDialog = (button, { keepValues = false } = {}) => {
        dialogForm.action = button.dataset.action;
        mealLabel.textContent = button.dataset.mealName;
        mealField.value = button.dataset.mealId;

        if (!keepValues) {
            dialogForm.querySelectorAll('input[name="food_id"]').forEach((input) => {
                input.checked = false;
            });
            gramsInput.value = '';
            search.value = '';
            gramsTouched = false;
        } else {
            gramsTouched = gramsInput.value !== '';
        }

        setGroup('', { scroll: false });
        updatePreview();
        lockPage();

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }

        // Si hay un alimento elegido (p. ej. tras un error de validación) se
        // muestra en la lista; si no, la lista empieza arriba.
        const checked = dialog.querySelector('input[name="food_id"]:checked');
        if (checked) {
            checked.closest('[data-food-option]')?.scrollIntoView({ block: 'nearest' });
        } else if (optionList) {
            optionList.scrollTop = 0;
        }

        // En pantallas táctiles no se abre el teclado de golpe: tapaba la lista.
        if (!window.matchMedia('(pointer: coarse)').matches) {
            setTimeout(() => search.focus(), 30);
        }
    };

    const closeDialog = () => {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
        }
    };

    document.querySelectorAll('[data-open-food-dialog]').forEach((button) => {
        button.addEventListener('click', () => openDialog(button));
    });

    dialog.querySelectorAll('[data-close-dialog]').forEach((button) => {
        button.addEventListener('click', closeDialog);
    });

    // Clic fuera del contenido cierra el diálogo
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            closeDialog();
        }
    });

    search.addEventListener('input', () => {
        applyFilters();
        if (optionList) {
            optionList.scrollTop = 0;
        }
    });

    clearButtons.forEach((button) => {
        button.addEventListener('click', () => {
            search.value = '';
            setGroup('');
            search.focus();
        });
    });

    groupSelect?.addEventListener('change', () => setGroup(groupSelect.value));

    // Enter en el buscador no envía el formulario
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const firstVisible = options.find((option) => !option.hidden);
            firstVisible?.querySelector('input').click();
            gramsInput.focus();
        }
    });

    groupChips.forEach((chip) => {
        chip.addEventListener('click', () => setGroup(chip.dataset.group));
    });

    dialogForm.addEventListener('change', (event) => {
        if (event.target.name === 'food_id') {
            const food = selectedFood();

            // Propone una porción del catálogo si aún no se escribieron gramos
            if (food && !gramsTouched && food.portion_grams) {
                gramsInput.value = food.portion_grams;
            }

            updatePreview();
        }
    });

    gramsInput.addEventListener('input', () => {
        gramsTouched = true;
        updatePreview();
    });

    // Si la validación del servidor falló, se reabre en la misma comida
    if (mealField.value) {
        const button = document.querySelector(`[data-open-food-dialog][data-meal-id="${mealField.value}"]`);

        if (button) {
            openDialog(button, { keepValues: true });
        }
    }

})();
