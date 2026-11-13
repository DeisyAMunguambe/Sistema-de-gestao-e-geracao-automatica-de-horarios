<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SGGH – Organize os seus horários</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500&family=Open+Sans:ital,wght@0,400;1,400&family=Outfit:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>

  <header class="header">
    <button class="header__toggle" aria-label="Abrir menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="header__nav" id="menu">
      <a href="#objectivos" class="header__link">Objectivos do sitema</a>
      <a href="#funcionalidades" class="header__link">Funcionalidades</a>
      <a href="#contactos" class="header__link">Contactos</a>
    </nav>
    <a href="../html-php/Formador.php" class="button button--small">Começar agora</a>
  </header>

  <main>
    <section class="hero">
      <div class="hero__content">
        <h1 class="hero__title">Organize os seus horários de forma simples e inteligente.</h1>
        <p class="hero__text">Crie, organize e gerencie horários académicos num único sistema, evitando conflitos entre formadores, turmas, módulos e salas.</p>
        <a href="#funcionalidades" class="button button--large">Começar agora</a>
      </div>
    </section>

    <section class="benefits" id="objectivos">
      <div class="benefits__grid">
        <article class="benefit-card">
          <svg class="benefit-card__icon" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><rect x="4" y="9" width="48" height="43" rx="3" fill="currentColor"/><path d="M15 3v10M41 3v10" stroke-linecap="round"/><rect x="10" y="22" width="36" height="24" rx="2" fill="#fff" stroke="none"/><path d="M22 26v10M22 40v2M34 26v10M34 40v2" stroke="#000" stroke-linecap="round"/></svg>
          <h3 class="benefit-card__title">Zero sobreposição</h3>
          <p class="benefit-card__text">Crie horários de forma inteligente turmas formadores e salas perfeitamente organizados.</p>
        </article>
        <article class="benefit-card benefit-card--accent">
          <svg class="benefit-card__icon" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M28 8 6 48h44L28 8Z"/><path d="M28 22v13M28 41v2"/><path d="M10 12l5 4M46 12l-5 4M28 2v3M2 26l5 1M54 26l-5 1"/></svg>
          <h3 class="benefit-card__title">Zero conflitos</h3>
          <p class="benefit-card__text">Uma solução inteligente para tornar a gestão de horários mais simples e eficiente.</p>
        </article>
        <article class="benefit-card benefit-card--accent">
          <svg class="benefit-card__icon" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="12" y="4" width="32" height="48" rx="3"/><path d="M20 14h18M20 22h18M20 30h18M20 38h18M20 46h18"/><circle cx="16" cy="14" r="1.5"/><circle cx="16" cy="22" r="1.5"/><circle cx="16" cy="30" r="1.5"/><circle cx="16" cy="38" r="1.5"/></svg>
          <h3 class="benefit-card__title">Planeie melhor</h3>
          <p class="benefit-card__text">Planeie, gere e acompanhe os horários da sua instituição num único lugar.</p>
        </article>
        <article class="benefit-card">
          <svg class="benefit-card__icon" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><rect x="3" y="9" width="42" height="38" rx="3" fill="currentColor"/><path d="M13 3v10M35 3v10"/><path d="M14 30l7 7 13-13" stroke="#fff" stroke-width="4"/><path d="M38 50l14-14" stroke-width="5"/></svg>
          <h3 class="benefit-card__title">Organize melhor</h3>
          <p class="benefit-card__text">Organize aulas, turmas, formadores e salas sem perder tempo com conflitos.</p>
        </article>
      </div>

      <div class="benefits__intro">
        <h2 class="benefits__title">Uma gestão mais simples, rápida e organizada.</h2>
        <p class="benefits__text">Organize todos os horários da sua instituição num só lugar. Tenha uma visão clara das turmas, formadores, módulos e salas, tornando o planeamento diário mais fácil e evitando sobreposições e conflitos</p>
      </div>
    </section>

    <section class="features" id="funcionalidades">
      <div class="features__inner">
        <div class="features__intro">
          <h2 class="features__title">Tudo o que precisa para uma gestão eficiente</h2>
          <p class="features__subtitle">Funcionalidades desenvolvidas para tornar o planeamento, a organização e o acompanhamento dos horários mais simples.</p>
        </div>

        <svg class="roadmap" viewBox="0 0 1411 793" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
          <path class="roadmap__road" d="M490 793C700 700 990 680 995 600C1000 530 760 540 620 440C560 400 1100 390 1100 345C1100 300 850 320 850 300"/>
          <g class="roadmap__edge">
            <path d="M490 793C700 700 900 650 995 603"/>
            <path d="M505 488C600 530 740 540 805 558"/>
            <path d="M615 443C800 410 1000 380 1100 348"/>
            <path d="M850 303C880 340 960 350 1030 358"/>
            <path d="M930 303C1000 280 1100 275 1205 271"/>
          </g>
          <g class="roadmap__pins">
            <path class="roadmap__pin roadmap__pin--soft" d="M893 253a35 35 0 1 0 0 .1ZM893 288l-12-18h24Z"/>
            <path class="roadmap__pin" d="M1045 313a35 35 0 1 0 0 .1ZM1045 348l-12-18h24Z"/>
            <path class="roadmap__pin" d="M747 371a35 35 0 1 0 0 .1ZM747 406l-12-18h24Z"/>
            <path class="roadmap__pin" d="M893 523a35 35 0 1 0 0 .1ZM893 558l-12-18h24Z"/>
            <path class="roadmap__pin" d="M480 650a35 35 0 1 0 0 .1ZM480 685l-12-18h24Z"/>
          </g>
        </svg>

        <article class="feature-card feature-card--05">
          <span class="feature-card__number">05</span>
          <h3 class="feature-card__title">Horários</h3>
          <p class="feature-card__text">Crie e organize horários evitando sobreposições e conflitos.</p>
        </article>
        <article class="feature-card feature-card--03">
          <span class="feature-card__number">03</span>
          <h3 class="feature-card__title">Módulos</h3>
          <p class="feature-card__text">Registe os módulos e associe-os às respetivas turmas.</p>
        </article>
        <article class="feature-card feature-card--04">
          <span class="feature-card__number">04</span>
          <h3 class="feature-card__title">Salas</h3>
          <p class="feature-card__text">Controle as salas disponíveis e a sua utilização.</p>
        </article>
        <article class="feature-card feature-card--01">
          <span class="feature-card__number">01</span>
          <h3 class="feature-card__title">Formadores</h3>
          <p class="feature-card__text">Cadastre e organize os formadores da instituição.</p>
        </article>
        <article class="feature-card feature-card--02">
          <span class="feature-card__number">02</span>
          <h3 class="feature-card__title">Turmas</h3>
          <p class="feature-card__text">Gerencie as turmas e as suas informações.</p>
        </article>
      </div>
    </section>
  </main>

  <footer class="footer" id="contactos">
    <p>@SGGH, 2026.</p>
    <p>Todos os direitos reservados. Este conteúdo é protegido por direitos de autor.<br>A reprodução, distribuição, edição ou uso não autorizado deste material,total ou parcial, sem consentimento prévio está estritamente proibido.</p>
    <p class="footer__contact">+258 843667407<br><a href="mailto:sggh@gmail.com">sggh@gmail.com</a></p>
  </footer>

  <script src="../js/script.js"></script>
</body>
</html>
