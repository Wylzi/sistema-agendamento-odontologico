<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('atendente');

$hoje = new DateTime('today');

$calAno = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) $hoje->format('Y');
$calMes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) $hoje->format('n');

if ($calMes < 1 || $calMes > 12) {
    $calMes = (int) $hoje->format('n');
}

$calUrlBase = 'calendario.php';
$calModo = 'link';

$clinica = buscarClinica((int) $_SESSION['clinica_id']);
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
    <title>Agendar — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <nav class="nav">
        <a href="calendario.php" class="ativo">Agendar</a>
        <a href="agendamentos.php">Agendamentos</a>
    </nav>

    <p class="contexto-usuario">
        <?= htmlspecialchars($_SESSION['usuario_nome']) ?> ·
        <?= htmlspecialchars($clinica['nome'] ?? 'Sem clínica') ?>
    </p>

    <?php require __DIR__ . '/../includes/_calendario.php'; ?>

    <p><a href="../logout.php" class="link-sair">Sair</a></p>
</body>
</html>