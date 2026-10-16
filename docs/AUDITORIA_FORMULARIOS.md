# Auditoría de formularios ↔ base de datos (NutriLife)

Fuente de verdad: `database/sql/proyecto_v3.sql` y `diccionario_de_datos.pdf`
(coinciden en tablas, columnas, tipos, nulos, valores por defecto e índices).
**No se modificó la BD ni las migraciones.**

## Matriz por formulario

| Formulario | Tabla | Campos del formulario | Asignados por el servidor | Validación clave |
|---|---|---|---|---|
| Crear / editar paciente | `patients` | first_name*, last_name*, birth_date, sex, phone, email*, occupation, goal, active (solo editar) | user_id, active=1 (crear) | email obligatorio, minúsculas, único por nutriólogo (incluye eliminados); sex ∈ Masculino/Femenino/Otro; birth_date ≤ hoy |
| Crear cita | `appointments` | patient_id*, appointment_at*, reason, status*, notes | user_id | patient_id: propio, activo y no eliminado; status ∈ Pendiente/Confirmada/Completada/Cancelada; notes ≤ 1000 |
| Cancelar / eliminar cita (calendario) | `appointments` | — (PATCH / DELETE) | — | cancelar solo Pendiente/Confirmada; eliminar bloqueado si tiene consultas o pagos (FK sin cascada) |
| Crear consulta | `consultations` | patient_id*, consultation_at*, appointment_id, reason, next_visit, observations, recommendations | user_id | patient_id propio y vigente; appointment_id propia, abierta y **del mismo paciente**; next_visit ≥ hoy; textos ≤ 5000 |
| Registrar pago | `payments` | patient_id*, amount*, paid_at*, concept*, payment_method*, status*, appointment_id, reference, notes | user_id | amount DECIMAL(10,2): 0.01–99 999 999.99, 2 decimales; appointment_id del mismo paciente |
| Nueva evaluación | `evaluations` | evaluation_date*, weight*, height*, body_fat, muscle_mass, waist, hip, chest, arm, thigh, notes | patient_id (ruta), bmi (calculado) | rangos idénticos en formulario y backend; 2 decimales; IMC ≤ 150 (bmi es DECIMAL(5,2)) |
| Registro | `users` | name*, email*, password*, password_confirmation* | role_id=1, access_code (6 dígitos único) | email en minúsculas antes de validar; único |
| Perfil | `users` | name*, email* · access_code (solo lectura) | — | email en minúsculas antes de validar; único salvo el propio |
| Contraseña / eliminar cuenta / login / recuperación | `users`, `password_reset_tokens` | estándar de Breeze | — | eliminar cuenta bloqueado si tiene datos clínicos (FK sin cascada) |

\* obligatorio

## Columnas sin pantalla para consultarlas (decisión pendiente)

Se capturan pero ninguna vista las muestra: `consultations.observations`,
`consultations.recommendations`, `appointments.notes`, `payments.reference`,
`payments.notes`, `evaluations.hip/chest/arm/thigh/notes`.
`users.role_id` no se usa (no existe área de administrador).
