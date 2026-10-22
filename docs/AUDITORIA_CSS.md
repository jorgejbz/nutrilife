# Auditoría y reorganización del CSS (22/10/2026)

## Qué se revisó

| Origen | Antes | Resultado |
|---|---|---|
| `public/css/admin.css` (panel) | 17 735 líneas, 70 `@media` | 24 módulos en `admin/` + 4 archivos en `responsive/admin/` |
| `public/css/app.css` (landing) | 4 090 líneas, 24 `@media` | 6 módulos en `app/` + 3 en `responsive/app/` |
| `public/css/login.css` (autenticación) | 2 120 líneas, 8 `@media` | 4 módulos en `login/` + 3 en `responsive/login/` |
| `public/css/diet-plans.css` (planes) | 3 366 líneas, 12 `@media` | 6 módulos en `diet-plans/` + 4 en `responsive/diet-plans/` |
| `*-light.css` / `*-dark.css` (6) | 1 `@media` en `app-light.css` | `themes/`; la `@media` pasó a `responsive/app/laptop.css` |
| `nutrilife-tokens.css` | — | `base/tokens.css` |
| Bloques `<style>` en vistas propias | Ninguno (solo en vistas del paquete AdminLTE, que no se tocaron) | — |
| `style="…"` fijos en vistas | 22 | Convertidos a utilidades (`admin/components/utilities.css`) |
| `style="…"` dinámicos | Anchos en %, `--progress`, `--w` | Se quedan: su valor sale de los datos |
| JavaScript que crea clases | `appointments-index.js`, `forms.js`, `date-picker.js`, `diet-plans.js`, `theme.js`… | Incluidos en la búsqueda de clases usadas |

**Totales:**

| | Antes | Después |
|---|---|---|
| Archivos | 11 | 61 |
| Líneas | 27 988 | 24 768 |
| Reglas | 3 391 | 2 937 (17 son utilidades nuevas) |
| `!important` | 240 | 188 (17 de las utilidades, que sustituyen estilos en línea) |
| `@media` | 115 | 107 (se unieron bloques contiguos con la misma condición) |

El respaldo completo de las hojas originales está en
`storage/backups/css_antes_auditoria_20261022/`.

## Cómo se cargan ahora

`config/stylesheets.php` define el orden de cada sección, y lo leen
`partials/theme-styles.blade.php` y `diet-plans/partials/styles.blade.php`:

1. `base/tokens.css`
2. Módulos base de la sección, en el **mismo orden** que tenían dentro de la
   hoja original.
3. `themes/<sección>-light.css` y `themes/<sección>-dark.css`; theme.js
   deshabilita el que no está activo.
4. `responsive/<sección>/`: `desktop` → `laptop` → `tablet` → `mobile` →
   `preferences`. Van de mayor a menor ancho porque todas las consultas son
   `max-width`: el rango más estrecho debe quedar al final.

Los planes alimenticios cargan sus módulos y su responsive después de todo
lo anterior, como antes.

¿Por qué los módulos base no se reagruparon por tipo (botones juntos,
formularios juntos…)? Varias páginas **redefinen las mismas clases**, como
`.form-section`, `.form-actions`, `.hero-content h2`, `.progress-grid` y
`.last-section`, y hoy gana la definición que aparece más abajo. Reordenar
esas partes cambiaría el diseño de algunas pantallas. Por eso cada módulo es un
tramo contiguo del archivo original, con nombre según su contenido.

## Qué se eliminó

Se eliminó solo lo **comprobadamente** inútil (detalle en el anexo):

1. **Reglas sin uso:**
   - **259 reglas** (235 del panel, 17 de la landing y 7 de autenticación).
     Usan clases que no existen en ninguna vista Blade, en ningún JS ni PHP
     del proyecto ni en el paquete AdminLTE.
   - Antes de borrar cada clase se buscaron también clases dinámicas
     (`nl-status--{{ … }}`, `'mini-status ' + …`, `` `nl-ref--${…}` ``) y las
     que agregan Bootstrap y AdminLTE en tiempo de ejecución (`show`,
     `collapsing`, `sidebar-collapse`, `menu-open`, `is-invalid`…).
   - Un selector con `:is(…)`, `:where(…)` o `:has(…)` solo cuenta como sin
     uso si **todas** sus alternativas lo están. Por eso se conservó la regla
     de tamaños mínimos de «Tipografía del panel», aunque varias de sus
     alternativas ya no existan.
   - La mayor parte (~200) era la interfaz anterior de planes alimenticios
     (`diet-*`), sustituida por `.dp-*` (`docs/UNIFICACION_PLAN_ALIMENTICIO.md`).
   - También se fueron decoraciones de la landing (`.nutrition-decor*`,
     `.hero-light`), logotipos antiguos (`.brand-name`, `.*-logo-icon`) e
     insignias de estado viejas (`.status-badge`, `.status-dot`).
2. **11 selectores dentro de listas** que nunca coinciden con nada; el resto
   de cada lista se conservó.
3. **1 105 declaraciones sin efecto** (1 035 del panel y 70 de la landing):
   - Estaban anuladas **siempre**, en todos los anchos de pantalla, por una
     declaración posterior del mismo selector.
   - Ejemplo: `.progress-grid` de la ficha del paciente, con 3 ajustes
     responsive que la hoja de Progreso anulaba más abajo.
   - No se quitó ninguna que pudiera servir de respaldo para navegadores
     antiguos: se conservan si la que gana usa `min()`, `max()`, `clamp()`,
     `color-mix()`, `dvh`, `env()`, `-webkit-`…
4. **8 selectores** quitados de listas porque el mismo selector, más abajo,
   los anulaba siempre.

## Verificación de que nada cambió

1. **Simulación de la cascada:**
   - Para cada selector y propiedad se calculó el valor ganador en **~120
     anchos de pantalla**: todos los puntos de corte ±1 px, más 320, 360, 390,
     414, 1280, 1366, 1440 y 1920.
   - También con dos altos de pantalla, con y sin movimiento reducido, y con
     las hojas de tema detrás.
   - Se comparó el CSS original con el nuevo en el nuevo orden de carga:
     **0 diferencias** en las cuatro secciones.
2. **Selectores distintos que podrían competir:** 29 pares de selectores
   distintos cambiaron de orden relativo. Se revisaron a mano y son falsos
   positivos. Por ejemplo, `.stats-grid > *` con `.hero-icon`: el ícono nunca
   es hijo directo de esa cuadrícula.
3. **Estilo calculado en el navegador:**
   - Se abrieron 20 páginas reales en Edge sin interfaz: landing, login,
     registro, dashboard, pacientes (listado, ficha y alta), citas, consultas,
     pagos (listado y detalle), progreso, perfil, planes alimenticios (listado
     y constructor), alimentos, clínica (panel, pacientes y agenda) y cobros
     del administrador.
   - Se cargaron con el CSS original y con el nuevo, en modo claro y oscuro.
   - Se compararon **todas las propiedades calculadas de cada elemento**,
     incluidos `::before`, `::after` y `::placeholder`.
   - Anchos de 360 y 414 px (en un `iframe`, porque Edge sin interfaz no baja
     de ~496 px de ventana), además de 496, 768, 1024, 1366 y 1920 px.
   - Resultado: **280 de 280 combinaciones idénticas**, sin contar la landing
     después de subir sus letras.
   - Para que la prueba fuera repetible se bloqueó internet (fuentes e íconos
     del CDN) y se pausaron animaciones y transiciones igual en ambas
     versiones.
   - Esta prueba encontró un caso que la simulación no cubría: la regla
     `:is()` de «Tipografía del panel». Se corrigió antes de entregar.
4. **Pruebas:**
   - `tests/Feature/StylesheetsTest.php` comprueba que todas las hojas
     configuradas existan, que no haya hojas sueltas ni `@media` fuera de
     `responsive/`, y el orden base → temas → responsive.
   - Pasa la suite completa.

## Errores y conflictos corregidos

- **`@media` repartidas por todo el panel.** Las 107 `@media` viven ahora
  solo en `responsive/`. Antes, una regla base de otra página anulaba a veces
  el ajuste para teléfono de una página anterior. Esos casos se detectaron y
  se quitó la regla muerta.
- **Puntos de corte equivalentes unificados:** `767px` pasó a `767.98px` y
  `575px` a `575.98px`, los de Bootstrap. La diferencia solo existía con
  anchos fraccionarios. Los demás puntos de corte se conservaron tal cual
  (1100, 1050, 1000, 900, 700, 650, 600, 520, 500, 480, 430…); cambiarlos
  movería el diseño en esos anchos.
- **Estilos en línea fijos** pasaron a clases (`nl-mt-12`, `nl-p-0-4`…) con
  `!important`, igual que las utilidades de Bootstrap, para conservar la
  prioridad del estilo en línea.
- **Comentarios obsoletos:** las vistas, `chart-theme.js` y el README ya no
  mencionan `admin.css`, `login.css`, `app.css` ni `diet-plans.css`.
- **Rutas de imágenes:** las `url('../images/…')` se ajustaron a la nueva
  profundidad de carpetas y se comprobó que cada archivo existe.

## Lo que se conservó a propósito (y por qué)

- **170 `!important`.** La mayoría compite con estilos de AdminLTE o
  Bootstrap que llegan con más especificidad (sidebar, navbar, `.content-wrapper`,
  `a:hover`, tablas, `.form-control`). Quitarlos sin una revisión visual
  página por página en el navegador podría romper el panel. Los que se
  eliminaron (70) eran declaraciones sin efecto.
- **Clases que varias páginas redefinen** (`.form-section`, `.hero-content h2`,
  `.form-actions`…). Hoy la última definición gana en todas esas páginas.
  Darles un nombre propio por página sería un cambio de diseño, y se dejó
  documentado aquí para una siguiente revisión.
- **Reglas globales intencionales:** `a` (color de enlaces), `img` (imágenes
  responsivas) y `body`.
- **Puntos de corte no estándar:** mismos valores para no mover el diseño.
- **Vistas del paquete AdminLTE** (`resources/views/vendor/adminlte`): traen
  sus propios `<style>`; son de terceros y no se modificaron.
- **Componentes de Breeze** (`components/dropdown`, `modal`,
  `application-logo`): `style="display:none"` lo necesita Alpine (`x-show`).

## Cambio de diseño pedido aparte: letras de la landing

Después de la reorganización se subió **un poco** el texto pequeño de la
landing, a pedido. Es el único cambio visual de esta entrega:

- Textos de lectura (descripciones, respuestas de preguntas frecuentes,
  características de los planes, pasos, botones): **+2 px**. Por ejemplo, de
  9–11 px a 11–13 px.
- Rótulos pequeños (números, insignias en mayúsculas como «MÁS POPULAR» o
  «PRÓXIMAMENTE»): +2–3 px; ninguno queda por debajo de 9 px.
- Menú, botones de la barra y pie de página: +1 px.
- Las maquetas del panel dentro de la landing (`nlm-*`, vista del dashboard)
  no se tocaron: son ilustraciones en miniatura.

Se cambiaron 61 valores de `font-size` directamente en sus reglas, en
`app/layout.css`, `app/hero.css`, `app/hero-organic.css`,
`app/sections.css`, `app/sections-extra.css`, `app/mockups.css` y
`responsive/app/laptop.css`. Los textos de menos de 10 px pasaron de 21 a 6–8.
Se revisó la landing en 1366 px y en teléfono sin desbordes nuevos.

## Anexo · Detalle de lo eliminado

Generado por el script de la auditoría a partir de las hojas originales
(respaldo: `storage/backups/css_antes_auditoria_20261022/`).

### admin (`admin.css`)

<details><summary>Reglas cuyo selector usa una clase que no existe en ninguna vista, JS ni PHP (235)</summary>

Clases inexistentes: `.dashboard-footer__status`, `.dashboard-status-dot`, `.diet-add-meal`, `.diet-add-meal__time`, `.diet-avatar`, `.diet-badge`, `.diet-bar`, `.diet-bar--sm`, `.diet-check`, `.diet-clinical-grid`, `.diet-dialog--catalog`, `.diet-dialog-hint`, `.diet-dialog-search`, `.diet-dialog-status`, `.diet-dish-ingredients`, `.diet-dish-option`, `.diet-dish-thumb`, `.diet-empty`, `.diet-field`, `.diet-field-grid`, `.diet-filter-chips`, `.diet-filter-chips--scroll`, `.diet-filter-chips--wrap`, `.diet-filter-search`, `.diet-filters`, `.diet-fixed-patient`, `.diet-food-nutrients`, `.diet-food-option`, `.diet-food-options`, `.diet-food-text`, `.diet-form-grid`, `.diet-form-grid--macros`, `.diet-grams-field`, `.diet-grams-form`, `.diet-grams-input`, `.diet-grams-save`, `.diet-group-select`, `.diet-header-actions`, `.diet-icon-btn--danger`, `.diet-icon-btn--sm`, `.diet-instructions`, `.diet-instructions-head`, `.diet-instructions-list`, `.diet-item`, `.diet-item-main`, `.diet-item-sub`, `.diet-item-text`, `.diet-item-thumb`, `.diet-item-values`, `.diet-items`, `.diet-kcal`, `.diet-kicker`, `.diet-last-consultation`, `.diet-link`, `.diet-link-btn`, `.diet-list-header`, `.diet-macro`, `.diet-macro-badge`, `.diet-macro-check`, `.diet-macro-grid`, `.diet-macro-split`, `.diet-macro-top`, `.diet-meal`, `.diet-meal-edit`, `.diet-meal-edit__actions`, `.diet-meal-edit__grid`, `.diet-meal-edit__wide`, `.diet-meal-empty`, `.diet-meal-foot`, `.diet-meal-head`, `.diet-meal-icon`, `.diet-meal-list`, `.diet-meal-notes`, `.diet-meal-title`, `.diet-meal-totals`, `.diet-no-results`, `.diet-note`, `.diet-note--warning`, `.diet-patient-block`, `.diet-patient-goal`, `.diet-patient-head`, `.diet-percent`, `.diet-plan-row`, `.diet-preview`, `.diet-remaining`, `.diet-row-actions`, `.diet-row-goal`, `.diet-row-progress`, `.diet-row-title`, `.diet-section-head`, `.diet-show-header`, `.diet-show-meta`, `.diet-summary-head`, `.diet-tag`, `.diet-tag--allergen`, `.diet-target-chips`, `.diet-top-grid`, `.diet-unit`, `.diet-unit-input`, `.diet-warning`, `.diet-warning--allergen`, `.diet-warning--forbidden`, `.diet-warning--info`, `.diet-warning--limited`, `.main-header`, `.nl-btn--warn`, `.nl-profile-title`, `.payment-alert`, `.status-active`, `.status-badge`, `.status-dot`, `.status-inactive`

</details>

Selectores quitados de una lista (el resto de la lista se conserva):

- `.status-badge`
- `.diet-food-option:focus-within`
- `.diet-note`
- `.diet-note > i`
- `.diet-add-meal input:focus, .diet-grams-form input:focus, .diet-grams-input input:focus`
- `.diet-filters` · @media (max-width: 767.98px)
- `.status-badge.inactive, .status-inactive`
- `.status-badge.inactive span`
- `.diet-link-btn:focus-visible`

Selectores quitados de una lista porque otra regla del mismo selector, más abajo, los anulaba siempre:

- `.btn-liquid-secondary` · -
- `.heading-icon` · -
- `.progress-grid` · @media(max-width: 1100px)
- `.progress-grid` · @media(max-width: 767px)
- `.progress-grid` · @media(max-width: 480px)
- `.required` · -
- `.liquid-input input` · -
- `.content-wrapper` · -

<details><summary>Declaraciones sin efecto: 1035 en 243 reglas</summary>

Cada una estaba anulada, en todos los anchos de pantalla, por una declaración posterior
del **mismo selector** (misma especificidad). Ejemplos de selectores afectados:

- `body` (13)
- `.form-actions` (9)
- `.header-eyebrow` (7)
- `.ambient` (7)
- `.ambient-one` (7)
- `.ambient-two` (7)
- `.content-wrapper` (6)
- `.form-section` (6)
- `.content-wrapper, .content-header` (5)
- `.ambient-three` (5)
- `.last-section` (5)
- `.glass-shine` (4)
- `.btn-liquid-secondary` (4)
- `.liquid-input:focus-within` (4)
- `.input-error` (4)
- `.field-error` (4)
- `.content-header` (3)
- `.btn-liquid-primary` (3)
- `.hero-decoration` (3)
- `.btn-liquid-secondary:hover` (3)
- `.section-number` (3)
- `.form-group` (3)
- `.form-group label` (3)
- `.liquid-input` (3)
- `.input-icon` (3)
- `.liquid-input input[type="date"]` (3)
- `.liquid-textarea` (3)
- `.textarea-icon` (3)
- `.liquid-textarea textarea` (3)
- `.form-main-header` (3)
- `.btn-liquid-primary:hover` (2)
- `.glass-alert` (2)
- `.glass-alert-icon` (2)
- `.glass-alert strong` (2)
- `.glass-alert span` (2)
- `.hero-content` (2)
- `.hero-icon` (2)
- `.hero-content h2` (2)
- `.form-heading` (2)
- `.form-heading-icon` (2)

</details>

### app (`app.css`)

<details><summary>Reglas cuyo selector usa una clase que no existe en ninguna vista, JS ni PHP (17)</summary>

Clases inexistentes: `.hero-light`, `.landing-logo-icon`, `.nutrition-decor`, `.nutrition-decor--left`, `.nutrition-decor--right`, `.nutrition-decor--top-left`, `.nutrition-decor--top-right`

</details>

<details><summary>Declaraciones sin efecto: 70 en 42 reglas</summary>

Cada una estaba anulada, en todos los anchos de pantalla, por una declaración posterior
del **mismo selector** (misma especificidad). Ejemplos de selectores afectados:

- `.hero-container` (3)
- `.hero-section` (3)
- `.hero-content h1` (2)
- `.hero-description` (2)
- `.dashboard-preview` (2)
- `.preview-content` (2)
- `.preview-sidebar` (2)
- `.landing-navbar` (1)
- `.landing-navbar.scrolled` (1)
- `.landing-logo` (1)
- `.landing-logo span span` (1)
- `.landing-nav-links a` (1)
- `.nav-login` (1)
- `.nav-trial` (1)
- `.nav-trial:hover` (1)
- `.hero-content` (1)
- `.hero-badge` (1)
- `.hero-content h1 span` (1)
- `.hero-primary, .hero-secondary` (1)
- `.hero-primary` (1)
- `.hero-secondary` (1)
- `.hero-benefits` (1)
- `.hero-benefits span` (1)
- `.hero-benefits i` (1)
- `.preview-stats > div` (1)
- `.preview-chart, .preview-patient` (1)
- `.fake-chart div` (1)
- `.floating-card` (1)
- `.patient-card` (1)
- `.growth-card` (1)
- `.landing-nav-links` (1)
- `.preview-main` (1)
- `.preview-bottom` (1)

</details>

### login (`login.css`)

<details><summary>Reglas cuyo selector usa una clase que no existe en ninguna vista, JS ni PHP (7)</summary>

Clases inexistentes: `.brand-logo-icon`, `.brand-name`, `.ml-3`, `.mobile-logo-icon`, `.ms-3`, `.register-logo-icon`

</details>

### diet-plans (`diet-plans.css`)

Selectores quitados de una lista (el resto de la lista se conserva):

- `.dp-text-input`
- `.dp-text-input:focus`
