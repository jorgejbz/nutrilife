# NutriLife

Guía para instalar NutriLife desde cero, pasarlo a otra computadora y
ejecutarlo. Los comandos se pueden copiar y pegar tal cual en **PowerShell**
(la terminal de Windows), salvo que se indique otra cosa.

---

## A. ¿Qué es NutriLife?

NutriLife es una plataforma web para nutriólogos y clínicas de nutrición.
Permite llevar:

- **Pacientes** con su historial clínico (patologías y alergias).
- **Citas** con calendario.
- **Consultas** y **evaluaciones antropométricas** (peso, grasa, músculo, IMC).
- **Progreso** de cada paciente con gráficas.
- **Pagos** de pacientes, con referencia automática (`EFE-…`, `TAR-…`, `TRA-…`).
- **Planes alimenticios**, con un catálogo de alimentos y otro de platillos.
- **Consultorios** (clínicas) con subperfiles de nutriólogo.
- Un **administrador global** para cuentas, cobros de planes y bitácora.
- Un **sitio público** (landing) con los planes Esencial, Pro y Consultorio.

### Cómo está hecho

| Parte | Tecnología | Dónde está |
|---|---|---|
| Backend | Laravel 13 (PHP) | `app/`, `routes/web.php`, `config/` |
| Vistas | Blade + AdminLTE 4 (paquete `jeroennoten/laravel-adminlte`) | `resources/views/` |
| Base de datos | MySQL o MariaDB, base `nutrilife` | migraciones en `database/migrations/` |
| JavaScript | Vite 8 + Alpine.js (se compila a `public/build`) | `resources/js/` |
| Estilos (CSS) | Archivos CSS normales, **no** pasan por Vite | `public/css/` (orden en `config/stylesheets.php`) |
| Pruebas | PHPUnit (usa SQLite en memoria, no toca tu base) | `tests/` |

---

## B. Requisitos para instalarlo

| Programa | Versión | Para qué |
|---|---|---|
| **PHP** | **8.3 o más nueva** (el proyecto pide `^8.3` en `composer.json`) | Ejecuta Laravel |
| **Composer** | 2.x | Instala las librerías de PHP (`vendor/`) |
| **Node.js** | 20.19 o más nueva, o 22.12 o más nueva (lo pide Vite 8) | Compila el JavaScript |
| **npm** | El que viene con Node.js | Instala las librerías de JavaScript |
| **MySQL o MariaDB** | MariaDB 10.4+ o MySQL 8 (XAMPP trae MariaDB 10.4) | Guarda los datos |
| **XAMPP** (opcional) | Cualquiera reciente | Para tener MySQL y **phpMyAdmin** fácilmente |
| **Git** (opcional) | Cualquiera | Para descargar el proyecto desde GitHub |
| **Navegador** | Chrome, Edge o Firefox actualizados | Para usar la aplicación |

### Extensiones de PHP necesarias

En el archivo `php.ini` de tu PHP deben estar activas (sin `;` al inicio):

```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_mysql
extension=zip
```

Además Laravel usa `ctype`, `dom`, `filter`, `hash`, `iconv`, `json`,
`libxml`, `pcre`, `session`, `tokenizer`, `xml` y `xmlwriter`, que normalmente
ya vienen incluidas. Para comprobarlo, dentro de la carpeta del proyecto:

```powershell
composer check-platform-reqs
```

Todo debe decir `success`.

> **Importante sobre XAMPP:** el PHP que trae XAMPP suele ser 8.2, y NutriLife
> necesita **8.3 o más nuevo**. Usa XAMPP solo para MySQL y phpMyAdmin, e
> instala aparte un PHP 8.3+ (por ejemplo desde <https://windows.php.net/download/>,
> versión *Non Thread Safe* x64, descomprimido en `C:\tools\php`). Agrega esa
> carpeta al `PATH` de Windows **antes** que `C:\xampp\php`.
> Para revisar cuál se está usando: `php -v`.

---

## C. Cómo pasar NutriLife a otra computadora

Necesitas llevar **tres cosas**: el código, la base de datos y tu
configuración (`.env`).

### 1. El código: elige una opción

**Opción 1: con Git (recomendada).** El proyecto está en
`https://github.com/jorgejbz/nutrilife`:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/jorgejbz/nutrilife.git NutriLife
```

Asegúrate de haber subido antes tus últimos cambios (`git push`) desde la
computadora anterior.

**Opción 2: copiando la carpeta.** Copia `C:\xampp\htdocs\NutriLife` completa
(por USB, nube, etc.). Puedes omitir `vendor/`, `node_modules/` y
`public/build/`, que se vuelven a generar con los comandos de instalación.

### 2. Lo que Git NO incluye (llévalo aparte)

| Qué | Por qué | Cómo llevarlo |
|---|---|---|
| La **base de datos** `nutrilife` | Vive en MySQL, no en la carpeta | Exportarla con phpMyAdmin (sección F) |
| El archivo **`.env`** | Tiene contraseñas y la clave de la app | Copiarlo a mano, o crear uno nuevo (sección E) |
| `vendor/` y `node_modules/` | Son librerías descargables | Se generan con `composer install` y `npm install` |
| `public/build/` | Es el JavaScript compilado | Se genera con `npm run build` |
| `storage/backups/` | Respaldos locales (`.sql` y CSS anteriores) | Cópialos si los quieres conservar |

NutriLife **no guarda archivos subidos por los usuarios**: no hay subida de
fotos ni documentos. Las imágenes de la landing, de los platillos y de la marca
están en `public/images/` y sí vienen con Git.

> ⚠️ **Nunca subas el archivo `.env` a GitHub** ni lo compartas: contiene la
> clave de la aplicación (`APP_KEY`) y la contraseña de la base de datos. Ya
> está en `.gitignore`. Tampoco compartas archivos `.sql` con datos reales de
> pacientes.

---

## D. Instalación paso a paso en Windows

1. **Instala los programas** de la sección B: PHP 8.3+, Composer, Node.js y
   XAMPP. Cierra y vuelve a abrir la terminal después de instalarlos.
2. **Comprueba las versiones.** Abre PowerShell y escribe:

   ```powershell
   php -v
   composer --version
   node -v
   npm -v
   ```

3. **Ve a la carpeta del proyecto.** Todos los comandos siguientes se
   ejecutan desde aquí:

   ```powershell
   cd C:\xampp\htdocs\NutriLife
   ```

4. **Instala las librerías de PHP.** Crea la carpeta `vendor/`:

   ```powershell
   composer install
   ```

5. **Instala las librerías de JavaScript.** Crea la carpeta `node_modules/`:

   ```powershell
   npm install
   ```

6. **Compila el JavaScript.** Crea `public/build/`. Sin este paso no
   funcionan el botón de modo claro/oscuro, los calendarios, las gráficas ni
   los diálogos de confirmación:

   ```powershell
   npm run build
   ```

7. **Crea y configura el archivo `.env`** (sección E).
8. **Prepara la base de datos** (sección F).
9. **Inicia NutriLife** (sección G).

---

## E. Configuración del archivo `.env`

1. Si trajiste tu `.env` de la otra computadora, pégalo en la raíz del
   proyecto y revisa solo la parte de la base de datos.
2. Si no, créalo a partir del ejemplo:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

   `key:generate` llena `APP_KEY`, la clave que protege las sesiones. Hazlo
   **solo con un `.env` nuevo**: si cambias la clave de un `.env` que ya se
   usaba, se cierran todas las sesiones.

3. Abre `.env` con un editor de texto y revisa estas variables. Los valores
   que se muestran son **ejemplos**:

```dotenv
# Aplicación
APP_NAME=NutriLife
APP_ENV=local              # local en tu computadora; production en un servidor
APP_DEBUG=true             # true muestra los errores; en producción va en false
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=America/Mexico_City

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306               # el puerto de tu MySQL (XAMPP usa 3306; cámbialo si el tuyo es otro, por ejemplo 3307)
DB_DATABASE=nutrilife
DB_USERNAME=root
DB_PASSWORD=               # vacío en XAMPP recién instalado; pon aquí tu contraseña si le asignaste una
```

### Variables propias de NutriLife (todas opcionales)

| Variable | Qué hace | Valor de ejemplo |
|---|---|---|
| `NUTRILIFE_TRIAL_DAYS` | Días de prueba gratuita | `7` |
| `NUTRILIFE_ACTIVATION_DELAY_MINUTES` | Minutos que tarda en activarse una cuenta nueva (simulación) | `5` (`0` = inmediato) |
| `NUTRILIFE_SIMULATED_PAYMENTS` | Contratar un plan sin pasarela de pago real | `true` |
| `NUTRILIFE_SIMULATED_EMAIL_VERIFICATION` | Muestra el correo como "verificado (simulado)" en Mi perfil | `false` |
| `NUTRILIFE_CLINIC_MAX_MEMBERS` | Máximo de subperfiles por clínica (vacío = sin límite) | *(vacío)* |
| `NUTRILIFE_CONTACT_EMAIL` | Correo de contacto de la landing (vacío = no se muestra) | *(vacío)* |
| `TRUSTED_PROXIES` | Para usar un túnel HTTPS (cloudflared o ngrok) sin error 419 | `127.0.0.1,::1` |
| `PHP_CLI_SERVER_WORKERS` | Atiende varias peticiones a la vez con `php artisan serve` (las páginas cargan más rápido) | `4` |
| `ASSET_URL` | Solo si usas un túnel: la URL pública del túnel | *(vacío)* |

El correo está en modo `MAIL_MAILER=log`: los correos (por ejemplo, el de
recuperar contraseña) no se envían, se escriben en
`storage/logs/laravel.log`.

Cada vez que cambies el `.env`, limpia la configuración guardada:

```powershell
php artisan config:clear
```

---

## F. Base de datos `nutrilife`

### 1. Inicia MySQL en XAMPP

Abre el **XAMPP Control Panel** y pulsa **Start** en **MySQL**. **Apache**
solo hace falta para abrir phpMyAdmin; NutriLife usa su propio servidor
(sección G).

phpMyAdmin se abre en <http://localhost/phpmyadmin>.

### 2. Exporta la base en la computadora anterior

1. En phpMyAdmin, elige la base **`nutrilife`** en la columna izquierda.
2. Pestaña **Exportar**, método **Rápido**, formato **SQL**.
3. Pulsa **Exportar**. Se descarga `nutrilife.sql`.

Si la base es grande, también puedes usar la terminal. Cambia `3306` por tu
puerto y quita `-p` si `root` no tiene contraseña:

```powershell
C:\xampp\mysql\bin\mysqldump.exe -h 127.0.0.1 -P 3306 -u root -p --result-file=nutrilife.sql nutrilife
```

### 3. Crea la base en la computadora nueva

En phpMyAdmin: **Nueva**, nombre `nutrilife`, cotejamiento
`utf8mb4_unicode_ci`, y pulsa **Crear**.

### 4. Importa el archivo `.sql`

Con la base `nutrilife` seleccionada: pestaña **Importar**, elige
`nutrilife.sql` y pulsa **Importar**.

Si el archivo supera el límite de phpMyAdmin, impórtalo desde la terminal:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3306 -u root -p nutrilife < nutrilife.sql"
```

### 5. Pon las credenciales en `.env`

Usa `DB_HOST`, `DB_PORT`, `DB_DATABASE=nutrilife`, `DB_USERNAME` y
`DB_PASSWORD` (sección E) y luego ejecuta `php artisan config:clear`.

### 6. Comprueba la conexión

```powershell
php artisan migrate:status
```

Si aparece la lista de migraciones con `Ran`, la conexión funciona. Si sale un
error, revisa la sección H.

### ¿Hay que ejecutar las migraciones después de importar?

- El `.sql` exportado ya trae la tabla `migrations`, así que Laravel sabe qué
  cambios ya se aplicaron.
- Si `migrate:status` muestra alguna como **`Pending`**, aplícala así. Antes,
  respalda la base (sección I):

  ```powershell
  php artisan migrate
  ```

  Las migraciones de NutriLife solo **agregan** tablas o columnas, y revisan
  antes si ya existen. No borran datos.

> ⛔ **No uses** `php artisan migrate:fresh`, `migrate:refresh`,
> `migrate:reset`, `db:wipe` ni `migrate:rollback` en una instalación normal:
> **borran tablas y datos**.

### Instalación nueva, sin datos (sin `.sql`)

```powershell
php artisan migrate
php artisan db:seed
php artisan nutrilife:create-admin admin@tu-correo.com
```

- `migrate` crea todas las tablas en la base `nutrilife` vacía.
- `db:seed` carga los planes, los catálogos clínicos y los catálogos de
  alimentos y platillos. Se puede repetir sin duplicar.
- `nutrilife:create-admin` crea el administrador global con ese correo (pon el
  tuyo). Te pide la contraseña; si la dejas vacía, genera una y la muestra.

---

## G. Comandos para ejecutar NutriLife

Necesitas **MySQL encendido** (XAMPP) y **una terminal** abierta en la carpeta
del proyecto:

```powershell
cd C:\xampp\htdocs\NutriLife
php artisan serve --host=127.0.0.1 --port=8000
```

Abre en el navegador: **<http://127.0.0.1:8000>**

- La terminal debe quedarse **abierta** mientras uses NutriLife. Para
  detenerlo, pulsa `Ctrl + C`.
- Con `npm run build` (sección D) **no** necesitas otra terminal para el
  JavaScript.
- Si vas a **modificar el JavaScript** (`resources/js`), abre una
  **segunda terminal** con `npm run dev`: los cambios se ven al guardar. Al
  terminar, vuelve a ejecutar `npm run build`.
- Los **CSS** (`public/css`) no se compilan: guarda el archivo y recarga la
  página con `Ctrl + F5`.

### Compartirlo por internet con un túnel (opcional)

Con cloudflared, en una terminal aparte:

```powershell
cloudflared.exe tunnel --url http://localhost:8000
```

Pon la URL `https://….trycloudflare.com` que te da en `APP_URL` y en
`ASSET_URL` del `.env`, y ejecuta `php artisan config:clear`. El servidor
debe iniciarse con `--host=0.0.0.0`. Por el túnel se usa el JavaScript
compilado (`npm run build`).

### Pruebas automáticas

```powershell
php artisan test
```

Usan una base SQLite temporal en memoria: **no tocan** tu base `nutrilife`.

---

## H. Problemas comunes y soluciones

| Problema | Solución |
|---|---|
| `php` no se reconoce como comando | Agrega la carpeta de tu PHP 8.3+ al `PATH` de Windows (Configuración → Variables de entorno), cierra y vuelve a abrir la terminal, y comprueba con `php -v`. |
| `composer` no se reconoce | Instala Composer desde <https://getcomposer.org/download/> (Composer-Setup.exe) y vuelve a abrir la terminal. |
| `Your PHP version (8.2…) does not satisfy that requirement` | Se está usando el PHP de XAMPP. Instala PHP 8.3+ y ponlo antes en el `PATH` (sección B). |
| MySQL no inicia en XAMPP | Otro programa usa el puerto. En XAMPP: **Config → my.ini**, cambia `port=3306` por `port=3307` (en `[client]` y `[mysqld]`), reinicia MySQL y pon `DB_PORT=3307` en `.env`. También revisa que no haya otro MySQL instalado como servicio de Windows. |
| `SQLSTATE[HY000] [2002] No connection could be made` | MySQL está apagado o el puerto no coincide. Enciéndelo en XAMPP y revisa `DB_HOST` y `DB_PORT` en `.env`. |
| `SQLSTATE[HY000] [1049] Unknown database 'nutrilife'` | La base no existe: créala e impórtala (sección F) y revisa `DB_DATABASE`. |
| `SQLSTATE[HY000] [1045] Access denied` | Usuario o contraseña incorrectos en `DB_USERNAME` / `DB_PASSWORD`. |
| `No application encryption key has been specified` | Falta `APP_KEY`: ejecuta `php artisan key:generate` (solo con un `.env` nuevo). |
| Falta el archivo `.env` | `Copy-Item .env.example .env` y configúralo (sección E). |
| `vendor/autoload.php` no existe | Faltan las librerías de PHP: `composer install`. |
| `Vite manifest not found` o no funciona el JavaScript (tema, calendarios, gráficas) | `npm install` y luego `npm run build`. |
| Los estilos se ven viejos o la página sale sin estilos | Recarga con `Ctrl + F5`. Comprueba que existan `public/css/base/tokens.css` y las demás hojas de `config/stylesheets.php`. |
| `Permission denied` o no se puede escribir en `storage` | La carpeta del proyecto no debe estar en una ubicación de solo lectura. Da permiso de escritura a tu usuario sobre `storage/` y `bootstrap/cache/` (clic derecho → Propiedades → Seguridad). |
| Error 404 en todas las páginas menos la principal | Abre la app con `php artisan serve` en <http://127.0.0.1:8000>, no desde `http://localhost/NutriLife`. |
| Error 500 | Lee el final de `storage/logs/laravel.log`; con `APP_DEBUG=true` el error se ve en pantalla. |
| Cambié el `.env` y no se nota | `php artisan config:clear` (y `php artisan cache:clear` si persiste). |
| 419 Page Expired al iniciar sesión por un túnel | Revisa `TRUSTED_PROXIES` y que `APP_URL`/`ASSET_URL` tengan la URL `https://` del túnel. |
| `npm install` falla o Vite pide otra versión de Node | Instala Node.js 22 LTS (o 20.19+), borra la carpeta `node_modules` y vuelve a ejecutar `npm install`. |

Comandos de limpieza seguros (no borran datos):

```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## I. Respaldo y mantenimiento

### Antes de actualizar el proyecto o cambiar de computadora

1. **Respalda la base de datos**: phpMyAdmin → `nutrilife` → Exportar (sección
   F), o con la terminal:

   ```powershell
   C:\xampp\mysql\bin\mysqldump.exe -h 127.0.0.1 -P 3306 -u root -p --result-file=respaldo_nutrilife.sql nutrilife
   ```

2. **Guarda una copia de `.env`** en un lugar seguro y privado.
3. **Sube tus cambios de código**: `git add .`, `git commit -m "…"` y
   `git push`, o copia la carpeta.
4. Si quieres conservarlos, copia también `storage/backups/` (respaldos
   locales) y `storage/logs/` (historial de errores).

### Archivos de usuarios

NutriLife no recibe archivos subidos: todo lo que capturan los usuarios
(pacientes, citas, pagos, planes, etc.) está **en la base de datos**. Por eso
el respaldo de la base es lo más importante. Las imágenes del sitio están en
`public/images/` (en Git).

### Después de actualizar el código

```powershell
composer install
npm install
npm run build
php artisan migrate:status
php artisan migrate
php artisan config:clear
```

`migrate:status` sirve para ver qué hay pendiente antes de aplicarlo; la
siguiente línea solo aplica lo que esté `Pending`. Hazlo con un respaldo
reciente de la base.

---

## J. Resumen rápido

### Instalar en otra computadora

1. Instala PHP 8.3+, Composer, Node.js 20.19+ o 22.12+, y XAMPP para
   MySQL/phpMyAdmin.
2. Clona o copia el proyecto en `C:\xampp\htdocs\NutriLife`.
3. Ejecuta `composer install`, `npm install` y `npm run build`.
4. Copia tu `.env`, o haz `Copy-Item .env.example .env` y
   `php artisan key:generate`.
5. Inicia MySQL, crea la base `nutrilife` e importa el `.sql` en phpMyAdmin.
6. Revisa `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` en `.env`.
7. Ejecuta `php artisan migrate:status` y, si hay algo `Pending`,
   `php artisan migrate`.
8. Ejecuta `php artisan serve --host=127.0.0.1 --port=8000` y abre
   <http://127.0.0.1:8000>.

### Comandos más usados

```powershell
php artisan serve --host=127.0.0.1 --port=8000   # iniciar NutriLife
npm run build                                    # compilar el JavaScript
npm run dev                                      # JavaScript en modo desarrollo (otra terminal)
php artisan migrate:status                       # ver migraciones aplicadas o pendientes
php artisan migrate                              # aplicar solo las pendientes
php artisan config:clear                         # después de cambiar .env
php artisan test                                 # pruebas automáticas
php artisan nutrilife:create-admin correo@…      # crear o restablecer el administrador
php artisan nutrilife:payment-references         # referencias de pagos antiguos (se puede repetir)
```

---

## Para desarrolladores

### Estilos (CSS)

Los estilos están en `public/css/` y se cargan en el orden de
`config/stylesheets.php`, con `@include('partials.theme-styles', ['section' => 'admin'])`
(`'app'`, `'admin'` o `'login'`):

```
public/css/
├── base/tokens.css          Paleta de marca (única fuente de color)
├── admin/                   Panel
│   ├── layout.css           Estructura y ajustes de AdminLTE
│   ├── pages/               Una hoja por módulo (pacientes, citas, pagos, clínica…)
│   └── components/          Diálogos, calendario, menú de usuario, referencias, utilidades…
├── app/                     Sitio público (landing)
├── login/                   Inicio de sesión, registro y páginas guest
├── diet-plans/              Planes alimenticios y platillos (todo bajo .dp)
├── themes/                  *-light.css / *-dark.css por sección
└── responsive/<sección>/    TODAS las @media: laptop → tablet → mobile → preferences
```

- El orden **importa**: los módulos base son partes contiguas de las hojas
  originales y la cascada depende de ese orden.
- Los ajustes por tamaño de pantalla van **solo** en `responsive/`, en el
  archivo del rango que corresponda:
  - `laptop`: `max-width` de 992 a 1200 px.
  - `tablet`: de 768 a 991.98 px.
  - `mobile`: menos de 768 px.
  - `preferences`: movimiento reducido.
- La prueba `tests/Feature/StylesheetsTest.php` comprueba que no haya `@media`
  fuera de `responsive/` y que todas las hojas estén en la configuración.
- Detalle de la auditoría de CSS: `docs/AUDITORIA_CSS.md`.

### Modo claro / oscuro

`partials/theme-init-script` pone `data-theme` en `<html>` antes de pintar,
usando la preferencia guardada, el modo del sistema o, si no hay ninguna,
oscuro. Solo queda activa la hoja `themes/*-light.css` o `themes/*-dark.css`
del modo elegido, y `resources/js/theme.js` las intercambia con el botón. Las
gráficas leen sus colores de las variables `--chart-*`.

### Otros documentos

En `docs/` están las notas de cada actualización (`ACTUALIZACION_*.md`),
planes alimenticios, referencias de pago y la auditoría de formularios.
