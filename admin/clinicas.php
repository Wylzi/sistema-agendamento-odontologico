<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('admin');

$mensagem = null;
$tipoMensagem = 'erro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrfValido();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $resultado = cadastrarClinica($_POST['nome'] ?? '', $_POST['endereco'] ?? '');
        if ($resultado['sucesso']) {
            $_SESSION['flash'] = ['texto' => 'Clínica cadastrada.', 'tipo' => 'sucesso'];
        } else {
            $_SESSION['flash'] = ['texto' => $resultado['erro'], 'tipo' => 'erro'];
        }
    } elseif ($acao === 'importar') {
        $resultado = importarClinicas($_POST['lista'] ?? '');
        $texto = $resultado['inseridas'] . ' clínica(s) cadastrada(s).';

        if (!empty($resultado['ignoradas'])) {
            $texto .= ' Não cadastradas (já existiam ou inválidas): ' . implode(', ', $resultado['ignoradas']);
        }

        $_SESSION['flash'] = ['texto' => $texto, 'tipo' => 'sucesso'];
    } elseif ($acao === 'remover') {
        $resultado = removerClinica((int) ($_POST['clinica_id'] ?? 0));
        if ($resultado['sucesso']) {
            $_SESSION['flash'] = ['texto' => 'Clínica removida.', 'tipo' => 'sucesso'];
        } else {
            $_SESSION['flash'] = ['texto' => $resultado['erro'], 'tipo' => 'erro'];
        }
    }

    header('Location: clinicas.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $mensagem = $_SESSION['flash']['texto'];
    $tipoMensagem = $_SESSION['flash']['tipo'];
    unset($_SESSION['flash']);
}

$clinicas = listarClinicas();

$tituloPagina = 'Clínicas — Protocolo Fast';
require __DIR__ . '/_cabecalho.php';
?>

<?php if ($mensagem): ?>
    <div class="alerta-<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Nova clínica</h2>
    <form method="post" action="clinicas.php">
        <?= campoCsrf() ?>
        <input type="hidden" name="acao" value="cadastrar">

        <label for="nome">Nome</label>
        <input type="text" name="nome" id="nome" required>

        <label for="endereco">Endereço (opcional)</label>
        <input type="text" name="endereco" id="endereco">

        <button type="submit">Cadastrar</button>
    </form>
</div>

<div class="card">
    <h2>Importar várias</h2>
    <p class="texto-auxiliar">Uma por linha. Para incluir o endereço, use ponto e vírgula: <em>Nome; Endereço</em></p>
    <form method="post" action="clinicas.php">
        <?= campoCsrf() ?>
        <input type="hidden" name="acao" value="importar">
        <textarea name="lista" rows="6" required placeholder="Unidade Centro; Av. Central, 100&#10;Unidade Anápolis&#10;Unidade Rio Verde; Rua 7, 250"></textarea>
        <button type="submit">Importar lista</button>
    </form>
</div>

<div class="card">
    <h2>Clínicas cadastradas (<?= count($clinicas) ?>)</h2>

    <?php if (empty($clinicas)): ?>
        <p class="vazio">Nenhuma clínica cadastrada.</p>
    <?php else: ?>
        <?php foreach ($clinicas as $c): ?>
            <div class="linha-lista">
                <div class="linha-lista-info">
                    <p class="linha-lista-titulo"><?= htmlspecialchars($c['nome']) ?></p>
                    <?php if ($c['endereco']): ?>
                        <p class="linha-lista-sub"><?= htmlspecialchars($c['endereco']) ?></p>
                    <?php endif; ?>
                </div>
                <form method="post" action="clinicas.php" onsubmit="return confirm('Remover essa clínica?');">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="acao" value="remover">
                    <input type="hidden" name="clinica_id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn-texto btn-perigo">Remover</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/_rodape.php'; ?>