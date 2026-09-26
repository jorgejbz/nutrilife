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

Al registrarse, un nutriólogo recibe una prueba de 7 días (`subscriptions`,
`status = en_prueba`). La nueva BD exige `subscription_plan_id`, por lo que la
prueba se asocia al plan **activo más económico** de `subscription_plans`.

Si todavía no hay planes registrados, el registro funciona igual y
simplemente no se crea la prueba (no se inventan planes). Para activarla,
registra al menos un plan en `subscription_plans`.

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
