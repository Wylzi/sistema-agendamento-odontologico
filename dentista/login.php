<?php
require_once __DIR__ . '/../includes/auth.php';

if (estaLogado()) {
    header('Location: dashboard.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (tentarLogin($email, $senha)) {
        header('Location: dashboard.php');
        exit;
    }

    $erro = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login do Dentista</title>
</head>
<body>
    <div class="tela-login">
        <div class="card-login">
            <h1>Área do Dentista</h1>

            <?php if ($erro): ?>
                <p><?= htmlspecialchars($erro) ?></p>
            <?php endif; ?>

            <form method="post" action="login.php">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required>

                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required>

                <button type="submit">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>