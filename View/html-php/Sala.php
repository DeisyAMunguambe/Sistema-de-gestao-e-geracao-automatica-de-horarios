<?php

require_once '../../bootstrap.php';

// require_login();
require_manage();

$q = post('q', $_GET['q'] ?? '');

/* ---------- Guardar / remover ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $acao = post('acao');

        if ($acao === 'guardar') {
            $id = (int) post('id');
            $nome = post('nome');
            $cap = (int) post('capacidade');
            $tipo = post('tipo');
            $bloco = (int) post('id_bloco');

            if (mb_strlen($nome) < 1 || $cap < 1 || !$bloco || !$tipo) {
                throw new Exception('Preencha todos os campos correctamente.');
            }

            if ($id) {
                execute(
                    'UPDATE sala SET nome=?,capacidade=?,tipo=?,id_bloco=? WHERE id_sala=?',
                    'sisii',
                    $nome,
                    $cap,
                    $tipo,
                    $bloco,
                    $id
                );
            } else {
                execute(
                    'INSERT INTO sala(nome,capacidade,tipo,id_bloco) VALUES(?,?,?,?)',
                    'sisi',
                    $nome,
                    $cap,
                    $tipo,
                    $bloco
                );
            }

            flash('success', 'Sala guardada.');
        } elseif ($acao === 'remover') {
            execute('DELETE FROM sala WHERE id_sala=?', 'i', (int) post('id'));
            flash('success', 'Sala removida.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Sala.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows(
        'SELECT s.*, b.nome bloco
         FROM sala s
         JOIN bloco_de_salas b ON b.id_bloco = s.id_bloco
         WHERE s.nome LIKE ? OR s.tipo LIKE ? OR b.nome LIKE ?
         ORDER BY s.nome',
        'sss',
        '%' . $q . '%',
        '%' . $q . '%',
        '%' . $q . '%'
    )
    : rows(
        'SELECT s.*, b.nome bloco
         FROM sala s
         JOIN bloco_de_salas b ON b.id_bloco = s.id_bloco
         ORDER BY s.nome'
    );

$blocos = rows('SELECT * FROM bloco_de_salas ORDER BY nome');

layout_start('Salas', 'Capacidade e tipo são usados automaticamente pelo gerador.');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar sala...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Nova sala">+ Nova sala</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Sala</th>
                <th>Tipo</th>
                <th>Capacidade</th>
                <th>Bloco</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><?= e($r['nome']) ?></td>
                    <td><span class="badge"><?= e($r['tipo']) ?></span></td>
                    <td><?= e($r['capacidade']) ?></td>
                    <td><?= e($r['bloco']) ?></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar sala" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover sala" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$data): ?>
                <tr>
                    <td colspan="5" class="empty">Nenhuma sala encontrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="modalForm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="guardar">
                    <input type="hidden" name="id" value="0">
                    <p class="modal-aviso text-danger d-none">Confirme a remoção. Os dados abaixo não podem ser editados.</p>

                <div class="form-grid">
                    <div class="field">
                        <label>Nome / número</label>
                        <input name="nome" value="" required>
                    </div>
                    <div class="field">
                        <label>Capacidade</label>
                        <input type="number" min="1" max="500" name="capacidade" value="30" required>
                    </div>
                    <div class="field">
                        <label>Tipo</label>
                        <select name="tipo">
                            <option>Normal</option>
                            <option>Laboratorio de Informatica</option>
                            <option>Laboratorio de Electricidade</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Bloco</label>
                        <select name="id_bloco" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($blocos as $b): ?>
                                <option value="<?= $b['id_bloco'] ?>" >
                                    <?= e($b['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary modal-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
layout_end();
