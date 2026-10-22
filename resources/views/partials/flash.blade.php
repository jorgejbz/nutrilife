{{-- =========================================================
     AVISOS DE LA ÚLTIMA ACCIÓN (session success / error y errores
     de validación). Estilos: public/css/admin/pages/clinic-admin.css.
     ========================================================= --}}
@if (session('success'))
    <div class="nl-alert" role="status">
        <i class="fas fa-circle-check" aria-hidden="true"></i>
        <div>{{ session('success') }}</div>
    </div>
@endif

@if (session('error'))
    <div class="nl-alert nl-alert--error" role="alert">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

@if (($showErrors ?? true) && $errors->any())
    <div class="nl-alert nl-alert--error" role="alert">
        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
        <div>
            Revisa los datos:
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
