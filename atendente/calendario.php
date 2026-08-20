<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('atendente');

$hoje = new DateTime('today');

$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) $hoje->format('Y');
$mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) $hoje->format('n');

if ($mes < 1 || $mes > 12) {
    $mes = (int) $hoje->format('n');
}

$primeiroDia = new DateTime("$ano-$mes-01");
$diasNoMes = (int) $primeiroDia->format('t');
$diaSemanaInicial = (int) $primeiroDia->format('w');

$ocupacao = ocupacaoDoMes($ano, $mes);
$totalVagas = totalEquipesAtivas();
$excecoes = excecoesDoMes($ano, $mes);
$feriados = feriadosNacionais($ano);

$mesAnterior = (clone $primeiroDia)->modify('-1 month');
$mesSeguinte = (clone $primeiroDia)->modify('+1 month');

$clinica = buscarClinica((int) $_SESSION['clinica_id']);
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
    <title>Agendar — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <p class="contexto-usuario">
        <?= htmlspecialchars($_SESSION['usuario_nome']) ?> ·
        <?= htmlspecialchars($clinica['nome'] ?? 'Sem clínica') ?>
    </p>

    <div class="calendario-nav">
        <a href="?ano=<?= $mesAnterior->format('Y') ?>&mes=<?= $mesAnterior->format('n') ?>">‹</a>
        <span><?= ucfirst(nomeDoMes($mes)) ?> <?= $ano ?></span>
        <a href="?ano=<?= $mesSeguinte->format('Y') ?>&mes=<?= $mesSeguinte->format('n') ?>">›</a>
    </div>

    <div class="calendario-semana">
        <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
    </div>

    <div class="calendario-grade">
        <?php for ($i = 0; $i < $diaSemanaInicial; $i++): ?>
            <div></div>
        <?php endfor; ?>

        <?php for ($dia = 1; $dia <= $diasNoMes; $dia++): ?>
            <?php
            $dataIso = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $ocupadas = $ocupacao[$dataIso] ?? 0;
            $livres = $totalVagas - $ocupadas;
            $passado = $dataIso < $hoje->format('Y-m-d');
            $situacao = situacaoDaData($dataIso, $excecoes, $feriados);

            if ($passado) {
                $indisponivel = true;
                $rotulo = '—';
            } elseif (!$situacao['disponivel']) {
                $indisponivel = true;
                $rotulo = $situacao['motivo'];
            } elseif ($livres <= 0) {
                $indisponivel = true;
                $rotulo = 'cheio';
            } else {
                $indisponivel = false;
                $rotulo = $livres . ($livres === 1 ? ' vaga' : ' vagas');
            }
            ?>

            <?php if ($indisponivel): ?>
                <div class="dia dia-indisponivel">
                    <span class="dia-numero"><?= $dia ?></span>
                    <span class="dia-status"><?= htmlspecialchars($rotulo) ?></span>
                </div>
            <?php else: ?>
                <a class="dia dia-livre" href="agendar.php?data=<?= $dataIso ?>">
                    <span class="dia-numero"><?= $dia ?></span>
                    <span class="dia-status"><?= htmlspecialchars($rotulo) ?></span>
                </a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <p><a href="../logout.php" class="link-sair">Sair</a></p>
</body>
</html>