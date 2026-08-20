<?php
require_once __DIR__ . '/../config/database.php';

/* ===================== Clínicas ===================== */

function listarClinicas(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query('SELECT id, nome, endereco FROM clinicas ORDER BY nome');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarClinica(int $clinicaId): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome, endereco FROM clinicas WHERE id = :id');
    $stmt->execute(['id' => $clinicaId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ===================== Equipes ===================== */

function listarEquipes(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query('SELECT id, nome FROM equipes WHERE ativo = 1 ORDER BY id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ===================== Agenda / vagas ===================== */

/**
 * Retorna, para cada dia do mês informado, quantas vagas já foram ocupadas.
 * Resultado: ['2026-09-04' => 2, '2026-09-07' => 1, ...]
 */
function ocupacaoDoMes(int $ano, int $mes): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT data, COUNT(*) AS ocupadas
         FROM agendamentos
         WHERE YEAR(data) = :ano AND MONTH(data) = :mes
         GROUP BY data'
    );
    $stmt->execute(['ano' => $ano, 'mes' => $mes]);

    $ocupacao = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $ocupacao[$linha['data']] = (int) $linha['ocupadas'];
    }
    return $ocupacao;
}

/** Retorna os ids das equipes que ainda estão livres numa data. */
function equipesLivresNaData(string $data): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT e.id, e.nome
         FROM equipes e
         WHERE e.ativo = 1
           AND e.id NOT IN (
               SELECT equipe_id FROM agendamentos WHERE data = :data
           )
         ORDER BY e.id'
    );
    $stmt->execute(['data' => $data]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function totalEquipesAtivas(): int
{
    $pdo = getConexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM equipes WHERE ativo = 1')->fetchColumn();
}

/* ===================== Formatação ===================== */

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

function diaSemanaAbreviado(string $dataIso): string
{
    $dias = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    return $dias[(int) $dt->format('w')];
}

function nomeDoMes(int $mes): string
{
    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
              'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    return $meses[$mes - 1];
}

function dataPorExtenso(string $dataIso): string
{
    $dt = DateTime::createFromFormat('Y-m-d', $dataIso);
    return $dt->format('j') . ' de ' . nomeDoMes((int) $dt->format('n'));
}

/* ===================== Disponibilidade de datas ===================== */

/**
 * Retorna os feriados nacionais de um ano, no formato ['2026-01-01' => 'Ano Novo', ...].
 * Inclui os móveis, calculados a partir da Páscoa.
 */
function feriadosNacionais(int $ano): array
{
    $pascoa = new DateTime("$ano-03-21");
    $pascoa->modify('+' . easter_days($ano) . ' days');

    $carnaval = (clone $pascoa)->modify('-47 days');
    $sextaSanta = (clone $pascoa)->modify('-2 days');
    $corpusChristi = (clone $pascoa)->modify('+60 days');

    return [
        "$ano-01-01" => 'Confraternização Universal',
        $carnaval->format('Y-m-d') => 'Carnaval',
        $sextaSanta->format('Y-m-d') => 'Sexta-feira Santa',
        "$ano-04-21" => 'Tiradentes',
        "$ano-05-01" => 'Dia do Trabalho',
        $corpusChristi->format('Y-m-d') => 'Corpus Christi',
        "$ano-09-07" => 'Independência',
        "$ano-10-12" => 'Nossa Senhora Aparecida',
        "$ano-11-02" => 'Finados',
        "$ano-11-15" => 'Proclamação da República',
        "$ano-11-20" => 'Consciência Negra',
        "$ano-12-25" => 'Natal',
    ];
}

/** Busca as exceções cadastradas para um mês. */
function excecoesDoMes(int $ano, int $mes): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT data, tipo, motivo FROM excecoes_calendario
         WHERE YEAR(data) = :ano AND MONTH(data) = :mes'
    );
    $stmt->execute(['ano' => $ano, 'mes' => $mes]);

    $excecoes = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $excecoes[$linha['data']] = $linha;
    }
    return $excecoes;
}

/**
 * Decide se uma data aceita agendamento.
 * Retorna ['disponivel' => bool, 'motivo' => string|null].
 */
function situacaoDaData(string $dataIso, array $excecoes, array $feriados): array
{
    $diaSemana = (int) (new DateTime($dataIso))->format('w');

    // Exceção cadastrada tem prioridade sobre qualquer regra
    if (isset($excecoes[$dataIso])) {
        $excecao = $excecoes[$dataIso];
        if ($excecao['tipo'] === 'liberado') {
            return ['disponivel' => true, 'motivo' => null];
        }
        return ['disponivel' => false, 'motivo' => $excecao['motivo'] ?: 'bloqueado'];
    }

    if ($diaSemana === 0) {
        return ['disponivel' => false, 'motivo' => 'domingo'];
    }

    if ($diaSemana === 6) {
        return ['disponivel' => false, 'motivo' => 'sábado'];
    }

    if (isset($feriados[$dataIso])) {
        return ['disponivel' => false, 'motivo' => 'feriado'];
    }

    return ['disponivel' => true, 'motivo' => null];
}

/* ===================== Criar agendamento ===================== */

function criarAgendamento(array $dados): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        // Trava as equipes já ocupadas nessa data para evitar corrida
        $stmt = $pdo->prepare(
            'SELECT equipe_id FROM agendamentos WHERE data = :data FOR UPDATE'
        );
        $stmt->execute(['data' => $dados['data']]);
        $ocupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare('SELECT id FROM equipes WHERE ativo = 1 ORDER BY id');
        $stmt->execute();
        $todasEquipes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $livres = array_values(array_diff($todasEquipes, $ocupadas));

        if (empty($livres)) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Não há mais vagas nesta data.'];
        }

        $equipeId = $livres[0];

        $stmt = $pdo->prepare(
            'INSERT INTO agendamentos
                (data, equipe_id, clinica_id, paciente_nome, ficha_numero, carga,
                 dentista_operador, marcado_por_usuario_id, telefone_contato)
             VALUES
                (:data, :equipe_id, :clinica_id, :paciente_nome, :ficha_numero, :carga,
                 :dentista_operador, :marcado_por, :telefone)'
        );
        $stmt->execute([
            'data'              => $dados['data'],
            'equipe_id'         => $equipeId,
            'clinica_id'        => $dados['clinica_id'],
            'paciente_nome'     => $dados['paciente_nome'],
            'ficha_numero'      => $dados['ficha_numero'],
            'carga'             => $dados['carga'],
            'dentista_operador' => $dados['dentista_operador'],
            'marcado_por'       => $dados['marcado_por'],
            'telefone'          => $dados['telefone'] ?: null,
        ]);

        $novoId = (int) $pdo->lastInsertId();

        $pdo->commit();

        return ['sucesso' => true, 'id' => $novoId, 'equipe_id' => $equipeId];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['sucesso' => false, 'erro' => 'Erro ao salvar o agendamento.'];
    }
}

/* ===================== Agenda (admin / equipe) ===================== */

/**
 * Lista agendamentos futuros. Se $equipeId for informado, filtra por equipe.
 */
function listarAgendamentos(?int $equipeId = null): array
{
    $pdo = getConexao();

    $sql =
        'SELECT a.id, a.data, a.paciente_nome, a.ficha_numero, a.carga,
                a.dentista_operador, a.telefone_contato, a.equipe_id,
                c.nome AS clinica_nome,
                e.nome AS equipe_nome,
                u.nome AS marcado_por
         FROM agendamentos a
         JOIN clinicas c ON c.id = a.clinica_id
         JOIN equipes e ON e.id = a.equipe_id
         JOIN usuarios u ON u.id = a.marcado_por_usuario_id
         WHERE a.data >= CURDATE()';

    $parametros = [];

    if ($equipeId !== null) {
        $sql .= ' AND a.equipe_id = :equipe_id';
        $parametros['equipe_id'] = $equipeId;
    }

    $sql .= ' ORDER BY a.data, a.equipe_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/** Troca a equipe de um agendamento (usado pelo admin). */
function trocarEquipeAgendamento(int $agendamentoId, int $novaEquipeId): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT data, equipe_id FROM agendamentos WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $agendamentoId]);
        $ag = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ag) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Agendamento não encontrado.'];
        }

        if ((int) $ag['equipe_id'] === $novaEquipeId) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'O agendamento já está nessa equipe.'];
        }

        // Verifica se a equipe de destino já tem alguém nessa data
        $stmt = $pdo->prepare(
            'SELECT id FROM agendamentos WHERE data = :data AND equipe_id = :equipe_id FOR UPDATE'
        );
        $stmt->execute(['data' => $ag['data'], 'equipe_id' => $novaEquipeId]);
        $conflito = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conflito) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Essa equipe já tem cirurgia nesse dia.'];
        }

        $stmt = $pdo->prepare('UPDATE agendamentos SET equipe_id = :equipe_id WHERE id = :id');
        $stmt->execute(['equipe_id' => $novaEquipeId, 'id' => $agendamentoId]);

        $pdo->commit();
        return ['sucesso' => true];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['sucesso' => false, 'erro' => 'Erro ao trocar a equipe.'];
    }
}