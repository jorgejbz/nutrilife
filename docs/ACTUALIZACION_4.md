# Actualización 4 — Consultorios, administrador global y mejoras

## Túnel HTTPS (cloudflared) y el error 419

`php artisan serve` atiende en HTTP y cloudflared entrega la página en HTTPS.
Laravel no confiaba en el túnel, así que creía que la petición era HTTP: los
formularios apuntaban a `http://…trycloudflare.com/login` dentro de una página
`https://…` y el login terminaba en **419 Page Expired**.

`bootstrap/app.php` ahora confía en el proxy local (`TRUSTED_PROXIES`, por
defecto `127.0.0.1,::1`). Con eso Laravel genera URLs `https://` y la sesión
y el token CSRF coinciden. Un cliente externo no puede falsificar esas
cabeceras: solo cuentan si la conexión viene de la propia máquina.

```
php artisan serve --host=0.0.0.0 --port=8000
cloudflared.exe tunnel --url http://localhost:8000
```

Ya no hace falta cambiar `APP_URL` / `ASSET_URL` en cada túnel. Si los
cambias, escríbelos sin espacio después del `=` y ejecuta `php artisan config:clear`.

## Consultorios (clínica con varios nutriólogos)

- **Cuenta principal** = nutriólogo con plan *Consultorio* (`User::isClinic()`).
  En la interfaz se identifica como **Clínica**; los individuales y los
  subperfiles siguen como *Nutriólogo*.
- **Subperfil** = `users.clinic_id` apunta a la clínica. Tiene su propio
  correo y contraseña. No tiene suscripción ni cobro propio: recibe las
  funciones de `config('nutrilife.clinic.member_plan')` (Pro) mientras la
  licencia Consultorio de la clínica esté vigente (`User::hasFeature`). Usa la
  misma lista `features` que los planes individuales, sin implementación paralela.
- **Pacientes**: sin cambios en la tabla. Cada expediente es de su
  nutriólogo responsable (`patients.user_id`); la clínica se obtiene a través
  de él. Cada nutriólogo ve solo los suyos; la clínica ve los de todos en
  `/clinica/pacientes` (solo lectura).
- Secciones (middleware `clinic`): `/clinica` (panel), `/clinica/equipo`
  (alta, edición, activar/desactivar), `/clinica/pacientes`, `/clinica/agenda`.
- Límite de subperfiles: `NUTRILIFE_CLINIC_MAX_MEMBERS` (vacío = sin límite).
- Si la licencia vence, los subperfiles ven un aviso (sus datos se conservan).

## Administrador global

- Rol `role_id = 2` (ya existía en `roles`). Las rutas `/admin/*` las protege
  el middleware `admin` en el servidor; no basta con ocultar el menú.
- `role_id` y `clinic_id` ya no son asignables en masa y el registro público
  siempre crea nutriólogos. El administrador solo se crea desde la terminal:

```
php artisan nutrilife:create-admin admin@nutrilife.test
php artisan nutrilife:create-admin admin@nutrilife.test --reset-password
```

- Panel con indicadores reales, listado con búsqueda/filtros/paginación,
  detalle de cuentas y clínicas con sus subperfiles, activar/desactivar,
  asignar plan y confirmar cobros manuales. Todo queda en `audit_logs`
  (`/admin/auditoria`). No se eliminan cuentas ni pacientes desde el panel.

## Pagos (`/pagos`)

- Detalle, edición, filtros (estado) y búsqueda (paciente, concepto, referencia).
- **Marcar como completado**: botón en cada pago *Pendiente*, con confirmación.
  Cambia `payments.status` a `Pagado`, pone la fecha de pago y guarda
  `status_changed_by` / `status_changed_at` + bitácora. También **Cancelar**.
- Son pagos manuales del nutriólogo (no hay pasarela): solo el nutriólogo que
  registró el pago puede editarlo o cambiar su estado. El estado no cambia
  desde el formulario de edición.
- Los cobros de suscripción (`subscription_payments`) solo los confirma el
  administrador en `/admin/cobros`, y solo si son manuales.

## Otros cambios

- `/perfil` es la ruta del perfil (`route('perfil')`); `/profile` redirige (301).
- Perfil: cédula, título, estado de la cuenta y del correo como estados
  separados. `NUTRILIFE_SIMULATED_EMAIL_VERIFICATION=true` muestra el correo
  como "Verificado (simulado)" solo fuera de producción y sin tocar
  `email_verified_at`.
- Logo del login: enlace accesible a la landing (`route('landing')`).
- Platillo de ejemplo «Ensalada de Nopales con Queso Panela» retirado
  (migración `2026_10_22_000002`, solo esa fila) y el seeder ya no lo crea.
- Tiempo de activación: `NUTRILIFE_ACTIVATION_DELAY_MINUTES` (ver `.env.example`).
  Se calcula al registrarse, así que el cambio aplica a registros nuevos.

## Base de datos

Migraciones aditivas: `users.clinic_id`, `payments|subscription_payments.status_changed_by/_at`
y la tabla `audit_logs`. Datos de prueba de la clínica:
`php artisan db:seed --class=ClinicDemoSeeder`.
