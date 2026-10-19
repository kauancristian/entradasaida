<?php

require_once __DIR__ . '/../config/Database.php';

class RestrictionModel extends connect
{
    private function horarioValido($horario)
    {
        if (!is_string($horario) || !preg_match('/^\d{2}:\d{2}$/', $horario)) {
            return false;
        }
        $valor = DateTimeImmutable::createFromFormat('!H:i', $horario);
        $erros = DateTimeImmutable::getLastErrors();
        return $valor
            && (!$erros || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))
            && $valor->format('H:i') === $horario;
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function listarRestricoes()
    {
        $consulta = $this->connect->query(
            'SELECT id_restricao, nome, tipo_registro, modo, hora_inicio, hora_fim, ativo
             FROM restricao
             ORDER BY nome'
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarTurmas()
    {
        $consulta = $this->connect->query(
            'SELECT id_turma, ano, turma FROM turma ORDER BY id_turma'
        );
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function restricoesPorTurma()
    {
        $consulta = $this->connect->query(
            'SELECT id_turma, id_restricao FROM restricao_turma ORDER BY id_turma, id_restricao'
        );
        $resultado = [];
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $vinculo) {
            $resultado[(int) $vinculo['id_turma']][] = (int) $vinculo['id_restricao'];
        }
        return $resultado;
    }

    public function salvarRestricao($id, $nome, $tipoRegistro, $modo, $horaInicio, $horaFim)
    {
        $tiposValidos = ['entrada', 'saida', 'estagio', 'todos'];
        $modosValidos = ['bloquear', 'permitir'];
        $nomeValido = is_string($nome);
        $quantidadeCaracteres = $nomeValido
            ? (function_exists('mb_strlen') ? mb_strlen(trim($nome), 'UTF-8') : strlen(trim($nome)))
            : 0;
        if (
            !$nomeValido
            || trim($nome) === ''
            || $quantidadeCaracteres > 100
            || !is_string($tipoRegistro)
            || !is_string($modo)
            || !in_array($tipoRegistro, $tiposValidos, true)
            || !in_array($modo, $modosValidos, true)
            || !$this->horarioValido($horaInicio)
            || !$this->horarioValido($horaFim)
            || $horaInicio === $horaFim
        ) {
            throw new InvalidArgumentException('Confira o nome, a operação e o intervalo de horário.');
        }

        $id = $id === null ? null : filter_var($id, FILTER_VALIDATE_INT);
        if ($id !== null && (!$id || $id < 1)) {
            throw new InvalidArgumentException('A restrição selecionada é inválida.');
        }

        $parametros = [
            'nome' => trim($nome),
            'tipo_registro' => $tipoRegistro,
            'modo' => $modo,
            'hora_inicio' => $horaInicio . ':00',
            'hora_fim' => $horaFim . ':00'
        ];

        if ($id === null) {
            $consulta = $this->connect->prepare(
                'INSERT INTO restricao (nome, tipo_registro, modo, hora_inicio, hora_fim)
                 VALUES (:nome, :tipo_registro, :modo, :hora_inicio, :hora_fim)'
            );
            $consulta->execute($parametros);
            return (int) $this->connect->lastInsertId();
        }

        $parametros['id_restricao'] = $id;
        $consulta = $this->connect->prepare(
            'UPDATE restricao
             SET nome = :nome, tipo_registro = :tipo_registro, modo = :modo,
                 hora_inicio = :hora_inicio, hora_fim = :hora_fim
             WHERE id_restricao = :id_restricao'
        );
        $consulta->execute($parametros);
        if ($consulta->rowCount() === 0) {
            $existe = $this->connect->prepare('SELECT 1 FROM restricao WHERE id_restricao = :id');
            $existe->execute(['id' => $id]);
            if (!$existe->fetchColumn()) {
                throw new InvalidArgumentException('A restrição não foi encontrada.');
            }
        }
        return $id;
    }

    public function excluirRestricao($id)
    {
        $consulta = $this->connect->prepare('DELETE FROM restricao WHERE id_restricao = :id');
        $consulta->execute(['id' => (int) $id]);
        return $consulta->rowCount() === 1;
    }

    public function salvarRestricoesDaTurma($idTurma, array $idsRestricoes)
    {
        $idTurma = filter_var($idTurma, FILTER_VALIDATE_INT);
        if (!$idTurma || $idTurma < 1) {
            throw new InvalidArgumentException('A turma selecionada é inválida.');
        }

        $idsRestricoes = array_values(array_unique(array_map('intval', $idsRestricoes)));
        $idsRestricoes = array_filter($idsRestricoes, static function ($id) {
            return $id > 0;
        });

        $this->connect->beginTransaction();
        try {
            $turma = $this->connect->prepare('SELECT 1 FROM turma WHERE id_turma = :id');
            $turma->execute(['id' => $idTurma]);
            if (!$turma->fetchColumn()) {
                throw new InvalidArgumentException('A turma selecionada não foi encontrada.');
            }

            if ($idsRestricoes) {
                $placeholders = implode(',', array_fill(0, count($idsRestricoes), '?'));
                $restricoes = $this->connect->prepare(
                    'SELECT COUNT(*) FROM restricao WHERE id_restricao IN (' . $placeholders . ')'
                );
                $restricoes->execute(array_values($idsRestricoes));
                if ((int) $restricoes->fetchColumn() !== count($idsRestricoes)) {
                    throw new InvalidArgumentException('Uma das restrições selecionadas não foi encontrada.');
                }
            }

            $excluir = $this->connect->prepare('DELETE FROM restricao_turma WHERE id_turma = :id_turma');
            $excluir->execute(['id_turma' => $idTurma]);
            $inserir = $this->connect->prepare(
                'INSERT INTO restricao_turma (id_restricao, id_turma) VALUES (:id_restricao, :id_turma)'
            );
            foreach ($idsRestricoes as $idRestricao) {
                $inserir->execute(['id_restricao' => $idRestricao, 'id_turma' => $idTurma]);
            }
            $this->connect->commit();
        } catch (Throwable $e) {
            if ($this->connect->inTransaction()) {
                $this->connect->rollBack();
            }
            throw $e;
        }
    }
}
