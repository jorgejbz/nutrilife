@extends('layouts.marketing')

@section('title', 'NutriLife | Gestión para nutriólogos')



@section('content')



<section

    class="hero-section"

    id="inicio"

    style="--hero-bg-image: url('{{ asset('images/landing/fondo-hero.png') }}'); --hero-bg-image-light: url('{{ asset('images/landing/fondoheroblanco.jpeg') }}')"

>



    {{-- El fondo se pasa como variable CSS con asset() para que la ruta
         funcione sin importar el dominio, subcarpeta o CDN. Verifica que
         el archivo exista en storage/app/public o public/images/landing/
         (según tu configuración) con este nombre exacto, sin mayúsculas. --}}

    <div class="landing-container hero-container">



        {{-- ============================

             TEXTO
        ============================= --}}

        <div class="hero-content">



            <div class="hero-badge">

                <i class="fas fa-leaf"></i>

                Plataforma para profesionales

                de la nutrición

            </div>





            <h1>

                Tu consulta nutricional,

                <span>

                    más simple.

                </span>

            </h1>





            <p class="hero-description">

                Gestiona pacientes, citas,

                evaluaciones, progreso y pagos

                desde un solo lugar.

                Dedica menos tiempo a administrar

                y más tiempo a tus pacientes.

            </p>





            <div class="hero-actions">



                <a

                    href="{{ route('register') }}"

                    class="hero-primary"

                >

                    Probar gratis 7 días

                    <i class="fas fa-arrow-right"></i>

                </a>





                <a

                    href="#funciones"

                    class="hero-secondary"

                >

                    <i class="far fa-circle-play"></i>

                    Conocer funciones

                </a>



            </div>





            <div class="hero-benefits">



                <span>

                    <i class="fas fa-check"></i>

                    Sin tarjeta de crédito

                </span>



                <span>

                    <i class="fas fa-check"></i>

                    Cancela cuando quieras

                </span>



                <span>

                    <i class="fas fa-check"></i>

                    7 días gratis

                </span>



            </div>



        </div>





        {{-- ============================

             MOCKUP DEL SISTEMA
        ============================= --}}

        <div class="hero-visual">



            <div class="dashboard-preview">



                <div class="preview-topbar">

                    <div>

                        <span></span>

                        <span></span>

                        <span></span>

                    </div>



                    <small>

                        NutriLife

                    </small>

                </div>





                <div class="preview-content">



                    <div class="preview-sidebar">

                        <div class="preview-logo">

                            <i class="fas fa-leaf"></i>

                        </div>

                        <div></div>

                        <div></div>

                        <div></div>

                        <div></div>

                    </div>





                    <div class="preview-main">



                        <div class="preview-title">

                            <small>

                                Dashboard

                            </small>

                            <strong>

                                Buenos días 👋

                            </strong>

                        </div>





                        <div class="preview-stats">



                            <div>

                                <span>

                                    Pacientes

                                </span>

                                <strong>

                                    24

                                </strong>

                            </div>



                            <div>

                                <span>

                                    Citas hoy

                                </span>

                                <strong>

                                    6

                                </strong>

                            </div>



                            <div>

                                <span>

                                    Ingresos

                                </span>

                                <strong>

                                    $8,400

                                </strong>

                            </div>



                        </div>





                        <div class="preview-bottom">



                            <div class="preview-chart">

                                <span>

                                    Progreso

                                </span>



                                <div class="fake-chart">

                                    <div></div>

                                    <div></div>

                                    <div></div>

                                    <div></div>

                                    <div></div>

                                    <div></div>

                                </div>

                            </div>





                            <div class="preview-patient">

                                <span>

                                    Próxima cita

                                </span>

                                <div>

                                    <i class="fas fa-user"></i>

                                    <p>

                                        <strong>

                                            Andrea López

                                        </strong>

                                        <small>

                                            12:30 PM

                                        </small>

                                    </p>

                                </div>

                            </div>



                        </div>



                    </div>

                </div>



            </div>





            {{-- TARJETAS FLOTANTES --}}

            <div class="floating-card patient-card">

                <div>

                    <i class="fas fa-user-check"></i>

                </div>

                <p>

                    <strong>

                        +12

                    </strong>

                    <span>

                        pacientes activos

                    </span>

                </p>

            </div>





            <div class="floating-card growth-card">

                <i class="fas fa-arrow-trend-up"></i>

                <span>

                    Seguimiento en tiempo real

                </span>

            </div>



        </div>



    </div>



</section>





{{-- Por ahora solo dejamos el ancla preparada --}}

{{-- ======================================================

     FUNCIONES
====================================================== --}}

<section

    class="features-section"

    id="funciones"

>

    <div class="landing-container">



        {{-- ENCABEZADO --}}

        <div class="section-heading">

            <span class="section-eyebrow">

                <i class="fas fa-sparkles"></i>

                TODO EN UN SOLO LUGAR

            </span>



            <h2>

                Las herramientas que necesitas para

                <span>

                    gestionar tu consulta.

                </span>

            </h2>



            <p>

                NutriLife centraliza la administración

                de tu consultorio para que tengas más

                control y dediques menos tiempo a tareas

                repetitivas.

            </p>

        </div>







        {{-- ============================================

             GRID DE FUNCIONES
        ============================================= --}}

        <div class="features-grid">



            {{-- PACIENTES --}}

            <article class="feature-card featured-card">

                <div class="feature-icon">

                    <i class="fas fa-users"></i>

                </div>



                <div class="feature-content">

                    <span class="feature-number">

                        01

                    </span>

                    <h3>

                        Gestión de pacientes

                    </h3>

                    <p>

                        Mantén organizada la información

                        de cada paciente, sus datos,

                        objetivos y seguimiento desde

                        un solo expediente.

                    </p>

                </div>



                <div class="feature-decoration">

                    <i class="fas fa-user-check"></i>

                </div>

            </article>





            {{-- CITAS --}}

            <article class="feature-card">

                <div class="feature-icon calendar">

                    <i class="far fa-calendar-check"></i>

                </div>



                <div class="feature-content">

                    <span class="feature-number">

                        02

                    </span>

                    <h3>

                        Agenda inteligente

                    </h3>

                    <p>

                        Organiza citas, consulta tu

                        calendario y controla citas

                        pendientes, confirmadas,

                        completadas o canceladas.

                    </p>

                </div>

            </article>





            {{-- PROGRESO --}}

            <article class="feature-card">

                <div class="feature-icon progress">

                    <i class="fas fa-chart-line"></i>

                </div>



                <div class="feature-content">

                    <span class="feature-number">

                        03

                    </span>

                    <h3>

                        Seguimiento de progreso

                    </h3>

                    <p>

                        Visualiza cambios de peso,

                        grasa corporal y masa muscular

                        mediante gráficas fáciles de

                        interpretar.

                    </p>

                </div>

            </article>





            {{-- EVALUACIONES --}}

            <article class="feature-card">

                <div class="feature-icon evaluation">

                    <i class="fas fa-clipboard-list"></i>

                </div>



                <div class="feature-content">

                    <span class="feature-number">

                        04

                    </span>

                    <h3>

                        Evaluaciones

                    </h3>

                    <p>

                        Registra peso, IMC,

                        composición corporal y

                        medidas para construir un

                        historial completo.

                    </p>

                </div>

            </article>





            {{-- PAGOS --}}

            <article class="feature-card">

                <div class="feature-icon payments">

                    <i class="fas fa-wallet"></i>

                </div>



                <div class="feature-content">

                    <span class="feature-number">

                        05

                    </span>

                    <h3>

                        Control de pagos

                    </h3>

                    <p>

                        Registra ingresos, métodos

                        de pago y movimientos para

                        entender mejor el desempeño

                        de tu consultorio.

                    </p>

                </div>

            </article>





            {{-- PLANES ALIMENTICIOS --}}

            <article class="feature-card future-feature">

                <div class="feature-label">

                    PRÓXIMAMENTE

                </div>



                <div class="feature-icon nutrition">

                    <i class="fas fa-utensils"></i>

                </div>



                <div class="feature-content">

                    <span class="feature-number">

                        06

                    </span>

                    <h3>

                        Planes alimenticios

                    </h3>

                    <p>

                        Crea planes personalizados,

                        calcula calorías y

                        macronutrientes y genera

                        documentos profesionales.

                    </p>

                </div>

            </article>



        </div>

        {{-- MENSAJE INFERIOR --}}

        <div class="features-bottom">



            <div class="features-bottom-icon">

                <i class="fas fa-leaf"></i>

            </div>



            <div>

                <strong>

                    Menos administración. Más nutrición.

                </strong>

                <span>

                    Toda la información de tu consulta

                    disponible cuando la necesites.

                </span>

            </div>



            <a href="{{ route('register') }}">

                Comenzar gratis

                <i class="fas fa-arrow-right"></i>

            </a>



        </div>


    </div>
</section>


    {{-- ======================================================

     CÓMO FUNCIONA
    ====================================================== --}}

    <section

    class="how-section"

    id="como-funciona"

>

    <div class="landing-container">



        <div class="section-heading">

            <span class="section-eyebrow">

                <i class="fas fa-route"></i>

                EMPIEZA EN MINUTOS

            </span>



            <h2>

                Lleva tu consulta al siguiente nivel

                <span>

                    en pocos pasos.

                </span>

            </h2>



            <p>

                No necesitas instalar programas ni hacer

                configuraciones complicadas. Crea tu cuenta,

                prueba NutriLife y comienza a organizar

                tu consulta.

            </p>

        </div>





        <div class="how-steps">



            {{-- PASO 1 --}}

            <div class="how-step">

                <div class="step-number">

                    01

                </div>



                <div class="step-icon">

                    <i class="fas fa-user-plus"></i>

                </div>



                <h3>

                    Crea tu cuenta

                </h3>



                <p>

                    Regístrate como profesional de la nutrición

                    y configura tu espacio de trabajo.

                </p>



                <span class="step-label">

                    Menos de 2 minutos

                </span>

            </div>





            <div class="step-line">

                <i class="fas fa-arrow-right"></i>

            </div>





            {{-- PASO 2 --}}

            <div class="how-step">

                <div class="step-number">

                    02

                </div>



                <div class="step-icon trial">

                    <i class="fas fa-gift"></i>

                </div>



                <h3>

                    Prueba NutriLife

                </h3>



                <p>

                    Obtén acceso durante 7 días para conocer

                    las herramientas y probarlas con tu consulta.

                </p>



                <span class="step-label">

                    7 días gratis

                </span>

            </div>





            <div class="step-line">

                <i class="fas fa-arrow-right"></i>

            </div>





            {{-- PASO 3 --}}

            <div class="how-step">

                <div class="step-number">

                    03

                </div>



                <div class="step-icon manage">

                    <i class="fas fa-laptop-medical"></i>

                </div>



                <h3>

                    Gestiona tu consulta

                </h3>



                <p>

                    Registra pacientes, programa citas,

                    visualiza progreso y controla tus ingresos.

                </p>



                <span class="step-label">

                    Todo centralizado

                </span>

            </div>





            <div class="step-line">

                <i class="fas fa-arrow-right"></i>

            </div>





            {{-- PASO 4 --}}

            <div class="how-step">

                <div class="step-number">

                    04

                </div>



                <div class="step-icon subscription">

                    <i class="fas fa-crown"></i>

                </div>



                <h3>

                    Continúa con tu plan

                </h3>



                <p>

                    Si NutriLife encaja con tu forma de trabajar,

                    selecciona un plan y continúa utilizando

                    tu información.

                </p>



                <span class="step-label">

                    Sin perder tus datos

                </span>

            </div>



        </div>

        {{-- CTA --}}
        <div class="how-cta">



            <div>

                <span>

                    ¿LISTO PARA PROBARLO?

                </span>

                <h3>

                    Empieza con 7 días gratis.

                </h3>

                <p>

                    Explora NutriLife antes de contratar un plan.

                </p>

            </div>



            <a

                href="{{ route('register') }}"

                class="how-cta-button"

            >

                Crear mi cuenta

                <i class="fas fa-arrow-right"></i>

            </a>



        </div>



    </div>

</section>

{{-- ======================================================

     DEMO DEL SISTEMA
====================================================== --}}
<section

    class="product-demo-section"

    id="demo"

>

    <div class="landing-container">



        <div class="section-heading">

            <span class="section-eyebrow">

                <i class="fas fa-desktop"></i>

                CONOCE NUTRILIFE

            </span>



            <h2>

                Diseñado para trabajar

                <span>

                    contigo todos los días.

                </span>

            </h2>



            <p>

                Así se ve NutriLife por dentro: una interfaz

                clara y ordenada, pensada para que encuentres

                todo en segundos.

            </p>

        </div>





        {{-- ==================================================

             DASHBOARD PRINCIPAL
        =================================================== --}}

        <div class="demo-featured">



            <div class="demo-copy">

                <span class="demo-number">

                    01

                </span>

                <span class="demo-tag">

                    DASHBOARD

                </span>



                <h3>

                    Toda tu consulta de un vistazo.

                </h3>



                <p>

                    Consulta rápidamente tus pacientes,

                    próximas citas, actividad reciente

                    e información importante desde un

                    único panel.

                </p>



                <div class="demo-benefits">

                    <span>

                        <i class="fas fa-check"></i>

                        Pacientes activos

                    </span>

                    <span>

                        <i class="fas fa-check"></i>

                        Próximas citas

                    </span>

                    <span>

                        <i class="fas fa-check"></i>

                        Ingresos del mes

                    </span>

                </div>

            </div>





            <div class="demo-browser">



                <div class="demo-browser-top">



                    <div class="browser-dots">

                        <span></span>

                        <span></span>

                        <span></span>

                    </div>



                    <div class="browser-address">

                        <i class="fas fa-lock"></i>

                        app.nutrilife.mx/dashboard

                    </div>



                </div>



                <div class="demo-browser-image">

                    <img

                        src="{{ asset('images/landing/dashboard.png') }}"

                        alt="Dashboard de NutriLife"

                    >

                </div>



            </div>



        </div>





        {{-- ==================================================

             OTRAS FUNCIONES
        =================================================== --}}

        <div class="demo-grid">



            {{-- CITAS --}}

            <article class="demo-card">



                <div class="demo-card-image">

                    <img

                        src="{{ asset('images/landing/citas.png') }}"

                        alt="Agenda y calendario de NutriLife"

                    >

                </div>



                <div class="demo-card-content">

                    <div>

                        <span>

                            AGENDA

                        </span>

                        <h3>

                            Organiza tus citas.

                        </h3>

                    </div>



                    <i class="far fa-calendar-check"></i>

                </div>



                <p>

                    Consulta tu calendario y conoce rápidamente

                    qué pacientes tienes programados.

                </p>



            </article>


            {{-- PROGRESO --}}
            <article class="demo-card">

                <div class="demo-card-image">
                    <img
                        src="{{ asset('images/landing/progreso.png') }}"
                        alt="Progreso de pacientes en NutriLife"
                    >
                </div>

                <div class="demo-card-content">
                    <div>
                        <span>
                            SEGUIMIENTO
                        </span>
                        <h3>
                            Visualiza resultados.
                        </h3>
                    </div>

                    <i class="fas fa-chart-line"></i>
                </div>

                <p>
                    Analiza peso, grasa corporal y masa muscular
                    mediante gráficas claras y fáciles de explicar.
                </p>

            </article>


            {{-- PAGOS --}}
            <article class="demo-card">

                <div class="demo-card-image">
                    <img
                        src="{{ asset('images/landing/pagos.png') }}"
                        alt="Control de pagos en NutriLife"
                    >
                </div>

                <div class="demo-card-content">
                    <div>
                        <span>
                            FINANZAS
                        </span>
                        <h3>
                            Controla tus ingresos.
                        </h3>
                    </div>

                    <i class="fas fa-wallet"></i>
                </div>

                <p>
                    Lleva un historial de pagos y conoce cuánto
                    está generando tu consulta.
                </p>

            </article>
        </div>





        {{-- CTA --}}

        <div class="demo-footer">



            <div>

                <span>

                    HECHO PARA NUTRIÓLOGOS

                </span>

                <h3>

                    Todo conectado en una sola plataforma.

                </h3>

            </div>



            <a href="{{ route('register') }}">

                Probar NutriLife

                <i class="fas fa-arrow-right"></i>

            </a>



        </div>



    </div>

</section>

{{-- ======================================================

     PLANES
====================================================== --}}

<section

    class="pricing-section"

    id="planes"

>

    <div class="landing-container">



        {{-- ENCABEZADO --}}

        <div class="section-heading">

            <span class="section-eyebrow">

                <i class="fas fa-tags"></i>

                PLANES SIMPLES

            </span>



            <h2>

                Elige el plan que se adapte

                <span>

                    a tu consulta.

                </span>

            </h2>



            <p>

                Comienza con 7 días gratis y conoce

                NutriLife antes de elegir un plan.

                Sin instalaciones y con acceso desde

                cualquier dispositivo.

            </p>

        </div>







        {{-- AVISO PRUEBA --}}

        <div class="trial-banner">

            <div class="trial-banner-icon">

                <i class="fas fa-gift"></i>

            </div>



            <div>

                <strong>

                    Todos los planes incluyen 7 días gratis

                </strong>

                <span>

                    Prueba NutriLife antes de realizar tu primera contratación.

                </span>

            </div>



            <div class="trial-pill">

                $0 para comenzar

            </div>

        </div>





        {{-- ==================================================

             PLANES
        =================================================== --}}

        <div class="pricing-grid">



            {{-- ESENCIAL --}}

            <article class="pricing-card">



                <div class="pricing-card-header">

                    <div class="pricing-icon">

                        <i class="fas fa-leaf"></i>

                    </div>



                    <span class="pricing-name">

                        ESENCIAL

                    </span>



                    <h3>

                        Para comenzar

                    </h3>



                    <p>

                        Ideal para nutriólogos independientes

                        que quieren organizar mejor su consulta.

                    </p>

                </div>





                <div class="pricing-price">

                    <span class="currency">

                        $

                    </span>

                    <strong>

                        249

                    </strong>

                    <span class="period">

                        MXN / mes

                    </span>

                </div>





                <div class="pricing-divider"></div>





                <div class="pricing-features">

                    <div>

                        <i class="fas fa-check"></i>

                        Gestión de pacientes

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Agenda de citas

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Evaluaciones

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Seguimiento de progreso

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Control de pagos

                    </div>

                </div>





                <a

                    href="{{ route('register') }}"

                    class="pricing-button secondary"

                >

                    Probar gratis

                    <i class="fas fa-arrow-right"></i>

                </a>



            </article>





            {{-- ==================================================

                 PRO
            =================================================== --}}

            <article class="pricing-card pricing-popular">



                <div class="popular-label">

                    <i class="fas fa-star"></i>

                    MÁS POPULAR

                </div>



                <div class="pricing-card-header">

                    <div class="pricing-icon popular">

                        <i class="fas fa-seedling"></i>

                    </div>



                    <span class="pricing-name">

                        PRO

                    </span>



                    <h3>

                        Para crecer

                    </h3>



                    <p>

                        Para profesionales que buscan administrar

                        su consulta y ofrecer una experiencia

                        más completa.

                    </p>

                </div>





                <div class="pricing-price">

                    <span class="currency">

                        $

                    </span>

                    <strong>

                        399

                    </strong>

                    <span class="period">

                        MXN / mes

                    </span>

                </div>





                <div class="pricing-divider"></div>





                <div class="pricing-features">

                    <div>

                        <i class="fas fa-check"></i>

                        Todo lo incluido en Esencial

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Planes alimenticios

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Cálculo de calorías y macros

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Exportación profesional a PDF

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Reportes avanzados

                    </div>

                </div>





                <a

                    href="{{ route('register') }}"

                    class="pricing-button primary"

                >

                    Comenzar 7 días gratis

                    <i class="fas fa-arrow-right"></i>

                </a>



            </article>





            {{-- ==================================================

                 CONSULTORIO
            =================================================== --}}

            <article class="pricing-card">



                <div class="pricing-card-header">

                    <div class="pricing-icon">

                        <i class="fas fa-building"></i>

                    </div>



                    <span class="pricing-name">

                        CONSULTORIO

                    </span>



                    <h3>

                        Para equipos

                    </h3>



                    <p>

                        Pensado para consultorios que trabajan

                        con varios profesionales y necesitan

                        mayor capacidad.

                    </p>

                </div>





                <div class="pricing-price">

                    <span class="currency">

                        $

                    </span>

                    <strong>

                        599

                    </strong>

                    <span class="period">

                        MXN / mes

                    </span>

                </div>





                <div class="pricing-divider"></div>





                <div class="pricing-features">

                    <div>

                        <i class="fas fa-check"></i>

                        Todo lo incluido en Pro

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Múltiples profesionales

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Gestión centralizada

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Mayor capacidad de pacientes

                    </div>

                    <div>

                        <i class="fas fa-check"></i>

                        Soporte prioritario

                    </div>

                </div>





                <a

                    href="{{ route('register') }}"

                    class="pricing-button secondary"

                >

                    Probar gratis

                    <i class="fas fa-arrow-right"></i>

                </a>



            </article>



        </div>





        {{-- NOTA --}}

        <div class="pricing-note">

            <i class="fas fa-circle-info"></i>

            <span>

                Los precios mostrados son mensuales y podrán

                ajustarse antes del lanzamiento oficial de NutriLife.

            </span>

        </div>



    </div>

</section>

{{-- ======================================================

     PREGUNTAS FRECUENTES
====================================================== --}}

<section

    class="faq-section"

    id="faq"

>

    <div class="landing-container">



        <div class="faq-layout">



            {{-- COLUMNA IZQUIERDA --}}

            <div class="faq-intro">

                <span class="section-eyebrow">

                    <i class="far fa-circle-question"></i>

                    PREGUNTAS FRECUENTES

                </span>



                <h2>

                    ¿Tienes dudas?

                    <span>

                        Aquí te ayudamos.

                    </span>

                </h2>



                <p>

                    Conoce cómo funciona NutriLife,

                    qué incluye tu prueba gratuita

                    y cómo puedes comenzar a utilizar

                    la plataforma.

                </p>



                <div class="faq-support-card">

                    <div class="faq-support-icon">

                        <i class="fas fa-headset"></i>

                    </div>



                    <div>

                        <strong>

                            ¿No encontraste tu respuesta?

                        </strong>

                        <span>

                            Estamos para ayudarte.

                        </span>

                    </div>



                    <a href="#contacto">

                        Contactar

                        <i class="fas fa-arrow-right"></i>

                    </a>

                </div>

            </div>





            {{-- ==================================================

                 PREGUNTAS
            =================================================== --}}

            <div class="faq-list">



                <details

                    class="faq-item"

                    open
                >

                    <summary>

                        <span class="faq-question-number">

                            01

                        </span>

                        <strong>

                            ¿Necesito instalar algún programa?

                        </strong>

                        <span class="faq-toggle">

                            <i class="fas fa-plus"></i>

                        </span>

                    </summary>



                    <div class="faq-answer">

                        <p>

                            No. NutriLife funciona desde el navegador,

                            por lo que podrás acceder a tu cuenta desde

                            una computadora, tablet o dispositivo

                            compatible con internet.

                        </p>

                    </div>

                </details>





                <details class="faq-item">

                    <summary>

                        <span class="faq-question-number">

                            02

                        </span>

                        <strong>

                            ¿Cómo funciona la prueba gratis de 7 días?

                        </strong>

                        <span class="faq-toggle">

                            <i class="fas fa-plus"></i>

                        </span>

                    </summary>



                    <div class="faq-answer">

                        <p>

                            Creas tu cuenta y tendrás un periodo de

                            prueba para conocer NutriLife y utilizar

                            sus principales herramientas antes de

                            contratar un plan.

                        </p>

                    </div>

                </details>





                <details class="faq-item">

                    <summary>

                        <span class="faq-question-number">

                            03

                        </span>

                        <strong>

                            ¿Qué pasa cuando terminan los 7 días?

                        </strong>

                        <span class="faq-toggle">

                            <i class="fas fa-plus"></i>

                        </span>

                    </summary>



                    <div class="faq-answer">

                        <p>

                            Al finalizar tu periodo de prueba podrás

                            seleccionar uno de los planes disponibles

                            para continuar utilizando NutriLife.

                        </p>

                    </div>

                </details>





                <details class="faq-item">

                    <summary>

                        <span class="faq-question-number">

                            04

                        </span>

                        <strong>

                            ¿Puedo utilizar NutriLife desde mi celular?

                        </strong>

                        <span class="faq-toggle">

                            <i class="fas fa-plus"></i>

                        </span>

                    </summary>



                    <div class="faq-answer">

                        <p>

                            La plataforma está diseñada para adaptarse

                            a diferentes tamaños de pantalla, permitiéndote

                            consultar información desde distintos

                            dispositivos.

                        </p>

                    </div>

                </details>





                <details class="faq-item">

                    <summary>

                        <span class="faq-question-number">

                            05

                        </span>

                        <strong>

                            ¿Puedo cancelar mi suscripción?

                        </strong>

                        <span class="faq-toggle">

                            <i class="fas fa-plus"></i>

                        </span>

                    </summary>



                    <div class="faq-answer">

                        <p>

                            Sí. La intención de nuestros planes es que

                            puedas utilizarlos mes a mes, sin obligarte

                            a permanecer durante largos periodos.

                        </p>

                    </div>

                </details>





                <details class="faq-item">

                    <summary>

                        <span class="faq-question-number">

                            06

                        </span>

                        <strong>

                            ¿NutriLife también tendrá planes alimenticios?

                        </strong>

                        <span class="faq-toggle">

                            <i class="fas fa-plus"></i>

                        </span>

                    </summary>



                    <div class="faq-answer">

                        <p>

                            Sí. NutriLife está pensado para integrar

                            la creación de planes alimenticios,

                            alimentos, cantidades, calorías,

                            macronutrientes y generación de documentos

                            profesionales para tus pacientes.

                        </p>

                    </div>

                </details>



            </div>



        </div>

    </div>

</section>



@endsection