# Propuesta (no aplicada): perfil profesional del nutriólogo

La BD definitiva (`database/sql/proyecto_v3.sql`) **no se modificó**. Este
documento solo describe qué haría falta si en el futuro se decide guardar
los datos profesionales que se pidieron para el registro.

| Dato | ¿Existe en la BD? | Dónde podría ir |
|---|---|---|
| Nombre, correo, contraseña | Sí (`users`) | — |
| Teléfono | No (`phone` solo existe en `patients`) | `nutritionist_profiles.phone` |
| Cédula profesional | No | `nutritionist_profiles.license_number` (único) |
| Especialidad | No (no hay catálogo) | tabla `specialties` + pivote `nutritionist_specialty` |
| Años de experiencia | No | `nutritionist_profiles.years_experience` |
| Biografía | No | `nutritionist_profiles.bio` |
| Fotografía | No | `nutritionist_profiles.photo_path` (archivo en `storage/app/public`) |
| Zona de atención | No | `nutritionist_profiles.city` / `state` |
| Modalidad | No | `nutritionist_profiles.modality` ENUM('presencial','en_linea','ambas') |
| Horarios | No | tabla `nutritionist_schedules` (día, hora inicio, hora fin) |

Recomendación: una tabla `nutritionist_profiles` 1:1 con `users` (en lugar de
agregar columnas a `users`), para no mezclar credenciales con datos públicos.
Pedir estos datos después del registro, desde **Mi perfil**, y no en el
registro inicial, para que crear la cuenta siga siendo rápido.
