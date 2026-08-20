<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('admin');

$paginaAtual = basename($_SERVER['SCRIPT_NAME']);
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
    <title><?= htmlspecialchars($tituloPagina ?? 'Protocolo Fast') ?></title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <nav class="nav">
        <a href="agenda.php" class="<?= $paginaAtual === 'agenda.php' ? 'ativo' : '' ?>">Agenda</a>
        <a href="usuarios.php" class="<?= $paginaAtual === 'usuarios.php' ? 'ativo' : '' ?>">Usuários</a>
        <a href="clinicas.php" class="<?= $paginaAtual === 'clinicas.php' ? 'ativo' : '' ?>">Clínicas</a>
        <a href="calendario.php" class="<?= $paginaAtual === 'calendario.php' ? 'ativo' : '' ?>">Calendário</a>
    </nav>
