<?php

require_once '../../bootstrap.php';

require_login();
require_once '../../Controller/GeradorHorarios.php';
require_once '../../Controller/PeriodosHorario.php';

$db = db();
$db->query("CREATE TABLE IF NOT EXISTS horario_ciclo (id_ciclo INT AUTO_INCREMENT PRIMARY KEY,id_turma INT NOT NULL,numero INT NOT NULL,data_inicio DATE NOT NULL,data_expiracao DATE NOT NULL,estado ENUM('Activo','Expirado','Arquivado') NOT NULL DEFAULT 'Activo',criado_por VARCHAR(150) NULL,criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_turma_ciclo(id_turma,numero)) ENGINE=InnoDB");
$db->query("CREATE TABLE IF NOT EXISTS horario_historico (id_historico INT AUTO_INCREMENT PRIMARY KEY,id_ciclo INT NOT NULL,id_turma INT NOT NULL,id_modulo INT NOT NULL,id_formador INT NOT NULL,id_sala INT NOT NULL,dia_semana VARCHAR(20) NOT NULL,turno VARCHAR(20) NOT NULL,hora_inicio TIME NOT NULL,hora_fim TIME NOT NULL,arquivado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(id_turma,id_modulo)) ENGINE=InnoDB");

// Primeira execução: regista como ciclo 1 os horários que já existiam antes desta funcionalidade.
$turmasComHorarioAntigo = rows("SELECT t.id_turma FROM turma t JOIN horario h ON h.id_turma=t.id_turma GROUP BY t.id_turma");
foreach ($turmasComHorarioAntigo as $ta) {
    $temCiclo = (int) scalar('SELECT COUNT(*) FROM horario_ciclo WHERE id_turma=?', 'i', (int)$ta['id_turma']);
    
    if (!$temCiclo) {
        execute(
            "INSERT INTO horario_ciclo(id_turma,numero,data_inicio,data_expiracao,estado,criado_por)
         VALUES(?,1,CURDATE(),CURDATE(),'Activo','Migração automática')",
            'i',
            (int)$ta['id_turma']
        );
    }
}


$turmas = rows('SELECT * FROM turma ORDER BY nome');
$sel = (int) ($_GET['turma'] ?? 0);

/* ---------- Geração de vários horários por turma ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_manage();
    set_time_limit(20); // se o gerador demorar, o PHP pára aos 20 s e liberta a sessão
    $id = (int) post('turma');
    if ($id <= 0) {
        flash('error', 'Seleccione uma turma antes de continuar.');
        redirect('GerarHorario.php');
    }

    $temHorario = (int) scalar('SELECT COUNT(*) FROM horario WHERE id_turma=?', 'i', $id) > 0;
    $proximo = $temHorario || post('acao') === 'proximo';
    $g = new GeradorHorarios(db());
    if ($g->gerar($id, $proximo)) {
        $numero = (int) scalar('SELECT COALESCE(MAX(numero),0)+1 FROM horario_ciclo WHERE id_turma=?', 'i', $id);
        execute("INSERT INTO horario_ciclo(id_turma,numero,data_inicio,data_expiracao,estado,criado_por) VALUES(?,?,CURDATE(),CURDATE(),'Activo',?)", 'iis', $id, $numero, (string)(user()['nome'] ?? 'Administrador'));
        flash('success', $proximo
            ? 'Novo horário gerado. Os módulos do horário anterior e dos horários arquivados não foram repetidos.'
            : 'Horário inicial gerado com sucesso.');
    } else {
        flash('error', $g->getErro());
    }
    redirect('GerarHorario.php?turma=' . $id);
}

/* ---------- Grelha semanal: intervalos reais de aula ---------- */
$dias = ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'];
$turnoSelecionado = 'noite';
if ($sel > 0) {
    foreach ($turmas as $turmaItem) {
        if ((int)$turmaItem['id_turma'] === $sel) {
            $turnoSelecionado = strtolower((string)$turmaItem['turno']);
            break;
        }
    }
}
$periodos = [
    'manha' => [['07:00', '07:45'], ['07:45', '08:30'], ['08:30', '09:15'], ['09:15', '10:00'], ['10:00', '10:45'], ['10:45', '11:30'], ['11:30', '12:15']],
    'tarde' => [['13:00', '13:45'], ['13:45', '14:30'], ['14:30', '15:15'], ['15:15', '16:00'], ['16:00', '16:45'], ['16:45', '17:30']],
    'noite' => [['17:00', '17:45'], ['17:45', '18:30'], ['18:30', '19:15'], ['19:25', '20:10'], ['20:10', '20:55']],
];
$periodosTurno = $periodos[$turnoSelecionado] ?? $periodos['noite'];
$linhasGrelha = $periodosTurno;
// O sábado usa períodos de 45 minutos dentro da janela 07:00–14:00.
$periodosSabado = [];
for ($m = 420; $m + 45 <= 840; $m += 45) $periodosSabado[] = [sprintf('%02d:%02d', intdiv($m, 60), $m % 60), sprintf('%02d:%02d', intdiv($m + 45, 60), ($m + 45) % 60)];
foreach ($periodosSabado as $ps) if (!in_array($ps, $linhasGrelha, true)) $linhasGrelha[] = $ps;
usort($linhasGrelha, fn($a, $b) => strcmp($a[0], $b[0]));
$times = [];
$periodos = [];
foreach ($linhasGrelha as $pair) {
    $times[] = $pair[0];
    $periodos[$pair[0]] = $pair[1];
}

$where = $sel ? ' WHERE h.id_turma=' . $sel : '';

$data = rows(
    "SELECT h.*, t.nome turma, m.siglas AS siglas, m.nome AS modulo_nome, m.carga_horaria,
            f.nome formador, s.nome sala
     FROM horario h
     JOIN turma t ON t.id_turma = h.id_turma
     JOIN modulo m ON m.id_modulo = h.id_modulo
     JOIN formador f ON f.id_formador = h.id_formador
     JOIN sala s ON s.id_sala = h.id_sala
     $where
     ORDER BY FIELD(h.dia_semana,'Segunda','Terca','Quarta','Quinta','Sexta','Sabado'),
              h.hora_inicio, t.nome"
);

// Agrupa por dia e hora de início
$group = [];

foreach ($data as $r) {
    $group[$r['dia_semana']][$r['hora_inicio']][] = $r;
}

// Módulos do horário activo com formador, verificador interno e período (início/fim).
$resumoModulos = $sel > 0 ? sgh_resumo_modulos($sel) : [];

layout_start('Horários');
?>
<div class="card form-card no-print" style="margin-bottom:18px">
    <div class="toolbar">
        <form class="schedule-tools">
            <select name="turma">
                <option value="0">Seleccione a turma</option>
                <?php foreach ($turmas as $t): ?>
                    <option value="<?= $t['id_turma'] ?>" <?= $sel == $t['id_turma'] ? 'selected' : '' ?>>
                        <?= e($t['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-light border">Filtrar</button>
        </form>

        <div class="schedule-tools">
            <?php if ($sel > 0): ?>
                <?php $temHorarioActual = (int) scalar('SELECT COUNT(*) FROM horario WHERE id_turma=?', 'i', $sel) > 0; ?>
                <?php if ($temHorarioActual && can_manage()): ?>
                    <a class="btn btn-light border" href="HorarioPDF.php?turma=<?= $sel ?>&auto=1" target="_blank" rel="noopener">Exportar PDF</a>
                <?php endif; ?>
                <?php if (can_manage()): ?>
                <form method="post"><input type="hidden" name="acao" value="<?= $temHorarioActual ? 'proximo' : 'gerar' ?>"><input type="hidden" name="turma" value="<?= $sel ?>"><button class="btn btn-primary" onclick="return confirm('<?= $temHorarioActual ? 'Gerar outro horário para esta turma? Os módulos já usados nos horários anteriores não serão repetidos.' : 'Gerar o horário inicial para esta turma?' ?>')"><?= $temHorarioActual ? '＋ Gerar novo horário' : 'GERAR HORARIO' ?></button></form>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

</div>

<div class="card table-wrap" style="margin-bottom:18px">
    <div class="p-3">
        <h3 class="h5 mb-1">Horários disponíveis</h3>
    </div>
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Turma</th>
                <th>Turno</th>
                <th>Tempos agendados</th>
                <th>Acção</th>
            </tr>
        </thead>
        <tbody>
            <?php $listaHorarios = rows("SELECT t.id_turma,t.nome,t.turno,COUNT(h.id_horario) AS total FROM turma t LEFT JOIN horario h ON h.id_turma=t.id_turma GROUP BY t.id_turma,t.nome,t.turno ORDER BY t.nome"); ?>
            <?php foreach ($listaHorarios as $lh): ?>
                <tr>
                    <td><?= e($lh['nome']) ?></td>
                    <td><?= e(ucfirst($lh['turno'])) ?></td>
                    <td><?= (int)$lh['total'] ?></td>
                    <td><a class="btn btn-light border btn-sm" href="GerarHorario.php?turma=<?= (int)$lh['id_turma'] ?>">Ver horário</a>
                        <?php if ((int)$lh['total'] > 0 && can_manage()): ?>
                            <a class="btn btn-light border btn-sm" href="HorarioPDF.php?turma=<?= (int)$lh['id_turma'] ?>&auto=1" target="_blank" rel="noopener">PDF</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$listaHorarios): ?><tr>
                    <td colspan="4" class="empty">Ainda não existem turmas.</td>
                </tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($sel > 0): ?>
    <div class="card table-wrap" style="margin-bottom:18px">
        <div class="p-3">
            <h3 class="h5 mb-1">Histórico de horários</h3>
        </div>
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Horário</th>
                    <th>Data de criação</th>
                    <th>Módulos guardados</th>
                </tr>
            </thead>
            <tbody>
                <?php $historicoCiclos = rows("SELECT c.id_ciclo,c.numero,c.data_inicio,COUNT(DISTINCT h.id_modulo) AS total_modulos FROM horario_ciclo c LEFT JOIN horario_historico h ON h.id_ciclo=c.id_ciclo WHERE c.id_turma=? AND c.estado='Arquivado' GROUP BY c.id_ciclo,c.numero,c.data_inicio ORDER BY c.numero DESC", 'i', $sel); ?>
                <?php foreach ($historicoCiclos as $hc): ?><tr>
                        <td>Horário <?= (int)$hc['numero'] ?></td>
                        <td><?= e($hc['data_inicio']) ?></td>
                        <td><?= (int)$hc['total_modulos'] ?></td>
                    </tr><?php endforeach; ?>
                <?php if (!$historicoCiclos): ?><tr>
                        <td colspan="3" class="empty">Ainda não existem horários anteriores.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card schedule">
        <table>
            <thead>
                <tr>
                    <th>Hora</th>
                    <?php foreach ($dias as $d): ?>
                        <th><?= e($d) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $aulasAte = array_fill_keys($dias, '00:00'); ?>
                <?php foreach ($times as $time): ?>
                    <tr>
                        <td class="time"><?= e($time) ?> – <?= e($periodos[$time] ?? date('H:i', strtotime($time . ' +45 minutes'))) ?></td>
                        <?php foreach ($dias as $d): ?>
                            <?php
                            $inicioAula = null;
                            foreach ($group[$d] ?? [] as $start => $arr) {
                                if (substr($start, 0, 5) === $time) {
                                    $inicioAula = $arr;
                                    break;
                                }
                            }
                            $aulaValida = [];
                            foreach ($inicioAula ?? [] as $aula) {
                                if (substr($aula['hora_fim'], 0, 5) > $time) $aulaValida[] = $aula;
                            }
                            // Procura também uma aula iniciada num tempo anterior que continue neste intervalo.
                            $aulaContinua = null;
                            if (!$aulaValida) {
                                foreach ($group[$d] ?? [] as $start => $arr) {
                                    if (substr($start, 0, 5) < $time) {
                                        foreach ($arr as $aula) {
                                            $fim = substr($aula['hora_fim'], 0, 5);
                                            if ($fim > $time) {
                                                $aulaContinua = $aula;
                                                break 2;
                                            }
                                        }
                                    }
                                }
                            }
                            if ($aulaValida):
                                $r = $aulaValida[0];
                                $fimAula = substr($r['hora_fim'], 0, 5);
                                $aulasAte[$d] = $fimAula;
                            ?>
                                <td class="slot class-slot">
                                    <div class="class">
                                        <strong><?= e($r['siglas']) ?> · <?= e($r['turma']) ?></strong>
                                        <small><?= e($r['formador']) ?></small>
                                        <small>Sala <?= e($r['sala']) ?></small>
                                    </div>
                                </td>
                            <?php elseif ($aulaContinua): ?>
                                <td class="slot class-continuation">
                                    <div class="class">
                                        <strong><?= e($aulaContinua['siglas']) ?> · <?= e($aulaContinua['turma']) ?></strong>
                                        <small><?= e($aulaContinua['formador']) ?></small>
                                        <small>Sala <?= e($aulaContinua['sala']) ?></small>
                                    </div>
                                </td>
                            <?php else: ?>
                                <td class="slot"></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <div class="card table-wrap" style="margin-top:18px">
        <div class="p-3">
            <h3 class="h5 mb-1">Duração prevista dos módulos</h3>
            <p class="hint mb-0">Calculada a partir da data de início da turma e da carga horária (1 tempo = 45 min). O verificador interno é outro formador que lecciona o mesmo módulo.</p>
        </div>
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Módulo</th>
                    <th>Formador</th>
                    <th>Verificador interno</th>
                    <th>Carga horária</th>
                    <th>Início</th>
                    <th>Fim previsto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumoModulos as $rm): ?>
                    <tr>
                        <td><strong><?= e($rm['siglas']) ?></strong> · <?= e($rm['modulo']) ?></td>
                        <td><?= e(implode(', ', $rm['formadores'])) ?></td>
                        <td><?= $rm['verificador'] ? e($rm['verificador']) : '—' ?></td>
                        <td><?= (int)$rm['carga_horaria'] ?> h</td>
                        <td><?= e(sgh_data($rm['inicio'])) ?></td>
                        <td><?= e(sgh_data($rm['fim'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$resumoModulos): ?>
                    <tr>
                        <td colspan="6" class="empty">Ainda não existe horário gerado para esta turma.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="card" style="padding:22px;margin-top:18px">
        <h3 class="h5">Seleccione uma turma</h3>
    </div>
<?php endif; ?>
<!-- Chat Box flutuante integrado na tela de Horários -->
<style>
.sgh-chat-fab{position:fixed;right:24px;bottom:24px;z-index:1050;width:60px;height:60px;border:0;border-radius:18px;background:var(--brand,#7b1fa2);color:#fff;box-shadow:0 8px 24px rgba(65,30,80,.22);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:transform .18s,box-shadow .18s}
.sgh-chat-fab:hover{transform:translateY(-3px);background:var(--brand-d,#5e1580);box-shadow:0 12px 28px rgba(65,30,80,.28)}
.sgh-chat-badge{position:absolute;top:-6px;right:-6px;min-width:23px;height:23px;padding:0 6px;border:2px solid var(--surface,#fff);border-radius:999px;background:#e5484d;color:#fff;font:700 11px/19px Inter,"Segoe UI",sans-serif;text-align:center;box-shadow:0 2px 6px #0002}
.sgh-chat-badge[hidden]{display:none}
.sgh-chat-fab svg{width:29px;height:29px}
.sgh-chat-modal{display:none;position:fixed;inset:0;z-index:1060;background:rgba(35,20,43,.28);align-items:flex-end;justify-content:flex-end;padding:22px;backdrop-filter:blur(2px)}
.sgh-chat-modal.open{display:flex}
.sgh-chat-window{width:min(560px,calc(100vw - 28px));height:min(720px,calc(100vh - 44px));background:var(--surface,#fff);border:1px solid var(--line,#efe3f4);border-radius:18px;overflow:hidden;box-shadow:0 20px 70px rgba(48,24,58,.22);display:flex;flex-direction:column}
.sgh-chat-head{height:62px;flex-shrink:0;background:var(--brand,#7b1fa2);color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 19px;font-weight:700;letter-spacing:.01em}
.sgh-chat-head button{border:0;background:transparent;color:#fff;font-size:27px;line-height:1;cursor:pointer}
.sgh-chat-frame{width:100%;height:100%;border:0;background:#fff;flex:1}
@media(max-width:600px){.sgh-chat-modal{padding:8px}.sgh-chat-window{width:100%;height:calc(100vh - 16px);border-radius:14px}.sgh-chat-fab{right:16px;bottom:16px;width:56px;height:56px}}
@media print{.sgh-chat-fab,.sgh-chat-modal{display:none!important}}
</style>
<button type="button" class="sgh-chat-fab no-print" id="sghChatOpen" aria-label="Abrir Chat Box" title="Chat com formadores e administração">
 <span class="sgh-chat-badge" id="sghChatBadge" hidden>0</span>
 <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.2A7.5 7.5 0 1 1 20 11.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7.5 10h9M7.5 13.5h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
</button>
<div class="sgh-chat-modal no-print" id="sghChatModal" aria-hidden="true">
 <section class="sgh-chat-window" role="dialog" aria-modal="true" aria-label="Chat Box SGH">
  <header class="sgh-chat-head"><span>Chat Box · SGH</span><button type="button" id="sghChatClose" aria-label="Fechar chat">&times;</button></header>
  <iframe class="sgh-chat-frame" id="sghChatFrame" title="Conversa com formadores e administração" data-src="Chat.php?embed=1"></iframe>
 </section>
</div>
<script>
(function(){
 const modal=document.getElementById('sghChatModal');
 const open=document.getElementById('sghChatOpen');
 const close=document.getElementById('sghChatClose');
 const frame=document.getElementById('sghChatFrame');
 const badge=document.getElementById('sghChatBadge');
 let chatAberto=false;
 function actualizarAviso(){fetch('ChatNotificacoes.php',{cache:'no-store',credentials:'same-origin'}).then(r=>r.ok?r.json():null).then(d=>{if(!d||typeof d.nao_lidas==='undefined')return;const n=Number(d.nao_lidas)||0;badge.textContent=n>99?'99+':String(n);badge.hidden=n<1;open.setAttribute('aria-label',n?('Abrir Chat Box, '+n+' mensagens por ler'):'Abrir Chat Box');}).catch(()=>{});}
 function showChat(){chatAberto=true;if(!frame.src) frame.src=frame.dataset.src; modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); badge.hidden=true; close.focus();}
 frame.addEventListener('load',function(){if(chatAberto){setTimeout(actualizarAviso,500);}});
 function hideChat(){chatAberto=false;modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); open.focus(); actualizarAviso();}
 open.addEventListener('click',showChat); close.addEventListener('click',hideChat);
 modal.addEventListener('click',function(e){if(e.target===modal) hideChat();});
 document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('open')) hideChat();});
 actualizarAviso();setInterval(actualizarAviso,12000);
})();
</script>
<?php
layout_end();
