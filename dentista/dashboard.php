<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Minha Agenda</title>
</head>
<body>
    <h1>Olá, <?= htmlspecialchars($_SESSION['dentista_nome']) ?></h1>
    <p><a href="logout.php">Sair</a></p>
</body>
</html>