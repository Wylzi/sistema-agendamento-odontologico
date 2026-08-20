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

/* ===================== Cadastro de clínicas ===================== */

function cadastrarClinica(string $nome, ?string $endereco = null): array
{
    $nome = trim($nome);

    if ($nome === '') {
        return ['sucesso' => false, 'erro' => 'Informe o nome da clínica.'];
    }

    $pdo = getConexao();

    $stmt = $pdo->prepare('SELECT id FROM clinicas WHERE nome = :nome');
    $stmt->execute(['nome' => $nome]);
    if ($stmt->fetch()) {
        return ['sucesso' => false, 'erro' => 'Já existe uma clínica com esse nome.'];
    }

    $endereco = $endereco !== null ? trim($endereco) : '';

    $stmt = $pdo->prepare('INSERT INTO clinicas (nome, endereco) VALUES (:nome, :endereco)');
    $stmt->execute([
        'nome'     => $nome,
        'endereco' => $endereco !== '' ? $endereco : null,
    ]);

    return ['sucesso' => true, 'id' => (int) $pdo->lastInsertId()];
}

/**
 * Cadastra várias clínicas de uma vez, uma por linha.
 * Aceita "Nome" ou "Nome; Endereço".
 */
function importarClinicas(string $texto): array
{
    $linhas = preg_split('/\r\n|\r|\n/', $texto);
    $inseridas = 0;
    $ignoradas = [];

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '') {
            continue;
        }

        $partes = explode(';', $linha, 2);
        $nome = trim($partes[0]);
        $endereco = isset($partes[1]) ? trim($partes[1]) : null;

        $resultado = cadastrarClinica($nome, $endereco);
        if ($resultado['sucesso']) {
            $inseridas++;
        } else {
            $ignoradas[] = $nome;
        }
    }

    return ['inseridas' => $inseridas, 'ignoradas' => $ignoradas];
}

function contarAtendentesDaClinica(int $clinicaId): int
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE clinica_id = :id');
    $stmt->execute(['id' => $clinicaId]);
    return (int) $stmt->fetchColumn();
}

function contarAgendamentosDaClinica(int $clinicaId): int
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM agendamentos WHERE clinica_id = :id');
    $stmt->execute(['id' => $clinicaId]);
    return (int) $stmt->fetchColumn();
}

function removerClinica(int $clinicaId): array
{
    if (contarAtendentesDaClinica($clinicaId) > 0) {
        return ['sucesso' => false, 'erro' => 'Essa clínica tem atendentes vinculados.'];
    }

    if (contarAgendamentosDaClinica($clinicaId) > 0) {
        return ['sucesso' => false, 'erro' => 'Essa clínica tem agendamentos registrados.'];
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare('DELETE FROM clinicas WHERE id = :id');
    $stmt->execute(['id' => $clinicaId]);

    return ['sucesso' => true];
}

/* ===================== Usuários ===================== */

function listarUsuarios(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query(
        'SELECT u.id, u.nome, u.usuario, u.tipo, u.ativo, u.precisa_trocar_senha,
                c.nome AS clinica_nome, e.nome AS equipe_nome
         FROM usuarios u
         LEFT JOIN clinicas c ON c.id = u.clinica_id
         LEFT JOIN equipes e ON e.id = u.equipe_id
         ORDER BY u.tipo, u.nome'
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cadastrarUsuario(array $dados): array
{
    $nome = trim($dados['nome'] ?? '');
    $usuario = strtolower(trim($dados['usuario'] ?? ''));
    $senha = $dados['senha'] ?? '';
    $tipo = $dados['tipo'] ?? '';

    if ($nome === '') {
        return ['sucesso' => false, 'erro' => 'Informe o nome completo.'];
    }

    if (!preg_match('/^[a-z0-9._]{3,40}$/', $usuario)) {
        return ['sucesso' => false, 'erro' => 'Usuário deve ter de 3 a 40 caracteres, usando apenas letras minúsculas, números, ponto e underline.'];
    }

    if (strlen($senha) < 6) {
        return ['sucesso' => false, 'erro' => 'A senha inicial precisa ter pelo menos 6 caracteres.'];
    }

    if (!in_array($tipo, ['atendente', 'integrante', 'admin'], true)) {
        return ['sucesso' => false, 'erro' => 'Tipo de acesso inválido.'];
    }

    $clinicaId = null;
    $equipeId = null;

    if ($tipo === 'atendente') {
        $clinicaId = (int) ($dados['clinica_id'] ?? 0);
        if (!$clinicaId) {
            return ['sucesso' => false, 'erro' => 'Selecione a clínica da atendente.'];
        }
    } elseif ($tipo === 'integrante') {
        $equipeId = (int) ($dados['equipe_id'] ?? 0);
        if (!$equipeId) {
            return ['sucesso' => false, 'erro' => 'Selecione a equipe.'];
        }
    }

    $pdo = getConexao();

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = :usuario');
    $stmt->execute(['usuario' => $usuario]);
    if ($stmt->fetch()) {
        return ['sucesso' => false, 'erro' => 'Esse nome de usuário já está em uso.'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nome, usuario, senha_hash, tipo, clinica_id, equipe_id)
         VALUES (:nome, :usuario, :senha_hash, :tipo, :clinica_id, :equipe_id)'
    );
    $stmt->execute([
        'nome'       => $nome,
        'usuario'    => $usuario,
        'senha_hash' => password_hash($senha, PASSWORD_DEFAULT),
        'tipo'       => $tipo,
        'clinica_id' => $clinicaId,
        'equipe_id'  => $equipeId,
    ]);

    return ['sucesso' => true, 'id' => (int) $pdo->lastInsertId()];
}

function redefinirSenhaUsuario(int $usuarioId, string $novaSenha): array
{
    if (strlen($novaSenha) < 6) {
        return ['sucesso' => false, 'erro' => 'A senha precisa ter pelo menos 6 caracteres.'];
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'UPDATE usuarios
         SET senha_hash = :hash, precisa_trocar_senha = 1,
             tentativas_falhas = 0, bloqueado_ate = NULL
         WHERE id = :id'
    );
    $stmt->execute([
        'hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
        'id'   => $usuarioId,
    ]);

    return ['sucesso' => true];
}

function alternarAtivoUsuario(int $usuarioId, int $adminLogadoId): array
{
    if ($usuarioId === $adminLogadoId) {
        return ['sucesso' => false, 'erro' => 'Você não pode desativar a própria conta.'];
    }

    $pdo = getConexao();
    $stmt = $pdo->prepare('UPDATE usuarios SET ativo = 1 - ativo WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);

    return ['sucesso' => true];
}

/* ===================== Exportação de calendário (.ics) ===================== */

function obterOuCriarTokenCalendario(int $usuarioId): string
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT token_calendario FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);
    $token = $stmt->fetchColumn();

    if ($token) {
        return $token;
    }

    $novoToken = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare('UPDATE usuarios SET token_calendario = :token WHERE id = :id');
    $stmt->execute(['token' => $novoToken, 'id' => $usuarioId]);

    return $novoToken;
}

function buscarUsuarioPorTokenCalendario(string $token): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT id, nome, tipo, equipe_id FROM usuarios
         WHERE token_calendario = :token AND ativo = 1'
    );
    $stmt->execute(['token' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function escaparTextoIcs(string $texto): string
{
    return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $texto);
}

function gerarIcsAgenda(array $agenda, string $nomeCalendario): string
{
    $fuso = new DateTimeZone('America/Sao_Paulo');
    $utc = new DateTimeZone('UTC');
    $cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];

    $linhas = [];
    $linhas[] = 'BEGIN:VCALENDAR';
    $linhas[] = 'VERSION:2.0';
    $linhas[] = 'PRODID:-//Protocolo Fast//PT-BR';
    $linhas[] = 'CALSCALE:GREGORIAN';
    $linhas[] = 'METHOD:PUBLISH';
    $linhas[] = 'X-WR-CALNAME:' . escaparTextoIcs($nomeCalendario);

    foreach ($agenda as $item) {
        $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $item['data'] . ' 08:00:00', $fuso);
        $fim = (clone $inicio)->modify('+2 hours');

        $inicioUtc = (clone $inicio)->setTimezone($utc);
        $fimUtc = (clone $fim)->setTimezone($utc);

        $descricao = 'Ficha ' . $item['ficha_numero']
            . ' | Carga ' . ($cargaLabel[$item['carga']] ?? '')
            . ' | Opera: ' . $item['dentista_operador'];

        $linhas[] = 'BEGIN:VEVENT';
        $linhas[] = 'UID:agendamento-' . $item['id'] . '@protocolo-fast';
        $linhas[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $linhas[] = 'DTSTART:' . $inicioUtc->format('Ymd\THis\Z');
        $linhas[] = 'DTEND:' . $fimUtc->format('Ymd\THis\Z');
        $linhas[] = 'SUMMARY:' . escaparTextoIcs($item['paciente_nome'] . ' — ' . $item['equipe_nome']);
        $linhas[] = 'LOCATION:' . escaparTextoIcs($item['clinica_nome']);
        $linhas[] = 'DESCRIPTION:' . escaparTextoIcs($descricao);
        $linhas[] = 'END:VEVENT';
    }

    $linhas[] = 'END:VCALENDAR';

    return implode("\r\n", $linhas);
}