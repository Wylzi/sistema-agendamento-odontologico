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

/* ===================== Equipes ===================== */

function listarEquipes(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query('SELECT id, nome FROM equipes WHERE ativo = 1 ORDER BY id');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function totalEquipesAtivas(): int
{
    $pdo = getConexao();
    return (int) $pdo->query('SELECT COUNT(*) FROM equipes WHERE ativo = 1')->fetchColumn();
}

function buscarEquipe(int $equipeId): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome FROM equipes WHERE id = :id');
    $stmt->execute(['id' => $equipeId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ===================== Ocupação / vagas ===================== */

/**
 * Retorna, para cada dia do mês, quantas vagas já foram ocupadas.
 * Resultado: ['2026-09-04' => 2, '2026-09-07' => 1, ...]
 */
function ocupacaoDoMes(int $ano, int $mes): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT data, COUNT(*) AS ocupadas
         FROM agendamentos
         WHERE YEAR(data) = :ano AND MONTH(data) = :mes AND cancelado = 0
         GROUP BY data'
    );
    $stmt->execute(['ano' => $ano, 'mes' => $mes]);

    $ocupacao = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        $ocupacao[$linha['data']] = (int) $linha['ocupadas'];
    }
    return $ocupacao;
}

/** Retorna as equipes que ainda estão livres numa data. */
function equipesLivresNaData(string $data, ?int $ignorarAgendamentoId = null): array
{
    $pdo = getConexao();

    $sql =
        'SELECT e.id, e.nome
         FROM equipes e
         WHERE e.ativo = 1
           AND e.id NOT IN (
               SELECT equipe_id FROM agendamentos
               WHERE data = :data AND cancelado = 0';

    $parametros = ['data' => $data];

    if ($ignorarAgendamentoId !== null) {
        $sql .= ' AND id <> :ignorar';
        $parametros['ignorar'] = $ignorarAgendamentoId;
    }

    $sql .= ') ORDER BY e.id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function contarAgendamentosNaData(string $data): int
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM agendamentos WHERE data = :data AND cancelado = 0');
    $stmt->execute(['data' => $data]);
    return (int) $stmt->fetchColumn();
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

function hojeIso(): string
{
    return (new DateTime('today'))->format('Y-m-d');
}

/* ===================== Disponibilidade de datas ===================== */

/**
 * Feriados nacionais do ano, incluindo os móveis (calculados a partir da Páscoa).
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

/** Versão que consulta o banco sozinha, para uma data isolada. */
function dataDisponivel(string $dataIso): array
{
    $dt = new DateTime($dataIso);
    $excecoes = excecoesDoMes((int) $dt->format('Y'), (int) $dt->format('n'));
    $feriados = feriadosNacionais((int) $dt->format('Y'));
    return situacaoDaData($dataIso, $excecoes, $feriados);
}

/* ===================== Agendamentos ===================== */

function criarAgendamento(array $dados): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT equipe_id FROM agendamentos WHERE data = :data AND cancelado = 0 FOR UPDATE'
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
        registrarErro('criarAgendamento', $e);
        return ['sucesso' => false, 'erro' => 'Erro ao salvar o agendamento.'];
    }
}

function buscarAgendamento(int $id): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT a.*, c.nome AS clinica_nome, e.nome AS equipe_nome, u.nome AS marcado_por
         FROM agendamentos a
         JOIN clinicas c ON c.id = a.clinica_id
         JOIN equipes e ON e.id = a.equipe_id
         JOIN usuarios u ON u.id = a.marcado_por_usuario_id
         WHERE a.id = :id'
    );
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Lista agendamentos. Opções:
 *   equipe_id, clinica_id, incluir_passados, incluir_cancelados
 */
function listarAgendamentos(array $opcoes = []): array
{
    $pdo = getConexao();

    $sql =
        'SELECT a.id, a.data, a.paciente_nome, a.ficha_numero, a.carga,
                a.dentista_operador, a.telefone_contato, a.equipe_id, a.clinica_id,
                a.cancelado, a.motivo_cancelamento,
                c.nome AS clinica_nome,
                e.nome AS equipe_nome,
                u.nome AS marcado_por
         FROM agendamentos a
         JOIN clinicas c ON c.id = a.clinica_id
         JOIN equipes e ON e.id = a.equipe_id
         JOIN usuarios u ON u.id = a.marcado_por_usuario_id
         WHERE 1 = 1';

    $parametros = [];

    if (empty($opcoes['incluir_cancelados'])) {
        $sql .= ' AND a.cancelado = 0';
    }

    if (empty($opcoes['incluir_passados'])) {
        $sql .= ' AND a.data >= CURDATE()';
    }

    if (!empty($opcoes['equipe_id'])) {
        $sql .= ' AND a.equipe_id = :equipe_id';
        $parametros['equipe_id'] = $opcoes['equipe_id'];
    }

    if (!empty($opcoes['clinica_id'])) {
        $sql .= ' AND a.clinica_id = :clinica_id';
        $parametros['clinica_id'] = $opcoes['clinica_id'];
    }

    $sql .= ' ORDER BY a.data, a.equipe_id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cancelarAgendamento(int $id, int $usuarioId, ?string $motivo): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT cancelado FROM agendamentos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $ag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ag) {
        return ['sucesso' => false, 'erro' => 'Agendamento não encontrado.'];
    }

    if ((int) $ag['cancelado'] === 1) {
        return ['sucesso' => false, 'erro' => 'Esse agendamento já está cancelado.'];
    }

    $motivo = $motivo !== null ? trim($motivo) : '';

    $stmt = $pdo->prepare(
        'UPDATE agendamentos
         SET cancelado = 1, cancelado_em = NOW(),
             cancelado_por_usuario_id = :usuario_id,
             motivo_cancelamento = :motivo
         WHERE id = :id'
    );
    $stmt->execute([
        'usuario_id' => $usuarioId,
        'motivo'     => $motivo !== '' ? $motivo : null,
        'id'         => $id,
    ]);

    return ['sucesso' => true];
}

/**
 * Edita um agendamento. Se a data mudar, verifica vaga na nova data.
 */
function editarAgendamento(int $id, array $dados): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM agendamentos WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        $atual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$atual) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Agendamento não encontrado.'];
        }

        if ((int) $atual['cancelado'] === 1) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Não é possível editar um agendamento cancelado.'];
        }

        $novaData = $dados['data'];
        $equipeId = (int) $atual['equipe_id'];

        // Se a data mudou, precisa validar a nova data e conseguir uma equipe livre nela
        if ($novaData !== $atual['data']) {
            if ($novaData < hojeIso()) {
                $pdo->rollBack();
                return ['sucesso' => false, 'erro' => 'A nova data já passou.'];
            }

            $situacao = dataDisponivel($novaData);
            if (!$situacao['disponivel']) {
                $pdo->rollBack();
                return ['sucesso' => false, 'erro' => 'A nova data não está disponível.'];
            }

            $stmt = $pdo->prepare(
                'SELECT equipe_id FROM agendamentos
                 WHERE data = :data AND cancelado = 0 AND id <> :id FOR UPDATE'
            );
            $stmt->execute(['data' => $novaData, 'id' => $id]);
            $ocupadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $pdo->prepare('SELECT id FROM equipes WHERE ativo = 1 ORDER BY id');
            $stmt->execute();
            $todasEquipes = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $livres = array_values(array_diff($todasEquipes, $ocupadas));

            if (empty($livres)) {
                $pdo->rollBack();
                return ['sucesso' => false, 'erro' => 'Não há vagas na nova data.'];
            }

            // Mantém a equipe atual se ela estiver livre na nova data
            $equipeId = in_array((int) $atual['equipe_id'], array_map('intval', $livres), true)
                ? (int) $atual['equipe_id']
                : (int) $livres[0];
        }

        $stmt = $pdo->prepare(
            'UPDATE agendamentos
             SET data = :data, equipe_id = :equipe_id,
                 paciente_nome = :paciente_nome, ficha_numero = :ficha_numero,
                 carga = :carga, dentista_operador = :dentista_operador,
                 telefone_contato = :telefone
             WHERE id = :id'
        );
        $stmt->execute([
            'data'              => $novaData,
            'equipe_id'         => $equipeId,
            'paciente_nome'     => $dados['paciente_nome'],
            'ficha_numero'      => $dados['ficha_numero'],
            'carga'             => $dados['carga'],
            'dentista_operador' => $dados['dentista_operador'],
            'telefone'          => $dados['telefone'] ?: null,
            'id'                => $id,
        ]);

        $pdo->commit();
        return ['sucesso' => true];
    } catch (Throwable $e) {
        $pdo->rollBack();
        registrarErro('editarAgendamento', $e);
        return ['sucesso' => false, 'erro' => 'Erro ao editar o agendamento.'];
    }
}

function trocarEquipeAgendamento(int $agendamentoId, int $novaEquipeId): array
{
    $pdo = getConexao();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT data, equipe_id, cancelado FROM agendamentos WHERE id = :id FOR UPDATE'
        );
        $stmt->execute(['id' => $agendamentoId]);
        $ag = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ag) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Agendamento não encontrado.'];
        }

        if ((int) $ag['cancelado'] === 1) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Esse agendamento está cancelado.'];
        }

        if ((int) $ag['equipe_id'] === $novaEquipeId) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'O agendamento já está nessa equipe.'];
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM agendamentos
             WHERE data = :data AND equipe_id = :equipe_id AND cancelado = 0 FOR UPDATE'
        );
        $stmt->execute(['data' => $ag['data'], 'equipe_id' => $novaEquipeId]);

        if ($stmt->fetch()) {
            $pdo->rollBack();
            return ['sucesso' => false, 'erro' => 'Essa equipe já tem cirurgia nesse dia.'];
        }

        $stmt = $pdo->prepare('UPDATE agendamentos SET equipe_id = :equipe_id WHERE id = :id');
        $stmt->execute(['equipe_id' => $novaEquipeId, 'id' => $agendamentoId]);

        $pdo->commit();
        return ['sucesso' => true];
    } catch (Throwable $e) {
        $pdo->rollBack();
        registrarErro('trocarEquipeAgendamento', $e);
        return ['sucesso' => false, 'erro' => 'Erro ao trocar a equipe.'];
    }
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

/* ===================== Exceções de calendário ===================== */

function listarExcecoesFuturas(): array
{
    $pdo = getConexao();
    $stmt = $pdo->query(
        'SELECT id, data, tipo, motivo FROM excecoes_calendario
         WHERE data >= CURDATE()
         ORDER BY data'
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function salvarExcecao(string $data, string $tipo, ?string $motivo): array
{
    $dt = DateTime::createFromFormat('Y-m-d', $data);
    if (!$dt || $dt->format('Y-m-d') !== $data) {
        return ['sucesso' => false, 'erro' => 'Data inválida.'];
    }

    if ($data < hojeIso()) {
        return ['sucesso' => false, 'erro' => 'Não é possível alterar datas passadas.'];
    }

    if (!in_array($tipo, ['bloqueado', 'liberado'], true)) {
        return ['sucesso' => false, 'erro' => 'Tipo inválido.'];
    }

    $diaSemana = (int) $dt->format('w');

    if ($tipo === 'liberado' && $diaSemana !== 6) {
        return ['sucesso' => false, 'erro' => 'Só é possível liberar sábados.'];
    }

    if ($tipo === 'bloqueado' && ($diaSemana === 0 || $diaSemana === 6)) {
        return ['sucesso' => false, 'erro' => 'Sábados e domingos já são bloqueados por padrão.'];
    }

    $motivo = $motivo !== null ? trim($motivo) : '';

    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'INSERT INTO excecoes_calendario (data, tipo, motivo)
         VALUES (:data, :tipo, :motivo)
         ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), motivo = VALUES(motivo)'
    );
    $stmt->execute([
        'data'   => $data,
        'tipo'   => $tipo,
        'motivo' => $motivo !== '' ? $motivo : null,
    ]);

    return ['sucesso' => true];
}

function removerExcecao(int $id): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('DELETE FROM excecoes_calendario WHERE id = :id');
    $stmt->execute(['id' => $id]);
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

/* ===================== Log de erros ===================== */

function registrarErro(string $contexto, Throwable $e): void
{
    error_log('[Protocolo Fast] ' . $contexto . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
}

/* ===================== Histórico ===================== */

/**
 * Busca agendamentos com filtros. Opções:
 *   busca, data_inicio, data_fim, clinica_id, equipe_id,
 *   situacao ('todos' | 'ativos' | 'cancelados')
 */
function buscarHistorico(array $filtros = []): array
{
    $pdo = getConexao();

    $sql =
        'SELECT a.id, a.data, a.paciente_nome, a.ficha_numero, a.carga,
                a.dentista_operador, a.telefone_contato, a.cancelado,
                a.motivo_cancelamento, a.criado_em,
                c.nome AS clinica_nome,
                e.nome AS equipe_nome,
                u.nome AS marcado_por
         FROM agendamentos a
         JOIN clinicas c ON c.id = a.clinica_id
         JOIN equipes e ON e.id = a.equipe_id
         JOIN usuarios u ON u.id = a.marcado_por_usuario_id
         WHERE 1 = 1';

    $parametros = [];

    if (!empty($filtros['busca'])) {
        $sql .= ' AND (a.paciente_nome LIKE :busca OR a.ficha_numero LIKE :busca)';
        $parametros['busca'] = '%' . $filtros['busca'] . '%';
    }

    if (!empty($filtros['data_inicio'])) {
        $sql .= ' AND a.data >= :data_inicio';
        $parametros['data_inicio'] = $filtros['data_inicio'];
    }

    if (!empty($filtros['data_fim'])) {
        $sql .= ' AND a.data <= :data_fim';
        $parametros['data_fim'] = $filtros['data_fim'];
    }

    if (!empty($filtros['clinica_id'])) {
        $sql .= ' AND a.clinica_id = :clinica_id';
        $parametros['clinica_id'] = $filtros['clinica_id'];
    }

    if (!empty($filtros['equipe_id'])) {
        $sql .= ' AND a.equipe_id = :equipe_id';
        $parametros['equipe_id'] = $filtros['equipe_id'];
    }

    if (($filtros['situacao'] ?? 'todos') === 'ativos') {
        $sql .= ' AND a.cancelado = 0';
    } elseif (($filtros['situacao'] ?? '') === 'cancelados') {
        $sql .= ' AND a.cancelado = 1';
    }

    $sql .= ' ORDER BY a.data DESC, a.equipe_id LIMIT 200';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}