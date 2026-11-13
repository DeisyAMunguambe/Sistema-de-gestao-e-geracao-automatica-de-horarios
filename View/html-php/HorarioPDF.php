<?php

require_once '../../bootstrap.php';

require_login();
require_once '../../Controller/PeriodosHorario.php';

sgh_garantir_tabelas();

$sel = (int) ($_GET['turma'] ?? 0);
$auto = !empty($_GET['auto']);
$dias = sgh_dias();
$nomeDia = ['Segunda' => 'Segunda', 'Terca' => 'Terça', 'Quarta' => 'Quarta', 'Quinta' => 'Quinta', 'Sexta' => 'Sexta', 'Sabado' => 'Sábado'];
$nomeTurno = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];

/**
 * Monta a grelha de uma turma: só as linhas de tempo usadas e, para cada dia,
 * blocos com rowspan (uma aula de vários tempos ocupa uma única célula).
 */
function pdf_montar_grelha(array $aulas, string $turno, array $dias): array
{
    $todas = sgh_linhas_grelha($turno);
    if (!$aulas || !$todas) {
        return ['linhas' => [], 'celulas' => [], 'cobertas' => []];
    }

    // 1) Linhas de tempo realmente usadas por alguma aula.
    $usadas = [];
    foreach ($aulas as $a) {
        $ini = substr((string) $a['hora_inicio'], 0, 5);
        $fim = substr((string) $a['hora_fim'], 0, 5);
        $achou = false;
        foreach ($todas as $i => $p) {
            if ($p[0] >= $ini && $p[0] < $fim) {
                $usadas[$i] = true;
                $achou = true;
            }
        }
        if (!$achou) { // hora fora da grelha: usa a primeira linha seguinte
            $alvo = count($todas) - 1;
            foreach ($todas as $i => $p) {
                if ($p[0] >= $ini) {
                    $alvo = $i;
                    break;
                }
            }
            $usadas[$alvo] = true;
        }
    }
    ksort($usadas);
    $linhas = [];
    foreach (array_keys($usadas) as $i) {
        $linhas[] = $todas[$i];
    }

    // 2) Blocos por dia (aulas sobrepostas ficam no mesmo bloco, sem se perderem).
    $porDia = [];
    foreach ($aulas as $a) {
        $porDia[$a['dia_semana']][] = $a;
    }

    $celulas = [];
    $cobertas = [];
    foreach ($dias as $d) {
        $lista = $porDia[$d] ?? [];
        usort($lista, fn($x, $y) => strcmp((string) $x['hora_inicio'], (string) $y['hora_inicio']));

        $blocos = [];
        $cur = null;
        foreach ($lista as $a) {
            $ini = substr((string) $a['hora_inicio'], 0, 5);
            $fim = substr((string) $a['hora_fim'], 0, 5);
            if ($cur !== null && $ini < $cur['fim']) {
                $cur['aulas'][] = $a;
                if ($fim > $cur['fim']) {
                    $cur['fim'] = $fim;
                }
                continue;
            }
            if ($cur !== null) {
                $blocos[] = $cur;
            }
            $cur = ['ini' => $ini, 'fim' => $fim, 'aulas' => [$a]];
        }
        if ($cur !== null) {
            $blocos[] = $cur;
        }

        foreach ($blocos as $b) {
            $r = count($linhas) - 1;
            foreach ($linhas as $i => $p) {
                if ($p[0] >= $b['ini']) {
                    $r = $i;
                    break;
                }
            }
            $n = 0;
            for ($k = $r; $k < count($linhas) && $linhas[$k][0] < $b['fim']; $k++) {
                $n++;
            }
            $n = max(1, $n);
            $celulas[$d][$r] = ['n' => $n, 'aulas' => $b['aulas']];
            for ($k = $r + 1; $k < $r + $n; $k++) {
                $cobertas[$d][$k] = true;
            }
        }
    }

    return ['linhas' => $linhas, 'celulas' => $celulas, 'cobertas' => $cobertas];
}

/* ---------- Dados de cada turma a exportar ---------- */
$turmas = $sel
    ? rows('SELECT t.*, c.nome AS curso FROM turma t LEFT JOIN curso c ON c.id_curso = t.id_curso WHERE t.id_turma = ?', 'i', $sel)
    : rows('SELECT t.*, c.nome AS curso FROM turma t LEFT JOIN curso c ON c.id_curso = t.id_curso ORDER BY t.nome');

$paginas = [];
foreach ($turmas as $t) {
    $tid = (int) $t['id_turma'];
    $aulas = rows(
        'SELECT h.id_modulo, h.dia_semana, h.hora_inicio, h.hora_fim,
                m.siglas, m.nome AS modulo, f.nome AS formador, s.nome AS sala
         FROM horario h
         JOIN modulo m ON m.id_modulo = h.id_modulo
         JOIN formador f ON f.id_formador = h.id_formador
         JOIN sala s ON s.id_sala = h.id_sala
         WHERE h.id_turma = ?
         ORDER BY h.hora_inicio',
        'i',
        $tid
    );
    if (!$aulas && !$sel) {
        continue; // exportação geral: ignora turmas sem horário
    }

    $resumo = sgh_resumo_modulos($tid);
    $verif = [];
    foreach ($resumo as $r) {
        $verif[$r['id_modulo']] = $r['verificador'];
    }

    $periodo = sgh_periodos_turma($tid);
    $ciclo = one("SELECT numero FROM horario_ciclo WHERE id_turma=? AND estado='Activo' ORDER BY numero DESC LIMIT 1", 'i', $tid);

    $paginas[] = [
        'turma' => $t,
        'aulas' => $aulas,
        'resumo' => $resumo,
        'verif' => $verif,
        'grelha' => pdf_montar_grelha($aulas, (string) $t['turno'], $dias),
        'inicio' => sgh_inicio_min($periodo['modulos']),
        'fim' => $periodo['fim'],
        'numero' => $ciclo ? (int) $ciclo['numero'] : null,
    ];
}

$temConteudo = (bool) array_filter($paginas, fn($p) => $p['aulas']);
?>
<!doctype html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>Horário · SGH</title>
    <style>
        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 18px;
            color: #111;
        }

        .actions {
            margin-bottom: 14px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .actions span {
            font-size: 12px;
            color: #666;
        }

        button {
            padding: 9px 13px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
        }

        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body {
                margin: 0;
            }

            .actions {
                display: none;
            }
        }

        .turma {
            page-break-after: always;
        }

        .turma:last-of-type {
            page-break-after: auto;
        }

        .head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            gap: 16px;
        }

        .head img {
            height: 34px;
            display: block;
            margin-bottom: 6px;
        }

        h1 {
            margin: 0;
            font-size: 19px;
        }

        .sub {
            margin: 3px 0 0;
            font-size: 11px;
            color: #444;
        }

        .meta {
            text-align: right;
            font-size: 10px;
            color: #555;
            line-height: 1.5;
        }

        h2 {
            font-size: 13px;
            margin: 16px 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 4px;
            font-size: 9px;
            vertical-align: middle;
        }

        th {
            background: #eee;
            text-align: center;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .grade td {
            text-align: center;
            height: 24px;
        }

        .time {
            width: 82px;
            font-weight: bold;
            background: #f5f5f5;
            text-align: center;
        }

        .class {
            background: #eef4ff;
            border: 1px solid #c8d9ff;
            border-radius: 5px;
            padding: 4px;
            text-align: left;
            margin: 2px 0;
        }

        .class b {
            display: block;
            color: #1d4ed8;
            font-size: 10px;
        }

        .class small {
            display: block;
            margin-top: 1px;
            font-size: 8.5px;
        }

        .class .verif {
            color: #7a2e0e;
        }

        .resumo td {
            text-align: left;
        }

        .resumo td.c,
        .resumo th.c {
            text-align: center;
        }

        .vazio {
            padding: 22px;
            border: 1px dashed #aaa;
            border-radius: 8px;
            font-size: 13px;
            color: #555;
        }

        .nota {
            margin-top: 6px;
            font-size: 8.5px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="actions">
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
        <span>No diálogo de impressão, escolha “Guardar como PDF” como destino.</span>
    </div>

    <?php if (!$paginas): ?>
        <div class="vazio">Não existe nenhuma turma com horário gerado para exportar.</div>
    <?php endif; ?>

    <?php foreach ($paginas as $p):
        $t = $p['turma'];
        $g = $p['grelha'];
    ?>
        <section class="turma">
            <div class="head">
                <div>
                    <img src="../img/horix.png" alt="Horix">
                    <h1>Horário da turma <?= e($t['nome']) ?></h1>
                    <p class="sub">
                        Curso: <?= e($t['curso'] ?? '—') ?> ·
                        Turno: <?= e($nomeTurno[$t['turno']] ?? ucfirst((string) $t['turno'])) ?>
                        <?= $p['numero'] ? ' · Horário nº ' . (int) $p['numero'] : '' ?>
                    </p>
                    <?php if ($p['aulas']): ?>
                        <p class="sub"><b>Período do horário:</b> <?= e(sgh_data($p['inicio'])) ?> a <?= e(sgh_data($p['fim'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="meta">
                    Sistema de Gestão de Horários<br>
                    Gerado em <?= date('d/m/Y H:i') ?>
                </div>
            </div>

            <?php if (!$p['aulas']): ?>
                <div class="vazio">Esta turma ainda não tem horário gerado.</div>
            <?php else: ?>
                <table class="grade">
                    <thead>
                        <tr>
                            <th class="time">Hora</th>
                            <?php foreach ($dias as $d): ?>
                                <th><?= e($nomeDia[$d]) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($g['linhas'] as $r => $par): ?>
                            <tr>
                                <td class="time"><?= e($par[0]) ?> – <?= e($par[1]) ?></td>
                                <?php foreach ($dias as $d): ?>
                                    <?php if (!empty($g['cobertas'][$d][$r])) continue; ?>
                                    <?php if (isset($g['celulas'][$d][$r])): $c = $g['celulas'][$d][$r]; ?>
                                        <td rowspan="<?= (int) $c['n'] ?>">
                                            <?php foreach ($c['aulas'] as $a): ?>
                                                <div class="class">
                                                    <b><?= e($a['siglas']) ?></b>
                                                    <small><?= e($a['formador']) ?> · Sala <?= e($a['sala']) ?></small>
                                                    <small class="verif">Verificador interno: <?= e($p['verif'][(int) $a['id_modulo']] ?? '—') ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php else: ?>
                                        <td></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Módulos, verificador interno e período</h2>
                <table class="resumo">
                    <thead>
                        <tr>
                            <th style="width:30%">Módulo</th>
                            <th style="width:17%">Formador</th>
                            <th style="width:17%">Verificador interno</th>
                            <th class="c" style="width:7%">Carga (h)</th>
                            <th class="c" style="width:8%">Horas/semana</th>
                            <th class="c" style="width:10%">Início</th>
                            <th class="c" style="width:10%">Fim</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($p['resumo'] as $r): ?>
                            <tr>
                                <td><b><?= e($r['siglas']) ?></b> · <?= e($r['modulo']) ?></td>
                                <td><?= e(implode(', ', $r['formadores'])) ?></td>
                                <td><?= $r['verificador'] ? e($r['verificador']) : '— (sem outro formador no módulo)' ?></td>
                                <td class="c"><?= (int) $r['carga_horaria'] ?></td>
                                <td class="c"><?= e(sgh_horas((float) $r['horas_semana'])) ?></td>
                                <td class="c"><?= e(sgh_data($r['inicio'])) ?></td>
                                <td class="c"><?= e(sgh_data($r['fim'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="nota">
                    Verificador interno: formador que lecciona o mesmo módulo e que não o lecciona nesta turma.
                    O período é calculado a partir da data de início e da carga horária (1 tempo = 45 min).
                </p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if ($auto && $temConteudo): ?>
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 400);
            });
        </script>
    <?php endif; ?>
</body>

</html>
