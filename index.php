<?php
require_once __DIR__ . '/includes/auth.php';

if (estaLogado()) {
    header('Location: painel.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (tentarLogin($usuario, $senha)) {
        header('Location: painel.php');
        exit;
    }

    $erro = 'Usuário ou senha inválidos.';
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
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Entrar — Protocolo Fast</title>
</head>
<body>
    <div class="tela-login">
        <div class="marca">
            <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
            <p class="marca-sub">Protocolo Fast</p>
        </div>

        <div class="card-login">
            <?php if ($erro): ?>
                <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php">
                <label for="usuario">Usuário</label>
                <input type="text" name="usuario" id="usuario" required autofocus autocomplete="username">

                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required autocomplete="current-password">

                <button type="submit">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>