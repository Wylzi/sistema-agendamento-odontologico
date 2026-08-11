<?php
require_once __DIR__ . '/functions.php';

session_start();

function tentarLogin(string $email, string $senha): bool
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT id, nome, senha_hash, tentativas_falhas, bloqueado_ate
         FROM dentistas WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);
    $dentista = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dentista) {
        return false;
    }

    // Verifica se está bloqueado no momento
    if ($dentista['bloqueado_ate'] !== null && $dentista['bloqueado_ate'] > date('Y-m-d H:i:s')) {
        return false;
    }

    if (password_verify($senha, $dentista['senha_hash'])) {
        // Login certo: zera o contador e o bloqueio
        $stmt = $pdo->prepare(
            'UPDATE dentistas SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $dentista['id']]);

        session_regenerate_id(true);
        $_SESSION['dentista_id'] = $dentista['id'];
        $_SESSION['dentista_nome'] = $dentista['nome'];
        return true;
    }

    // Login errado: incrementa tentativas e bloqueia se passou do limite
    $novasTentativas = $dentista['tentativas_falhas'] + 1;
    $bloqueadoAte = null;

    if ($novasTentativas >= 5) {
        $bloqueadoAte = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    }

    $stmt = $pdo->prepare(
        'UPDATE dentistas SET tentativas_falhas = :tentativas, bloqueado_ate = :bloqueado WHERE id = :id'
    );
    $stmt->execute([
        'tentativas' => $novasTentativas,
        'bloqueado'  => $bloqueadoAte,
        'id'         => $dentista['id'],
    ]);

    return false;
}

function estaLogado(): bool
{
    return isset($_SESSION['dentista_id']);
}

function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}