<?php
require_once __DIR__ . '/../config/database.php';

function listarClinicas(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query('SELECT id, nome, endereco FROM clinicas ORDER BY nome');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarClinica(string $nome, ?string $endereco): array
{
    if (trim($nome) === '') {
        return ['sucesso' => false, 'erro' => 'Informe o nome da clínica.'];
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare('INSERT INTO clinicas (nome, endereco) VALUES (:nome, :endereco)');
    $stmt->execute([
        'nome'     => trim($nome),
        'endereco' => $endereco !== null && trim($endereco) !== '' ? trim($endereco) : null,
    ]);

    return ['sucesso' => true, 'id' => (int) $pdo->lastInsertId()];
}

function listarHorariosDisponiveis(int $clinicaId): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT h.id, h.data, h.hora, h.vagas_totais, h.vagas_ocupadas, d.nome AS dentista_nome
         FROM horarios_disponiveis h
         JOIN dentistas d ON d.id = h.dentista_id
         WHERE h.clinica_id = :clinica_id
           AND h.vagas_ocupadas < h.vagas_totais
           AND h.data >= CURDATE()
         ORDER BY h.data, h.hora'
    );
    $stmt->execute(['clinica_id' => $clinicaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function criarAgendamento(int $horarioId, string $fichaNumero): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM horarios_disponiveis WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $horarioId]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$horario) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Horário não encontrado.'];
        }

        if ($horario['vagas_ocupadas'] >= $horario['vagas_totais']) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Esse horário acabou de lotar.'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO agendamentos (horario_id, ficha_numero) VALUES (:horario_id, :ficha)'
        );
        $stmt->execute(['horario_id' => $horarioId, 'ficha' => $fichaNumero]);

        $stmt = $pdo->prepare(
            'UPDATE horarios_disponiveis SET vagas_ocupadas = vagas_ocupadas + 1 WHERE id = :id'
        );
        $stmt->execute(['id' => $horarioId]);

        $pdo->commit();

        return ['sucesso' => true];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['sucesso' => false, 'erro' => 'Erro ao agendar.'];
    }

}

function cadastrarHorario(int $dentistaId, int $clinicaId, string $data, string $hora, int $vagas): array
{
    if (!$clinicaId) {
        return ['sucesso' => false, 'erro' => 'Selecione uma clínica.'];
    }

    if ($data === '' || $hora === '') {
        return ['sucesso' => false, 'erro' => 'Informe data e horário.'];
    }

    if ($data < date('Y-m-d')) {
        return ['sucesso' => false, 'erro' => 'Não é possível cadastrar horário em data passada.'];
    }

    if ($vagas < 1) {
        return ['sucesso' => false, 'erro' => 'Informe ao menos 1 vaga.'];
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'INSERT INTO horarios_disponiveis (dentista_id, clinica_id, data, hora, vagas_totais)
         VALUES (:dentista_id, :clinica_id, :data, :hora, :vagas)'
    );

    try {
        $stmt->execute([
            'dentista_id' => $dentistaId,
            'clinica_id'  => $clinicaId,
            'data'        => $data,
            'hora'        => $hora,
            'vagas'       => $vagas,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['sucesso' => false, 'erro' => 'Você já tem um horário cadastrado nessa clínica, data e hora.'];
        }
        return ['sucesso' => false, 'erro' => 'Erro ao cadastrar horário.'];
    }

    return ['sucesso' => true, 'id' => (int) $pdo->lastInsertId()];
}