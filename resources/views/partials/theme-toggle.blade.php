{{-- =========================================================
     THEME TOGGLE
     Botón reusable para cambiar entre tema claro/oscuro.

     Uso normal (con texto "Claro"/"Oscuro"):
         @include('partials.theme-toggle')

     Uso compacto (solo ícono — para espacios chicos, ej. junto
     al nombre de usuario):
         @include('partials.theme-toggle', ['compact' => true])

     Requiere:
       - resources/js/theme.js cargado en la página (vía @vite)
       - El snippet anti-parpadeo en <head> — ver
         resources/views/partials/theme-init-script.blade.php
     ========================================================= --}}

<button
    type="button"
    data-theme-toggle
    class="theme-toggle-btn {{ ($compact ?? false) ? 'theme-toggle-btn--icon-only' : '' }}"
    aria-label="Cambiar tema"
>
    <i class="fas fa-sun" data-theme-icon></i>
    <span data-theme-label>Claro</span>
</button>

