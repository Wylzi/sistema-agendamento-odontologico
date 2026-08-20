<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('integrante');

$equipeId = (int) $_SESSION['equipe_id'];
$agendamentos = listarAgendamentos($equipeId);

$cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];

$pdo = getConexao();
$stmt = $pdo->prepare('SELECT nome FROM equipes WHERE id = :id');
$stmt->execute(['id' => $equipeId]);
$nomeEquipe = $stmt->fetchColumn() ?: 'Equipe';
$tokenCalendario = obterOuCriarTokenCalendario((int) $_SESSION['usuario_id']);

$dirAtual = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$dirRaiz = dirname($dirAtual);
$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlCalendario = $protocolo . '://' . $_SERVER['HTTP_HOST'] . $dirRaiz . '/calendario.php?token=' . urlencode($tokenCalendario);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Minha agenda — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <div class="cabecalho-equipe">
        <div>
            <p class="cabecalho-titulo">Minha agenda</p>
            <p class="cabecalho-sub"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></p>
        </div>
        <span class="chip-equipe"><?= htmlspecialchars($nomeEquipe) ?></span>
    </div>

    <?php if (empty($agendamentos)): ?>
        <div class="card">
            <p class="vazio">Nenhuma cirurgia agendada.</p>
        </div>
    <?php else: ?>
        <?php foreach ($agendamentos as $ag): ?>
            <div class="card card-equipe">
                <div class="card-topo">
                    <span class="agenda-data"><?= ucfirst(diaSemanaAbreviado($ag['data'])) ?>, <?= dataPorExtenso($ag['data']) ?></span>
                    <span class="agenda-hora">08:00</span>
                </div>
                <p class="paciente-nome"><?= htmlspecialchars($ag['paciente_nome']) ?></p>
                <p class="paciente-detalhe">
                    <?= htmlspecialchars($ag['clinica_nome']) ?> ·
                    Carga <?= $cargaLabel[$ag['carga']] ?? '' ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

        <div class="card">
        <h2>Sincronizar com meu celular</h2>
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

    <p><a href="../logout.php" class="link-sair">Sair</a></p>
</body>
</html>