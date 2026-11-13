<?php

require_once '../../bootstrap.php';

if (logged()) {
    redirect('TelaPrincipal.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = post('email');
    $senha = (string) ($_POST['senha'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        $error = 'Preencha o e-mail e a palavra-passe.';
    } else {
        $u = one(
            'SELECT id_utilizador,nome,email,senha,perfil FROM utilizador WHERE email=? AND ativo=1',
            's',
            $email
        );

        if ($u && !in_array($u['perfil'], perfis(), true)) {
            $error = 'O perfil desta conta não é válido. Contacte o Chefe de Departamento.';
        } elseif ($u && password_verify($senha, $u['senha'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $u['id_utilizador'],
                'nome' => $u['nome'],
                'email' => $u['email'],
                'perfil' => $u['perfil'],
            ];
            redirect('TelaPrincipal.php');
        }

        if ($error === '') {
            $error = 'E-mail ou palavra-passe incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Entre no Horix – Sistema de Gestão de Horários.">
  <meta name="theme-color" content="#7b1fa2">
  <title>Entrar – Horix</title>
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%237b1fa2'/%3E%3Cpath d='M9 12h14M9 17h14M9 22h8' stroke='%23fff' stroke-width='2.5' stroke-linecap='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Outfit:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/login.css">
</head>
<body>

  <main class="login">

    <aside class="login__showcase" aria-hidden="true">
      <span class="login__blob login__blob--one"></span>
      <span class="login__blob login__blob--two"></span>
      <span class="login__blob login__blob--three"></span>

      <a href="../../index.php" class="login__brand" tabindex="-1">
        <img class="login__logo" src="../img/logo-branco.png" alt="Horix">
      </a>

      <div class="login__showcase-content">
        <h2 class="login__showcase-title">Horários sem conflitos, num só lugar.</h2>
        <p class="login__showcase-text">Formadores, turmas, módulos e salas perfeitamente organizados.</p>

        <div class="mini-schedule">
          <div class="mini-schedule__header">
            <span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span>
          </div>
          <div class="mini-schedule__grid">
            <span class="mini-schedule__block mini-schedule__block--purple">Programação</span>
            <span class="mini-schedule__block mini-schedule__block--lilac">Redes</span>
            <span class="mini-schedule__block"></span>
            <span class="mini-schedule__block mini-schedule__block--purple">Base de dados</span>
            <span class="mini-schedule__block mini-schedule__block--lilac">PHP</span>

            <span class="mini-schedule__block mini-schedule__block--lilac">Java</span>
            <span class="mini-schedule__block"></span>
            <span class="mini-schedule__block mini-schedule__block--purple">Web</span>
            <span class="mini-schedule__block mini-schedule__block--lilac">SQL</span>
            <span class="mini-schedule__block"></span>
          </div>
        </div>

        <span class="login__badge login__badge--top">✓ Zero conflitos</span>
        <span class="login__badge login__badge--bottom">✓ Zero sobreposição</span>
      </div>
    </aside>

    <section class="login__panel">
      <div class="login__card">

        <a href="../../index.php" class="login__back">← Voltar ao início</a>

        <h1 class="login__title">Bem-vindo de volta</h1>
        <p class="login__subtitle">Entre na sua conta para gerir os horários da instituição.</p>

        <form class="login-form" id="loginForm" method="post" action="Login.php" novalidate>

          <div class="field" id="fieldEmail">
            <label class="field__label" for="email">Email</label>
            <div class="field__box">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              <input class="field__input" type="email" id="email" name="email" placeholder="o.seu@email.com" value="<?= e(post('email')) ?>" autocomplete="username" required>
            </div>
            <p class="field__error" id="emailError" role="alert"></p>
          </div>

          <div class="field" id="fieldPassword">
            <label class="field__label" for="password">Palavra-passe</label>
            <div class="field__box">
              <svg class="field__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
              <input class="field__input" type="password" id="password" name="senha" placeholder="••••••••" autocomplete="current-password" required>
              <button class="field__toggle" type="button" id="togglePassword" aria-label="Mostrar palavra-passe">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <p class="field__error" id="passwordError" role="alert"></p>
          </div>

          

          <p class="login-form__message<?= $error ? ' login-form__message--error' : '' ?>" id="formMessage" role="status"><?= e($error) ?></p>

          <button class="login-form__submit" type="submit" id="submitButton">
            <span class="login-form__submit-text">Entrar</span>
            <span class="login-form__spinner" aria-hidden="true"></span>
          </button>
        </form>
      </div>

      <p class="login__copy">© Horix, 2026. Todos os direitos reservados.</p>
    </section>
  </main>

  <script src="../js/login.js"></script>
</body>
</html>
