<?php

require_once(__DIR__ . '/../config/Database.php');

class select_model extends connect
{
    private $colunasValidacaoAtrasoDisponiveis = null;

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
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 9 
                    AND DATE(s.dae) = CURDATE() 
                    ORDER BY s.dae DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3B()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 10 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY s.dae DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3C()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 11 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY s.dae DESC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function saida_estagio_3D()
    {
        $queryStr = "SELECT a.nome, s.dae FROM aluno a 
                    JOIN saida_estagio s ON a.id_aluno = s.id_aluno 
                    WHERE a.id_turma = 12 
                    AND DATE(s.dae) = CURDATE()
                    ORDER BY s.dae  DESC";
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

    private function buscarAtrasos($condicaoData)
    {
        $camposValidacao = $this->suportaValidacaoAtrasos()
            ? 'r.status_justificativa, r.observacao_validacao, r.validado_por, r.validado_em'
            : "'pendente' AS status_justificativa, NULL AS observacao_validacao, NULL AS validado_por, NULL AS validado_em";
        $queryStr = "SELECT r.id_registro_entrada, r.date_time, r.nome_responsavel, r.nome_conducente,
                            {$camposValidacao},
                            a.id_turma, a.nome, t.turma AS letra_turma,
                            tr.tipo AS tipo_responsavel, tc.tipo AS tipo_conducente, m.motivo
                     FROM registro_entrada r
                     INNER JOIN aluno a ON a.id_aluno = r.id_aluno
                     INNER JOIN turma t ON t.id_turma = a.id_turma
                     LEFT JOIN tipo_responsavel tr ON tr.id_tipo_responsavel = r.id_tipo_responsavel
                     LEFT JOIN tipo_conducente tc ON tc.id_tipo_conducente = r.id_tipo_conducente
                     LEFT JOIN motivo m ON m.id_motivo = r.id_motivo
                     WHERE {$condicaoData}
                       AND TIME(r.date_time) BETWEEN '07:40:00' AND '11:40:59'
                       AND a.id_turma IN (9, 10, 11, 12)
                     ORDER BY r.date_time DESC, a.nome ASC";
        $query = $this->connect->query($queryStr);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function atrasosHoje()
    {
        return $this->buscarAtrasos('DATE(r.date_time) = CURDATE()');
    }

    public function historicoAtrasos()
    {
        return $this->buscarAtrasos('DATE(r.date_time) < CURDATE()');
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
