<?php
/**
 * Gerador automático de horários semanais por backtracking.
 *
 * Como funciona (resumo):
 *  1. Lê da BD as salas, os módulos do curso da turma, os formadores de cada módulo,
 *     as disponibilidades e as aulas já ocupadas por OUTRAS turmas.
 *  2. Uma turma NÃO consegue ter todos os módulos do curso na mesma semana
 *     (só cabe 1 sessão por dia). Por isso agenda só os primeiros módulos (por ordem)
 *     que cabem. Os restantes ficam para o botão "Gerar novo horário".
 *  3. Para esses módulos, o backtracking tenta dia/hora/formador/sala sem conflitos.
 *  4. Grava tudo numa transacção: se algo falhar, não fica horário parcial.
 *
 * Regras:
 *  - módulos com carga <= 25 h têm 1 sessão por semana; os restantes têm 2;
 *  - cada sessão ocupa 4 tempos consecutivos de 45 minutos;
 *  - as duas sessões do mesmo módulo ficam em dias não consecutivos;
 *  - o sábado (07:00–14:00) só é usado se nem um módulo couber de segunda a sexta.
 */
class GeradorHorarios
{
    /** Limite de tentativas do backtracking (evita ficar minutos a pensar). */
    private const MAX_TENTATIVAS = 20000;
    /** Máximo de sessões por semana de segunda a sexta (1 por dia). */
    private const MAX_SESSOES_UTEIS = 5;
    /** Máximo de sessões por semana com sábado (5 dias + 2 ao sábado). */
    private const MAX_SESSOES_SEMANA = 7;
    private const TEMPOS_POR_SESSAO = 4;
    private const CARGA_COM_UMA_SESSAO = 25;

    private mysqli $db;
    private string $erro = '';
    /** Aulas já ocupadas (outras turmas) + aulas colocadas até agora. */
    private array $ocup = [];
    /** Aulas colocadas na solução actual. */
    private array $solucao = [];
    private int $tentativas = 0;
    private bool $permitirSabado = false;

    private array $dias = ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado'];
    private array $slots = [
        'manha' => [['07:00', '07:45'], ['07:45', '08:30'], ['08:30', '09:15'], ['09:15', '10:00'], ['10:00', '10:45'], ['10:45', '11:30'], ['11:30', '12:15']],
        'tarde' => [['13:00', '13:45'], ['13:45', '14:30'], ['14:30', '15:15'], ['15:15', '16:00'], ['16:00', '16:45'], ['16:45', '17:30']],
        'noite' => [['17:00', '17:45'], ['17:45', '18:30'], ['18:30', '19:15'], ['19:25', '20:10'], ['20:10', '20:55']],
    ];

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getErro(): string
    {
        return $this->erro;
    }

    /* ------------------------------------------------------------------
     * Funções pequenas de apoio
     * ---------------------------------------------------------------- */

    /** Corre um SELECT e devolve todas as linhas. */
    private function q(string $sql): array
    {
        $r = $this->db->query($sql);
        if (!$r) {
            throw new RuntimeException($this->db->error);
        }

        return $r->fetch_all(MYSQLI_ASSOC);
    }

    /** "HH:MM" -> minutos desde as 00:00. */
    private function mins(string $t): int
    {
        $p = explode(':', $t);

        return (int) $p[0] * 60 + (int) $p[1];
    }

    private function sessoesDoModulo(array $modulo): int
    {
        return ((float) $modulo['carga_horaria'] <= self::CARGA_COM_UMA_SESSAO) ? 1 : 2;
    }

    /* ------------------------------------------------------------------
     * Geração
     * ---------------------------------------------------------------- */

    /**
     * Gera (ou regenera) o horário de uma turma.
     *
     * @param int|null $turmaId null = todas as turmas, uma a uma
     * @param bool $proximo true = guardar o horário actual no histórico e gerar um novo
     *                      apenas com os módulos que ainda não foram agendados
     */
    public function gerar(?int $turmaId = null, bool $proximo = false): bool
    {
        $this->erro = '';

        if ($turmaId === null) {
            foreach ($this->q('SELECT id_turma FROM turma ORDER BY nome') as $t) {
                if (!$this->gerarUma((int) $t['id_turma'], false)) {
                    return false;
                }
            }

            return true;
        }

        return $this->gerarUma($turmaId, $proximo);
    }

    private function gerarUma(int $turmaId, bool $proximo): bool
    {
        $this->erro = '';
        $this->ocup = [];
        $this->solucao = [];
        $this->tentativas = 0;
        $this->permitirSabado = false;

        try {
            $this->criarTabelasDeCiclos();

            $salas = $this->q('SELECT id_sala, nome, capacidade, tipo FROM sala ORDER BY capacidade, id_sala');
            if (!$salas) {
                throw new RuntimeException('Cadastre pelo menos uma sala.');
            }

            $modulos = $this->modulosDaTurma($turmaId, $proximo);
            if (!$modulos) {
                throw new RuntimeException($proximo
                    ? 'Não existem módulos novos para esta turma: todos os módulos do curso já aparecem nos horários anteriores.'
                    : 'Não existem módulos associados ao curso das turmas. Cadastre módulos e associe-os aos formadores.');
            }

            $formadores = [];
            foreach ($this->q('SELECT fm.id_modulo, f.id_formador, f.nome FROM for_mod fm JOIN formador f ON f.id_formador = fm.id_formador ORDER BY f.nome') as $r) {
                $formadores[(int) $r['id_modulo']][] = $r;
            }

            $disp = [];
            foreach ($this->q('SELECT * FROM disponibilidade') as $r) {
                $disp[(int) $r['id_formador']][$r['dia_semana']][] = [$this->mins($r['hora_inicio']), $this->mins($r['hora_fim'])];
            }

            foreach ($this->q('SELECT * FROM horario WHERE id_turma <> ' . $turmaId) as $r) {
                $this->ocup[] = [
                    'turma'    => (int) $r['id_turma'],
                    'formador' => (int) $r['id_formador'],
                    'sala'     => (int) $r['id_sala'],
                    'dia'      => $r['dia_semana'],
                    'ini'      => $this->mins($r['hora_inicio']),
                    'fim'      => $this->mins($r['hora_fim']),
                ];
            }

            if (!$this->resolverPorLotes($modulos, $formadores, $salas, $disp)) {
                throw new RuntimeException(
                    'Não foi encontrada uma combinação válida, mesmo recorrendo ao sábado, nem para o primeiro módulo ('
                    . $modulos[0]['siglas'] . '). Verifique disponibilidades, tipo de sala, capacidade e conflitos com outras turmas.'
                );
            }

            $this->gravar($turmaId, $proximo);

            return true;
        } catch (Throwable $e) {
            try {
                $this->db->rollback();
            } catch (Throwable $ignorado) {
            }
            $this->erro = $e->getMessage();

            return false;
        }
    }

    private function criarTabelasDeCiclos(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS horario_ciclo (id_ciclo INT AUTO_INCREMENT PRIMARY KEY,id_turma INT NOT NULL,numero INT NOT NULL,data_inicio DATE NOT NULL,data_expiracao DATE NOT NULL,estado ENUM('Activo','Expirado','Arquivado') NOT NULL DEFAULT 'Activo',criado_por VARCHAR(150) NULL,criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_turma_ciclo(id_turma,numero)) ENGINE=InnoDB");
        $this->db->query("CREATE TABLE IF NOT EXISTS horario_historico (id_historico INT AUTO_INCREMENT PRIMARY KEY,id_ciclo INT NOT NULL,id_turma INT NOT NULL,id_modulo INT NOT NULL,id_formador INT NOT NULL,id_sala INT NOT NULL,dia_semana VARCHAR(20) NOT NULL,turno VARCHAR(20) NOT NULL,hora_inicio TIME NOT NULL,hora_fim TIME NOT NULL,arquivado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(id_turma,id_modulo)) ENGINE=InnoDB");
    }

    /** Módulos do curso da turma, por ordem (sem os já agendados, se $proximo). */
    private function modulosDaTurma(int $turmaId, bool $proximo): array
    {
        $sql = "SELECT t.id_turma, t.nome AS turma_nome, t.id_curso, t.turno, t.num_formandos,
                       m.id_modulo, m.nome AS modulo, m.siglas, m.carga_horaria, m.tipo_sala, m.ordem
                FROM turma t
                JOIN modulo m ON m.id_curso = t.id_curso
                WHERE t.id_turma = $turmaId";

        if ($proximo) {
            $sql .= " AND m.id_modulo NOT IN (SELECT id_modulo FROM horario_historico WHERE id_turma = $turmaId)
                      AND m.id_modulo NOT IN (SELECT id_modulo FROM horario WHERE id_turma = $turmaId)";
        }

        return $this->q($sql . ' ORDER BY m.ordem, m.nome');
    }

    /* ------------------------------------------------------------------
     * Escolha dos módulos que cabem na semana
     * ---------------------------------------------------------------- */

    /**
     * Fase 1: só segunda a sexta (máx. 5 sessões). Se nem 1 módulo couber, fase 2: com sábado
     * (máx. 7 sessões). Em cada fase, se falhar, tira o último módulo e tenta outra vez.
     */
    private function resolverPorLotes(array $modulos, array $formadores, array $salas, array $disp): bool
    {
        $ocupBase = $this->ocup;

        foreach ([false, true] as $comSabado) {
            $lote = $this->primeiroLote($modulos, $comSabado ? self::MAX_SESSOES_SEMANA : self::MAX_SESSOES_UTEIS);

            while ($lote) {
                $this->ocup = $ocupBase;
                $this->solucao = [];
                $this->tentativas = 0;
                $this->permitirSabado = $comSabado;

                $tarefas = $this->criarTarefas($lote, $formadores);
                if ($this->colocar($tarefas, 0, $salas, $disp)) {
                    return true;
                }

                array_pop($lote); // não coube: tenta com menos um módulo
            }
        }

        return false;
    }

    /** Primeiros módulos, por ordem, cujas sessões somadas não passam do limite (pelo menos um). */
    private function primeiroLote(array $modulos, int $maxSessoes): array
    {
        $lote = [];
        $sessoes = 0;

        foreach ($modulos as $modulo) {
            $n = $this->sessoesDoModulo($modulo);
            if ($lote && $sessoes + $n > $maxSessoes) {
                break;
            }
            $lote[] = $modulo;
            $sessoes += $n;
        }

        return $lote;
    }

    /** Uma tarefa por sessão semanal de cada módulo. */
    private function criarTarefas(array $modulos, array $formadores): array
    {
        $tarefas = [];

        foreach ($modulos as $aula) {
            $moduloId = (int) $aula['id_modulo'];

            if (empty($formadores[$moduloId])) {
                throw new RuntimeException('O módulo ' . $aula['siglas'] . ' não tem nenhum formador associado. Vá a Formadores > Módulos.');
            }

            $aula['tempos_aula'] = self::TEMPOS_POR_SESSAO;
            $aula['formadores'] = $formadores[$moduloId];

            for ($i = 0; $i < $this->sessoesDoModulo($aula); $i++) {
                $tarefas[] = $aula;
            }
        }

        return $tarefas;
    }

    /* ------------------------------------------------------------------
     * Backtracking
     * ---------------------------------------------------------------- */

    private function colocar(array $tarefas, int $i, array $salas, array $disp): bool
    {
        if ($i >= count($tarefas)) {
            return true;
        }
        if (++$this->tentativas > self::MAX_TENTATIVAS) {
            return false;
        }

        foreach ($this->candidatos($tarefas[$i], $salas, $disp) as $c) {
            $this->ocup[] = $c['ocupacao'];
            $this->solucao[] = $c;

            if ($this->colocar($tarefas, $i + 1, $salas, $disp)) {
                return true;
            }

            array_pop($this->ocup);
            array_pop($this->solucao);
        }

        return false;
    }

    /** Todas as combinações dia/hora/formador/sala válidas para uma sessão, por ordem cronológica. */
    private function candidatos(array $aula, array $salas, array $disp): array
    {
        $saida = [];
        $indiceDia = array_flip($this->dias);
        $tempos = (int) $aula['tempos_aula'];

        foreach ($this->dias as $dia) {
            if ($dia === 'Sabado' && !$this->permitirSabado) {
                continue;
            }
            if (!$this->diaPermitido($aula, $dia, $indiceDia)) {
                continue;
            }

            $slots = ($dia === 'Sabado') ? $this->slotsSabado() : ($this->slots[$aula['turno']] ?? $this->slots['noite']);

            for ($inicio = 0; $inicio + $tempos <= count($slots); $inicio++) {
                $ini = $slots[$inicio][0];
                $fim = $slots[$inicio + $tempos - 1][1];
                $minIni = $this->mins($ini);
                $minFim = $this->mins($fim);

                foreach ($aula['formadores'] as $formador) {
                    $formadorId = (int) $formador['id_formador'];

                    if (!$this->formadorDisponivel($disp[$formadorId] ?? [], $dia, $minIni, $minFim)) {
                        continue;
                    }

                    foreach ($salas as $sala) {
                        $salaId = (int) $sala['id_sala'];

                        if (!$this->salaServe($sala, $aula)) {
                            continue;
                        }
                        if ($this->temConflito((int) $aula['id_turma'], $formadorId, $salaId, $dia, $minIni, $minFim)) {
                            continue;
                        }

                        $saida[] = [
                            'a'        => $aula,
                            'formador' => $formadorId,
                            'sala'     => $salaId,
                            'dia'      => $dia,
                            'ini'      => $ini,
                            'fim'      => $fim,
                            'ocupacao' => [
                                'turma'    => (int) $aula['id_turma'],
                                'formador' => $formadorId,
                                'sala'     => $salaId,
                                'dia'      => $dia,
                                'ini'      => $minIni,
                                'fim'      => $minFim,
                            ],
                        ];
                    }
                }
            }
        }

        usort($saida, static fn(array $x, array $y): int => [$indiceDia[$x['dia']], $x['ini']] <=> [$indiceDia[$y['dia']], $y['ini']]);

        return $saida;
    }

    /** Sábado: tempos contínuos de 45 minutos entre 07:00 e 14:00. */
    private function slotsSabado(): array
    {
        $saida = [];
        for ($m = 420; $m + 45 <= 840; $m += 45) {
            $saida[] = [sprintf('%02d:%02d', intdiv($m, 60), $m % 60), sprintf('%02d:%02d', intdiv($m + 45, 60), ($m + 45) % 60)];
        }

        return $saida;
    }

    /** As duas sessões do mesmo módulo têm de ficar em dias não consecutivos. */
    private function diaPermitido(array $aula, string $dia, array $indiceDia): bool
    {
        foreach ($this->solucao as $anterior) {
            $mesmoModulo = (int) $anterior['a']['id_modulo'] === (int) $aula['id_modulo'];

            if ($mesmoModulo && abs($indiceDia[$dia] - $indiceDia[$anterior['dia']]) < 2) {
                return false;
            }
        }

        return true;
    }

    /** Sem disponibilidade registada = livre sempre; com registos, tem de caber num intervalo do dia. */
    private function formadorDisponivel(array $dispDoFormador, string $dia, int $minIni, int $minFim): bool
    {
        if (!$dispDoFormador) {
            return true;
        }

        foreach ($dispDoFormador[$dia] ?? [] as $intervalo) {
            if ($intervalo[0] <= $minIni && $intervalo[1] >= $minFim) {
                return true;
            }
        }

        return false;
    }

    /** A sala tem de ter capacidade para a turma e o tipo exigido pelo módulo (se houver). */
    private function salaServe(array $sala, array $aula): bool
    {
        if ((int) $sala['capacidade'] < (int) $aula['num_formandos']) {
            return false;
        }

        $exigido = mb_strtolower(trim((string) $aula['tipo_sala']));

        return $exigido === '' || $exigido === mb_strtolower(trim((string) $sala['tipo']));
    }

    /** Conflito = mesmo dia, horas sobrepostas e (mesma turma ou mesmo formador ou mesma sala). */
    private function temConflito(int $turmaId, int $formadorId, int $salaId, string $dia, int $minIni, int $minFim): bool
    {
        foreach ($this->ocup as $o) {
            if ($o['dia'] !== $dia || !($minIni < $o['fim'] && $minFim > $o['ini'])) {
                continue;
            }
            if ($o['turma'] === $turmaId || $o['formador'] === $formadorId || $o['sala'] === $salaId) {
                return true;
            }
        }

        return false;
    }

    /* ------------------------------------------------------------------
     * Gravação (tudo ou nada)
     * ---------------------------------------------------------------- */

    private function gravar(int $turmaId, bool $proximo): void
    {
        $this->db->begin_transaction();

        if ($proximo) {
            $ciclo = $this->db->query("SELECT id_ciclo FROM horario_ciclo WHERE id_turma = $turmaId AND estado = 'Activo' ORDER BY numero DESC LIMIT 1")->fetch_assoc();
            if ($ciclo) {
                $cid = (int) $ciclo['id_ciclo'];
                $this->db->query("INSERT INTO horario_historico (id_ciclo, id_turma, id_modulo, id_formador, id_sala, dia_semana, turno, hora_inicio, hora_fim)
                                  SELECT $cid, id_turma, id_modulo, id_formador, id_sala, dia_semana, turno, hora_inicio, hora_fim FROM horario WHERE id_turma = $turmaId");
                $this->db->query("UPDATE horario_ciclo SET estado = 'Arquivado' WHERE id_ciclo = $cid");
            }
        }

        $this->db->query('DELETE FROM horario WHERE id_turma = ' . $turmaId);

        $st = $this->db->prepare('INSERT INTO horario (id_turma, id_modulo, id_formador, id_sala, dia_semana, turno, hora_inicio, hora_fim) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($this->solucao as $c) {
            $tid = (int) $c['a']['id_turma'];
            $mid = (int) $c['a']['id_modulo'];
            $turno = (string) $c['a']['turno'];
            $fid = $c['formador'];
            $sala = $c['sala'];
            $dia = $c['dia'];
            $ini = $c['ini'];
            $fim = $c['fim'];
            $st->bind_param('iiiissss', $tid, $mid, $fid, $sala, $dia, $turno, $ini, $fim);
            $st->execute();
        }

        $this->db->commit();
    }
}
