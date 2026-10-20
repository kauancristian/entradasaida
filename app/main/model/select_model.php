<?php

require_once(__DIR__ . '/../config/Database.php');

class select_model extends connect
{
    private $colunasValidacaoAtrasoDisponiveis = null;
    private $colunasUsuarioSessaoDisponiveis = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function select_curso()
    {
        $queryStr = "SELECT * FROM curso";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function select_turmas()
    {
        $queryStr = "SELECT * FROM turma";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function select_alunos()
    {
        $queryStr = "SELECT * FROM aluno";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function select_alunosE()
    {
        try {
            $queryStr = "SELECT * FROM aluno WHERE id_turma IN (9, 10, 11, 12)";
            $query = $this->connect->query($queryStr);
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            error_log("Selecionados " . count($result) . " alunos do 3º ano");
            return $result;
        } catch (PDOException $e) {
            error_log("Erro ao selecionar alunos do 3º ano: " . $e->getMessage());
            return [];
        }
    }

    public function saida_estagio_3A()
    {
        $queryStr = "SELECT s.id_saida, s.dae, a.id_aluno, a.nome, a.matricula, a.id_turma FROM aluno a
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 9 
                    AND DATE(s.dae) = CURDATE() 
                    ORDER BY s.dae DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3B()
    {
        $queryStr = "SELECT s.id_saida, s.dae, a.id_aluno, a.nome, a.matricula, a.id_turma FROM aluno a
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 10 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY s.dae DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3C()
    {
        $queryStr = "SELECT s.id_saida, s.dae, a.id_aluno, a.nome, a.matricula, a.id_turma FROM aluno a
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 11 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY s.dae DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3D()
    {
        $queryStr = "SELECT s.id_saida, s.dae, a.id_aluno, a.nome, a.matricula, a.id_turma FROM aluno a
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 12 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY s.dae  DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saidasEstagio()
    {
        try {
            $camposUsuarioSessao = $this->camposUsuarioSessao('saida_estagio', 's');
            $queryStr = "SELECT s.id_saida, s.dae, {$camposUsuarioSessao}, a.id_aluno, a.nome, a.matricula, a.id_turma,
                                t.ano, t.turma
                         FROM saida_estagio s
                         INNER JOIN aluno a ON a.id_aluno = s.id_aluno
                         LEFT JOIN turma t ON t.id_turma = a.id_turma
                         ORDER BY s.dae DESC, s.id_saida DESC";
            $query = $this->connect->query($queryStr);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao consultar saídas de estágio: ' . $e->getMessage());
            throw new RuntimeException('Falha ao consultar saídas de estágio.', 0, $e);
        }
    }

    public function alunosAusentesEstagioNaData($data, $idTurma = null)
    {
        try {
            $sql = "SELECT a.id_aluno, a.nome, a.matricula, a.id_turma,
                           CONCAT(t.ano, ' ', UPPER(t.turma)) AS nome_turma
                    FROM aluno a
                    INNER JOIN turma t ON t.id_turma = a.id_turma
                    LEFT JOIN saida_estagio s
                        ON s.id_aluno = a.id_aluno
                       AND DATE(s.dae) = :data_saida
                       AND TIME(s.dae) <= '13:20:00'
                    WHERE a.id_turma IN (9, 10, 11, 12)
                      AND s.id_saida IS NULL";
            $parametros = ['data_saida' => $data];
            if ($idTurma !== null) {
                $sql .= ' AND a.id_turma = :id_turma';
                $parametros['id_turma'] = $idTurma;
            }
            $sql .= ' ORDER BY t.ano, t.turma, a.nome';

            $consulta = $this->connect->prepare($sql);
            $consulta->execute($parametros);
            return $consulta->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao consultar ausências de estágio: ' . $e->getMessage());
            throw new RuntimeException('Falha ao consultar ausências de estágio.', 0, $e);
        }
    }

    public function saidasEstagioHojePorTurma()
    {
        $queryStr = "SELECT s.id_saida, s.dae, a.id_aluno, a.nome, a.matricula, a.id_turma
                     FROM saida_estagio s
                     INNER JOIN aluno a ON a.id_aluno = s.id_aluno
                     WHERE a.id_turma IN (9, 10, 11, 12)
                       AND DATE(s.dae) = CURDATE()
                     ORDER BY s.dae DESC, s.id_saida DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saida_estagio_3A_relatorio()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 9 
                    AND DATE(s.dae) = CURDATE() 
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3B_relatorio()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 10 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3C_relatorio()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 11 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3D_relatorio()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 12 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3A_relatorio()
    {
        $queryStr = "SELECT a.nome, '3ºA - ENFERMAGEM' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = CURDATE()
                 WHERE a.id_turma = 9 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3B_relatorio()
    {
        $queryStr = "SELECT a.nome, '3ºB - INFORMÁTICA' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = CURDATE()
                 WHERE a.id_turma = 10 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3C_relatorio()
    {
        $queryStr = "SELECT a.nome, '3ºC - ADMINISTRAÇÃO' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = CURDATE()
                 WHERE a.id_turma = 11 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3D_relatorio()
    {
        $queryStr = "SELECT a.nome, '3ºD - EDIFICAÇÃO' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = CURDATE()
                 WHERE a.id_turma = 12 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saida_estagio_3A_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 9 
                    AND DATE(s.dae) = '$data' 
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3B_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 10 
                    AND DATE(s.dae) = '$data'
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3C_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 11 
                    AND DATE(s.dae) = '$data'
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3D_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 12 
                    AND DATE(s.dae) = '$data'
                    ORDER BY a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3A_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, '3ºA - ENFERMAGEM' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = :data
                 WHERE a.id_turma = 9 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->prepare($queryStr);
        $query->execute(['data' => $data]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3B_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, '3ºB - INFORMÁTICA' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = :data
                 WHERE a.id_turma = 10 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->prepare($queryStr);
        $query->execute(['data' => $data]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3C_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, '3ºC - ADMINISTRAÇÃO' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = :data
                 WHERE a.id_turma = 11 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->prepare($queryStr);
        $query->execute(['data' => $data]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function alunos_ausentes_3D_relatorio_dia($data)
    {
        $queryStr = "SELECT a.nome, '3ºD - EDIFICAÇÃO' AS turma 
                 FROM aluno a 
                 LEFT JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                 AND DATE(s.dae) = :data
                 WHERE a.id_turma = 12 
                 AND s.id_aluno IS NULL
                 ORDER BY a.nome ASC";
        $query = $this->connect->prepare($queryStr);
        $query->execute(['data' => $data]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function suportaValidacaoAtrasos()
    {
        if ($this->colunasValidacaoAtrasoDisponiveis === null) {
            $colunas = $this->connect->query("SHOW COLUMNS FROM registro_entrada")->fetchAll(PDO::FETCH_COLUMN);
            $necessarias = ['status_justificativa', 'observacao_validacao', 'validado_por', 'validado_em'];
            $this->colunasValidacaoAtrasoDisponiveis = count(array_intersect($necessarias, $colunas)) === count($necessarias);
        }

        return $this->colunasValidacaoAtrasoDisponiveis;
    }

    private function camposUsuarioSessao($tabela, $alias)
    {
        $colunasDisponiveis = $this->colunasDaTabela($tabela);

        $campos = [];
        foreach (['id_usuario_sessao', 'usuario_nome', 'usuario_email'] as $coluna) {
            $campos[] = in_array($coluna, $colunasDisponiveis, true)
                ? "{$alias}.{$coluna} AS {$coluna}"
                : "NULL AS {$coluna}";
        }

        return implode(', ', $campos);
    }

    private function colunasDaTabela($tabela)
    {
        if (!isset($this->colunasUsuarioSessaoDisponiveis[$tabela])) {
            $tabelasPermitidas = ['registro_entrada', 'registro_saida', 'saida_estagio'];
            if (!in_array($tabela, $tabelasPermitidas, true)) {
                throw new InvalidArgumentException('Tabela inválida para consultar identidade da sessão.');
            }

            $this->colunasUsuarioSessaoDisponiveis[$tabela] = $this->connect
                ->query('SHOW COLUMNS FROM `' . $tabela . '`')
                ->fetchAll(PDO::FETCH_COLUMN);
        }

        return $this->colunasUsuarioSessaoDisponiveis[$tabela];
    }

    private function expressaoUsuarioSessao($tabela, $alias, $coluna)
    {
        return in_array($coluna, $this->colunasDaTabela($tabela), true)
            ? "{$alias}.{$coluna}"
            : 'NULL';
    }

    private function buscarAtrasos($condicaoData, $parametros = [], $condicaoEscopo = '1 = 1')
    {
        $camposValidacao = $this->suportaValidacaoAtrasos()
            ? 'r.status_justificativa, r.observacao_validacao, r.validado_por, r.validado_em'
            : "'pendente' AS status_justificativa, NULL AS observacao_validacao, NULL AS validado_por, NULL AS validado_em";
        $colunasEntrada = $this->connect->query('SHOW COLUMNS FROM registro_entrada')
            ->fetchAll(PDO::FETCH_COLUMN);
        $campoJustificativa = in_array('justificativa_atraso', $colunasEntrada, true)
            ? 'r.justificativa_atraso'
            : 'NULL AS justificativa_atraso';
        $condicaoAtrasoSemResponsavel = " OR (
                   COALESCE(r.nome_responsavel, '') = ''
                   AND COALESCE(r.nome_conducente, '') = ''
               )";
        $camposUsuarioSessao = $this->camposUsuarioSessao('registro_entrada', 'r');
        $queryStr = "SELECT r.id_registro_entrada, r.date_time, r.nome_responsavel, r.nome_conducente, r.id_motivo,
                            {$camposUsuarioSessao},
                            {$camposValidacao}, {$campoJustificativa},
                            a.id_turma, a.nome, a.matricula, t.turma AS letra_turma,
                            tr.tipo AS tipo_responsavel, tc.tipo AS tipo_conducente, m.motivo
                     FROM registro_entrada r
                     INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                     INNER JOIN turma t ON t.id_turma = a.id_turma
                     LEFT JOIN tipo_responsavel tr ON tr.id_tipo_responsavel = r.id_tipo_responsavel
                     LEFT JOIN tipo_conducente tc ON tc.id_tipo_conducente = r.id_tipo_conducente
                     LEFT JOIN motivo m ON m.id_motivo = r.id_motivo
                     WHERE ({$condicaoData})
                       AND {$condicaoEscopo}
                       AND a.id_turma IN (9, 10, 11, 12)
                       AND (
                           (TIME(r.date_time) >= '07:40:00' AND TIME(r.date_time) < '11:40:00')
                           {$condicaoAtrasoSemResponsavel}
                       )
                     ORDER BY r.date_time DESC, a.nome ASC";
        $query = $this->connect->prepare($queryStr);
        $query->execute($parametros);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atrasosHoje()
    {
        $condicaoPendencia = $this->suportaValidacaoAtrasos()
            ? " AND r.status_justificativa = 'pendente'"
            : '';

        return $this->buscarAtrasos('DATE(r.date_time) = CURDATE()' . $condicaoPendencia);
    }

    public function atrasosPorData($data)
    {
        return $this->buscarAtrasos('DATE(r.date_time) = :data', ['data' => $data]);
    }

    public function historicoAtrasos()
    {
        $condicaoHistorico = $this->suportaValidacaoAtrasos()
            ? "DATE(r.date_time) < CURDATE() OR (DATE(r.date_time) = CURDATE() AND r.status_justificativa IN ('aprovada', 'recusada'))"
            : 'DATE(r.date_time) < CURDATE()';

        return $this->buscarAtrasos($condicaoHistorico);
    }

    public function todosAtrasos()
    {
        return $this->buscarAtrasos('1 = 1');
    }

    public function atrasosParaRelatorio($escopo, $valor, $tipoRelatorio)
    {
        $periodos = [
            'dia_atual' => ['inicio' => 'today', 'fim' => 'tomorrow'],
            'ultimos_30_dias' => ['inicio' => '-30 days', 'fim' => 'tomorrow'],
            'ultimos_12_meses' => ['inicio' => '-12 months', 'fim' => 'tomorrow']
        ];
        if (!isset($periodos[$tipoRelatorio])) {
            throw new InvalidArgumentException('Período de relatório de atrasos inválido.');
        }

        $agora = new DateTimeImmutable();
        $inicio = $agora->modify($periodos[$tipoRelatorio]['inicio'])->setTime(0, 0, 0);
        $fim = $agora->modify($periodos[$tipoRelatorio]['fim'])->setTime(0, 0, 0);
        $condicoesEscopo = [
            'aluno' => 'a.id_aluno = :filtro_id',
            'turma' => 'a.id_turma = :filtro_id',
            'ano' => 'a.id_turma IN (9, 10, 11, 12)'
        ];
        if (!isset($condicoesEscopo[$escopo])) {
            throw new InvalidArgumentException('Escopo de relatório de atrasos inválido.');
        }

        $condicao = $condicoesEscopo[$escopo];
        $parametros = [
            'inicio' => $inicio->format('Y-m-d H:i:s'),
            'fim' => $fim->format('Y-m-d H:i:s')
        ];
        if ($escopo === 'ano') {
            $ano = filter_var($valor, FILTER_VALIDATE_INT);
            if ($ano !== 3) {
                throw new InvalidArgumentException('Ano inválido para relatório de atrasos.');
            }
        }
        if ($escopo !== 'ano') {
            $id = filter_var($valor, FILTER_VALIDATE_INT);
            $idsPermitidos = $escopo === 'turma' ? [9, 10, 11, 12] : null;
            if (!$id || ($idsPermitidos !== null && !in_array($id, $idsPermitidos, true))) {
                throw new InvalidArgumentException('Filtro de relatório de atrasos inválido.');
            }
            $parametros['filtro_id'] = $id;
        }

        return $this->buscarAtrasos(
            'r.date_time >= :inicio AND r.date_time < :fim',
            $parametros,
            $condicao
        );
    }

    public function saidasAntecipadas()
    {
        $administrador = $this->expressaoUsuarioSessao('registro_saida', 's', 'usuario_nome');
        $queryStr = "SELECT s.id_registro_saida, s.date_time, s.nome_responsavel, s.nome_conducente,
                            s.id_tipo_responsavel, s.id_tipo_conducente, s.id_motivo,
                            a.id_aluno, a.nome, a.matricula, a.id_turma,
                            t.ano, t.turma,
                            tr.tipo AS tipo_responsavel, tc.tipo AS tipo_conducente,
                            m.motivo, {$administrador} AS administrador
                     FROM registro_saida s
                     LEFT JOIN aluno a ON a.id_aluno = s.id_aluno
                     LEFT JOIN turma t ON t.id_turma = a.id_turma
                     LEFT JOIN tipo_responsavel tr ON tr.id_tipo_responsavel = s.id_tipo_responsavel
                     LEFT JOIN tipo_conducente tc ON tc.id_tipo_conducente = s.id_tipo_conducente
                     LEFT JOIN motivo m ON m.id_motivo = s.id_motivo
                     ORDER BY s.date_time DESC, s.id_registro_saida DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSaidasEstagioPorTurma($id_turma, $data)
    {
        try {
            $sql = "SELECT a.nome, se.data_saida, se.hora_saida 
                    FROM saida_estagio se 
                    INNER JOIN alunos a ON se.id_aluno = a.id 
                    WHERE a.id_turma = :id_turma 
                    AND DATE(se.data_saida) = :data 
                    ORDER BY a.nome";

            $stmt = $this->connect->prepare($sql);
            $stmt->bindParam(':id_turma', $id_turma);
            $stmt->bindParam(':data', $data);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar saídas de estágio: " . $e->getMessage());
            return [];
        }
    }

    public function select_motivo(){
        $queryStr = "SELECT * FROM motivo";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function select_conducente(){
        $queryStr = "SELECT * FROM tipo_conducente";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function select_responsavel(){
        $queryStr = "SELECT * FROM tipo_responsavel";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function select_funcionario(){
        $queryStr = "SELECT * FROM funcionario";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

};
