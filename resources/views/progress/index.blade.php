@extends('adminlte::page')

@section('title', 'Progreso | NutriLife')

{{-- =========================================================
     CONTENT HEADER
========================================================= --}}
@section('content_header')

    <div class="progress-header">
        <div>
            <span class="progress-eyebrow">
                <i class="fas fa-leaf me-1"></i>
                NUTRILIFE
            </span>

            <h1>Progreso</h1>

            <p>Consulta la evolución de tus pacientes.</p>
        </div>
    </div>

@stop


{{-- =========================================================
     CONTENT
========================================================= --}}
@section('content')

    <div class="progress-page">

        {{-- MÉTRICAS --}}
        @include('progress.partials.index-stats')

        {{-- BÚSQUEDA DE PACIENTES --}}
        <div class="progress-search-wrapper">

            <div class="progress-search" role="search">

                <div class="progress-search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>

                    <input
                        type="search"
                        id="patientSearch"
                        class="progress-search-input"
                        placeholder="Buscar por nombre o correo"
                        autocomplete="off"
                        aria-label="Buscar pacientes por nombre o correo"
                    >

                    <button
                        type="button"
                        id="clearPatientSearch"
                        class="progress-search-clear"
                        aria-label="Limpiar búsqueda"
                        title="Limpiar búsqueda"
                        hidden
                    >
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>

                <span
                    id="patientSearchCount"
                    class="progress-search-count"
                    aria-live="polite"
                ></span>

            </div>

            <div
                id="patientSearchEmpty"
                class="patient-search-empty"
                role="status"
                hidden
            >
                <i class="fas fa-user-slash" aria-hidden="true"></i>
                <span>No hay pacientes que coincidan con tu búsqueda.</span>
            </div>

        </div>

        {{-- PACIENTES --}}
        <div id="patientSearchResults">
            @include('progress.partials.index-patients')
        </div>

    </div>

@stop


{{-- =========================================================
     ESTILOS
========================================================= --}}
@section('css')

    @include('partials.theme-styles', ['section' => 'admin'])

    <style>
        .progress-search-wrapper {
            margin-bottom: 18px;
        }

        .progress-search {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }

        .progress-search-field {
            position: relative;
            flex: 1 1 320px;
            max-width: 460px;
        }

        .progress-search-field > i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--text-muted, #8b929d);
            font-size: 15px;
            pointer-events: none;
            transition: color .2s ease;
        }

        .progress-search-field:focus-within > i {
            color: var(--accent-text, #23845e);
        }

        .progress-search-input {
            width: 100%;
            height: 48px;
            padding: 0 46px;
            border: 1px solid var(--input-border, #e5e7eb);
            border-radius: 13px;
            background: var(--input-bg, #fff);
            color: var(--input-text, #1f2937);
            font: inherit;
            font-size: 15px;
            outline: none;
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background .2s ease;
        }

        .progress-search-input::placeholder {
            color: var(--input-placeholder, #9ca3af);
        }

        .progress-search-input:focus {
            border-color: var(--accent, #2e9d72);
            background: var(--input-bg-focus, #fff);
            box-shadow: 0 0 0 3px rgba(var(--nl-accent-rgb), .16);
        }

        .progress-search-input::-webkit-search-cancel-button {
            display: none;
        }

        .progress-search-clear {
            position: absolute;
            top: 50%;
            right: 7px;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: var(--text-muted, #8b929d);
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }

        .progress-search-clear:hover {
            background: rgba(var(--nl-overlay-rgb), .1);
            color: var(--text-primary, #1f2937);
        }

        .progress-search-count {
            margin-left: auto;
            color: var(--text-muted, #8b929d);
            font-size: 13px;
            white-space: nowrap;
        }

        .patient-search-empty {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
            padding: 14px 18px;
            border: 1px dashed rgba(var(--nl-overlay-rgb), .22);
            border-radius: 14px;
            color: var(--text-muted, #8b929d);
            font-size: 14px;
        }

        .patient-search-empty > i {
            color: var(--accent, #2e9d72);
            font-size: 15px;
        }

        /* Mientras se busca, el encabezado del panel sobra. */
        .progress-panel.is-searching .progress-panel-header {
            display: none;
        }

        .progress-search-clear[hidden],
        .patient-search-empty[hidden],
        .progress-panel[hidden],
        .progress-grid > .patient-progress-card[hidden] {
            display: none !important;
        }

        @media (max-width: 576px) {
            .progress-search-field {
                flex-basis: 100%;
                max-width: none;
            }

            .progress-search-count {
                margin-left: 0;
            }

            .progress-search-input {
                font-size: 14px;
            }
        }
    </style>

@stop


{{-- =========================================================
     SCRIPTS
========================================================= --}}
@section('js')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('patientSearch');
            const clearButton = document.getElementById('clearPatientSearch');
            const resultsContainer = document.getElementById('patientSearchResults');
            const emptyMessage = document.getElementById('patientSearchEmpty');
            const countLabel = document.getElementById('patientSearchCount');

            if (!searchInput || !resultsContainer) {
                return;
            }

            /*
             * Panel del listado: su encabezado ("Evolución de pacientes")
             * se oculta mientras hay una búsqueda activa.
             */
            const patientsPanel = resultsContainer.querySelector('.progress-panel');

            /*
             * Normaliza texto:
             * - Ignora mayúsculas y minúsculas.
             * - Ignora acentos.
             * - Elimina espacios sobrantes.
             */
            function normalizeText(text) {
                return (text || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, ' ');
            }

            const PATIENT_SELECTOR = '.patient-progress-card';

            /*
             * Devuelve todas las tarjetas de pacientes del listado.
             */
            function getPatientCards() {
                return Array.from(
                    resultsContainer.querySelectorAll(PATIENT_SELECTOR)
                );
            }

            /*
             * Texto sobre el que se realiza la búsqueda.
             *
             * Usa data-search (nombre completo + correo) para poder
             * buscar por correo aunque no se muestre en la tarjeta.
             */
            function getPatientSearchText(card) {
                const raw = card.dataset.search || card.textContent;

                return normalizeText(raw);
            }

            /*
             * Cada palabra escrita debe aparecer en el paciente,
             * sin importar el orden ("perez ana" encuentra a "Ana Pérez").
             */
            function matchesQuery(text, query) {
                if (!query) {
                    return true;
                }

                return query
                    .split(' ')
                    .every(function (word) {
                        return text.includes(word);
                    });
            }

            function filterPatients() {
                const query = normalizeText(searchInput.value);
                const patients = getPatientCards();

                let visibleCount = 0;

                patients.forEach(function (patient) {
                    const matches = matchesQuery(
                        getPatientSearchText(patient),
                        query
                    );

                    patient.hidden = !matches;

                    if (matches) {
                        visibleCount++;
                    }
                });

                clearButton.hidden = searchInput.value.length === 0;

                /*
                 * Al buscar, se oculta el encabezado del panel.
                 * Si no hay coincidencias, también se oculta el panel
                 * vacío para dejar solo el aviso de "sin resultados".
                 */
                if (patientsPanel) {
                    patientsPanel.classList.toggle(
                        'is-searching',
                        query !== ''
                    );

                    patientsPanel.hidden =
                        query !== '' && visibleCount === 0;
                }

                /*
                 * Si el listado está vacío por sí solo, el panel ya
                 * muestra su propio mensaje: no duplicamos el aviso.
                 */
                if (patients.length === 0) {
                    emptyMessage.hidden = true;
                    countLabel.textContent = '';
                    return;
                }

                emptyMessage.hidden = visibleCount > 0;

                if (query) {
                    countLabel.textContent =
                        visibleCount + (
                            visibleCount === 1
                                ? ' paciente encontrado'
                                : ' pacientes encontrados'
                        );
                } else {
                    countLabel.textContent =
                        patients.length + (
                            patients.length === 1
                                ? ' paciente'
                                : ' pacientes'
                        );
                }
            }

            searchInput.addEventListener('input', filterPatients);

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && searchInput.value !== '') {
                    searchInput.value = '';
                    filterPatients();
                }
            });

            clearButton.addEventListener('click', function () {
                searchInput.value = '';
                filterPatients();
                searchInput.focus();
            });

            filterPatients();
        });
    </script>

@stop
