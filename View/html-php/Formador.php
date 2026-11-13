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
            $email = post('email');
            $telefone = post('telefone');

            if (mb_strlen($nome) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Nome ou e-mail inválido.');
            }

            if ($id) {
                execute(
                    'UPDATE formador SET nome=?,email=?,telefone=? WHERE id_formador=?',
                    'sssi',
                    $nome,
                    $email,
                    $telefone,
                    $id
                );
            } else {
                execute(
                    'INSERT INTO formador(nome,email,telefone) VALUES(?,?,?)',
                    'sss',
                    $nome,
                    $email,
                    $telefone
                );
            }

            flash('success', 'Formador guardado.');
        } elseif ($acao === 'remover') {
            execute('DELETE FROM formador WHERE id_formador=?', 'i', (int) post('id'));
            flash('success', 'Formador removido.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Formador.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows(
        'SELECT * FROM formador WHERE nome LIKE ? OR email LIKE ? OR telefone LIKE ? ORDER BY nome',
        'sss',
        '%' . $q . '%',
        '%' . $q . '%',
        '%' . $q . '%'
    )
    : rows('SELECT * FROM formador ORDER BY nome');

layout_start('Formadores');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar formador...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Novo formador">+ Novo formador</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><strong><?= e($r['nome']) ?></strong></td>
                    <td><?= e($r['email']) ?></td>
                    <td><?= e($r['telefone']) ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-sm btn-light border" href="Disponibilidade.php?id=<?= $r['id_formador'] ?>">Disponibilidade</a>
                            <a class="btn btn-sm btn-light border" href="FormadorModulos.php?id=<?= $r['id_formador'] ?>">Módulos</a>
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar formador" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover formador" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$data): ?>
                <tr>
                    <td colspan="4" class="empty">Nenhum formador encontrado.</td>
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
                        <label>Nome completo</label>
                        <input name="nome" maxlength="100" value="" required>
                    </div>
                    <div class="field">
                        <label>E-mail</label>
                        <input type="email" name="email" value="" required>
                    </div>
                    <div class="field">
                        <label>Telefone</label>
                        <input name="telefone" maxlength="20" value="">
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
