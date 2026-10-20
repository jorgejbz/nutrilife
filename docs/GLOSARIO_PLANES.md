# Glosario de planes de NutriLife

## Los tres planes

| | **Esencial** | **Pro** | **Consultorio** |
|---|---|---|---|
| Para quién | Nutriólogo independiente que empieza a organizar su consulta | Nutriólogo con consulta en crecimiento | Consultorio o clínica con más volumen |
| Precio | $249 MXN / mes | $399 MXN / mes | $599 MXN / mes |
| Cobro | Mensual | Mensual | Mensual |
| Límite de pacientes | 50 | 200 | Sin límite |
| Prueba gratuita | 7 días | 7 días | 7 días |
| Usuario de demostración | esencial@nutrilife.test | pro@nutrilife.test | consultorio@nutrilife.test |
| Código de acceso (app pacientes) | 724901 | 724902 | 724903 |

Contraseña de los usuarios de demostración: `NutriLife2026!`
(script: `database/sql/demo_usuarios_por_plan.sql` o, con todos los datos de
prueba, `php artisan db:seed --class=DemoDataSeeder`).

### Qué incluye cada plan

| Función | Esencial | Pro | Consultorio | Estado en la app hoy |
|---|:-:|:-:|:-:|---|
| Gestión de pacientes (expediente) | ✓ | ✓ | ✓ | Disponible |
| Agenda de citas | ✓ | ✓ | ✓ | Disponible |
| Consultas | ✓ | ✓ | ✓ | Disponible |
| Evaluaciones antropométricas | ✓ | ✓ | ✓ | Disponible |
| Seguimiento de progreso (gráficas) | ✓ | ✓ | ✓ | Disponible |
| Control de pagos de pacientes | ✓ | ✓ | ✓ | Disponible |
| Código de acceso para la app de pacientes | ✓ | ✓ | ✓ | Disponible (la app móvil, próximamente) |
| Planes alimenticios | — (sí durante la prueba) | ✓ | ✓ | Disponible: `/planes-alimenticios` (ver `docs/PLANES_ALIMENTICIOS.md`) |
| Cálculo de calorías y macros | — (sí durante la prueba) | ✓ | ✓ | Disponible dentro de Planes alimenticios (totales del día contra objetivos, desde el catálogo `foods`) |
| Exportación profesional a PDF | — | ✓ | ✓ | Próximamente |
| Reportes avanzados | — | ✓ | ✓ | Próximamente |
| Varios profesionales en una cuenta | — | — | ✓ | **No soportado por la BD actual** (no hay tabla de clínicas/equipos) |
| Gestión centralizada del consultorio | — | — | ✓ | **No soportado por la BD actual** (depende de la anterior) |
| Más capacidad de pacientes | 50 | 200 | Sin límite | Guardado en `subscription_plans.max_patients`; **la app todavía no lo aplica** |
| Soporte prioritario | — | — | ✓ | Es un acuerdo de servicio, no una función del sistema |

### Por qué estos límites de pacientes

- **Esencial · 50:** suficiente para un nutriólogo que atiende de 2 a 3
  pacientes por día con seguimiento mensual.
- **Pro · 200:** el salto natural de una consulta llena; justifica el precio
  intermedio junto con los planes alimenticios.
- **Consultorio · sin límite:** pensado para varios profesionales; un límite
  por cuenta no tendría sentido.

`max_patients` es la **única diferencia entre planes que guarda la BD**
(además de precio y periodo). Las funciones por plan se definen en
`config/nutrilife.php` → `features` y el sistema ya las aplica:

| Función | Quién entra | Dónde se revisa |
|---|---|---|
| Planes alimenticios | Pro y Consultorio con suscripción `activa` (o `cancelada` hasta que termine el periodo pagado) y la prueba gratuita vigente | Menú (`can`), rutas (`feature:diet_plans`), `DietPlanPolicy` y cada consulta filtrada por `user_id` |

Esencial, la prueba vencida, una suscripción vencida y el administrador no
entran: ven la pantalla "Disponible en Pro y Consultorio" (HTTP 403).

---

## Términos

**Plan** (`subscription_plans`): lo que se vende. Tiene nombre, precio,
periodo de cobro (`mensual` o `anual`), límite de pacientes (`max_patients`,
vacío = sin límite) y si está a la venta (`active`).

**Suscripción** (`subscriptions`): la relación de un nutriólogo con un plan en
un periodo. Un nutriólogo puede tener varias en su historial; la que cuenta
es la más reciente.

**Estados de una suscripción** (`subscriptions.status`):

| Estado | Significado | Qué ve el nutriólogo en el panel |
|---|---|---|
| `en_prueba` | Prueba gratuita de 7 días en curso | "Prueba gratuita · X días" (ámbar los últimos 2 días) |
| `activa` | Compró el sistema y su periodo está pagado | Nada: sin avisos |
| `vencida` | Terminó la prueba o un periodo pagado sin renovarse | "Prueba terminada · Elegir plan" si nunca pagó |
| `cancelada` | El nutriólogo canceló (`cancelled_at` guarda la fecha) | — |

**Prueba gratuita:** suscripción `en_prueba` que se crea cuando la cuenta se
activa (no al registrarse), ligada al plan activo más barato. Vence exactamente
`NUTRILIFE_TRIAL_DAYS` días (7 por defecto) después de su `created_at`. Al
vencer, el panel se bloquea y se muestra la pantalla de contratación
(`/contratar`).

**Contratación simulada:** elegir un paquete en `/contratar` crea una
suscripción `activa` por un periodo (mes o año según `billing_period`) y un
cobro en `subscription_payments` con método `Simulado` y referencia `SIM-…`.
No se cobra nada; se puede apagar con `NUTRILIFE_SIMULATED_PAYMENTS=false`.

**Periodo actual** (`starts_at` → `current_period_ends_at`): fechas que cubre el
último pago. En un plan mensual se recorre un mes con cada cobro.

**Cobro de suscripción** (`subscription_payments`): lo que el nutriólogo paga a
NutriLife (monto, fecha, método, estado `pagado` y referencia). No confundir
con **Pagos** (`payments`), que son los cobros del nutriólogo a sus pacientes.

**Código de acceso** (`users.access_code`): 6 dígitos únicos por nutriólogo.
Junto con su correo, es la credencial de sus pacientes para la app móvil.

**Límite de pacientes** (`max_patients`): número máximo de pacientes que el plan
permite registrar. Vacío = sin límite.
