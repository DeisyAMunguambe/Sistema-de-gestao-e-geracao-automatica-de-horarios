<?php

require_once '../../bootstrap.php';

// require_login();
require_manage();

$id = (int) ($_GET['id'] ?? $_POST['id_formador'] ?? 0);
$f = one('SELECT * FROM formador WHERE id_formador=?', 'i', $id);

if (!$f) {
    redirect('Formador.php');
}

/* ---------- Adicionar intervalo ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dia = post('dia');
        $ini = post('hora_inicio');
        $fim = post('hora_fim');
        $allowed = ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'];

        if (!in_array($dia, $allowed, true) || $ini >= $fim) {
            throw new Exception('Dia ou intervalo inválido.');
        }

        execute(
            'INSERT INTO disponibilidade(id_formador,dia_semana,hora_inicio,hora_fim) VALUES(?,?,?,?)',
            'isss',
            $id,
            $dia,
            $ini,
            $fim
        );

        flash('success', 'Disponibilidade adicionada.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível: ' . $e->getMessage());
    }

    redirect('Disponibilidade.php?id=' . $id);
}

/* ---------- Remover intervalo ---------- */
if (isset($_GET['del'])) {
    execute(
        'DELETE FROM disponibilidade WHERE id_disponibilidade=? AND id_formador=?',
        'ii',
        (int) $_GET['del'],
        $id
    );

    flash('success', 'Disponibilidade removida.');
    redirect('Disponibilidade.php?id=' . $id);
}

/* ---------- Dados para a página ---------- */
$data = rows(
    "SELECT * FROM disponibilidade
     WHERE id_formador=?
     ORDER BY FIELD(dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado'), hora_inicio",
    'i',
    $id
);

layout_start('Disponibilidade', 'Defina quando ' . $f['nome'] . ' pode lecionar.');
?>
<div class="toolbar">
    <a class="btn btn-light border" href="Formador.php">← Voltar</a>
</div>

<div class="grid-2">
    <div class="card form-card">
        <h3>Adicionar intervalo</h3>
        <form method="post">
            <input type="hidden" name="id_formador" value="<?= $id ?>">

            <div class="field">
                <label>Dia</label>
                <select name="dia">
                    <option>Segunda</option>
                    <option>Terca</option>
                    <option>Quarta</option>
                    <option>Quinta</option>
                    <option>Sexta</option>
                    <option>Sabado</option>
                </select>
            </div>

            <div class="form-grid" style="margin-top:14px">
                <div class="field">
                    <label>Início</label>
                    <input type="time" name="hora_inicio" value="17:00" required>
                </div>
                <div class="field">
                    <label>Fim</label>
                    <input type="time" name="hora_fim" value="20:55" required>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary">Adicionar</button>
            </div>
        </form>
    </div>

    <div class="card table-wrap">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Dia</th>
                    <th>Intervalo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $r): ?>
                    <tr>
                        <td><?= e($r['dia_semana']) ?></td>
                        <td><?= e(substr($r['hora_inicio'], 0, 5)) ?> – <?= e(substr($r['hora_fim'], 0, 5)) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-danger" data-confirm="Remover este intervalo?" href="?id=<?= $id ?>&del=<?= $r['id_disponibilidade'] ?>">Remover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$data): ?>
                    <tr>
                        <td colspan="3" class="empty">Sem disponibilidade definida.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
layout_end();
