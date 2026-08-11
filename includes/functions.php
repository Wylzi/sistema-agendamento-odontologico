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

        if ($horario['data'] < date('Y-m-d')) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Esse horário não está mais disponível.'];
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

function listarAgendaDentista(int $dentistaId): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT a.id AS agendamento_id, a.ficha_numero,
                h.data, h.hora,
                c.nome AS clinica_nome
         FROM agendamentos a
         JOIN horarios_disponiveis h ON h.id = a.horario_id
         JOIN clinicas c ON c.id = h.clinica_id
         WHERE h.dentista_id = :dentista_id
           AND h.data >= CURDATE()
         ORDER BY h.data, h.hora'
    );
    $stmt->execute(['dentista_id' => $dentistaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatarDataBr(string $dataIso): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    return $dt->format('d/m/Y');
}

function diaDaSemanaBr(string $dataIso): string
{
    $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    return $dias[(int) $dt->format('w')];
}

function formatarHoraBr(string $hora): string
{
    return substr($hora, 0, 5);
}

function diaDoMes(string $dataIso): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    return $dt->format('d');
}

function diaSemanaAbreviado(string $dataIso): string
{
    $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    return $dias[(int) $dt->format('w')];
}

function listarMeusHorarios(int $dentistaId): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT h.id, h.data, h.hora, h.vagas_totais, h.vagas_ocupadas, c.nome AS clinica_nome
         FROM horarios_disponiveis h
         JOIN clinicas c ON c.id = h.clinica_id
         WHERE h.dentista_id = :dentista_id
           AND h.data >= CURDATE()
         ORDER BY h.data, h.hora'
    );
    $stmt->execute(['dentista_id' => $dentistaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function removerHorario(int $dentistaId, int $horarioId): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT id FROM horarios_disponiveis WHERE id = :id AND dentista_id = :dentista_id'
        );
        $stmt->execute(['id' => $horarioId, 'dentista_id' => $dentistaId]);

        if (!$stmt->fetch()) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Horário não encontrado.'];
        }

        $stmt = $pdo->prepare('DELETE FROM agendamentos WHERE horario_id = :horario_id');
        $stmt->execute(['horario_id' => $horarioId]);

        $stmt = $pdo->prepare(
            'DELETE FROM horarios_disponiveis WHERE id = :id AND dentista_id = :dentista_id'
        );
        $stmt->execute(['id' => $horarioId, 'dentista_id' => $dentistaId]);

        $pdo->commit();

        return ['sucesso' => true];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['sucesso' => false, 'erro' => 'Erro ao remover horário.'];
    }
}

function obterOuCriarTokenCalendario(int $dentistaId): string
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT token_calendario FROM dentistas WHERE id = :id');
    $stmt->execute(['id' => $dentistaId]);
    $token = $stmt->fetchColumn();

    if ($token) {
        return $token;
    }

    $novoToken = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare('UPDATE dentistas SET token_calendario = :token WHERE id = :id');
    $stmt->execute(['token' => $novoToken, 'id' => $dentistaId]);

    return $novoToken;
}

function escaparTextoIcs(string $texto): string
{
    return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $texto);
}

function gerarIcsAgenda(array $agenda, string $nomeDentista): string
{
    $fuso = new DateTimeZone('America/Sao_Paulo');
    $utc = new DateTimeZone('UTC');

    $linhas = [];
    $linhas[] = 'BEGIN:VCALENDAR';
    $linhas[] = 'VERSION:2.0';
    $linhas[] = 'PRODID:-//Agendamento Odontologico//PT-BR';
    $linhas[] = 'CALSCALE:GREGORIAN';
    $linhas[] = 'METHOD:PUBLISH';
    $linhas[] = 'X-WR-CALNAME:Agenda - ' . escaparTextoIcs($nomeDentista);

    foreach ($agenda as $item) {
        $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $item['data'] . ' ' . $item['hora'], $fuso);
        $fim = (clone $inicio)->modify('+2 hours');

        $inicioUtc = (clone $inicio)->setTimezone($utc);
        $fimUtc = (clone $fim)->setTimezone($utc);

        $linhas[] = 'BEGIN:VEVENT';
        $linhas[] = 'UID:agendamento-' . $item['agendamento_id'] . '@agendamento-odontologico';
        $linhas[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $linhas[] = 'DTSTART:' . $inicioUtc->format('Ymd\THis\Z');
        $linhas[] = 'DTEND:' . $fimUtc->format('Ymd\THis\Z');
        $linhas[] = 'SUMMARY:' . escaparTextoIcs('Ficha ' . $item['ficha_numero']);
        $linhas[] = 'LOCATION:' . escaparTextoIcs($item['clinica_nome']);
        $linhas[] = 'END:VEVENT';
    }

    $linhas[] = 'END:VCALENDAR';

    return implode("\r\n", $linhas);
}

function buscarDentistaPorTokenCalendario(string $token): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome FROM dentistas WHERE token_calendario = :token');
    $stmt->execute(['token' => $token]);
    $dentista = $stmt->fetch(PDO::FETCH_ASSOC);
    return $dentista ?: null;
}