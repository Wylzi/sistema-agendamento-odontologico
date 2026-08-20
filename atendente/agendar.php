<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('atendente');

$data = $_GET['data'] ?? $_POST['data'] ?? '';
$hoje = (new DateTime('today'))->format('Y-m-d');

// Valida o formato da data recebida
$dt = DateTime::createFromFormat('Y-m-d', $data);
if (!$dt || $dt->format('Y-m-d') !== $data) {
    header('Location: calendario.php');
    exit;
}

$ano = (int) $dt->format('Y');
$mes = (int) $dt->format('n');

$excecoes = excecoesDoMes($ano, $mes);
$feriados = feriadosNacionais($ano);
$situacao = situacaoDaData($data, $excecoes, $feriados);

$clinica = buscarClinica((int) $_SESSION['clinica_id']);
$equipesLivres = equipesLivresNaData($data);

$erro = null;
$bloqueado = false;

if ($data < $hoje) {
    $erro = 'Essa data já passou.';
    $bloqueado = true;
} elseif (!$situacao['disponivel']) {
    $erro = 'Essa data não está disponível para agendamento.';
    $bloqueado = true;
} elseif (empty($equipesLivres)) {
    $erro = 'Não há mais vagas nesta data.';
    $bloqueado = true;
}

if (!$bloqueado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = criarAgendamento([
        'data'              => $data,
        'clinica_id'        => (int) $_SESSION['clinica_id'],
        'paciente_nome'     => trim($_POST['paciente_nome'] ?? ''),
        'ficha_numero'      => trim($_POST['ficha_numero'] ?? ''),
        'carga'             => $_POST['carga'] ?? '',
        'dentista_operador' => trim($_POST['dentista_operador'] ?? ''),
        'marcado_por'       => (int) $_SESSION['usuario_id'],
        'telefone'          => trim($_POST['telefone'] ?? ''),
    ]);

    if ($resultado['sucesso']) {
        header('Location: confirmado.php?id=' . $resultado['id']);
        exit;
    }

    $erro = $resultado['erro'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Confirmar agendamento — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <p><a href="calendario.php" class="link-voltar">‹ Voltar ao calendário</a></p>

    <div class="card resumo-data">
        <div>
            <p class="resumo-dia"><?= ucfirst(diaDaSemanaBr($data)) ?>, <?= dataPorExtenso($data) ?></p>
            <p class="resumo-hora">Cirurgia às 08:00</p>
        </div>
    </div>

    <?php if ($erro): ?>
        <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (!$bloqueado): ?>
        <form method="post" action="agendar.php" class="card">
            <input type="hidden" name="data" value="<?= htmlspecialchars($data) ?>">

            <label>Clínica</label>
            <p class="campo-fixo"><?= htmlspecialchars($clinica['nome'] ?? '') ?></p>

            <label for="paciente_nome">Paciente</label>
            <input type="text" name="paciente_nome" id="paciente_nome" required
                   value="<?= htmlspecialchars($_POST['paciente_nome'] ?? '') ?>">

            <label for="ficha_numero">Número da ficha</label>
            <input type="text" name="ficha_numero" id="ficha_numero" required
                   value="<?= htmlspecialchars($_POST['ficha_numero'] ?? '') ?>">

            <label for="carga">Carga</label>
            <select name="carga" id="carga" required>
                <option value="">Selecione...</option>
                <option value="superior" <?= (($_POST['carga'] ?? '') === 'superior') ? 'selected' : '' ?>>Superior</option>
                <option value="inferior" <?= (($_POST['carga'] ?? '') === 'inferior') ? 'selected' : '' ?>>Inferior</option>
                <option value="ambas" <?= (($_POST['carga'] ?? '') === 'ambas') ? 'selected' : '' ?>>Ambas</option>
            </select>

            <label for="dentista_operador">Dentista que vai operar</label>
            <input type="text" name="dentista_operador" id="dentista_operador" required
                   value="<?= htmlspecialchars($_POST['dentista_operador'] ?? '') ?>">

            <label>Responsável pela marcação</label>
            <p class="campo-fixo"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>

            <label for="telefone">Telefone de contato</label>
            <input type="tel" name="telefone" id="telefone"
                   value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">

            <button type="submit" class="btn-dourado">Confirmar</button>
        </form>
    <?php endif; ?>
</body>
</html>