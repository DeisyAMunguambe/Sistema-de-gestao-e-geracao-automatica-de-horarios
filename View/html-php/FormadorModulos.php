<?php
require_once '../../bootstrap.php';
require_login();
require_manage();
$id = (int)($_GET['id'] ?? $_POST['id_formador'] ?? 0);
$f = one('SELECT * FROM formador WHERE id_formador=?', 'i', $id);
if (!$f) redirect('Formador.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ids = array_values(array_unique(array_filter(array_map('intval', $_POST['modulos'] ?? []))));
        $db = db();
        $db->begin_transaction();
        execute('DELETE FROM for_mod WHERE id_formador=?', 'i', $id);
        foreach ($ids as $mid) execute('INSERT INTO for_mod(id_formador,id_modulo) VALUES(?,?)', 'ii', $id, $mid);
        $db->commit();
        flash('success', 'Módulos do formador actualizados.');
    } catch (Throwable $e) {
        if (isset($db) && $db instanceof mysqli) $db->rollback();
        flash('error', 'Não foi possível guardar: '.$e->getMessage());
    }
    redirect('FormadorModulos.php?id='.$id);
}
$modulos = rows('SELECT m.*, c.nome AS curso FROM modulo m JOIN curso c ON c.id_curso=m.id_curso ORDER BY c.nome,m.ordem,m.nome');
$assigned = rows('SELECT id_modulo FROM for_mod WHERE id_formador=?', 'i', $id);
$idsAssigned = array_map('intval', array_column($assigned, 'id_modulo'));
layout_start('Módulos do formador', 'Seleccione todos os módulos que este formador pode leccionar.');
?>
<div class="toolbar"><a class="btn btn-light border" href="Formador.php">← Voltar aos formadores</a></div>
<div class="card form-card" style="margin-bottom:18px">
  <h3><?= e($f['nome']) ?></h3>
  <p class="hint">Um formador pode leccionar vários módulos e um módulo pode ser associado a vários formadores.</p>
  <form method="post">
    <input type="hidden" name="id_formador" value="<?= $id ?>">
    <div class="table-wrap"><table class="table align-middle mb-0"><thead><tr><th>Seleccionar</th><th>Módulo</th><th>Sigla</th><th>Curso</th><th>Carga horária</th></tr></thead><tbody>
    <?php foreach ($modulos as $m): ?><tr>
      <td><input type="checkbox" name="modulos[]" value="<?= (int)$m['id_modulo'] ?>" <?= in_array((int)$m['id_modulo'], $idsAssigned, true) ? 'checked' : '' ?>></td>
      <td><?= e($m['nome']) ?></td><td><?= e($m['siglas']) ?></td><td><?= e($m['curso']) ?></td><td><?= (int)$m['carga_horaria'] ?></td>
    </tr><?php endforeach; ?>
    <?php if (!$modulos): ?><tr><td colspan="5" class="empty">Não existem módulos cadastrados.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="form-actions"><button class="btn btn-primary">Guardar módulos</button></div>
  </form>
</div>
<?php layout_end();
