# NutriLife

Plataforma web para nutriólogos: pacientes, citas, consultas, evaluaciones
antropométricas, progreso y pagos. Antes se llamaba **NutriVita** (y en
pantalla aparecía como **NutriAdmin**).

- Laravel 13 · PHP 8.3+
- AdminLTE 4 (jeroennoten/laravel-adminlte 4) para el panel
- MySQL 8 / MariaDB 10.4+ — base de datos `proyecto`
- Vite 8 solo para JavaScript (los estilos viven en `public/css`)

---

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env        # o conserva tu .env actual
php artisan key:generate    # solo si es un .env nuevo
```

### Base de datos

La estructura oficial es `database/sql/proyecto_v3.sql` (copia sin cambios del
script entregado). Hay dos formas equivalentes de crearla:

**A. Solo con migraciones**

```bash
php artisan migrate
```

**B. Importando el SQL y después las tablas internas de Laravel**

```bash
mysql -u root -p < database/sql/proyecto_v3.sql
php artisan migrate      # crea sessions, cache, jobs, password_reset_tokens
```

Las migraciones de negocio (`2024_06_01_*`) detectan si la tabla ya existe y
en ese caso no hacen nada, así que la opción B no choca con el SQL importado.
Ninguna migración modifica ni borra datos.

> Laravel 13 no ofrece `Blueprint::check()`. La migración
> `2024_06_01_000015_create_food_pathology_restrictions_table.php` crea la
> restricción `fpr_food_or_group_check` con SQL nativo (misma condición y
> nombre que en `proyecto_v3.sql`).

### Assets

```bash
npm run build     # producción
npm run dev       # desarrollo
```

`public/build` no se incluye: es obligatorio compilar después de instalar.

---

## Planes y prueba gratuita

Todo nutriólogo nuevo empieza con una **prueba gratuita de 7 días**: una fila
en `subscriptions` con `status = en_prueba`. Se ve en todo el panel (indicador
en la barra superior, `partials/trial-badge`) y en el dashboard, hasta que la
suscripción pasa a `activa` (cuando compra el sistema).

1. **Insertar los planes una vez** (obligatorio: `subscription_plan_id` no
   puede ser nulo). Solo inserta datos, no cambia la estructura:

   ```bash
   php artisan db:seed --class=SubscriptionPlanSeeder
   ```

   Crea Esencial ($249), Pro ($399) y Consultorio ($599), mensuales, con los
   precios de la landing. Puede ejecutarse varias veces sin duplicar.

2. Al registrarse se crea la prueba ligada al plan activo más económico.
3. El middleware `EnsureTrialSubscription` (rutas del panel):
   - crea la prueba si el nutriólogo no tiene suscripción (p. ej. se
     registró antes de que existieran planes; sus 7 días cuentan desde ese
     momento);
   - cambia `en_prueba` → `vencida` cuando pasa la fecha de fin.
4. Al terminar la prueba el panel muestra "Prueba terminada · Elegir plan".
   El acceso no se bloquea.

Cada nutriólogo recibe automáticamente un `access_code` único de 6 dígitos
(credencial de sus pacientes para la app móvil).

---

## Estilos

```
public/css/
├── nutrilife-tokens.css   Paleta oficial. ÚNICA fuente de color de marca.
├── app.css                Sitio público — estructura
├── app-light.css          Sitio público — valores del modo claro
├── app-dark.css           Sitio público — valores del modo oscuro
├── admin.css              Panel — estructura (incluye sidebar/navbar de AdminLTE)
├── admin-light.css        Panel — modo claro
├── admin-dark.css         Panel — modo oscuro
├── login.css              Login, registro y recuperación de contraseña — estructura
├── login-light.css        Autenticación — modo claro
└── login-dark.css         Autenticación — modo oscuro
```

- Los archivos base (`app.css`, `admin.css`, `login.css`) **no contienen
  colores de marca**: solo leen variables (`var(--…)`). Los únicos colores
  literales que quedan son tonos de estado (rojo, ámbar, turquesa).
- Para cambiar la identidad visual se edita `nutrilife-tokens.css`; para
  ajustar un modo, su archivo `*-light.css` o `*-dark.css`.
- Cada layout carga solo su sección con
  `@include('partials.theme-styles', ['section' => 'admin'])`
  (`'app'`, `'admin'` o `'login'`).

### Modo claro / oscuro

1. `partials/theme-init-script` (en `<head>`, antes de pintar) lee la
   preferencia `nutrilife-theme` de `localStorage` (o el modo del sistema;
   por defecto, oscuro) y pone `data-theme` en `<html>`.
2. Solo queda activa la hoja del modo elegido (`*-light.css` o `*-dark.css`);
   la otra se deshabilita.
3. El botón (`partials/theme-toggle`) usa `resources/js/theme.js`, que cambia
   el modo, intercambia las hojas, guarda la preferencia y emite el evento
   `nutrilife:themechange`.
4. Las gráficas (Chart.js) leen sus colores de variables `--chart-*`
   (`resources/js/chart-theme.js`) y se redibujan al cambiar de modo.

La preferencia guardada con la clave anterior (`nutriadmin-theme`) se migra
sola. El modo de color propio de AdminLTE está desactivado
(`config/adminlte.php` → `color_mode.enabled = false`) para que haya un solo
sistema.

---

## Logotipo

`public/images/brand/`:

| Archivo | Uso |
|---|---|
| `nutrilife-emblem-{64,192,512}.png` | Sidebar, preloader, favicon, pie del dashboard |
| `nutrilife-wordmark-light.png` / `-dark.png` | Navbar pública, login, registro (según el modo) |
| `nutrilife-logo-light.png` / `-dark.png` | Páginas de recuperación de contraseña |
| `*-white.png` | Versión blanca (disponible, sin uso actual) |

Los archivos originales de la guía de marca están en
`resources/brand/originales/` y el favicon anterior en `resources/brand/legado/`.

---

## Registro de nutriólogos

El registro pide solo lo que la tabla `users` puede guardar (BD definitiva):
nombre, correo y contraseña. El servidor asigna `role_id = 1` y genera el
`access_code` de 6 dígitos (visible en **Mi perfil**).

Teléfono, cédula profesional, especialidad, años de experiencia, biografía,
foto, zona de atención, modalidad y horarios **no tienen columna ni tabla**
en la BD, por eso no se solicitan. Para agregarlos haría falta ampliar la BD
(ver propuesta en `docs/PROPUESTA_PERFIL_NUTRIOLOGO.md`).

## Landing

- Dirigida a nutriólogos. Secciones: hero, confianza, funciones, cómo
  funciona, vista del panel (maquetas HTML/SVG en
  `resources/views/landing/partials/mockups`), app para pacientes, planes,
  preguntas frecuentes y pie de página.
- Las maquetas usan datos de ejemplo y siguen el modo claro/oscuro; las
  capturas PNG anteriores (con la marca previa) están en
  `resources/brand/legado/landing/`.
- Correo de contacto público: `NUTRILIFE_CONTACT_EMAIL` en `.env`
  (`config/nutrilife.php`). Si está vacío, la landing no muestra contacto.
- Los precios de la sección de planes están escritos en la vista; no se leen
  de `subscription_plans`.
