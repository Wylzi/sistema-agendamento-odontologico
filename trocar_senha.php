<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

$erro = null;
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';

    if (strlen($novaSenha) < 6) {
        $erro = 'A senha precisa ter pelo menos 6 caracteres.';
    } elseif ($novaSenha !== $confirmacao) {
        $erro = 'As senhas não conferem.';
    } else {
        $pdo = getConexao();
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET senha_hash = :hash, precisa_trocar_senha = 0 WHERE id = :id'
        );
        $stmt->execute([
            'hash' => password_hash($novaSenha, PASSWORD_DEFAULT),
            'id'   => $_SESSION['usuario_id'],
        ]);

        $_SESSION['precisa_trocar_senha'] = 0;
        header('Location: painel.php');
        exit;
    }
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
    <title>Trocar senha — Protocolo Fast</title>
</head>
<body>
    <div class="tela-login">
        <div class="marca">
            <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
            <p class="marca-sub">Protocolo Fast</p>
        </div>

        <div class="card-login">
            <h2>Defina sua senha</h2>
            <p style="font-size:0.85rem;color:var(--cinza-texto);margin-bottom:6px;">
                Por segurança, escolha uma senha pessoal antes de continuar.
            </p>

            <?php if ($erro): ?>
                <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="post" action="trocar_senha.php">
                <label for="nova_senha">Nova senha</label>
                <input type="password" name="nova_senha" id="nova_senha" required autocomplete="new-password">

                <label for="confirmacao">Repita a senha</label>
                <input type="password" name="confirmacao" id="confirmacao" required autocomplete="new-password">

                <button type="submit">Salvar e continuar</button>
            </form>
        </div>
    </div>
</body>
</html>