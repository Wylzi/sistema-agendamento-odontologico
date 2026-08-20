<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('admin');

$mensagem = null;
$tipoMensagem = 'erro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $data = $_POST['data'] ?? '';
        $tipo = $_POST['tipo'] ?? '';

        if ($tipo === 'bloqueado' && contarAgendamentosNaData($data) > 0) {
            $_SESSION['flash'] = [
                'texto' => 'Esse dia já tem cirurgia agendada. Remova o agendamento antes de bloquear.',
                'tipo'  => 'erro',
            ];
        } else {
            $resultado = salvarExcecao($data, $tipo, $_POST['motivo'] ?? '');
            $_SESSION['flash'] = $resultado['sucesso']
                ? ['texto' => 'Data atualizada.', 'tipo' => 'sucesso']
                : ['texto' => $resultado['erro'], 'tipo' => 'erro'];
        }
    } elseif ($acao === 'remover') {
        removerExcecao((int) ($_POST['excecao_id'] ?? 0));
        $_SESSION['flash'] = ['texto' => 'Exceção removida.', 'tipo' => 'sucesso'];
    }

    header('Location: calendario.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $mensagem = $_SESSION['flash']['texto'];
    $tipoMensagem = $_SESSION['flash']['tipo'];
    unset($_SESSION['flash']);
}

$excecoes = listarExcecoesFuturas();
$anoAtual = (int) (new DateTime('today'))->format('Y');
$feriados = feriadosNacionais($anoAtual);
$hoje = (new DateTime('today'))->format('Y-m-d');

$tituloPagina = 'Calendário — Protocolo Fast';
require __DIR__ . '/_cabecalho.php';
?>

<?php if ($mensagem): ?>
    <div class="alerta-<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Liberar ou bloquear uma data</h2>
    <p class="texto-auxiliar">
        Domingos, sábados e feriados nacionais já são bloqueados automaticamente.
        Use aqui para liberar um sábado ou bloquear um dia específico.
    </p>

    <form method="post" action="calendario.php">
        <input type="hidden" name="acao" value="salvar">

        <label for="data">Data</label>
        <input type="date" name="data" id="data" required min="<?= $hoje ?>">

        <label for="tipo">O que fazer</label>
        <select name="tipo" id="tipo" required>
            <option value="">Selecione...</option>
            <option value="liberado">Liberar (sábado com cirurgia)</option>
            <option value="bloqueado">Bloquear (feriado, recesso, etc.)</option>
        </select>

        <label for="motivo">Motivo (opcional)</label>
        <input type="text" name="motivo" id="motivo" placeholder="Ex: feriado municipal">

        <button type="submit">Salvar</button>
    </form>
</div>

<div class="card">
    <h2>Datas com exceção (<?= count($excecoes) ?>)</h2>

    <?php if (empty($excecoes)): ?>
        <p class="vazio">Nenhuma exceção cadastrada.</p>
    <?php else: ?>
        <?php foreach ($excecoes as $ex): ?>
            <div class="linha-lista">
                <div class="linha-lista-info">
                    <p class="linha-lista-titulo">
                        <?= ucfirst(diaDaSemanaBr($ex['data'])) ?>, <?= formatarDataBr($ex['data']) ?>
                    </p>
                    <p class="linha-lista-sub">
                        <?= $ex['tipo'] === 'liberado' ? 'Liberado' : 'Bloqueado' ?>
                        <?php if ($ex['motivo']): ?>
                            · <?= htmlspecialchars($ex['motivo']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <form method="post" action="calendario.php" onsubmit="return confirm('Remover essa exceção?');">
                    <input type="hidden" name="acao" value="remover">
                    <input type="hidden" name="excecao_id" value="<?= $ex['id'] ?>">
                    <button type="submit" class="btn-texto btn-perigo">Remover</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Feriados nacionais de <?= $anoAtual ?></h2>
    <p class="texto-auxiliar">Bloqueados automaticamente pelo sistema.</p>

    <?php foreach ($feriados as $dataFeriado => $nomeFeriado): ?>
        <?php if ($dataFeriado >= $hoje): ?>
            <div class="linha-lista">
                <div class="linha-lista-info">
                    <p class="linha-lista-titulo"><?= htmlspecialchars($nomeFeriado) ?></p>
                    <p class="linha-lista-sub"><?= formatarDataBr($dataFeriado) ?></p>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/_rodape.php'; ?>