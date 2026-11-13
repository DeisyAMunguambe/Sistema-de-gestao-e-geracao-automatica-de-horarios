
<?php
require_once '../../bootstrap.php';
require_login();

$utilizador = user();
$idUtilizador = (int)($utilizador['id'] ?? $utilizador['id_utilizador'] ?? 0);
$isFormador = is_formador();
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';
$idFormador = 0;
$erro = '';
$formadores = [];

if ($isFormador) {
    $ligacao = one(
        'SELECT id_formador FROM formador WHERE email=? LIMIT 1',
        's',
        (string)($utilizador['email'] ?? '')
    );

    $idFormador = (int)($ligacao['id_formador'] ?? 0);
} else {
    if (!can_manage()) {
        http_response_code(403);
        exit('Não tem permissão para aceder ao chat.');
    }

    $idFormador = (int)($_GET['formador'] ?? 0);
}

if (!$isFormador) {
    $formadores = rows(
        "SELECT f.id_formador, f.nome, f.email,
            (
                SELECT COUNT(*)
                FROM chat_mensagem m
                JOIN chat_conversa c ON c.id_conversa = m.id_conversa
                WHERE c.id_formador = f.id_formador
                AND m.id_remetente <> ?
                AND m.lida_em IS NULL
            ) AS nao_lidas
        FROM formador f
        ORDER BY f.nome",
        'i',
        $idUtilizador
    );

    if (!$idFormador && $formadores) {
        $idFormador = (int)$formadores[0]['id_formador'];
    }
}

$formador = $idFormador
    ? one('SELECT * FROM formador WHERE id_formador=?', 'i', $idFormador)
    : null;

if ($isFormador && !$formador) {
    $erro = 'A sua conta não está associada a um formador. O e-mail da conta deve coincidir com o e-mail registado em Formadores.';
}

$idConversa = 0;

if ($formador) {
    $conversa = one(
        'SELECT * FROM chat_conversa WHERE id_formador=?',
        'i',
        $idFormador
    );

    if (!$conversa) {
        $admin = one(
            "SELECT id_utilizador
            FROM utilizador
            WHERE perfil='Chefe de Departamento'
            AND ativo=1
            ORDER BY id_utilizador
            LIMIT 1"
        );

        if ($admin) {
            execute(
                'INSERT IGNORE INTO chat_conversa(id_formador, id_administrador) VALUES(?, ?)',
                'ii',
                $idFormador,
                (int)$admin['id_utilizador']
            );

            $conversa = one(
                'SELECT * FROM chat_conversa WHERE id_formador=?',
                'i',
                $idFormador
            );
        } else {
            $erro = 'Não existe uma conta activa de Chefe de Departamento para receber as mensagens.';
        }
    }

    if ($conversa) {
        $idConversa = (int)$conversa['id_conversa'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idConversa) {
    if (post('acao') === 'enviar') {
        $texto = post('mensagem');
        $idHorario = (int)post('id_horario');

        if ($texto === '' || mb_strlen($texto) > 5000) {
            $erro = 'Escreva uma mensagem com até 5000 caracteres.';
        } elseif ($idHorario) {
            $aula = one(
                'SELECT id_horario FROM horario WHERE id_horario=? AND id_formador=?',
                'ii',
                $idHorario,
                $idFormador
            );

            if (!$aula) {
                $erro = 'A aula seleccionada não pertence a este formador.';
            }
        }

        if (!$erro) {
            execute(
                'INSERT INTO chat_mensagem(id_conversa, id_remetente, id_horario, mensagem) VALUES(?, ?, NULLIF(?, 0), ?)',
                'iiis',
                $idConversa,
                $idUtilizador,
                $idHorario,
                $texto
            );

            redirect(
                'Chat.php?embed=1' . ($isFormador ? '' : '&formador=' . $idFormador)
            );
        }
    }
}

$mensagens = [];
$aulas = [];

if ($idConversa) {
    execute(
        'UPDATE chat_mensagem SET lida_em=NOW() WHERE id_conversa=? AND id_remetente<>? AND lida_em IS NULL',
        'ii',
        $idConversa,
        $idUtilizador
    );

    $mensagens = rows(
        'SELECT m.*, u.nome AS remetente, h.dia_semana, h.hora_inicio, h.hora_fim,
            t.nome AS turma, mo.nome AS modulo_nome
        FROM chat_mensagem m
        JOIN utilizador u ON u.id_utilizador = m.id_remetente
        LEFT JOIN horario h ON h.id_horario = m.id_horario
        LEFT JOIN turma t ON t.id_turma = h.id_turma
        LEFT JOIN modulo mo ON mo.id_modulo = h.id_modulo
        WHERE m.id_conversa=?
        ORDER BY m.enviada_em ASC, m.id_mensagem ASC',
        'i',
        $idConversa
    );

    $aulas = rows(
        "SELECT h.id_horario, h.dia_semana, h.hora_inicio, h.hora_fim,
            t.nome AS turma, mo.nome AS modulo_nome
        FROM horario h
        JOIN turma t ON t.id_turma = h.id_turma
        JOIN modulo mo ON mo.id_modulo = h.id_modulo
        WHERE h.id_formador=?
        ORDER BY FIELD(h.dia_semana, 'Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'),
            h.hora_inicio",
        'i',
        $idFormador
    );
}

if (!$embed) {
    layout_start('Chat Box', 'Mensagens entre formadores e administração.');
}
?>

<?php if ($embed): ?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat Box SGH</title>

    <style>
        :root {
            --brand: #7b1fa2;
            --brand-d: #5e1580;
            --brand-soft: #f1e1f7;
            --bg: #fdf6fd;
            --surface: #fff;
            --text: #1a1320;
            --muted: #6e6277;
            --line: #efe3f4;
            --soft: #f6ecf8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font: 13px/1.5 Inter, "Segoe UI", system-ui, Arial, sans-serif;
            color: var(--text);
            background: var(--surface);
        }

        .wrap {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            display: flex;
            flex: 1;
            min-height: 0;
            background: var(--surface);
        }

        .people {
            width: 178px;
            flex-shrink: 0;
            overflow: auto;
            border-right: 1px solid var(--line);
            background: var(--bg);
        }

        .people a {
            display: block;
            padding: 13px 12px;
            color: var(--text);
            text-decoration: none;
            border-bottom: 1px solid var(--line);
            font-size: 12px;
            overflow-wrap: anywhere;
            transition: background .15s;
        }

        .people a:hover {
            background: var(--soft);
        }

        .people a.active {
            background: var(--brand-soft);
            color: var(--brand-d);
            font-weight: 700;
            border-left: 3px solid var(--brand);
        }

        .people small {
            display: inline-block;
            color: var(--brand);
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 2px 7px;
            margin-top: 6px;
            font-size: 10px;
            font-weight: 700;
        }

        .panel {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--surface);
        }

        .title {
            padding: 15px 17px;
            border-bottom: 1px solid var(--line);
            font-weight: 700;
            color: var(--text);
            background: var(--surface);
        }

        .messages {
            flex: 1;
            min-height: 0;
            overflow: auto;
            padding: 16px;
            background: var(--bg);
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .bubble {
            max-width: 88%;
            align-self: flex-start;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px 14px 14px 4px;
            padding: 10px 12px;
            overflow-wrap: anywhere;
            box-shadow: 0 2px 5px rgba(45, 20, 55, .035);
        }

        .bubble.mine {
            align-self: flex-end;
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
            border-radius: 14px 14px 4px 14px;
        }

        .bubble small {
            display: block;
            font-size: 10px;
            color: var(--muted);
            margin-top: 6px;
        }

        .bubble.mine small {
            color: #f3e4fa;
        }

        .context {
            font-size: 11px;
            margin-top: 7px;
            font-style: normal;
            color: var(--brand-d);
            background: var(--brand-soft);
            padding: 5px 7px;
            border-radius: 7px;
        }

        .bubble.mine .context {
            color: #fff;
            background: #ffffff25;
        }

        .compose {
            padding: 12px;
            border-top: 1px solid var(--line);
            background: var(--surface);
            display: grid;
            gap: 9px;
        }

        .compose select,
        .compose textarea {
            width: 100%;
            border: 1px solid #ddcfe4;
            border-radius: 9px;
            padding: 10px;
            background: var(--surface);
            color: var(--text);
            font: inherit;
            outline: none;
        }

        .compose select:focus,
        .compose textarea:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px #7b1fa21c;
        }

        .compose textarea {
            resize: vertical;
            min-height: 70px;
        }

        .send {
            justify-self: end;
            background: var(--brand);
            color: #fff;
            border: 0;
            border-radius: 9px;
            padding: 10px 17px;
            font: 600 12px Inter, "Segoe UI", sans-serif;
            cursor: pointer;
            transition: background .15s;
        }

        .send:hover {
            background: var(--brand-d);
        }

        .error {
            padding: 11px;
            color: #9b1c1c;
            background: #fff0f0;
            border-bottom: 1px solid #f4caca;
        }

        .empty {
            margin: auto;
            text-align: center;
            color: var(--muted);
            padding: 22px;
        }

        .no-convo {
            padding: 18px;
            color: var(--muted);
        }

        @media (max-width: 480px) {
            .people {
                width: 112px;
            }

            .people a {
                padding: 10px 7px;
                font-size: 11px;
            }

            .messages {
                padding: 10px;
            }

            .compose {
                padding: 9px;
            }

            .bubble {
                max-width: 95%;
            }
        }
    </style>
</head>

<body>
<div class="wrap">
    <?php else: ?>

        <div class="card" style="padding:16px">

<?php endif; ?>

<?php if ($erro): ?>
    <div class="error"><?= e($erro) ?></div>
<?php endif; ?>

<div class="content">

    <?php if (!$isFormador): ?>
        <nav class="people">
            <?php foreach ($formadores as $f): ?>
                <a class="<?= ((int)$f['id_formador'] === $idFormador ? 'active' : '') ?>"
                    href="Chat.php?embed=1&amp;formador=<?=(int)$f['id_formador']?>">
                    <?= e($f['nome']) ?>

                    <?php if ((int)$f['nao_lidas'] > 0): ?>
                        <small><?= $f['nao_lidas'] ?> por ler</small>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <section class="panel">

        <div class="title">
            <?= e($isFormador ? 'Conversa com a administração' : ($formador['nome'] ?? 'Seleccione um formador')) ?>
        </div>

        <?php if (!$idConversa): ?>

            <div class="no-convo">
                Não foi possível abrir esta conversa.
            </div>

        <?php else: ?>

            <div class="messages" id="chatMessages">

                <?php if (!$mensagens): ?>
                    <div class="empty">
                        Ainda não há mensagens. Envie a primeira mensagem.
                    </div>
                <?php endif; ?>

                <?php foreach ($mensagens as $m): ?>
                    <div class="bubble <?= ((int)$m['id_remetente'] === $idUtilizador ? 'mine' : '') ?>">

                        <div><?= nl2br(e($m['mensagem'])) ?></div>

                        <?php if ($m['id_horario']): ?>
                            <div class="context">
                                Aula:
                                <?= e($m['turma'] . ' · ' . $m['modulo_nome'] . ' · ' . $m['dia_semana'] . ' ' .
                                    substr($m['hora_inicio'], 0, 5) . '-' .  substr($m['hora_fim'], 0, 5)) ?>
                            </div>
                        <?php endif; ?>

                        <small>
                            <?= e($m['remetente']) ?> ·
                            <?= e(date('d/m H:i', strtotime($m['enviada_em']))) ?>
                        </small>
                    </div>
                <?php endforeach; ?>

            </div>

            <form method="post" class="compose">

                <input type="hidden" name="acao" value="enviar">
                <select name="id_horario">
                    <option value="0">Mensagem geral (sem aula)</option>
                    <?php foreach ($aulas as $a): ?>
                        <option value="<?=(int)$a['id_horario']?>">
                            <?= e($a['turma'] . ' · ' . $a['modulo_nome'] . ' · ' . $a['dia_semana'] . ' ' .
                                substr($a['hora_inicio'], 0, 5)
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <textarea name="mensagem" maxlength="5000" required placeholder="Escreva a sua mensagem..." ></textarea>

                <button class="send" type="submit"> Enviar mensagem </button>

            </form>
        <?php endif; ?>
    </section>
</div>
<?php if ($embed): ?>
</div>

<script>
    const m = document.getElementById('chatMessages');

    if (m) {
        m.scrollTop = m.scrollHeight;
    }
</script>

</body>
</html>

<?php else: ?>
</div>
<?php
layout_end();
endif;
?>
