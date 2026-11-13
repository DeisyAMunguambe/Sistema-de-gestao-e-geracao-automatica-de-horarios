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
            $dep = (int) post('id_departamento');

            if (!$dep || mb_strlen($nome) < 2) {
                throw new Exception('Preencha nome e departamento.');
            }

            if ($id) {
                execute('UPDATE bloco_de_salas SET nome=?,id_departamento=? WHERE id_bloco=?', 'sii', $nome, $dep, $id);
            } else {
                execute('INSERT INTO bloco_de_salas(nome,id_departamento) VALUES(?,?)', 'si', $nome, $dep);
            }

            flash('success', 'Bloco guardado.');
        } elseif ($acao === 'remover') {
            execute('DELETE FROM bloco_de_salas WHERE id_bloco=?', 'i', (int) post('id'));
            flash('success', 'Bloco removido.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Bloco.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows(
        'SELECT b.*, d.nome dep
         FROM bloco_de_salas b
         JOIN departamento d ON d.id_departamento = b.id_departamento
         WHERE b.nome LIKE ? OR d.nome LIKE ?
         ORDER BY b.nome',
        'ss',
        '%' . $q . '%',
        '%' . $q . '%'
    )
    : rows(
        'SELECT b.*, d.nome dep
         FROM bloco_de_salas b
         JOIN departamento d ON d.id_departamento = b.id_departamento
         ORDER BY b.nome'
    );

$deps = rows('SELECT * FROM departamento ORDER BY nome');

layout_start('Blocos de salas', 'Agrupe salas por edifício ou bloco.');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar bloco...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Novo bloco">+ Novo bloco</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Bloco</th>
                <th>Departamento</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><?= e($r['nome']) ?></td>
                    <td><?= e($r['dep']) ?></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar bloco" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover bloco" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$data): ?>
                <tr>
                    <td colspan="3" class="empty">Nenhum bloco encontrado.</td>
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
                        <label>Nome</label>
                        <input name="nome" value="" required>
                    </div>
                    <div class="field">
                        <label>Departamento</label>
                        <select name="id_departamento" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($deps as $d): ?>
                                <option value="<?= $d['id_departamento'] ?>" >
                                    <?= e($d['nome']) ?>
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
