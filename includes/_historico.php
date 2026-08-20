<?php
/**
 * Tela de histórico compartilhada pelos três perfis.
 *
 * Espera:
 *   $histClinicaFixa → id da clínica quando o perfil é limitado a ela (ou null)
 *   $histEquipeFixa  → id da equipe quando o perfil é limitado a ela (ou null)
 *   $histPodeFiltrar → true quando o usuário pode escolher clínica/equipe (admin)
 *   $histUrlBase     → arquivo que recebe o formulário
 *
 * @var int|null $histClinicaFixa
 * @var int|null $histEquipeFixa
 * @var bool     $histPodeFiltrar
 * @var string   $histUrlBase
 */

$busca       = trim($_GET['busca'] ?? '');
$dataInicio  = $_GET['data_inicio'] ?? '';
$dataFim     = $_GET['data_fim'] ?? '';
$situacao    = $_GET['situacao'] ?? 'todos';

$clinicaFiltro = $histClinicaFixa ?? (!empty($_GET['clinica_id']) ? (int) $_GET['clinica_id'] : null);
$equipeFiltro  = $histEquipeFixa ?? (!empty($_GET['equipe_id']) ? (int) $_GET['equipe_id'] : null);

// Só busca se algum filtro foi informado
$filtrouAlgo = $busca !== ''
    || $dataInicio !== ''
    || $dataFim !== ''
    || $situacao !== 'todos'
    || (!empty($histPodeFiltrar) && ($clinicaFiltro || $equipeFiltro));

$resultados = $filtrouAlgo
    ? buscarHistorico([
        'busca'       => $busca,
        'data_inicio' => $dataInicio,
        'data_fim'    => $dataFim,
        'clinica_id'  => $clinicaFiltro,
        'equipe_id'   => $equipeFiltro,
        'situacao'    => $situacao,
    ])
    : [];

$cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];
?>

<div class="card">
    <h2>Buscar</h2>

    <form method="get" action="<?= htmlspecialchars($histUrlBase) ?>">
        <label for="busca">Paciente ou ficha</label>
        <input type="text" name="busca" id="busca" value="<?= htmlspecialchars($busca) ?>"
               placeholder="Nome do paciente ou número da ficha">

        <div class="filtro-datas">
            <div>
                <label for="data_inicio">De</label>
                <input type="date" name="data_inicio" id="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>">
            </div>
            <div>
                <label for="data_fim">Até</label>
                <input type="date" name="data_fim" id="data_fim" value="<?= htmlspecialchars($dataFim) ?>">
            </div>
        </div>

        <?php if (!empty($histPodeFiltrar)): ?>
            <label for="clinica_id">Clínica</label>
            <select name="clinica_id" id="clinica_id">
                <option value="">Todas</option>
                <?php foreach (listarClinicas() as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $clinicaFiltro === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="equipe_id">Equipe</label>
            <select name="equipe_id" id="equipe_id">
                <option value="">Todas</option>
                <?php foreach (listarEquipes() as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $equipeFiltro === (int) $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label for="situacao">Situação</label>
        <select name="situacao" id="situacao">
            <option value="todos" <?= $situacao === 'todos' ? 'selected' : '' ?>>Todas</option>
            <option value="ativos" <?= $situacao === 'ativos' ? 'selected' : '' ?>>Confirmadas</option>
            <option value="cancelados" <?= $situacao === 'cancelados' ? 'selected' : '' ?>>Canceladas</option>
        </select>

        <button type="submit">Buscar</button>
    </form>
</div>

<?php if (!$filtrouAlgo): ?>
    <div class="card">
        <p class="vazio">Use os filtros acima para consultar o histórico.</p>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Resultados (<?= count($resultados) ?>)</h2>

        <?php if (empty($resultados)): ?>
            <p class="vazio">Nenhum agendamento encontrado.</p>
        <?php else: ?>
            <?php foreach ($resultados as $ag): ?>
                <div class="historico-item <?= $ag['cancelado'] ? 'card-cancelado' : '' ?>">
                    <div class="card-topo">
                        <span class="agenda-data">
                            <?= ucfirst(diaSemanaAbreviado($ag['data'])) ?>, <?= formatarDataBr($ag['data']) ?>
                        </span>
                        <?php if ($ag['cancelado']): ?>
                            <span class="tag-cancelado">cancelado</span>
                        <?php else: ?>
                            <span class="chip-equipe"><?= htmlspecialchars($ag['equipe_nome']) ?></span>
                        <?php endif; ?>
                    </div>

                    <p class="paciente-nome"><?= htmlspecialchars($ag['paciente_nome']) ?></p>
                    <p class="paciente-detalhe">
                        Ficha <?= htmlspecialchars($ag['ficha_numero']) ?> ·
                        Carga <?= $cargaLabel[$ag['carga']] ?? '' ?> ·
                        <?= htmlspecialchars($ag['clinica_nome']) ?>
                    </p>
                    <p class="paciente-meta">Opera: <?= htmlspecialchars($ag['dentista_operador']) ?></p>
                    <p class="paciente-meta">Marcou: <?= htmlspecialchars($ag['marcado_por']) ?></p>

                    <?php if ($ag['cancelado'] && $ag['motivo_cancelamento']): ?>
                        <p class="paciente-meta">Motivo: <?= htmlspecialchars($ag['motivo_cancelamento']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>