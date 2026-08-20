<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('atendente');

$clinicaId = (int) $_SESSION['clinica_id'];
$mostrarPassados = isset($_GET['passados']);

$agendamentos = listarAgendamentos([
    'clinica_id'         => $clinicaId,
    'incluir_passados'   => $mostrarPassados,
    'incluir_cancelados' => true,
]);

$clinica = buscarClinica($clinicaId);
$cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];

$mensagem = null;
$tipoMensagem = 'erro';

if (isset($_SESSION['flash'])) {
    $mensagem = $_SESSION['flash']['texto'];
    $tipoMensagem = $_SESSION['flash']['tipo'];
    unset($_SESSION['flash']);
}
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
    <title>Agendamentos — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <nav class="nav">
        <a href="calendario.php">Agendar</a>
        <a href="agendamentos.php" class="ativo">Agendamentos</a>
    </nav>

    <p class="contexto-usuario">
        <?= htmlspecialchars($_SESSION['usuario_nome']) ?> ·
        <?= htmlspecialchars($clinica['nome'] ?? '') ?>
    </p>

    <?php if ($mensagem): ?>
        <div class="alerta-<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>

    <p class="filtro-linha">
        <?php if ($mostrarPassados): ?>
            <a href="agendamentos.php" class="link-voltar">Mostrar só os próximos</a>
        <?php else: ?>
            <a href="agendamentos.php?passados=1" class="link-voltar">Incluir agendamentos passados</a>
        <?php endif; ?>
    </p>

    <?php if (empty($agendamentos)): ?>
        <div class="card">
            <p class="vazio">Nenhum agendamento.</p>
        </div>
    <?php else: ?>
        <?php foreach ($agendamentos as $ag): ?>
            <div class="card <?= $ag['cancelado'] ? 'card-cancelado' : '' ?>">
                <div class="card-topo">
                    <span class="agenda-data">
                        <?= ucfirst(diaSemanaAbreviado($ag['data'])) ?>, <?= formatarDataBr($ag['data']) ?>
                    </span>
                    <?php if ($ag['cancelado']): ?>
                        <span class="tag-cancelado">cancelado</span>
                    <?php else: ?>
                        <span class="agenda-hora">08:00</span>
                    <?php endif; ?>
                </div>

                <p class="paciente-nome"><?= htmlspecialchars($ag['paciente_nome']) ?></p>
                <p class="paciente-detalhe">
                    Ficha <?= htmlspecialchars($ag['ficha_numero']) ?> ·
                    Carga <?= $cargaLabel[$ag['carga']] ?? '' ?>
                </p>
                <p class="paciente-meta">Opera: <?= htmlspecialchars($ag['dentista_operador']) ?></p>

                <?php if ($ag['cancelado'] && $ag['motivo_cancelamento']): ?>
                    <p class="paciente-meta">Motivo: <?= htmlspecialchars($ag['motivo_cancelamento']) ?></p>
                <?php endif; ?>

                <?php if (!$ag['cancelado'] && $ag['data'] >= hojeIso()): ?>
                    <p class="acoes-agendamento">
                        <a href="editar.php?id=<?= $ag['id'] ?>" class="link-acao">Editar</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p><a href="../logout.php" class="link-sair">Sair</a></p>
</body>
</html>