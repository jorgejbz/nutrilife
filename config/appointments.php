<?php

/*
|--------------------------------------------------------------------------
| Citas desde la app móvil (API /api/availability, cancel, reschedule)
|--------------------------------------------------------------------------
| Reglas para que el paciente cancele o reprograme su cita desde la app.
| El panel web del nutriólogo no las usa.
*/

return [
    // Horas mínimas de anticipación para cancelar o reprogramar
    'min_hours_before' => 24,

    // Duración de cada cita en minutos
    'slot_minutes' => 60,

    // Hasta cuántos días adelante puede agendar
    'max_days_ahead' => 60,

    // Horario del nutriólogo (ISO: 1 = lunes … 6 = sábado; domingo cerrado)
    'working_hours' => [
        1 => [['09:00', '14:00'], ['15:00', '19:00']],
        2 => [['09:00', '14:00'], ['15:00', '19:00']],
        3 => [['09:00', '14:00'], ['15:00', '19:00']],
        4 => [['09:00', '14:00'], ['15:00', '19:00']],
        5 => [['09:00', '14:00'], ['15:00', '19:00']],
        6 => [['09:00', '13:00']],
    ],

    // Días sin atención
    'closed_dates' => ['2026-11-16', '2026-12-25', '2027-01-01'],

    // Estatus que toma la cita al reprogramarla
    'status_after_reschedule' => 'Pendiente',

    // Estatus que el paciente puede modificar
    'modifiable_statuses' => ['Pendiente', 'Confirmada'],
];
