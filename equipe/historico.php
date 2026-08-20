<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('integrante');

$histClinicaFixa = null;
$histEquipeFixa = (int) $_SESSION['equipe_id'];
$histPodeFiltrar = false;
$histUrlBase = 'historico.php';

$equipe = buscarEquipe($histEquipeFixa);
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
    <title>Histórico — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <nav class="nav">
        <a href="agenda.php">Minha agenda</a>
        <a href="historico.php" class="ativo">Histórico</a>
    </nav>

    <p class="contexto-usuario">
        <?= htmlspecialchars($_SESSION['usuario_nome']) ?> ·
        <?= htmlspecialchars($equipe['nome'] ?? '') ?>
    </p>

    <?php require __DIR__ . '/../includes/_historico.php'; ?>

    <p><a href="../logout.php" class="link-sair">Sair</a></p>
</body>
</html>