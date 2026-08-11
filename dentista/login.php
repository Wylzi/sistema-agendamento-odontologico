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
    <title>Login do Dentista</title>
</head>
<body>
    <h1>Área do Dentista</h1>

    <?php if ($erro): ?>
        <p><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label for="email">E-mail</label><br>
        <input type="email" name="email" id="email" required><br>

        <label for="senha">Senha</label><br>
        <input type="password" name="senha" id="senha" required><br>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>