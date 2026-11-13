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
            $curso = (int) post('id_curso');
            $turno = post('turno');
            $num = (int) post('num_formandos');
            $data = post('data_inicio') ?: null;

            if (
                mb_strlen($nome) < 2
                || !$curso
                || !in_array($turno, ['manha', 'tarde', 'noite'], true)
                || $num < 1
            ) {
                throw new Exception('Verifique os dados da turma.');
            }

            if ($id) {
                execute(
                    'UPDATE turma SET nome=?,id_curso=?,turno=?,num_formandos=?,data_inicio=? WHERE id_turma=?',
                    'sisisi',
                    $nome,
                    $curso,
                    $turno,
                    $num,
                    $data,
                    $id
                );
            } else {
                execute(
                    'INSERT INTO turma(nome,id_curso,turno,num_formandos,data_inicio) VALUES(?,?,?,?,?)',
                    'sisis',
                    $nome,
                    $curso,
                    $turno,
                    $num,
                    $data
                );
            }

            flash('success', 'Turma guardada.');
        } elseif ($acao === 'remover') {
            execute('DELETE FROM turma WHERE id_turma=?', 'i', (int) post('id'));
            flash('success', 'Turma removida.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Turma.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows(
        'SELECT t.*, c.nome curso
         FROM turma t
         JOIN curso c ON c.id_curso = t.id_curso
         WHERE t.nome LIKE ? OR c.nome LIKE ?
         ORDER BY t.nome',
        'ss',
        '%' . $q . '%',
        '%' . $q . '%'
    )
    : rows(
        'SELECT t.*, c.nome curso
         FROM turma t
         JOIN curso c ON c.id_curso = t.id_curso
         ORDER BY t.nome'
    );

$cursos = rows('SELECT * FROM curso ORDER BY nome');

layout_start('Turmas', 'Configure turno, capacidade e data de início da turma.');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar turma...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Nova turma">+ Nova turma</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Turma</th>
                <th>Curso</th>
                <th>Turno</th>
                <th>Formandos</th>
                <th>Início</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><strong><?= e($r['nome']) ?></strong></td>
                    <td><?= e($r['curso']) ?></td>
                    <td><span class="badge blue"><?= e(ucfirst($r['turno'])) ?></span></td>
                    <td><?= e($r['num_formandos']) ?></td>
                    <td><?= e($r['data_inicio'] ?: '—') ?></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar turma" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover turma" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$data): ?>
                <tr>
                    <td colspan="6" class="empty">Nenhuma turma encontrada.</td>
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
                        <label>Curso</label>
                        <select name="id_curso" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id_curso'] ?>" >
                                    <?= e($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Turno</label>
                        <select name="turno">
                            <option value="manha">Manhã</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite" selected>Noite</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>N.º de formandos</label>
                        <input type="number" min="1" max="500" name="num_formandos" value="30" required>
                    </div>
                    <div class="field">
                        <label>Data de início</label>
                        <input type="date" name="data_inicio" value="">
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
