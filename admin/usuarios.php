<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('admin');

$mensagem = null;
$tipoMensagem = 'erro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $resultado = cadastrarUsuario([
            'nome'       => $_POST['nome'] ?? '',
            'usuario'    => $_POST['usuario'] ?? '',
            'senha'      => $_POST['senha'] ?? '',
            'tipo'       => $_POST['tipo'] ?? '',
            'clinica_id' => $_POST['clinica_id'] ?? 0,
            'equipe_id'  => $_POST['equipe_id'] ?? 0,
        ]);

        $_SESSION['flash'] = $resultado['sucesso']
            ? ['texto' => 'Usuário cadastrado.', 'tipo' => 'sucesso']
            : ['texto' => $resultado['erro'], 'tipo' => 'erro'];

    } elseif ($acao === 'redefinir') {
        $resultado = redefinirSenhaUsuario(
            (int) ($_POST['usuario_id'] ?? 0),
            $_POST['nova_senha'] ?? ''
        );

        $_SESSION['flash'] = $resultado['sucesso']
            ? ['texto' => 'Senha redefinida. A pessoa vai trocar no próximo acesso.', 'tipo' => 'sucesso']
            : ['texto' => $resultado['erro'], 'tipo' => 'erro'];

    } elseif ($acao === 'alternar_ativo') {
        $resultado = alternarAtivoUsuario(
            (int) ($_POST['usuario_id'] ?? 0),
            (int) $_SESSION['usuario_id']
        );

        $_SESSION['flash'] = $resultado['sucesso']
            ? ['texto' => 'Situação do usuário alterada.', 'tipo' => 'sucesso']
            : ['texto' => $resultado['erro'], 'tipo' => 'erro'];
    }

    header('Location: usuarios.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $mensagem = $_SESSION['flash']['texto'];
    $tipoMensagem = $_SESSION['flash']['tipo'];
    unset($_SESSION['flash']);
}

$usuarios = listarUsuarios();
$clinicas = listarClinicas();
$equipes = listarEquipes();

$tipoLabel = ['atendente' => 'Atendente', 'integrante' => 'Equipe', 'admin' => 'Admin'];

$tituloPagina = 'Usuários — Protocolo Fast';
require __DIR__ . '/_cabecalho.php';
?>

<?php if ($mensagem): ?>
    <div class="alerta-<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Novo usuário</h2>
    <form method="post" action="usuarios.php">
        <input type="hidden" name="acao" value="cadastrar">

        <label for="nome">Nome completo</label>
        <input type="text" name="nome" id="nome" required>

        <label for="usuario">Usuário</label>
        <input type="text" name="usuario" id="usuario" required
               placeholder="maria.centro" pattern="[a-zA-Z0-9._]{3,40}">

        <label for="senha">Senha inicial</label>
        <input type="text" name="senha" id="senha" required minlength="6">
        <p class="texto-auxiliar">A pessoa será obrigada a trocar no primeiro acesso.</p>

        <label for="tipo">Tipo de acesso</label>
        <select name="tipo" id="tipo" required onchange="ajustarVinculo()">
            <option value="">Selecione...</option>
            <option value="atendente">Atendente</option>
            <option value="integrante">Equipe</option>
            <option value="admin">Admin</option>
        </select>

        <div id="campo-clinica" class="escondido">
            <label for="clinica_id">Clínica</label>
            <select name="clinica_id" id="clinica_id">
                <option value="">Selecione...</option>
                <?php foreach ($clinicas as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="campo-equipe" class="escondido">
            <label for="equipe_id">Equipe</label>
            <select name="equipe_id" id="equipe_id">
                <option value="">Selecione...</option>
                <?php foreach ($equipes as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Cadastrar usuário</button>
    </form>
</div>

<div class="card">
    <h2>Usuários cadastrados (<?= count($usuarios) ?>)</h2>

    <?php foreach ($usuarios as $u): ?>
        <div class="linha-lista">
            <div class="linha-lista-info">
                <p class="linha-lista-titulo">
                    <?= htmlspecialchars($u['nome']) ?>
                    <?php if (!$u['ativo']): ?>
                        <span class="tag-inativo">inativo</span>
                    <?php endif; ?>
                </p>
                <p class="linha-lista-sub">
                    <?= htmlspecialchars($u['usuario']) ?> ·
                    <?= $tipoLabel[$u['tipo']] ?? '' ?>
                    <?php if ($u['clinica_nome']): ?>
                        · <?= htmlspecialchars($u['clinica_nome']) ?>
                    <?php elseif ($u['equipe_nome']): ?>
                        · <?= htmlspecialchars($u['equipe_nome']) ?>
                    <?php endif; ?>
                </p>
            </div>

            <button type="button" class="btn-texto"
                    onclick="document.getElementById('acoes-<?= $u['id'] ?>').classList.toggle('escondido')">
                Opções
            </button>
        </div>

        <div id="acoes-<?= $u['id'] ?>" class="acoes-usuario escondido">
            <form method="post" action="usuarios.php" class="linha-acao">
                <input type="hidden" name="acao" value="redefinir">
                <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                <input type="text" name="nova_senha" placeholder="Nova senha" required minlength="6">
                <button type="submit" class="btn-dourado btn-pequeno">Redefinir</button>
            </form>

            <form method="post" action="usuarios.php"
                  onsubmit="return confirm('<?= $u['ativo'] ? 'Desativar' : 'Reativar' ?> esse usuário?');">
                <input type="hidden" name="acao" value="alternar_ativo">
                <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn-texto <?= $u['ativo'] ? 'btn-perigo' : '' ?>">
                    <?= $u['ativo'] ? 'Desativar' : 'Reativar' ?>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<script>
    function ajustarVinculo() {
        const tipo = document.getElementById('tipo').value;
        const campoClinica = document.getElementById('campo-clinica');
        const campoEquipe = document.getElementById('campo-equipe');

        campoClinica.classList.toggle('escondido', tipo !== 'atendente');
        campoEquipe.classList.toggle('escondido', tipo !== 'integrante');
    }
</script>

<?php require __DIR__ . '/_rodape.php'; ?>