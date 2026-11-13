<?php

session_start();

require_once __DIR__ . '/Conexao/Conexao.php';

/* ------------------------------------------------------------------
 * Utilitários gerais
 * ---------------------------------------------------------------- */

/** Escapa texto para saída segura em HTML. */
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

/** Linha da BD em JSON seguro para atributos HTML (sem a palavra-passe). */
function row_json(array $r): string
{
    unset($r['senha']);

    return e(json_encode($r, JSON_UNESCAPED_UNICODE));
}

/** Redirecciona para outra página e termina o script. */
function redirect($url): never
{
    header('Location: ' . $url);
    exit;
}

/** Lê um campo de $_POST (com trim). */
function post($key, $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

/* ------------------------------------------------------------------
 * Mensagens flash
 * ---------------------------------------------------------------- */

function flash($type, $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $f;
}

/* ------------------------------------------------------------------
 * Autenticação e permissões
 * ---------------------------------------------------------------- */

const PERFIL_CHEFE = 'Chefe de Departamento';
const PERFIL_FORMADOR = 'Formador';

/** Perfis válidos do sistema. */
function perfis(): array
{
    return [PERFIL_CHEFE, PERFIL_FORMADOR];
}

/**
 * Páginas (em View/html-php) protegidas por perfil.
 * Qualquer página aqui listada só abre para os perfis indicados; as restantes
 * páginas protegidas exigem apenas sessão iniciada.
 */
function page_acl(): array
{
    $todos = [PERFIL_CHEFE, PERFIL_FORMADOR];
    $chefe = [PERFIL_CHEFE];

    return [
        // Chefe de Departamento e Formador
        'TelaPrincipal.php' => $todos,
        'GerarHorario.php' => $todos,
        // Apenas Chefe de Departamento
        'HorarioPDF.php' => $chefe,
        'Formador.php' => $chefe,
        'FormadorModulos.php' => $chefe,
        'Disponibilidade.php' => $chefe,
        'Modulo.php' => $chefe,
        'Turma.php' => $chefe,
        'Sala.php' => $chefe,
        'Bloco.php' => $chefe,
        'Curso.php' => $chefe,
        'Departamento.php' => $chefe,
        'Utilizador.php' => $chefe,
    ];
}

function logged(): bool
{
    return !empty($_SESSION['user']);
}

/**
 * Revalida a sessão contra a base de dados (uma vez por pedido): se a conta
 * foi desactivada, removida ou mudou de perfil, a sessão é actualizada ou terminada.
 */
function refresh_session_user(): void
{
    static $done = false;

    if ($done || !logged()) {
        return;
    }

    $done = true;
    $u = one(
        'SELECT id_utilizador,nome,email,perfil FROM utilizador WHERE id_utilizador=? AND ativo=1',
        'i',
        (int) ($_SESSION['user']['id'] ?? 0)
    );

    if (!$u || !in_array($u['perfil'], perfis(), true)) {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('error', 'A sua sessão não é válida. Inicie sessão novamente.');
        redirect(login_url());
    }

    $_SESSION['user'] = [
        'id' => $u['id_utilizador'],
        'nome' => $u['nome'],
        'email' => $u['email'],
        'perfil' => $u['perfil'],
    ];
}

/** Caminho para Login.php, válido a partir de qualquer página de View/html-php. */
function login_url(): string
{
    return 'Login.php';
}

function require_login(): void
{
    if (!logged()) {
        redirect(login_url());
    }

    refresh_session_user();
}

function user(): array
{
    return $_SESSION['user'] ?? [];
}

/** Perfil do utilizador autenticado ('' se não houver sessão). */
function perfil(): string
{
    return (string) (user()['perfil'] ?? '');
}

function is_chefe(): bool
{
    return logged() && perfil() === PERFIL_CHEFE;
}

function is_formador(): bool
{
    return logged() && perfil() === PERFIL_FORMADOR;
}

/** Operações administrativas (criar, editar, eliminar, gerar, exportar): só o Chefe de Departamento. */
function can_manage(): bool
{
    return is_chefe();
}

/** Indica se o perfil actual pode abrir a página (ficheiro) indicada. */
function can_access(string $file): bool
{
    if (!logged()) {
        return false;
    }

    $acl = page_acl();

    return !isset($acl[$file]) || in_array(perfil(), $acl[$file], true);
}

/** Nega o acesso: 403 para pedidos que alteram dados (POST), aviso + redirecção para os restantes. */
function deny_access(): never
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>Acesso negado</title>'
            . '<p>Acesso negado: não tem permissão para esta operação.</p>'
            . '<p><a href="TelaPrincipal.php">Voltar à visão geral</a></p>';
        exit;
    }

    flash('error', 'Não tem permissão para aceder a esta funcionalidade.');
    redirect('TelaPrincipal.php');
}

function require_manage(): void
{
    require_login();

    if (!can_manage()) {
        deny_access();
    }
}

/**
 * Guarda central: corre em todas as páginas de View/html-php que incluem
 * este ficheiro, mesmo que a própria página se esqueça de verificar o perfil.
 */
function enforce_page_access(): void
{
    $file = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $acl = page_acl();

    if (!isset($acl[$file])) {
        return;
    }

    require_login();

    if (!can_access($file)) {
        deny_access();
    }
}

/* ------------------------------------------------------------------
 * Acesso à base de dados (prepared statements)
 * ---------------------------------------------------------------- */

/** Executa uma consulta e devolve o primeiro valor da primeira linha. */
function scalar(string $sql, string $types = '', ...$args)
{
    $st = db()->prepare($sql);

    if (!$st) {
        throw new RuntimeException(db()->error);
    }

    if ($types) {
        $st->bind_param($types, ...$args);
    }

    $st->execute();
    $r = $st->get_result();

    return $r ? ($r->fetch_row()[0] ?? 0) : $st->affected_rows;
}

/** Executa uma consulta e devolve todas as linhas. */
function rows(string $sql, string $types = '', ...$args): array
{
    $st = db()->prepare($sql);

    if (!$st) {
        throw new RuntimeException(db()->error);
    }

    if ($types) {
        $st->bind_param($types, ...$args);
    }

    $st->execute();
    $r = $st->get_result();

    return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
}

/** Executa uma consulta e devolve apenas a primeira linha (ou null). */
function one(string $sql, string $types = '', ...$args): ?array
{
    $a = rows($sql, $types, ...$args);

    return $a[0] ?? null;
}

/** Executa INSERT / UPDATE / DELETE. */
function execute(string $sql, string $types = '', ...$args): bool
{
    $st = db()->prepare($sql);

    if (!$st) {
        throw new RuntimeException(db()->error);
    }

    if ($types) {
        $st->bind_param($types, ...$args);
    }

    return $st->execute();
}

/* ------------------------------------------------------------------
 * Interface: título, menu e ícones
 * ---------------------------------------------------------------- */

function page_title(string $title): void
{
    $GLOBALS['_title'] = $title;
}

/** Devolve "active" se $file for a página actual. */
function active(string $file): string
{
    return basename($_SERVER['PHP_SELF']) === $file ? 'active' : '';
}

/** Devolve um ícone SVG inline. */
function icon(string $name, int $size = 18): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'plan' => '<path d="M4 5h16M4 12h16M4 19h10"/><circle cx="17" cy="19" r="3"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8M8 11h8"/>',
        'school' => '<path d="m3 9 9-5 9 5-9 5z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 10v6"/>',
        'room' => '<path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"/><path d="M9 21v-5h6v5M8 7h2M14 7h2M8 11h2M14 11h2"/>',
        'building' => '<path d="M4 21V4h12v17M16 9h4v12M8 8h4M8 12h4M8 16h4"/>',
        'book-open' => '<path d="M3 5a2 2 0 0 1 2-2h6v17H5a2 2 0 0 0-2 2zM21 5a2 2 0 0 0-2-2h-6v17h6a2 2 0 0 1 2 2z"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'logout' => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
        'moon' => '<path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8z"/>',
    ];

    $body = $paths[$name] ?? $paths['dashboard'];

    return '<svg class="icon-svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

/** Menu lateral completo por secções: [título, [[ficheiro, rótulo, ícone], ...]]. */
function nav_all(): array
{
    return [
        ['', [
            ['TelaPrincipal.php', 'Visão geral', 'dashboard'],
            ['GerarHorario.php', 'Horários', 'calendar'],

        ]],
        ['Gestão', [
            ['Formador.php', 'Formadores', 'users'],
            ['Modulo.php', 'Módulos', 'book'],
            ['Turma.php', 'Turmas', 'school'],
            ['Sala.php', 'Salas', 'room'],
        ]],
        ['Estrutura', [
            ['Bloco.php', 'Blocos', 'building'],
            ['Curso.php', 'Cursos', 'book-open'],
            ['Departamento.php', 'Departamentos', 'building'],
            ['Utilizador.php', 'Utilizadores', 'user'],
        ]],
    ];
}

/** Menu lateral apenas com as entradas permitidas ao perfil actual. */
function nav(): array
{
    $menu = [];

    foreach (nav_all() as $secao) {
        $itens = array_values(array_filter($secao[1], fn($n) => can_access($n[0])));

        if ($itens) {
            $menu[] = [$secao[0], $itens];
        }
    }

    return $menu;
}

/* ------------------------------------------------------------------
 * Layout comum das páginas (Bootstrap 5)
 *
 * Opcional, definido pela página antes de layout_start():
 *   $GLOBALS['_banner'] = 'HTML da faixa superior';
 *   $GLOBALS['_tabs']   = [['Rótulo', 'ficheiro.php', true/false], ...];
 * ---------------------------------------------------------------- */

function layout_start(string $title, string $subtitle = ''): void
{
    page_title($title);
    $fl = flashes();
    $banner = $GLOBALS['_banner'] ?? '';
    $tabs = $GLOBALS['_tabs'] ?? [];
    $u = user();
    $inicial = e(strtoupper(substr($u['nome'] ?? 'U', 0, 1)));
?>
    <!doctype html>
    <html lang="pt">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= e($title) ?> · SGH</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500&family=Open+Sans:wght@400;600&family=Outfit:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../css/bootstrap.min.css">
        <link rel="stylesheet" href="../css/app.css">
    </head>

    <body>
        <?php if ($banner): ?>
            <div class="top-banner alert alert-dismissible fade show rounded-0 mb-0 text-center" role="alert">
                <?= $banner ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <aside class="sidebar">
        <div class="brand">
            <img src="../img/horix.png" alt="Horix - Gestão de Horários">
        </div>

        <?php if (can_manage()): ?>
            <div class="dropdown">
                <a class="create-new" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>+</span> Criar novo…
                </a>
                <ul class="dropdown-menu shadow-sm">
                    <li><a class="dropdown-item" href="Turma.php#form">Turma</a></li>
                    <li><a class="dropdown-item" href="Formador.php#form">Formador</a></li>
                    <li><a class="dropdown-item" href="Modulo.php#form">Módulo</a></li>
                    <li><a class="dropdown-item" href="Sala.php#form">Sala</a></li>
                    <li><a class="dropdown-item" href="Curso.php#form">Curso</a></li>
                </ul>
            </div>
        <?php endif; ?>

        <nav>
            <?php foreach (nav() as $secao): ?>
                <?php if ($secao[0] !== ''): ?>
                    <div class="nav-title"><?= e(mb_strtoupper($secao[0])) ?></div>
                <?php endif; ?>
                <?php foreach ($secao[1] as $n): ?>
                    <a class="nav-link <?= active($n[0]) ?>" href="<?= $n[0] ?>">
                        <span class="nav-icon"><?= icon($n[2], 20) ?></span>
                        <span><?= $n[1] ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom dropup">
            <div class="user-row">
                <div class="avatar"><?= $inicial ?></div>
                <!-- <span class="flex-grow-1"><?= e($u['nome'] ?? 'Utilizador') ?></span> -->
                <a href="#" class="text-body px-2" data-bs-toggle="dropdown" aria-label="Opções">•••</a>
                <ul class="dropdown-menu shadow-sm">
                    <li><button class="dropdown-item" type="button" onclick="toggleTheme()">Modo claro / escuro</button></li>
                    <li><a class="dropdown-item" href="Logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
        </aside>

        <main class="main">
            <header class="topbar">
                <div class="title-wrap">
                    <div>
                        <h1><?= e($title) ?></h1>
                        <?php if ($subtitle && !$tabs): ?>
                            <p><?= e($subtitle) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($tabs): ?>
                        <nav class="tabs-bar">
                            <?php foreach ($tabs as $t): ?>
                                <a class="<?= $t[2] ? 'on' : '' ?>" href="<?= $t[1] ?>"><?= e($t[0]) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    <?php endif; ?>
                </div>
                <div class="profile">
                    <div class="avatar"><?= $inicial ?></div>
                    <div>
                        <strong><?= e($u['nome'] ?? 'Utilizador') ?></strong>
                        <small><?= e($u['perfil'] ?? '') ?></small>
                    </div>
                </div>
            </header>

            <?php foreach ($fl as $f): ?>
                <div class="alert alert-<?= $f['type'] === 'error' ? 'danger' : 'success' ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>

            <section class="content">
            <?php
        }

        function layout_end(): void
        {
            ?>
            </section>
        </main>
        <script src="../js/bootstrap.bundle.min.js"></script>
        <script src="../js/app.js"></script>
    </body>

    </html>
<?php
        }

enforce_page_access();
