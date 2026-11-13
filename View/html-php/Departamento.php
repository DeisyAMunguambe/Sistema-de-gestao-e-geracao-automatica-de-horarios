<?php

require_once '../../bootstrap.php';

// require_login();
require_manage();

$q = post('q', $_GET['q'] ?? '');

/* ---------- Guardar / remover ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = post('acao');

    try {
        if ($acao === 'guardar') {
            $id = (int) post('id');
            $nome = post('nome');

            if (mb_strlen($nome) < 2) {
                throw new Exception('O nome deve ter pelo menos 2 caracteres.');
            }

            if ($id) {
                execute('UPDATE departamento SET nome=? WHERE id_departamento=?', 'si', $nome, $id);
            } else {
                execute('INSERT INTO departamento(nome) VALUES(?)', 's', $nome);
            }

            flash('success', 'Departamento guardado com sucesso.');
        } elseif ($acao === 'remover') {
            execute('DELETE FROM departamento WHERE id_departamento=?', 'i', (int) post('id'));
            flash('success', 'Departamento removido.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível concluir: ' . $e->getMessage());
    }

    redirect('Departamento.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows('SELECT * FROM departamento WHERE nome LIKE ? ORDER BY nome', 's', '%' . $q . '%')
    : rows('SELECT * FROM departamento ORDER BY nome');

layout_start('Departamentos', 'Organize as áreas académicas do sistema.');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar departamento...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Novo departamento">+ Novo departamento</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Nome</th>
                <th style="width:180px">Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><?= e($r['nome']) ?></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar departamento" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover departamento" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$data): ?>
                <tr>
                    <td colspan="2" class="empty">Nenhum departamento encontrado.</td>
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

                <div class="field">
                    <label>Nome</label>
                    <input name="nome" maxlength="100" value="" required>
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
