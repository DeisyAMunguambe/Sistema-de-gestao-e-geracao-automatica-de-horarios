<?php
/**
 * Funções partilhadas pela página Horários e pela exportação em PDF:
 *  - período de cada módulo (data de início e de fim) segundo a carga horária;
 *  - verificador interno de cada módulo;
 *  - grelha de tempos por turno.
 *
 * Depende de bootstrap.php (rows(), one()).
 */

/** Dias da semana, pela ordem usada na base de dados. */
function sgh_dias(): array
{
    return ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'];
}

/** Tempos de 45 minutos de cada turno (iguais aos do GeradorHorarios). */
function sgh_periodos_turno(string $turno): array
{
    $p = [
        'manha' => [['07:00', '07:45'], ['07:45', '08:30'], ['08:30', '09:15'], ['09:15', '10:00'], ['10:00', '10:45'], ['10:45', '11:30'], ['11:30', '12:15']],
        'tarde' => [['13:00', '13:45'], ['13:45', '14:30'], ['14:30', '15:15'], ['15:15', '16:00'], ['16:00', '16:45'], ['16:45', '17:30']],
        'noite' => [['17:00', '17:45'], ['17:45', '18:30'], ['18:30', '19:15'], ['19:25', '20:10'], ['20:10', '20:55']],
    ];

    return $p[strtolower($turno)] ?? $p['noite'];
}

/** Sábado: tempos contínuos de 45 minutos entre 07:00 e 14:00. */
function sgh_periodos_sabado(): array
{
    $out = [];
    for ($m = 420; $m + 45 <= 840; $m += 45) {
        $out[] = [
            sprintf('%02d:%02d', intdiv($m, 60), $m % 60),
            sprintf('%02d:%02d', intdiv($m + 45, 60), ($m + 45) % 60),
        ];
    }

    return $out;
}

/** Linhas da grelha de uma turma: tempos do turno + tempos do sábado, por ordem de início. */
function sgh_linhas_grelha(string $turno): array
{
    $linhas = sgh_periodos_turno($turno);
    foreach (sgh_periodos_sabado() as $ps) {
        if (!in_array($ps, $linhas, true)) {
            $linhas[] = $ps;
        }
    }
    usort($linhas, fn($a, $b) => strcmp($a[0], $b[0]));

    return $linhas;
}

/** Garante as tabelas de ciclos/histórico (também criadas por GerarHorario.php). */
function sgh_garantir_tabelas(): void
{
    $db = db();
    $db->query("CREATE TABLE IF NOT EXISTS horario_ciclo (id_ciclo INT AUTO_INCREMENT PRIMARY KEY,id_turma INT NOT NULL,numero INT NOT NULL,data_inicio DATE NOT NULL,data_expiracao DATE NOT NULL,estado ENUM('Activo','Expirado','Arquivado') NOT NULL DEFAULT 'Activo',criado_por VARCHAR(150) NULL,criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_turma_ciclo(id_turma,numero)) ENGINE=InnoDB");
    $db->query("CREATE TABLE IF NOT EXISTS horario_historico (id_historico INT AUTO_INCREMENT PRIMARY KEY,id_ciclo INT NOT NULL,id_turma INT NOT NULL,id_modulo INT NOT NULL,id_formador INT NOT NULL,id_sala INT NOT NULL,dia_semana VARCHAR(20) NOT NULL,turno VARCHAR(20) NOT NULL,hora_inicio TIME NOT NULL,hora_fim TIME NOT NULL,arquivado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(id_turma,id_modulo)) ENGINE=InnoDB");
}

/**
 * Horas de uma sessão: nº de tempos de 45 min que cabem entre início e fim.
 * Ex.: 4 tempos = 3 h (a mesma regra do gerador); 17:00–20:55 = 5 tempos = 3,75 h.
 */
function sgh_horas_sessao(string $dia, string $turno, string $ini, string $fim): float
{
    $ini = substr($ini, 0, 5);
    $fim = substr($fim, 0, 5);
    $grelha = $dia === 'Sabado' ? sgh_periodos_sabado() : sgh_periodos_turno($turno);

    $n = 0;
    foreach ($grelha as $p) {
        if ($p[0] >= $ini && $p[1] <= $fim) {
            $n++;
        }
    }
    if ($n > 0) {
        return $n * 0.75;
    }

    // Hora fora da grelha: usa a diferença real em horas.
    [$h1, $m1] = array_map('intval', explode(':', $ini));
    [$h2, $m2] = array_map('intval', explode(':', $fim));

    return max(0, ($h2 * 60 + $m2) - ($h1 * 60 + $m1)) / 60;
}

/**
 * Calcula, para cada módulo, o dia em que começa e o dia em que termina.
 *
 * Percorre o calendário a partir de $inicio; sempre que o dia da semana coincide
 * com uma sessão do módulo, desconta as horas da sessão à carga horária. O módulo
 * termina na sessão em que a carga horária fica cumprida.
 *
 * @param array $linhas linhas de horário (id_modulo, dia_semana, turno, hora_inicio, hora_fim)
 * @param array $carga  [id_modulo => carga horária em horas]
 * @return array [id_modulo => ['inicio'=>?DateTimeImmutable,'fim'=>?DateTimeImmutable,'sessoes_semana'=>int,'horas_semana'=>float]]
 */
function sgh_calcular_periodos(array $linhas, DateTimeImmutable $inicio, array $carga): array
{
    $idx = array_flip(sgh_dias());
    $sessoes = [];
    $res = [];

    foreach ($linhas as $l) {
        $d = $idx[$l['dia_semana']] ?? null;
        if ($d === null) {
            continue;
        }
        $h = sgh_horas_sessao($l['dia_semana'], (string) $l['turno'], (string) $l['hora_inicio'], (string) $l['hora_fim']);
        if ($h <= 0) {
            continue;
        }
        $mid = (int) $l['id_modulo'];
        $sessoes[$mid][$d][] = $h;
        $res[$mid] ??= ['inicio' => null, 'fim' => null, 'sessoes_semana' => 0, 'horas_semana' => 0.0];
        $res[$mid]['sessoes_semana']++;
        $res[$mid]['horas_semana'] += $h;
    }

    $restante = [];
    foreach ($sessoes as $mid => $_) {
        $c = (float) ($carga[$mid] ?? 0);
        if ($c > 0) {
            $restante[$mid] = $c;
        }
    }

    $dia = $inicio;
    for ($i = 0; $i < 2500 && $restante; $i++, $dia = $dia->modify('+1 day')) {
        $w = (int) $dia->format('N') - 1; // 0 = segunda ... 6 = domingo
        foreach ($restante as $mid => $falta) {
            foreach ($sessoes[$mid][$w] ?? [] as $h) {
                if ($falta <= 0) {
                    break;
                }
                $falta -= $h;
                $res[$mid]['inicio'] ??= $dia;
                $res[$mid]['fim'] = $dia;
            }
            if ($falta <= 0) {
                unset($restante[$mid]);
            } else {
                $restante[$mid] = $falta;
            }
        }
    }

    return $res;
}

/** Último dia (fim) de um conjunto de períodos, ou null. */
function sgh_fim_max(array $periodos): ?DateTimeImmutable
{
    $max = null;
    foreach ($periodos as $p) {
        if ($p['fim'] instanceof DateTimeImmutable && ($max === null || $p['fim'] > $max)) {
            $max = $p['fim'];
        }
    }

    return $max;
}

/** Primeiro dia (início) de um conjunto de períodos, ou null. */
function sgh_inicio_min(array $periodos): ?DateTimeImmutable
{
    $min = null;
    foreach ($periodos as $p) {
        if ($p['inicio'] instanceof DateTimeImmutable && ($min === null || $p['inicio'] < $min)) {
            $min = $p['inicio'];
        }
    }

    return $min;
}

/**
 * Períodos dos módulos do horário activo de uma turma.
 *
 * O 1.º horário começa na data de início da turma (ou, se não existir, na data em
 * que foi gerado). Cada novo horário começa no dia a seguir ao fim do anterior.
 *
 * @return array ['inicio'=>DateTimeImmutable,'fim'=>?DateTimeImmutable,'modulos'=>array]
 */
function sgh_periodos_turma(int $turmaId): array
{
    sgh_garantir_tabelas();

    $turma = one('SELECT id_turma, data_inicio FROM turma WHERE id_turma=?', 'i', $turmaId);
    $vazio = ['inicio' => new DateTimeImmutable('today'), 'fim' => null, 'modulos' => []];
    if (!$turma) {
        return $vazio;
    }

    $carga = [];
    foreach (rows('SELECT id_modulo, carga_horaria FROM modulo') as $m) {
        $carga[(int) $m['id_modulo']] = (float) $m['carga_horaria'];
    }

    $ciclos = rows('SELECT id_ciclo, numero, data_inicio, estado FROM horario_ciclo WHERE id_turma=? ORDER BY numero', 'i', $turmaId);

    if (!empty($turma['data_inicio'])) {
        $inicio = new DateTimeImmutable($turma['data_inicio']);
    } elseif (!empty($ciclos[0]['data_inicio'])) {
        $inicio = new DateTimeImmutable($ciclos[0]['data_inicio']);
    } else {
        $inicio = new DateTimeImmutable('today');
    }

    // Horários anteriores (arquivados): o seguinte começa quando o último módulo acaba.
    foreach ($ciclos as $c) {
        if ($c['estado'] === 'Activo') {
            continue;
        }
        $linhas = rows('SELECT id_modulo, dia_semana, turno, hora_inicio, hora_fim FROM horario_historico WHERE id_ciclo=?', 'i', (int) $c['id_ciclo']);
        $fim = sgh_fim_max(sgh_calcular_periodos($linhas, $inicio, $carga));
        if ($fim) {
            $inicio = $fim->modify('+1 day');
        }
    }

    $linhas = rows('SELECT id_modulo, dia_semana, turno, hora_inicio, hora_fim FROM horario WHERE id_turma=?', 'i', $turmaId);
    $mods = sgh_calcular_periodos($linhas, $inicio, $carga);

    return ['inicio' => $inicio, 'fim' => sgh_fim_max($mods), 'modulos' => $mods];
}

/** Formadores habilitados por módulo: [id_modulo => [['id'=>..,'nome'=>..], ...]] por ordem alfabética. */
function sgh_formadores_por_modulo(): array
{
    $map = [];
    foreach (rows('SELECT fm.id_modulo, f.id_formador, f.nome FROM for_mod fm JOIN formador f ON f.id_formador = fm.id_formador ORDER BY f.nome') as $r) {
        $map[(int) $r['id_modulo']][] = ['id' => (int) $r['id_formador'], 'nome' => $r['nome']];
    }

    return $map;
}

/**
 * Verificador interno de um módulo: o primeiro formador (por ordem alfabética)
 * que lecciona o mesmo módulo e que não é quem o lecciona nesta turma.
 * Devolve null quando o módulo só tem esse formador.
 */
function sgh_verificador_interno(int $moduloId, array $idsQueLeccionam, array $mapa): ?string
{
    foreach ($mapa[$moduloId] ?? [] as $f) {
        if (!in_array($f['id'], $idsQueLeccionam, true)) {
            return $f['nome'];
        }
    }

    return null;
}

/**
 * Resumo dos módulos do horário activo de uma turma: formador(es), verificador
 * interno, carga horária e período (início/fim). Ordenado pela ordem do módulo.
 */
function sgh_resumo_modulos(int $turmaId): array
{
    $linhas = rows(
        'SELECT h.id_modulo, h.id_formador, h.dia_semana, h.turno, h.hora_inicio, h.hora_fim,
                m.nome AS modulo, m.siglas, m.carga_horaria, m.ordem, f.nome AS formador
         FROM horario h
         JOIN modulo m ON m.id_modulo = h.id_modulo
         JOIN formador f ON f.id_formador = h.id_formador
         WHERE h.id_turma = ?
         ORDER BY m.ordem, m.nome',
        'i',
        $turmaId
    );
    if (!$linhas) {
        return [];
    }

    $per = sgh_periodos_turma($turmaId)['modulos'];
    $mapa = sgh_formadores_por_modulo();
    $out = [];

    foreach ($linhas as $l) {
        $mid = (int) $l['id_modulo'];
        if (!isset($out[$mid])) {
            $out[$mid] = [
                'id_modulo' => $mid,
                'siglas' => $l['siglas'],
                'modulo' => $l['modulo'],
                'carga_horaria' => (int) $l['carga_horaria'],
                'formadores' => [],
                'ids_formadores' => [],
                'verificador' => null,
                'inicio' => $per[$mid]['inicio'] ?? null,
                'fim' => $per[$mid]['fim'] ?? null,
                'sessoes_semana' => $per[$mid]['sessoes_semana'] ?? 0,
                'horas_semana' => $per[$mid]['horas_semana'] ?? 0.0,
            ];
        }
        $fid = (int) $l['id_formador'];
        if (!in_array($fid, $out[$mid]['ids_formadores'], true)) {
            $out[$mid]['ids_formadores'][] = $fid;
            $out[$mid]['formadores'][] = $l['formador'];
        }
    }

    foreach ($out as $mid => &$o) {
        $o['verificador'] = sgh_verificador_interno($mid, $o['ids_formadores'], $mapa);
    }
    unset($o);

    return array_values($out);
}

/** Data no formato dd/mm/aaaa (ou "—"). */
function sgh_data(?DateTimeImmutable $d): string
{
    return $d ? $d->format('d/m/Y') : '—';
}

/** Horas sem casas decimais inúteis (3 → "3", 3.75 → "3,75"). */
function sgh_horas(float $h): string
{
    return rtrim(rtrim(number_format($h, 2, ',', ''), '0'), ',');
}
