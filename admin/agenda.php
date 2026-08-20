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

    if ($resultado['sucesso']) {
        $mensagem = 'Equipe alterada.';
        $tipoMensagem = 'sucesso';
    } else {
        $mensagem = $resultado['erro'];
    }
}

$agendamentos = listarAgendamentos();
$equipes = listarEquipes();
$cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];

// Agrupa por data
$porData = [];
foreach ($agendamentos as $ag) {
    $porData[$ag['data']][] = $ag;
}

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

<?php require __DIR__ . '/_rodape.php'; ?>