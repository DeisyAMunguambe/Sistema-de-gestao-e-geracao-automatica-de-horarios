<?php

require_once '../../bootstrap.php';

require_login();
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
            $perfil = post('perfil');
            $senha = (string) ($_POST['senha'] ?? '');

            if (
                mb_strlen($nome) < 3
                || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || !in_array($perfil, perfis(), true)
            ) {
                throw new Exception('Dados do utilizador inválidos.');
            }

            if ($id) {
                // Evita ficar sem nenhum Chefe de Departamento (bloqueio total do sistema).
                $actual = one('SELECT perfil FROM utilizador WHERE id_utilizador=?', 'i', $id);
                if (
                    $actual && $actual['perfil'] === PERFIL_CHEFE && $perfil !== PERFIL_CHEFE
                    && (int) scalar('SELECT COUNT(*) FROM utilizador WHERE perfil=? AND ativo=1', 's', PERFIL_CHEFE) <= 1
                ) {
                    throw new Exception('Tem de existir pelo menos um Chefe de Departamento activo.');
                }

                if ($senha !== '') {
                    execute(
                        'UPDATE utilizador SET nome=?,email=?,perfil=?,senha=? WHERE id_utilizador=?',
                        'ssssi',
                        $nome,
                        $email,
                        $perfil,
                        password_hash($senha, PASSWORD_DEFAULT),
                        $id
                    );
                } else {
                    execute(
                        'UPDATE utilizador SET nome=?,email=?,perfil=? WHERE id_utilizador=?',
                        'sssi',
                        $nome,
                        $email,
                        $perfil,
                        $id
                    );
                }
            } else {
                if (mb_strlen($senha) < 8) {
                    throw new Exception('A palavra-passe deve ter pelo menos 8 caracteres.');
                }

                execute(
                    'INSERT INTO utilizador(nome,email,senha,perfil) VALUES(?,?,?,?)',
                    'ssss',
                    $nome,
                    $email,
                    password_hash($senha, PASSWORD_DEFAULT),
                    $perfil
                );
            }

            flash('success', 'Utilizador guardado.');
        } elseif ($acao === 'remover') {
            $id = (int) post('id');

            if ($id == user()['id']) {
                throw new Exception('Não pode remover a sua própria conta.');
            }

            $alvo = one('SELECT perfil FROM utilizador WHERE id_utilizador=?', 'i', $id);
            if (
                $alvo && $alvo['perfil'] === PERFIL_CHEFE
                && (int) scalar('SELECT COUNT(*) FROM utilizador WHERE perfil=? AND ativo=1', 's', PERFIL_CHEFE) <= 1
            ) {
                throw new Exception('Tem de existir pelo menos um Chefe de Departamento activo.');
            }

            execute('DELETE FROM utilizador WHERE id_utilizador=?', 'i', $id);
            flash('success', 'Utilizador removido.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Utilizador.php');
}

/* ---------- Dados para a página ---------- */
$data = $q !== ''
    ? rows(
        'SELECT * FROM utilizador WHERE nome LIKE ? OR email LIKE ? ORDER BY nome',
        'ss',
        '%' . $q . '%',
        '%' . $q . '%'
    )
    : rows('SELECT * FROM utilizador ORDER BY nome');

layout_start('Utilizadores', 'Contas de acesso e perfis.');
?>
<div class="toolbar">
    <form class="toolbar-left" method="get">
        <input class="search" name="q" value="<?= e($q) ?>" placeholder="Pesquisar utilizador...">
        <button class="btn btn-light border">Pesquisar</button>
    </form>
    <button type="button" class="btn btn-primary" data-modal="novo" data-title="Novo utilizador">+ Novo utilizador</button>
</div>

<div class="card table-wrap">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Estado</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><?= e($r['nome']) ?></td>
                    <td><?= e($r['email']) ?></td>
                    <td><span class="badge blue"><?= e($r['perfil']) ?></span></td>
                    <td><span class="badge green">Activo</span></td>
                    <td>
                        <div class="actions">
                            <button type="button" class="btn btn-sm btn-light border" data-modal="editar" data-title="Editar utilizador" data-row="<?= row_json($r) ?>">Editar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-modal="remover" data-title="Remover utilizador" data-row="<?= row_json($r) ?>">Remover</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
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
                        <label>E-mail</label>
                        <input type="email" name="email" value="" required>
                    </div>
                    <div class="field">
                        <label>Perfil</label>
                        <select name="perfil">
                            <?php foreach (perfis() as $p): ?>
                                <option><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Palavra-passe <small class="text-muted fw-normal">(ao editar, vazio mantém a actual)</small></label>
                        <input type="password" name="senha" minlength="8">
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
