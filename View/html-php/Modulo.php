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
            $sig = post('siglas');
            $ch = (int) post('carga_horaria');
            $ord = (int) post('ordem');
            $tipo = post('tipo_sala');
            $curso = (int) post('id_curso');

            if (mb_strlen($nome) < 3 || mb_strlen($sig) < 2 || $ch < 1 || $ord < 1 || !$curso) {
                throw new Exception('Verifique os dados do módulo.');
            }

            if ($id) {
                execute(
                    'UPDATE modulo SET nome=?,siglas=?,carga_horaria=?,ordem=?,tipo_sala=?,id_curso=? WHERE id_modulo=?',
                    'ssiisii',
                    $nome,
                    $sig,
                    $ch,
                    $ord,
                    $tipo,
                    $curso,
                    $id
                );
            } else {
                execute(
                    'INSERT INTO modulo(nome,siglas,carga_horaria,ordem,tipo_sala,id_curso) VALUES(?,?,?,?,?,?)',
                    'ssiisi',
                    $nome,
                    $sig,
                    $ch,
                    $ord,
                    $tipo,
                    $curso
                );
            }

            flash('success', 'Módulo guardado.');
        } elseif ($acao === 'remover') {
            execute('DELETE FROM modulo WHERE id_modulo=?', 'i', (int) post('id'));
            flash('success', 'Módulo removido.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Modulo.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows(
        'SELECT m.*, c.nome curso
         FROM modulo m
         JOIN curso c ON c.id_curso = m.id_curso
         WHERE m.nome LIKE ? OR m.siglas LIKE ? OR c.nome LIKE ?
         ORDER BY m.ordem, m.nome',
        'sss',
        '%' . $q . '%',
        '%' . $q . '%',
        '%' . $q . '%'
    )
    : rows(
        'SELECT m.*, c.nome curso
         FROM modulo m
         JOIN curso c ON c.id_curso = m.id_curso
         ORDER BY m.ordem, m.nome'
    );

$cursos = rows('SELECT * FROM curso ORDER BY nome');

layout_start('Módulos');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar módulo...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Novo módulo">+ Novo módulo</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Sigla</th>
                <th>Módulo</th>
                <th>Curso</th>
                <th>C.H.</th>
                <th>Ordem</th>
                <th>Sala</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><span class="badge blue"><?= e($r['siglas']) ?></span></td>
                    <td><?= e($r['nome']) ?></td>
                    <td><?= e($r['curso']) ?></td>
                    <td><?= e($r['carga_horaria']) ?></td>
                    <td><?= e($r['ordem']) ?></td>
                    <td><?= e($r['tipo_sala'] ?: 'Qualquer') ?></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar módulo" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover módulo" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$data): ?>
                <tr>
                    <td colspan="7" class="empty">Nenhum módulo encontrado.</td>
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
                        <label>Sigla</label>
                        <input name="siglas" maxlength="20" value="" required>
                    </div>
                    <div class="field">
                        <label>Carga horária (tempos)</label>
                        <input type="number" min="1" name="carga_horaria" value="40" required>
                    </div>
                    <div class="field">
                        <label>Ordem</label>
                        <input type="number" min="1" name="ordem" value="1" required>
                    </div>
                    <div class="field">
                        <label>Tipo de sala</label>
                        <select name="tipo_sala">
                            <option value="">Qualquer</option>
                            <option>Normal</option>
                            <option>Laboratorio</option>
                            <option>Auditorio</option>
                        </select>
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
