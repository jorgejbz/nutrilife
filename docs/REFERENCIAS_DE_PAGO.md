# Referencias de pago de NutriLife

Todos los pagos de la plataforma tienen una **referencia interna de NutriLife**
(`internal_reference`). La genera el servidor de forma automática según el
método de pago; nadie la escribe a mano.

## Formato

`PRE-AAAAMMDD-NNNNNN`, por ejemplo `TRA-20261022-000140`.

| Prefijo | Método | Color del distintivo |
|---|---|---|
| `EFE` | Efectivo | verde |
| `TAR` | Tarjeta (crédito, débito, terminal) | azul |
| `TRA` | Transferencia bancaria / SPEI | morado |
| `SIM` | Pago simulado (contratación de planes sin cobro real) | ámbar |
| `PAS` | Pasarela en línea (para cuando se conecte una) | turquesa |
| `OTR` | Otro / no especificado | gris |

- `AAAAMMDD` es la fecha de registro del pago, en la zona horaria de la app
  (`America/Mexico_City`).
- `NNNNNN` es un consecutivo **global**: lo comparten `payments` y
  `subscription_payments`. Nunca se repite, aunque coincidan el método y el
  día, y no se reutiliza cuando un pago se cancela o se elimina.
- Si el método está vacío, es «Otro», es «Manual» (plan asignado por el
  administrador) o no se reconoce, el prefijo es `OTR`. El sistema nunca
  adivina si fue efectivo, tarjeta o transferencia.

## Referencia interna y referencia externa

| Columna | Qué es | Quién la llena |
|---|---|---|
| `internal_reference` | Referencia de NutriLife (única) | El servidor |
| `reference` | Referencia externa: clave de rastreo SPEI, folio de la terminal, id de la pasarela. En los cobros de planes antiguos guarda el código de origen (`NL-…`, `SIM-…`, `SEED-…`). | El nutriólogo (opcional) o el flujo de origen |

La interna nunca sustituye a la externa. NutriLife no inventa claves bancarias,
códigos de autorización ni identificadores de transacción.

## Cómo se genera

- `App\Services\PaymentReferenceService` genera la referencia y decide el
  prefijo según el método.
- `App\Models\Concerns\HasInternalReference`, que usan `Payment` y
  `SubscriptionPayment`, la crea al guardar el pago. Así la reciben todos los
  módulos: `/pagos`, la contratación de planes (`PlanCheckout`), los planes que
  asigna el administrador y los seeders.
- El contador vive en la tabla `reference_counters` y se lee con
  `SELECT … FOR UPDATE` dentro de una transacción, así que dos registros
  simultáneos no pueden recibir el mismo número. Se comprobó con 8 procesos en
  paralelo contra MySQL: 200 referencias, todas distintas.
- Cada tabla tiene un índice `UNIQUE` sobre la referencia como última
  barrera. Si un número ya existe (por ejemplo, por datos importados), el
  servicio pasa al siguiente.
- Si se pierde el contador, se reconstruye desde el número más alto que ya
  se usó.
- La referencia no es asignable en masa, así que una petición HTTP no puede
  fijarla. Un cambio directo en el código también se descarta al guardar.
- En el formulario de `/pagos` se ve una **vista previa informativa**
  (`EFE-20261022-······`). El número definitivo lo asigna el servidor.

## Política de cambios

| Situación | Referencia |
|---|---|
| Editar concepto, monto, fecha o notas | No cambia |
| Pago **pendiente** que cambia a un método con otro prefijo | Se genera una nueva con el prefijo correcto. La anterior queda en la bitácora (`reference_from` / `reference_to`) y no se reutiliza. |
| Pago **pagado o cancelado** al que se le corrige el método | Se conserva la histórica. La bitácora guarda `method_from` / `method_to` y `reference_kept`. El distintivo muestra el método actual. |
| Cancelar o eliminar | Se conserva |

Las acciones que se registran en `audit_logs` llevan la referencia:
`payment.created`, `payment.updated`, `payment.completed`,
`payment.cancelled` y `subscription_payment.completed`.

## Pagos históricos

La migración `2026_10_22_000003_add_internal_payment_references` agrega las
columnas y asigna referencia a los pagos que ya existían. Toma la fecha de
`created_at` y no cambia ningún otro campo, ni siquiera `updated_at`; incluye
pagos cancelados y eliminados. El mismo proceso se puede repetir sin
problema, porque solo asigna referencia a los pagos que no la tienen:

```
php artisan nutrilife:payment-references --dry-run   # solo cuenta
php artisan nutrilife:payment-references
```

Antes de aplicarla se respaldó `storage/backups/pagos_antes_referencias_20261022.sql`.

## Dónde se ven

- **`/pagos`:**
  - Columna «Referencia y método», con botón para copiar la referencia y la
    referencia externa debajo.
  - Búsqueda por referencia interna o externa y filtro por método.
  - En el detalle y en la edición del pago.
- **`/clinica/pagos`** (nuevo, solo lectura):
  - Pagos de la cuenta principal y de sus subperfiles, con filtros por
    nutriólogo, método, estado y referencia. Nunca muestra pagos de otra
    clínica.
  - Desde el panel se llega con el indicador «Pagos pendientes».
- **Administrador:**
  - `/admin/cobros`: búsqueda por referencia.
  - Detalle de cada cuenta.
  - Bitácora.
- **`/perfil`:** historial de cobros del plan.
