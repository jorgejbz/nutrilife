/**
 * ==========================================================
 * NUTRILIFE — CALENDARIO DE LOS CAMPOS DE FECHA
 *
 * Mejora progresiva de <input type="date"> y
 * <input type="datetime-local">: el campo nativo se queda tal
 * cual (mismo name, mismo formato Y-m-d / Y-m-d\TH:i, mismos
 * min, max y required, validación del navegador y escritura con
 * teclado) y solo se cambia el calendario que se abre.
 *
 * - Las fechas se manejan como año/mes/día, nunca con
 *   toISOString(), para que la zona horaria no mueva el día.
 * - Al elegir una fecha se dispara "input" y "change", así que
 *   los scripts de cada página (vista previa de la cita,
 *   "Fin no puede ser antes del inicio", etc.) siguen igual.
 * - Dentro de un <dialog> el calendario se inserta en el propio
 *   diálogo para no quedar tapado ni bloqueado.
 *
 * Un campo puede quedarse con el selector nativo con
 * data-native-picker.
 * ==========================================================
 */

(function () {

    const TYPES = ['date', 'datetime-local'];
    const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const WEEKDAYS = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];
    const WEEKDAYS_LONG = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
    const MOBILE = window.matchMedia('(max-width: 575.98px)');

    /* ------------------------------------------------------
     | Fechas como { y, m, d } (m de 0 a 11)
     * ------------------------------------------------------ */
    const pad = (n) => String(n).padStart(2, '0');

    function parse(value) {
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value || '');

        return match ? { y: +match[1], m: +match[2] - 1, d: +match[3] } : null;
    }

    const format = (date) => date.y + '-' + pad(date.m + 1) + '-' + pad(date.d);
    const compare = (a, b) => (a.y - b.y) || (a.m - b.m) || (a.d - b.d);
    const daysIn = (y, m) => new Date(y, m + 1, 0).getDate();
    const weekday = (y, m, d) => (new Date(y, m, d).getDay() + 6) % 7; // 0 = lunes

    function today() {
        const now = new Date();

        return { y: now.getFullYear(), m: now.getMonth(), d: now.getDate() };
    }

    function shift(date, days) {
        const moved = new Date(date.y, date.m, date.d + days);

        return { y: moved.getFullYear(), m: moved.getMonth(), d: moved.getDate() };
    }

    function shiftMonth(date, months) {
        const first = new Date(date.y, date.m + months, 1);
        const y = first.getFullYear();
        const m = first.getMonth();

        return { y, m, d: Math.min(date.d, daysIn(y, m)) };
    }

    /* ------------------------------------------------------
     | Estado del calendario abierto (solo uno a la vez)
     * ------------------------------------------------------ */
    let open = null;

    function limits(input) {
        return { min: parse(input.min), max: parse(input.max) };
    }

    function inRange(date, range) {
        return (!range.min || compare(date, range.min) >= 0)
            && (!range.max || compare(date, range.max) <= 0);
    }

    function clamp(date, range) {
        if (range.min && compare(date, range.min) < 0) {
            return { ...range.min };
        }

        if (range.max && compare(date, range.max) > 0) {
            return { ...range.max };
        }

        return date;
    }

    function timeOf(input) {
        const match = /T(\d{2}:\d{2})/.exec(input.value || '');

        return match ? match[1] : '';
    }

    function setValue(input, value) {
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /* ------------------------------------------------------
     | Construcción del panel
     * ------------------------------------------------------ */
    function buildPanel(input) {
        const isDateTime = input.type === 'datetime-local';
        const panel = document.createElement('div');
        const range = limits(input);
        const label = document.querySelector('label[for="' + input.id + '"]')?.textContent.replace('*', '').trim();

        panel.className = 'nl-datepicker' + (isDateTime ? ' nl-datepicker--time' : '');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', 'Calendario' + (label ? ': ' + label : ''));

        const minYear = range.min ? range.min.y : today().y - 100;
        const maxYear = range.max ? range.max.y : today().y + 10;
        let yearOptions = '';

        for (let y = maxYear; y >= minYear; y--) {
            yearOptions += '<option value="' + y + '">' + y + '</option>';
        }

        panel.innerHTML = `
            <div class="nl-datepicker__head">
                <button type="button" class="nl-datepicker__nav" data-nav="-1" aria-label="Mes anterior">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <div class="nl-datepicker__selects">
                    <select class="nl-datepicker__month" aria-label="Mes">
                        ${MONTHS.map((name, i) => '<option value="' + i + '">' + name + '</option>').join('')}
                    </select>
                    <select class="nl-datepicker__year" aria-label="Año">${yearOptions}</select>
                </div>
                <button type="button" class="nl-datepicker__nav" data-nav="1" aria-label="Mes siguiente">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            <div class="nl-datepicker__weekdays" aria-hidden="true">
                ${WEEKDAYS.map((day) => '<span>' + day + '</span>').join('')}
            </div>
            <div class="nl-datepicker__grid" role="grid"></div>
            ${isDateTime ? `
            <label class="nl-datepicker__time">
                <span><i class="far fa-clock" aria-hidden="true"></i> Hora</span>
                <input type="time" step="${input.step && +input.step >= 60 ? input.step : 60}">
            </label>` : ''}
            <div class="nl-datepicker__foot">
                <button type="button" class="nl-datepicker__link" data-action="today">Hoy</button>
                ${input.required ? '' : '<button type="button" class="nl-datepicker__link" data-action="clear">Borrar</button>'}
                ${isDateTime ? '<button type="button" class="nl-datepicker__apply" data-action="apply">Aplicar</button>' : ''}
            </div>`;

        return panel;
    }

    function render() {
        const { panel, input, view, selected, range } = open;
        const grid = panel.querySelector('.nl-datepicker__grid');
        const now = today();
        const first = weekday(view.y, view.m, 1);
        const total = daysIn(view.y, view.m);
        const rows = Math.ceil((first + total) / 7);
        let html = '';

        for (let i = 0; i < rows * 7; i++) {
            if (i % 7 === 0) {
                html += '<div class="nl-datepicker__row" role="row">';
            }

            const day = i - first + 1;

            if (day < 1 || day > total) {
                html += '<span class="nl-datepicker__day is-empty" role="gridcell"></span>';
            } else {
                const date = { y: view.y, m: view.m, d: day };
                const disabled = !inRange(date, range);
                const isSelected = selected && compare(date, selected) === 0;
                const isToday = compare(date, now) === 0;
                const isFocus = compare(date, view) === 0;
                const classes = ['nl-datepicker__day'];

                if (isSelected) classes.push('is-selected');
                if (isToday) classes.push('is-today');

                html += '<button type="button" role="gridcell" class="' + classes.join(' ') + '"'
                    + ' data-date="' + format(date) + '"'
                    + ' tabindex="' + (isFocus ? '0' : '-1') + '"'
                    + ' aria-selected="' + (isSelected ? 'true' : 'false') + '"'
                    + (isToday ? ' aria-current="date"' : '')
                    + (disabled ? ' disabled aria-disabled="true"' : '')
                    + ' aria-label="' + WEEKDAYS_LONG[weekday(view.y, view.m, day)] + ' ' + day + ' de ' + MONTHS[view.m].toLowerCase() + ' de ' + view.y + '">'
                    + day + '</button>';
            }

            if (i % 7 === 6) {
                html += '</div>';
            }
        }

        grid.innerHTML = html;
        grid.setAttribute('aria-label', MONTHS[view.m] + ' ' + view.y);

        const monthSelect = panel.querySelector('.nl-datepicker__month');
        const yearSelect = panel.querySelector('.nl-datepicker__year');

        if (!yearSelect.querySelector('option[value="' + view.y + '"]')) {
            yearSelect.insertAdjacentHTML('beforeend', '<option value="' + view.y + '">' + view.y + '</option>');
        }

        monthSelect.value = String(view.m);
        yearSelect.value = String(view.y);

        /* Sin flechas hacia meses fuera del rango permitido */
        const prevMonth = shiftMonth({ ...view, d: 1 }, -1);
        const nextMonth = shiftMonth({ ...view, d: 1 }, 1);
        panel.querySelector('[data-nav="-1"]').disabled = !!range.min
            && compare({ ...prevMonth, d: daysIn(prevMonth.y, prevMonth.m) }, range.min) < 0;
        panel.querySelector('[data-nav="1"]').disabled = !!range.max && compare(nextMonth, range.max) > 0;

        const todayButton = panel.querySelector('[data-action="today"]');
        todayButton.disabled = !inRange(now, range);

        if (input.type === 'datetime-local') {
            panel.querySelector('[data-action="apply"]').disabled = !selected;
        }
    }

    function focusDay() {
        open?.panel.querySelector('.nl-datepicker__day[tabindex="0"]')?.focus({ preventScroll: true });
    }

    /* ------------------------------------------------------
     | Posición: debajo del campo (o arriba si no cabe), siempre
     | dentro de la pantalla. En teléfono, hoja inferior.
     * ------------------------------------------------------ */
    function position(fitScroll) {
        if (!open) {
            return;
        }

        const { panel, input } = open;

        if (MOBILE.matches) {
            panel.classList.add('is-sheet');
            panel.style.top = panel.style.left = '';

            return;
        }

        panel.classList.remove('is-sheet');
        panel.style.maxHeight = '';

        const gap = 8;
        const width = panel.offsetWidth;
        const height = panel.offsetHeight;
        let anchor = input.getBoundingClientRect();

        /* No cabe ni arriba ni abajo (laptop de 768px de alto): se
           desplaza la página lo justo para que quepa debajo del campo,
           sin esconder el campo bajo el encabezado. */
        if (fitScroll === true && !input.closest('dialog')) {
            const fitsBelow = window.innerHeight - anchor.bottom >= height + gap * 2;
            const fitsAbove = anchor.top >= height + gap * 2;
            const needed = anchor.bottom + gap + height + gap - window.innerHeight;
            const header = document.querySelector('.app-header')?.getBoundingClientRect().bottom || 0;

            if (!fitsBelow && !fitsAbove && anchor.top - needed >= header + gap) {
                window.scrollBy(0, needed);
                anchor = input.getBoundingClientRect();
            }
        }

        const below = window.innerHeight - anchor.bottom - gap * 2;
        const above = anchor.top - gap * 2;
        let top;

        if (below >= height || below >= above) {
            top = anchor.bottom + gap;
            panel.style.maxHeight = Math.max(below, 200) + 'px';
        } else {
            panel.style.maxHeight = above + 'px';
            top = anchor.top - gap - Math.min(height, above);
        }

        const left = Math.min(Math.max(gap, anchor.left), window.innerWidth - width - gap);

        panel.style.top = Math.max(gap, top) + 'px';
        panel.style.left = Math.max(gap, left) + 'px';
    }

    /* ------------------------------------------------------
     | Abrir / cerrar
     * ------------------------------------------------------ */
    function openPicker(input, { focus = true } = {}) {
        if (open?.input === input) {
            return;
        }

        closePicker(false);

        const range = limits(input);
        const selected = parse(input.value);
        const panel = buildPanel(input);
        const host = input.closest('dialog') || document.body;
        const backdrop = document.createElement('div');

        backdrop.className = 'nl-datepicker-backdrop';

        open = {
            input,
            panel,
            backdrop,
            range,
            selected,
            view: clamp(selected || today(), range),
            time: timeOf(input),
        };

        host.appendChild(backdrop);
        host.appendChild(panel);

        const timeInput = panel.querySelector('.nl-datepicker__time input');

        if (timeInput) {
            timeInput.value = open.time || '09:00';
        }

        render();
        position(true);
        input.setAttribute('aria-expanded', 'true');
        input.closest('.nl-date-field')?.querySelector('.nl-date-trigger')?.setAttribute('aria-expanded', 'true');

        bindPanel(panel);

        if (focus) {
            focusDay();
        }
    }

    function closePicker(returnFocus = true) {
        if (!open) {
            return;
        }

        const { input, panel, backdrop } = open;

        panel.remove();
        backdrop.remove();
        input.setAttribute('aria-expanded', 'false');
        input.closest('.nl-date-field')?.querySelector('.nl-date-trigger')?.setAttribute('aria-expanded', 'false');
        open = null;

        if (returnFocus) {
            input.focus({ preventScroll: true });
        }
    }

    function choose(date) {
        if (!open || !inRange(date, open.range)) {
            return;
        }

        const { input, panel } = open;

        if (input.type === 'datetime-local') {
            /* Con hora: se marca el día y se confirma con "Aplicar" */
            open.selected = date;
            open.view = date;
            render();
            focusDay();

            return;
        }

        setValue(input, format(date));
        closePicker();
    }

    function apply() {
        if (!open?.selected) {
            return;
        }

        const time = open.panel.querySelector('.nl-datepicker__time input').value || '09:00';
        const input = open.input;

        setValue(input, format(open.selected) + 'T' + time);
        closePicker();

        /* Si la hora queda fuera del máximo/mínimo, el navegador lo avisa */
        if (!input.checkValidity()) {
            input.reportValidity();
        }
    }

    function bindPanel(panel) {
        panel.addEventListener('click', function (event) {
            const day = event.target.closest('.nl-datepicker__day[data-date]');
            const nav = event.target.closest('[data-nav]');
            const action = event.target.closest('[data-action]')?.dataset.action;

            if (day && !day.disabled) {
                choose(parse(day.dataset.date));
            } else if (nav && !nav.disabled) {
                open.view = clamp(shiftMonth(open.view, +nav.dataset.nav), open.range);
                render();
            } else if (action === 'today') {
                const now = today();
                open.view = now;
                choose(now);
            } else if (action === 'clear') {
                setValue(open.input, '');
                closePicker();
            } else if (action === 'apply') {
                apply();
            }
        });

        panel.addEventListener('change', function (event) {
            if (event.target.matches('.nl-datepicker__month, .nl-datepicker__year')) {
                const y = +panel.querySelector('.nl-datepicker__year').value;
                const m = +panel.querySelector('.nl-datepicker__month').value;
                open.view = clamp({ y, m, d: Math.min(open.view.d, daysIn(y, m)) }, open.range);
                render();
            }
        });

        panel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation(); // que no cierre también el <dialog> que lo contiene
                closePicker();

                return;
            }

            const day = event.target.closest('.nl-datepicker__day[data-date]');

            if (!day) {
                return;
            }

            const moves = {
                ArrowLeft: () => shift(open.view, -1),
                ArrowRight: () => shift(open.view, 1),
                ArrowUp: () => shift(open.view, -7),
                ArrowDown: () => shift(open.view, 7),
                Home: () => shift(open.view, -weekday(open.view.y, open.view.m, open.view.d)),
                End: () => shift(open.view, 6 - weekday(open.view.y, open.view.m, open.view.d)),
                PageUp: () => shiftMonth(open.view, event.shiftKey ? -12 : -1),
                PageDown: () => shiftMonth(open.view, event.shiftKey ? 12 : 1),
            };

            if (moves[event.key]) {
                event.preventDefault();
                open.view = clamp(moves[event.key](), open.range);
                render();
                focusDay();
            }
        });
    }

    /* ------------------------------------------------------
     | Preparar cada campo
     * ------------------------------------------------------ */
    function enhance(input) {
        if (input.dataset.nlDate || input.hasAttribute('data-native-picker') || !TYPES.includes(input.type)) {
            return;
        }

        input.dataset.nlDate = '1';
        input.classList.add('nl-date-input');
        input.setAttribute('aria-haspopup', 'dialog');
        input.setAttribute('aria-expanded', 'false');

        /* Envoltura propia: el botón del calendario se ubica respecto al
           campo y no respecto a su etiqueta o contenedor. */
        const field = document.createElement('span');
        field.className = 'nl-date-field';
        input.insertAdjacentElement('beforebegin', field);
        field.appendChild(input);

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'nl-date-trigger';
        trigger.setAttribute('aria-label', 'Abrir calendario');
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = '<i class="far fa-calendar-alt" aria-hidden="true"></i>';
        trigger.disabled = input.disabled || input.readOnly;
        input.insertAdjacentElement('afterend', trigger);

        trigger.addEventListener('click', function () {
            if (open?.input === input) {
                closePicker();
            } else {
                openPicker(input);
            }
        });

        /* Con el dedo no se enfoca el campo: así el teléfono no abre
           además su selector nativo ni el teclado. */
        let touchStart = null;

        input.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'touch' || event.pointerType === 'pen') {
                event.preventDefault();
                input.dataset.nlTouch = '1';
                touchStart = { x: event.clientX, y: event.clientY };
            } else {
                delete input.dataset.nlTouch;
                touchStart = null;
            }
        });

        /* Sin foco, el navegador puede mandar el "click" del toque al
           contenedor, así que con el dedo se abre al levantarlo (si no
           fue un desplazamiento). */
        input.addEventListener('pointerup', function (event) {
            if (!touchStart || input.disabled || input.readOnly) {
                return;
            }

            const moved = Math.abs(event.clientX - touchStart.x) + Math.abs(event.clientY - touchStart.y);
            touchStart = null;

            if (moved < 12) {
                openPicker(input);
            }
        });

        input.addEventListener('click', function (event) {
            if (input.disabled || input.readOnly) {
                return;
            }

            if (input.dataset.nlTouch) {
                event.preventDefault();
            }

            /* Con mouse se puede seguir escribiendo: el foco se queda en el campo */
            openPicker(input, { focus: !!input.dataset.nlTouch });
        });

        input.addEventListener('keydown', function (event) {
            if ((event.altKey && event.key === 'ArrowDown') || event.key === 'F4') {
                event.preventDefault();
                openPicker(input);
            } else if (event.key === 'Escape' && open?.input === input) {
                event.preventDefault();
                event.stopPropagation();
                closePicker();
            } else if (event.key === 'Tab' && open?.input === input && !event.shiftKey) {
                /* Tab desde el campo entra al calendario */
                event.preventDefault();
                focusDay();
            }
        });

        /* Si se escribe la fecha a mano, el calendario la sigue */
        input.addEventListener('input', function () {
            if (open?.input === input) {
                const typed = parse(input.value);
                open.selected = typed;
                open.view = clamp(typed || open.view, open.range);
                render();
            }
        });
    }

    function enhanceAll(root) {
        root.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach(enhance);
    }

    /* Cerrar al hacer clic fuera, al salir con Tab o al cambiar el tamaño */
    document.addEventListener('pointerdown', function (event) {
        if (!open) {
            return;
        }

        const inside = open.panel.contains(event.target)
            || open.input === event.target
            || open.input.closest('.nl-date-field')?.querySelector('.nl-date-trigger')?.contains(event.target);

        if (!inside) {
            closePicker(false);
        }
    });

    document.addEventListener('focusin', function (event) {
        if (open && !open.panel.contains(event.target) && event.target !== open.input) {
            closePicker(false);
        }
    });

    window.addEventListener('resize', position);
    window.addEventListener('scroll', position, true);
    MOBILE.addEventListener?.('change', position);

    /* El <dialog> que contiene el campo se cierra: también el calendario */
    document.addEventListener('close', function (event) {
        if (open && event.target.contains?.(open.input)) {
            closePicker(false);
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => enhanceAll(document));
    } else {
        enhanceAll(document);
    }

    window.NutriDatePicker = { enhance: enhanceAll };

})();
