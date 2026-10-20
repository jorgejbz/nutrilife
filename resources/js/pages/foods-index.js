/**
 * ==========================================================
 * NUTRILIFE — CATÁLOGO DE ALIMENTOS (resources/views/foods/index)
 *
 *  - Los selects de filtro envían el formulario al cambiar.
 *  - Estado de carga mientras se recarga con nuevos filtros o página.
 *  - En celular, los filtros se pliegan detrás del botón "Filtros".
 *  - Diálogo de detalle con los datos ya cargados en la página
 *    (no hace peticiones extra).
 * ==========================================================
 */

(function () {

    const form = document.getElementById('foodFilters');
    const results = document.getElementById('foodResults');
    const loading = results?.querySelector('.food-loading');

    const showLoading = () => {
        if (!results || !loading) {
            return;
        }
        results.setAttribute('aria-busy', 'true');
        results.classList.add('is-loading');
        loading.hidden = false;
    };

    // Al volver con "Atrás" el navegador puede restaurar la página con el
    // estado de carga activo: se limpia.
    window.addEventListener('pageshow', () => {
        results?.removeAttribute('aria-busy');
        results?.classList.remove('is-loading');
        if (loading) {
            loading.hidden = true;
        }
    });

    /* ------------------------------------------------------
     | Filtros
     * ------------------------------------------------------ */
    if (form) {
        form.querySelectorAll('select[data-auto-submit]').forEach((select) => {
            select.addEventListener('change', () => form.requestSubmit());
        });

        form.addEventListener('submit', () => {
            // La página siempre vuelve a la 1 al cambiar filtros (el form no
            // envía "page"). Los campos vacíos o con el valor por defecto no se
            // envían para que la URL quede limpia (/alimentos?grupo=3).
            const skipped = Array.from(form.elements).filter((element) => element.name && (
                element.value.trim() === '' || (element.name === 'orden' && element.value === 'nombre')
            ));
            skipped.forEach((element) => { element.disabled = true; });
            setTimeout(() => skipped.forEach((element) => { element.disabled = false; }), 0);
            showLoading();
        });

        // Panel plegable en pantallas angostas
        const toggle = form.querySelector('.food-filters-toggle');
        const panel = document.getElementById('foodFilterPanel');
        const narrow = window.matchMedia('(max-width: 575.98px)');

        const syncPanel = () => {
            if (!toggle || !panel) {
                return;
            }

            if (narrow.matches) {
                toggle.hidden = false;
                const open = toggle.getAttribute('aria-expanded') === 'true';
                panel.hidden = !open;
            } else {
                toggle.hidden = true;
                panel.hidden = false;
            }
        };

        toggle?.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(open));
            panel.hidden = !open;
            if (open) {
                panel.querySelector('select')?.focus();
            }
        });

        narrow.addEventListener('change', syncPanel);
        syncPanel();
    }

    document.querySelectorAll('[data-food-nav]').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (!event.metaKey && !event.ctrlKey && !event.shiftKey && event.button === 0) {
                showLoading();
            }
        });
    });

    /* ------------------------------------------------------
     | Detalle del alimento
     * ------------------------------------------------------ */
    const dialog = document.getElementById('foodDetailDialog');

    if (!dialog) {
        return;
    }

    let foods = [];

    try {
        foods = JSON.parse(document.getElementById('foodDetailData')?.textContent || '[]');
    } catch (error) {
        foods = [];
    }

    const byId = new Map(foods.map((food) => [String(food.id), food]));
    const number = new Intl.NumberFormat('es-MX', { maximumFractionDigits: 1 });
    const field = (name) => dialog.querySelector(`[data-detail="${name}"]`);
    let opener = null;

    const per100 = (value, food) => (food.portion_grams > 0 ? (value * 100) / food.portion_grams : null);

    const cell = (value, unit) => {
        const td = document.createElement('td');
        if (value === null || value === undefined) {
            td.textContent = 'Sin dato';
            td.className = 'is-empty';
        } else {
            td.textContent = `${number.format(value)} ${unit}`;
        }
        return td;
    };

    const fill = (food) => {
        const group = field('group');
        group.innerHTML = '';
        const icon = document.createElement('i');
        icon.className = `fas ${food.icon}`;
        icon.setAttribute('aria-hidden', 'true');
        group.append(icon, ` ${food.group || 'Sin grupo'}`);

        field('name').textContent = food.name;
        field('portion').textContent = `Porción de referencia: ${food.portion ? `${food.portion} · ` : ''}${number.format(food.portion_grams)} g`;
        field('portion-head').textContent = `Por porción (${number.format(food.portion_grams)} g)`;

        const rows = [
            ['Energía', food.kcal, 'kcal'],
            ['Proteína', food.protein, 'g'],
            ['Carbohidratos', food.carbs, 'g'],
            ['Grasas', food.lipids, 'g'],
            ['Fibra', food.fiber, 'g'],
            ['Sodio', food.sodium, 'mg'],
        ];

        const body = field('rows');
        body.innerHTML = '';
        rows.forEach(([label, value, unit]) => {
            const tr = document.createElement('tr');
            const th = document.createElement('th');
            th.scope = 'row';
            th.textContent = label;
            tr.append(th, cell(value, unit), cell(value === null ? null : per100(value, food), unit));
            body.append(tr);
        });

        field('gi').textContent = food.glycemic_index !== null && food.glycemic_index !== undefined
            ? String(food.glycemic_index)
            : 'Sin dato';
        field('allergens').textContent = food.allergens.length ? food.allergens.join(', ') : 'Ninguno registrado';
        field('source').textContent = food.source || 'Sin dato';
    };

    const open = (button) => {
        const food = byId.get(button.dataset.foodDetail);
        if (!food) {
            return;
        }

        opener = button;
        fill(food);
        document.documentElement.classList.add('nl-dialog-open');

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
    };

    const close = () => {
        if (typeof dialog.close === 'function') {
            dialog.close();
        } else {
            dialog.removeAttribute('open');
            dialog.dispatchEvent(new Event('close'));
        }
    };

    document.querySelectorAll('[data-food-detail]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });

    dialog.querySelectorAll('[data-close-dialog]').forEach((button) => button.addEventListener('click', close));

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            close();
        }
    });

    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove('nl-dialog-open');
        opener?.focus();
    });

})();
