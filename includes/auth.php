<?php
require_once __DIR__ . '/functions.php';

session_start();

const HASH_FALSO = '$2y$10$WaZCRVRqGVsScaAtH.K.2O27buNzQIMsfno6N/cB8G.ptdCw1cehu';

function tentarLogin(string $email, string $senha): bool
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT id, nome, senha_hash, tentativas_falhas, bloqueado_ate
         FROM dentistas WHERE email = :email'
    );
    $stmt->execute(['email' => $email]);
    $dentista = $stmt->fetch(PDO::FETCH_ASSOC);

    // Roda o password_verify() SEMPRE, exista o e-mail ou não —
    // isso é o que equaliza o tempo de resposta nos dois casos.
    $hashParaChecar = $dentista['senha_hash'] ?? HASH_FALSO;
    $senhaCorreta = password_verify($senha, $hashParaChecar);

    if (!$dentista) {
        return false;
    }

    // Verifica se está bloqueado no momento
    if ($dentista['bloqueado_ate'] !== null && $dentista['bloqueado_ate'] > date('Y-m-d H:i:s')) {
        return false;
    }

    if ($senhaCorreta) {
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