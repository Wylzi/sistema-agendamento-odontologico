<?php
require_once __DIR__ . '/functions.php';

session_start();

const HASH_FALSO = '$2y$10$WaZCRVRqGVsScaAtH.K.2O27buNzQIMsfno6N/cB8G.ptdCw1cehu';

function tentarLogin(string $usuario, string $senha): bool
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT id, nome, usuario, senha_hash, tipo, clinica_id, equipe_id,
                precisa_trocar_senha, tentativas_falhas, bloqueado_ate
         FROM usuarios
         WHERE usuario = :usuario AND ativo = 1'
    );
    $stmt->execute(['usuario' => $usuario]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);

    // Roda o password_verify() SEMPRE, exista o usuário ou não,
    // para equalizar o tempo de resposta.
    $hashParaChecar = $dados['senha_hash'] ?? HASH_FALSO;
    $senhaCorreta = password_verify($senha, $hashParaChecar);

    if (!$dados) {
        return false;
    }

    if ($dados['bloqueado_ate'] !== null && $dados['bloqueado_ate'] > date('Y-m-d H:i:s')) {
        return false;
    }

    if ($senhaCorreta) {
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $dados['id']]);

        session_regenerate_id(true);
        $_SESSION['usuario_id']   = $dados['id'];
        $_SESSION['usuario_nome'] = $dados['nome'];
        $_SESSION['usuario_tipo'] = $dados['tipo'];
        $_SESSION['clinica_id']   = $dados['clinica_id'];
        $_SESSION['equipe_id']    = $dados['equipe_id'];
        $_SESSION['precisa_trocar_senha'] = (int) $dados['precisa_trocar_senha'];
        return true;
    }

    $novasTentativas = $dados['tentativas_falhas'] + 1;
    $bloqueadoAte = null;

    if ($novasTentativas >= 5) {
        $bloqueadoAte = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    }

    $stmt = $pdo->prepare(
        'UPDATE usuarios SET tentativas_falhas = :tentativas, bloqueado_ate = :bloqueado WHERE id = :id'
    );
    $stmt->execute([
        'tentativas' => $novasTentativas,
        'bloqueado'  => $bloqueadoAte,
        'id'         => $dados['id'],
    ]);

    return false;
}

function estaLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: /sistema-agendamento-odontologico/index.php');
        exit;
    }

    if (!empty($_SESSION['precisa_trocar_senha'])) {
        $paginaAtual = basename($_SERVER['SCRIPT_NAME']);
        if ($paginaAtual !== 'trocar_senha.php') {
            header('Location: trocar_senha.php');
            exit;
        }
    }
}

function exigirTipo(string ...$tiposPermitidos): void
{
    exigirLogin();

    if (!in_array($_SESSION['usuario_tipo'], $tiposPermitidos, true)) {
        http_response_code(403);
        exit('Você não tem permissão para acessar esta página.');
    }
}

function tipoUsuario(): string
{
    return $_SESSION['usuario_tipo'] ?? '';
}

function fazerLogout(): void
{
    $_SESSION = [];
    session_destroy();
}