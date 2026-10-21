/**
 * ==========================================================
 * NUTRILIFE — PLANES ALIMENTICIOS
 * index / create / edit / show (resources/views/diet-plans)
 *
 * Comportamiento migrado de la versión nutrilife_planesAlim
 * (antes era JS en línea con onclick):
 *  - Constructor: formulario "Agregar comida" desplegable.
 *  - Drawers laterales: "Alimento" (catálogo, con buscador,
 *    porciones/gramos, cantidad y vista previa del aporte) con
 *    paso a "Agregar manualmente"; "Platillo" (tarjetas con
 *    buscador). Esc, la X o clic fuera los cierran.
 *
 * Más lo que ya tenía la versión final:
 *  - Filtros del listado que se aplican al cambiar.
 *  - Formulario: kcal que aportan los macros y texto del switch.
 *  - Editar comida y cambiar gramos (formularios desplegables).
 *  - Drawer "Duplicar / asignar".
 *  - Si la validación del servidor falla, el drawer se reabre en
 *    la misma comida con lo capturado.
 *
 * (La confirmación de borrado, data-confirm, y el bloqueo de
 * envíos dobles viven en forms.js.)
 *
 * Todo cálculo que se guarda lo hace el servidor; aquí solo hay
 * ayudas visuales y el cálculo de gramos a partir de porciones.
 * ==========================================================
 */

(function () {

    const KCAL_PER_GRAM = { protein: 4, carbs: 4, lipids: 9 };

    const numberFormat = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 0 });
    const decimalFormat = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });
    const qtyFormat = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 });

    const normalize = (text) => (text || '')
        .toString()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();

    const escapeHtml = (text) => (text || '').toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const isTouch = () => window.matchMedia('(pointer: coarse)').matches;

    /* ------------------------------------------------------
     | Listado: filtros automáticos
     * ------------------------------------------------------ */
    document.querySelectorAll('form.dp-filters[data-auto-submit]').forEach((form) => {
        // El buscador se envía con Enter; selects y estados al cambiar.
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
            if (!check || !checkText) {
                return;
            }

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

            const pct = (key) => Math.round((kcalFromMacros[key] / total) * 100);
            const target = parseFloat(kcalInput?.value) || 0;
            let message = `Los macros aportan ${numberFormat.format(total)} kcal `
                + `(proteína ${pct('protein')}%, carbohidratos ${pct('carbs')}%, grasas ${pct('lipids')}%).`;

            check.classList.remove('is-off');

            if (target > 0) {
                const diff = total - target;
                const off = Math.abs(diff) > target * 0.05;
                check.classList.toggle('is-off', off);
                message += off
                    ? ` Difieren ${numberFormat.format(Math.abs(diff))} kcal de las calorías objetivo.`
                    : ' Coinciden con las calorías objetivo.';
            }

            checkText.textContent = message;
        };

        [kcalInput, ...Object.values(macroInputs)].forEach((input) => {
            input?.addEventListener('input', updateMacroCheck);
        });

        updateMacroCheck();

        // La fecha de fin no puede ser anterior al inicio
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
     | Constructor
     * ------------------------------------------------------ */
    const builder = document.getElementById('dietBuilder');

    if (!builder) {
        return;
    }

    /* ---------- Agregar comida (formulario desplegable) ---------- */
    const mealForm = document.getElementById('mealForm');

    document.querySelectorAll('[data-dp-toggle-meal-form]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!mealForm) {
                return;
            }

            mealForm.hidden = !mealForm.hidden;
            document.querySelectorAll('[data-dp-toggle-meal-form][aria-expanded]').forEach((toggle) => {
                toggle.setAttribute('aria-expanded', String(!mealForm.hidden));
            });

            if (!mealForm.hidden) {
                mealForm.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                mealForm.querySelector('input[name="meal_name"]')?.focus();
            }
        });
    });

    /* ---------- Editar comida / cambiar gramos ---------- */
    document.querySelectorAll('[data-dp-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.dpToggle);

            if (!target) {
                return;
            }

            target.hidden = !target.hidden;
            document.querySelectorAll(`[data-dp-toggle="${target.id}"][aria-expanded]`).forEach((toggle) => {
                toggle.setAttribute('aria-expanded', String(!target.hidden));
            });

            if (!target.hidden) {
                const input = target.querySelector('input:not([type="hidden"])');
                input?.focus();
                input?.select?.();
            }
        });
    });

    /* ---------- Drawers ---------- */
    const drawers = Array.from(document.querySelectorAll('[data-dp-drawer]'));

    const lockPage = () => document.body.classList.add('dp-drawer-lock');
    const unlockPage = () => {
        if (!document.querySelector('.dp-drawer-overlay.is-open')) {
            document.body.classList.remove('dp-drawer-lock');
        }
    };

    let lastTrigger = null;

    const openDrawer = (overlay) => {
        drawers.forEach((other) => other !== overlay && closeDrawer(other, { restoreFocus: false }));
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        lockPage();
    };

    function closeDrawer(overlay, { restoreFocus = true } = {}) {
        if (!overlay.classList.contains('is-open')) {
            return;
        }

        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        unlockPage();

        if (restoreFocus && lastTrigger) {
            lastTrigger.focus();
        }
    }

    drawers.forEach((overlay) => {
        overlay.querySelectorAll('[data-dp-close]').forEach((button) => {
            button.addEventListener('click', () => closeDrawer(overlay));
        });

        // Clic fuera del panel
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeDrawer(overlay);
            }
        });
    });

    // Esc cierra el drawer abierto (si el diálogo de confirmación no está encima)
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        drawers.forEach((overlay) => closeDrawer(overlay));
    });

    /** Pone la ruta, el nombre y el id de la comida en el drawer. */
    const bindMeal = (overlay, button) => {
        overlay.querySelectorAll('[data-meal-label]').forEach((label) => {
            label.textContent = button.dataset.mealName;
        });
        overlay.querySelectorAll('[data-meal-input]').forEach((input) => {
            input.value = button.dataset.mealId;
        });
    };

    /* ======================================================
       DRAWER: ALIMENTO (catálogo + manual)
       ====================================================== */
    const foodDrawer = document.getElementById('foodDrawer');

    const food = (() => {
        if (!foodDrawer) {
            return null;
        }

        const form = document.getElementById('foodForm');
        const customForm = document.getElementById('customFoodForm');
        const divider = document.getElementById('customFoodDivider');
        const select = document.getElementById('foodSelect');
        const search = document.getElementById('foodSearch');
        const group = document.getElementById('foodGroupSelect');
        const count = document.getElementById('foodResultCount');
        const quantity = document.getElementById('foodQuantity');
        const quantityUnit = document.getElementById('foodQuantityUnit');
        const equivalence = document.getElementById('portionEquivalence');
        const gramsInput = document.getElementById('foodGrams');
        const preview = document.getElementById('foodPreview');
        const measureButtons = Array.from(foodDrawer.querySelectorAll('[data-measure]'));
        const options = Array.from(select.options).filter((option) => option.value);

        let mode = 'portion';

        const selected = () => {
            const option = select.options[select.selectedIndex];
            return option && option.value ? option : null;
        };

        const warningsOf = (option) => {
            try {
                return JSON.parse(option.dataset.warnings || '[]');
            } catch (error) {
                return [];
            }
        };

        /* Buscador y grupo: ocultan opciones del select */
        const applyFilters = () => {
            const term = normalize(search.value);
            const groupId = group?.value || '';
            let visible = 0;

            options.forEach((option) => {
                const matches = (!term || option.dataset.name.includes(term))
                    && (!groupId || option.dataset.group === groupId);

                option.hidden = !matches;
                option.disabled = !matches && !option.selected;
                visible += matches ? 1 : 0;
            });

            if (count) {
                const noun = options.length === 1 ? 'alimento' : 'alimentos';
                count.textContent = (term || groupId)
                    ? `${visible} de ${options.length} ${noun}`
                    : `${options.length} ${noun}`;
            }

            return visible;
        };

        /* Porciones o gramos */
        const setMode = (value, { keepQuantity = false } = {}) => {
            mode = value;

            measureButtons.forEach((button) => {
                const active = button.dataset.measure === value;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', String(active));
            });

            if (!keepQuantity) {
                if (value === 'portion') {
                    quantity.value = 1;
                } else {
                    const option = selected();
                    quantity.value = option ? (parseFloat(option.dataset.portion) || 100) : 100;
                }
            }

            quantity.min = value === 'portion' ? '0.25' : '0.1';
            quantity.step = 'any';

            updatePreview();
        };

        const changeQuantity = (direction) => {
            let value = parseFloat(quantity.value) || 0;
            const step = mode === 'portion' ? 0.5 : 10;

            value += direction * step;
            value = mode === 'portion' ? Math.max(0.5, value) : Math.max(1, value);

            quantity.value = Math.round(value * 100) / 100;
            updatePreview();
        };

        function updatePreview() {
            const option = selected();

            quantity.setCustomValidity('');

            if (!option) {
                preview.innerHTML = '<div class="dp-preview-placeholder">'
                    + '<i class="fas fa-apple-whole"></i>'
                    + '<span>Selecciona un alimento para ver sus valores nutricionales.</span>'
                    + '</div>';
                equivalence.textContent = 'Selecciona un alimento.';
                quantityUnit.textContent = mode === 'portion' ? 'porciones' : 'g';
                gramsInput.value = '';
                return;
            }

            const portionGrams = parseFloat(option.dataset.portion) || 0;
            const portionDescription = option.dataset.portionDescription || '1 porción';
            const amount = parseFloat(quantity.value) || 0;

            let grams;
            let visibleDescription;

            if (mode === 'portion') {
                grams = amount * portionGrams;
                const piece = portionDescription.toLowerCase().includes('pieza');
                quantityUnit.textContent = piece
                    ? (amount === 1 ? 'pieza' : 'piezas')
                    : (amount === 1 ? 'porción' : 'porciones');
                visibleDescription = `${qtyFormat.format(amount)} × ${portionDescription}`;
                equivalence.innerHTML = `<span>${escapeHtml(visibleDescription)}</span>`
                    + `<strong>≈ ${numberFormat.format(grams)} g</strong>`;
            } else {
                grams = amount;
                quantityUnit.textContent = 'g';
                const portions = portionGrams > 0 ? grams / portionGrams : 0;
                visibleDescription = `${numberFormat.format(grams)} g`;
                equivalence.innerHTML = `<span>${escapeHtml(visibleDescription)}</span>`
                    + `<strong>≈ ${qtyFormat.format(portions)} ${portions === 1 ? 'porción' : 'porciones'}</strong>`;
            }

            // diet_plan_items.grams DECIMAL(6,2): mín. 0.1 g, máx. 9999.99 g
            grams = Math.round(grams * 100) / 100;
            gramsInput.value = grams > 0 ? grams.toFixed(2) : '';

            if (grams < 0.1) {
                quantity.setCustomValidity('La cantidad debe ser de al menos 0.1 g.');
            } else if (grams > 9999.99) {
                quantity.setCustomValidity('La cantidad no puede ser mayor a 9999.99 g.');
            }

            const factor = portionGrams > 0 ? grams / portionGrams : 0;
            const kcal = (parseFloat(option.dataset.kcal) || 0) * factor;
            const protein = (parseFloat(option.dataset.protein) || 0) * factor;
            const carbs = (parseFloat(option.dataset.carbs) || 0) * factor;
            const fat = (parseFloat(option.dataset.fat) || 0) * factor;

            const warnings = warningsOf(option)
                .map((warning) => `<span class="dp-warning is-${escapeHtml(warning.type)}">`
                    + '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> '
                    + `${escapeHtml(warning.text)}</span>`)
                .join('');

            preview.innerHTML = `<div class="dp-food-preview-name">${escapeHtml(option.dataset.label)}</div>`
                + `<div class="dp-food-preview-portion">${escapeHtml(visibleDescription)} · ${numberFormat.format(grams)} g</div>`
                + `<div class="dp-food-preview-kcal">${numberFormat.format(kcal)} <small>kcal</small></div>`
                + '<div class="dp-food-preview-macros">'
                + `<div><span>PROTEÍNA</span><strong>${decimalFormat.format(protein)}g</strong></div>`
                + `<div><span>CARBOHIDRATOS</span><strong>${decimalFormat.format(carbs)}g</strong></div>`
                + `<div><span>GRASAS</span><strong>${decimalFormat.format(fat)}g</strong></div>`
                + '</div>'
                + (warnings ? `<div class="dp-food-preview-warnings">${warnings}</div>` : '');
        }

        /* Catálogo ⇄ alimento manual */
        const showPanel = (panel) => {
            const custom = panel === 'custom';
            form.hidden = custom;
            divider.hidden = custom;
            customForm.hidden = !custom;

            if (!isTouch()) {
                setTimeout(() => (custom ? customForm.querySelector('#customName') : search)?.focus(), 30);
            }
        };

        const reset = () => {
            form.reset();
            customForm.reset();
            select.value = '';
            search.value = '';
            if (group) {
                group.value = '';
            }
            applyFilters();
            setMode('portion');
        };

        const open = (button, { keepValues = false, panel = 'food' } = {}) => {
            lastTrigger = button;

            if (!keepValues) {
                reset();
            }

            form.action = button.dataset.action;
            customForm.action = button.dataset.customAction;
            bindMeal(foodDrawer, button);

            if (keepValues) {
                // Tras un error: alimento y gramos que se habían elegido
                applyFilters();
                if (selected() && gramsInput.value) {
                    quantity.value = parseFloat(gramsInput.value);
                    setMode('grams', { keepQuantity: true });
                } else {
                    setMode('portion');
                }
            }

            openDrawer(foodDrawer);
            showPanel(panel);
        };

        search.addEventListener('input', applyFilters);
        group?.addEventListener('change', applyFilters);

        // Enter en el buscador elige el primer alimento visible (no envía)
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                const first = options.find((option) => !option.hidden);

                if (first) {
                    select.value = first.value;
                    select.dispatchEvent(new Event('change'));
                    quantity.focus();
                }
            }
        });

        select.addEventListener('change', () => {
            applyFilters();
            setMode('portion');
        });

        measureButtons.forEach((button) => {
            button.addEventListener('click', () => setMode(button.dataset.measure));
        });

        foodDrawer.querySelectorAll('[data-quantity-step]').forEach((button) => {
            button.addEventListener('click', () => changeQuantity(parseFloat(button.dataset.quantityStep)));
        });

        quantity.addEventListener('input', updatePreview);

        foodDrawer.querySelectorAll('[data-dp-custom]').forEach((button) => {
            button.addEventListener('click', () => showPanel(button.dataset.dpCustom === 'show' ? 'custom' : 'food'));
        });

        // Antes de enviar: los gramos salen de la cantidad y la forma de medir
        form.addEventListener('submit', (event) => {
            updatePreview();

            if (!gramsInput.value || !quantity.checkValidity()) {
                event.preventDefault();
                quantity.reportValidity();
            }
        });

        return { open };
    })();

    /* ======================================================
       DRAWER: PLATILLO
       ====================================================== */
    const dishDrawer = document.getElementById('dishDrawer');

    const dish = (() => {
        if (!dishDrawer) {
            return null;
        }

        const form = dishDrawer.querySelector('[data-dp-form="dish"]');
        const search = document.getElementById('dishSearch');
        const hidden = document.getElementById('selectedDishId');
        const submit = document.getElementById('dishSubmit');
        const noResults = document.getElementById('dishNoResults');
        const cards = Array.from(dishDrawer.querySelectorAll('.dp-dish-card-item'));

        const select = (card) => {
            cards.forEach((other) => {
                const isSelected = other === card;
                other.classList.toggle('is-selected', isSelected);
                other.setAttribute('aria-selected', String(isSelected));
            });

            hidden.value = card ? card.dataset.dishId : '';
            submit.disabled = !hidden.value;
        };

        /* Búsqueda en tiempo real de platillos */
        const filter = () => {
            const term = normalize(search.value);
            let visible = 0;

            cards.forEach((card) => {
                card.hidden = Boolean(term) && !card.dataset.name.includes(term);
                visible += card.hidden ? 0 : 1;
            });

            noResults.hidden = visible > 0;
        };

        const open = (button, { keepValues = false } = {}) => {
            lastTrigger = button;
            form.action = button.dataset.action;
            bindMeal(dishDrawer, button);

            search.value = '';
            filter();

            const previous = keepValues
                ? cards.find((card) => card.dataset.dishId === hidden.value && !card.disabled)
                : null;
            select(previous || null);

            openDrawer(dishDrawer);
            previous?.scrollIntoView({ block: 'nearest' });

            if (!isTouch()) {
                setTimeout(() => search.focus(), 30);
            }
        };

        cards.forEach((card) => {
            card.addEventListener('click', () => {
                if (!card.disabled) {
                    select(card);
                }
            });
        });

        search.addEventListener('input', filter);

        // Enter en el buscador elige el primer platillo disponible
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                const first = cards.find((card) => !card.hidden && !card.disabled);

                if (first) {
                    select(first);
                    submit.focus();
                }
            }
        });

        form.addEventListener('submit', (event) => {
            if (!hidden.value) {
                event.preventDefault();
            }
        });

        return { open };
    })();

    /* ======================================================
       DRAWER: DUPLICAR / ASIGNAR
       ====================================================== */
    const duplicateDrawer = document.getElementById('duplicateDrawer');

    /* ---------- Botones que abren drawers ---------- */
    document.querySelectorAll('[data-dp-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const type = button.dataset.dpOpen;

            if (type === 'food') {
                food?.open(button);
            } else if (type === 'dish') {
                dish?.open(button);
            } else if (type === 'duplicate' && duplicateDrawer) {
                lastTrigger = button;
                openDrawer(duplicateDrawer);
                if (!isTouch()) {
                    setTimeout(() => duplicateDrawer.querySelector('select, input:not([type="hidden"])')?.focus(), 30);
                }
            }
        });
    });

    /* ---------- Reabrir tras un error de validación ---------- */
    const reopenFor = (overlay, type) => {
        const mealId = overlay?.dataset.reopenMeal;

        if (!mealId) {
            return;
        }

        const button = document.querySelector(`[data-dp-open="${type}"][data-meal-id="${mealId}"]`);

        if (!button) {
            return;
        }

        if (type === 'food') {
            food?.open(button, { keepValues: true, panel: overlay.dataset.reopenPanel === 'custom' ? 'custom' : 'food' });
        } else {
            dish?.open(button, { keepValues: true });
        }
    };

    reopenFor(foodDrawer, 'food');
    reopenFor(dishDrawer, 'dish');

    if (duplicateDrawer?.dataset.reopen) {
        openDrawer(duplicateDrawer);
    }

})();
