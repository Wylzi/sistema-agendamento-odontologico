<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('admin');

$mensagem = null;
$tipoMensagem = 'erro';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'trocar_equipe') {
    $resultado = trocarEquipeAgendamento(
        (int) ($_POST['agendamento_id'] ?? 0),
        (int) ($_POST['equipe_id'] ?? 0)
    );

    $_SESSION['flash'] = $resultado['sucesso']
        ? ['texto' => 'Equipe alterada.', 'tipo' => 'sucesso']
        : ['texto' => $resultado['erro'], 'tipo' => 'erro'];

    header('Location: agenda.php');
    exit;
}

if (isset($_SESSION['flash'])) {
    $mensagem = $_SESSION['flash']['texto'];
    $tipoMensagem = $_SESSION['flash']['tipo'];
    unset($_SESSION['flash']);
}

$agendamentos = listarAgendamentos();
$equipes = listarEquipes();
$cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];

// Agrupa por data
$porData = [];
foreach ($agendamentos as $ag) {
    $porData[$ag['data']][] = $ag;
}

$tokenCalendario = obterOuCriarTokenCalendario((int) $_SESSION['usuario_id']);

$dirAtual = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$dirRaiz = dirname($dirAtual);
$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlCalendario = $protocolo . '://' . $_SERVER['HTTP_HOST'] . $dirRaiz . '/calendario.php?token=' . urlencode($tokenCalendario);

$tituloPagina = 'Agenda — Protocolo Fast';
require __DIR__ . '/_cabecalho.php';
?>

<?php if ($mensagem): ?>
    <div class="alerta-<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<?php if (empty($porData)): ?>
    <div class="card">
        <p class="vazio">Nenhuma cirurgia agendada.</p>
    </div>
<?php else: ?>
    <?php foreach ($porData as $data => $lista): ?>
        <div class="grupo-data">
            <p class="grupo-data-titulo"><?= ucfirst(diaDaSemanaBr($data)) ?>, <?= dataPorExtenso($data) ?></p>
            <p class="grupo-data-sub">
                <?= count($lista) ?> <?= count($lista) === 1 ? 'cirurgia' : 'cirurgias' ?> · 08:00
            </p>
        </div>

        <?php foreach ($lista as $ag): ?>
            <div class="card">
                <div class="card-topo">
                    <span class="chip-equipe"><?= htmlspecialchars($ag['equipe_nome']) ?></span>
                    <button type="button" class="btn-texto" onclick="document.getElementById('troca-<?= $ag['id'] ?>').classList.toggle('escondido')">
                        Trocar equipe
                    </button>
                </div>

                <p class="paciente-nome"><?= htmlspecialchars($ag['paciente_nome']) ?></p>
                <p class="paciente-detalhe">
                    Ficha <?= htmlspecialchars($ag['ficha_numero']) ?> ·
                    Carga <?= $cargaLabel[$ag['carga']] ?? '' ?> ·
                    <?= htmlspecialchars($ag['clinica_nome']) ?>
                </p>
                <p class="paciente-meta">Opera: <?= htmlspecialchars($ag['dentista_operador']) ?></p>
                <p class="paciente-meta">
                    Marcou: <?= htmlspecialchars($ag['marcado_por']) ?>
                    <?php if ($ag['telefone_contato']): ?>
                        · <?= htmlspecialchars($ag['telefone_contato']) ?>
                    <?php endif; ?>
                </p>

                <form method="post" action="agenda.php" id="troca-<?= $ag['id'] ?>" class="troca-equipe escondido">
                    <input type="hidden" name="acao" value="trocar_equipe">
                    <input type="hidden" name="agendamento_id" value="<?= $ag['id'] ?>">
                    <select name="equipe_id" required>
                        <?php foreach ($equipes as $eq): ?>
                            <?php if ((int) $eq['id'] !== (int) $ag['equipe_id']): ?>
                                <option value="<?= $eq['id'] ?>"><?= htmlspecialchars($eq['nome']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-dourado btn-pequeno">Mover</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card">
    <h2>Sincronizar agenda geral</h2>
    <p class="texto-auxiliar">Adicione esse link uma vez no Google Agenda ou no Calendário do iPhone — ele atualiza sozinho.</p>
    <input type="text" readonly value="<?= htmlspecialchars($urlCalendario) ?>" onclick="this.select()">
    <button type="button" id="btn-copiar">Copiar link</button>
    <div id="area-copiado"></div>
</div>

<script>
    document.getElementById('btn-copiar').addEventListener('click', function () {
        const input = this.previousElementSibling;
        navigator.clipboard.writeText(input.value).then(() => {
            document.getElementById('area-copiado').innerHTML =
                '<p class="texto-copiado">Link copiado!</p>';
        });
    });
</script>

<?php require __DIR__ . '/_rodape.php'; ?>