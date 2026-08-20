<?php
/**
 * @var int    $calAno
 * @var int    $calMes
 * @var string $calUrlBase
 * @var string $calModo
 * @var string $calDataSelecionada
 * @var string $calDataOriginal
 * @var int    $calIgnorarAgendamento
 * @var array  $calParametrosExtras
 */
/**
 * Renderiza uma grade de calendário mensal.
 *
 * Espera as variáveis:
 *   $calAno, $calMes        → mês exibido
 *   $calUrlBase             → página que recebe os links de navegação
 *   $calModo                → 'link' (vai para outra página) ou 'selecao' (marca um campo)
 *   $calDataSelecionada     → data já escolhida (opcional)
 *   $calIgnorarAgendamento  → id de agendamento a desconsiderar na ocupação (opcional)
 *   $calParametrosExtras    → array de parâmetros extras para os links (opcional)
 */

$calHoje = hojeIso();
$calPrimeiroDia = new DateTime("$calAno-$calMes-01");
$calDiasNoMes = (int) $calPrimeiroDia->format('t');
$calDiaSemanaInicial = (int) $calPrimeiroDia->format('w');

$calOcupacao = ocupacaoDoMes($calAno, $calMes);
$calTotalVagas = totalEquipesAtivas();
$calExcecoes = excecoesDoMes($calAno, $calMes);
$calFeriados = feriadosNacionais($calAno);

$calMesAnterior = (clone $calPrimeiroDia)->modify('-1 month');
$calMesSeguinte = (clone $calPrimeiroDia)->modify('+1 month');

$calExtras = '';
foreach (($calParametrosExtras ?? []) as $chave => $valor) {
    $calExtras .= '&' . urlencode($chave) . '=' . urlencode($valor);
}
?>

<div class="calendario-nav">
    <a href="<?= $calUrlBase ?>?ano=<?= $calMesAnterior->format('Y') ?>&mes=<?= $calMesAnterior->format('n') ?><?= $calExtras ?>">‹</a>
    <span><?= ucfirst(nomeDoMes($calMes)) ?> <?= $calAno ?></span>
    <a href="<?= $calUrlBase ?>?ano=<?= $calMesSeguinte->format('Y') ?>&mes=<?= $calMesSeguinte->format('n') ?><?= $calExtras ?>">›</a>
</div>

<div class="calendario-semana">
    <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
</div>

<div class="calendario-grade">
    <?php for ($i = 0; $i < $calDiaSemanaInicial; $i++): ?>
        <div></div>
    <?php endfor; ?>

    <?php for ($dia = 1; $dia <= $calDiasNoMes; $dia++): ?>
        <?php
        $dataIso = sprintf('%04d-%02d-%02d', $calAno, $calMes, $dia);
        $ocupadas = $calOcupacao[$dataIso] ?? 0;

        // Se estamos editando, a própria vaga do agendamento não conta como ocupada
        if (!empty($calIgnorarAgendamento) && $dataIso === ($calDataOriginal ?? '')) {
            $ocupadas = max(0, $ocupadas - 1);
        }

        $livres = $calTotalVagas - $ocupadas;
        $passado = $dataIso < $calHoje;
        $situacao = situacaoDaData($dataIso, $calExcecoes, $calFeriados);
        $selecionado = isset($calDataSelecionada) && $calDataSelecionada === $dataIso;

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
        <?php elseif (($calModo ?? 'link') === 'selecao'): ?>
            <label class="dia dia-livre <?= $selecionado ? 'dia-selecionado' : '' ?>">
                <input type="radio" name="data" value="<?= $dataIso ?>" class="escondido"
                       <?= $selecionado ? 'checked' : '' ?> required
                       onchange="marcarDia(this)">
                <span class="dia-numero"><?= $dia ?></span>
                <span class="dia-status"><?= htmlspecialchars($rotulo) ?></span>
            </label>
        <?php else: ?>
            <a class="dia dia-livre" href="agendar.php?data=<?= $dataIso ?>">
                <span class="dia-numero"><?= $dia ?></span>
                <span class="dia-status"><?= htmlspecialchars($rotulo) ?></span>
            </a>
        <?php endif; ?>
    <?php endfor; ?>
</div>