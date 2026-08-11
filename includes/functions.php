<?php
require_once __DIR__ . '/../config/database.php';

function listarClinicas(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query('SELECT id, nome, endereco FROM clinicas ORDER BY nome');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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