<?php

require_once '../../bootstrap.php';

require_login();

/* ---------- Contadores ---------- */
$c = [
    'turmas' => (int) scalar('SELECT COUNT(*) FROM turma'),
    'formadores' => (int) scalar('SELECT COUNT(*) FROM formador'),
    'salas' => (int) scalar('SELECT COUNT(*) FROM sala'),
    'aulas' => (int) scalar('SELECT COUNT(*) FROM horario'),
];

$GLOBALS['_tabs'] = [
    ['Visão geral', 'TelaPrincipal.php', true],
    ['Horários', 'GerarHorario.php', false],
];

/* ---------- Turmas recentes ---------- */
$turmas = rows(
    'SELECT t.id_turma, t.nome, t.turno, t.data_inicio, c.nome curso,
            (SELECT COUNT(*) FROM horario h WHERE h.id_turma = t.id_turma) aulas
     FROM turma t
     JOIN curso c ON c.id_curso = t.id_curso
     ORDER BY t.id_turma DESC
     LIMIT 8'
);

layout_start('Início');
?>
<div class="row g-4">
    <div class="col-12">
        <!-- <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a class="promo" style="background:var(--c1)" <?= can_access('Formador.php') ? 'href="Formador.php"' : '' ?>>
                    <span class="ico"><?= icon('users', 56) ?></span>
                    <div>
                        <h3>Formadores</h3>
                        <p>Registe docentes e defina quando podem lecionar.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="promo" style="background:var(--c2)" href="Modulo.php">
                    <span class="ico"><?= icon('book', 56) ?></span>
                    <div>
                        <h3>Módulos</h3>
                        <p>Crie a estrutura curricular com carga e tipo de sala.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a class="promo" style="background:var(--c3)" href="GerarHorario.php">
                    <span class="ico"><?= icon('calendar', 56) ?></span>
                    <div>
                        <h3>Gerar horário</h3>
                        <p>O gerador evita conflitos de turma, formador e sala.</p>
                    </div>
                </a>
            </div>
        </div> -->

        <div class="card">
            <div class="status-tabs">
                <a class="on" <?= can_access('Turma.php') ? 'href="Turma.php"' : '' ?>>
                    <?= icon('school', 28) ?>
                    <div><strong>Turmas</strong><span><?= $c['turmas'] ?> registadas</span></div>
                </a>
                <a <?= can_access('Formador.php') ? 'href="Formador.php"' : '' ?>>
                    <?= icon('users', 28) ?>
                    <div><strong>Formadores</strong><span><?= $c['formadores'] ?> registados</span></div>
                </a>
                <a <?= can_access('Sala.php') ? 'href="Sala.php"' : '' ?>>
                    <?= icon('room', 28) ?>
                    <div><strong>Salas</strong><span><?= $c['salas'] ?> registadas</span></div>
                </a>
                <a href="GerarHorario.php">
                    <?= icon('calendar', 28) ?>
                    <div><strong>Aulas geradas</strong><span><?= $c['aulas'] ?> aulas</span></div>
                </a>
            </div>

            <div class="table-wrap">
                <table class="table align-middle">
                    <!-- <thead>
                        <tr>
                            <th>Turma</th>
                            <th>Estado</th>
                            <th>Formadores</th>
                            <th>Início</th>
                            <th>Aulas</th>
                        </tr>
                    </thead> -->
                    <tbody>
                        <tr class="group-row">
                            <td colspan="5">TURMAS RECENTES</td>
                        </tr>
                        <?php foreach ($turmas as $t): ?>
                            <?php
                            $formadores = rows(
                                'SELECT DISTINCT f.nome FROM horario h
                                 JOIN formador f ON f.id_formador = h.id_formador
                                 WHERE h.id_turma=? LIMIT 3',
                                'i',
                                $t['id_turma']
                            );
                            ?>
                            <tr>
                                <td>
                                    <div class="doc">
                                        <?= icon('book-open', 24) ?>
                                        <div>
                                            <strong><?= e($t['nome']) ?></strong>
                                            <small><?= e($t['curso']) ?> · <?= e(ucfirst($t['turno'])) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="dot <?= $t['aulas'] ? 'ok' : '' ?>"></span>
                                    <?= $t['aulas'] ? 'Horário gerado' : 'Sem horário' ?>
                                </td>
                                <td>
                                    <?php if ($formadores): ?>
                                        <div class="avs">
                                            <?php foreach ($formadores as $i => $f): ?>
                                                <span class="av av<?= $i % 3 ?>" title="<?= e($f['nome']) ?>"><?= e(strtoupper(substr($f['nome'], 0, 1))) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="hint">Sem formadores</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($t['data_inicio'] ?: '—') ?></td>
                                <td><?= (int) $t['aulas'] ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$turmas): ?>
                            <tr>
                                <td colspan="5" class="empty">Ainda não há turmas.<?= can_manage() ? ' Crie a primeira em “Criar novo…”.' : '' ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- <div class="col-xl-3">
        <div class="card p-4">
            <h2 class="h5 fw-bold mb-3">Regras do gerador</h2>
            <ul class="hint ps-3 mb-0" style="line-height:1.8">
                <li>Uma turma não tem duas aulas em simultâneo.</li>
                <li>Um formador não está em duas turmas ao mesmo tempo.</li>
                <li>Uma sala não é usada por duas aulas ao mesmo tempo.</li>
                <li>Capacidade, tipo de sala e disponibilidade são respeitados.</li>
            </ul>
        </div>
    </div> -->
</div>
<?php
layout_end();
