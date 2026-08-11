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
         ORDER BY h.data, h.hora'
    );
    $stmt->execute(['clinica_id' => $clinicaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}