<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$agenda = listarAgendaDentista($_SESSION['dentista_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda</title>
</head>
<body>
    <h1>Olá, <?= htmlspecialchars($_SESSION['dentista_nome']) ?></h1>

    <nav class="nav">
        <a href="dashboard.php" class="ativo">Ver agenda</a>
        <a href="gerenciar.php">Cadastrar horários</a>
    </nav>

    <h2>Próximos atendimentos</h2>

    <?php if (empty($agenda)): ?>
        <p>Nenhum atendimento agendado ainda.</p>
    <?php else: ?>
        <?php foreach ($agenda as $item): ?>
            <div class="agenda-item">
                <div class="agenda-badge">
                    <span class="agenda-badge-dia"><?= htmlspecialchars(diaDoMes($item['data'])) ?></span>
                    <span class="agenda-badge-semana"><?= htmlspecialchars(diaSemanaAbreviado($item['data'])) ?></span>
                </div>
                <div class="agenda-info">
                    <div class="agenda-linha-topo">
                        <span class="agenda-hora"><?= htmlspecialchars(formatarHoraBr($item['hora'])) ?></span>
                        <span class="agenda-ficha">Ficha <strong><?= htmlspecialchars($item['ficha_numero']) ?></strong></span>
                    </div>
                    <p class="agenda-clinica"><?= htmlspecialchars($item['clinica_nome']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <p><a href="logout.php" class="link-sair">Sair</a></p>
</body>
</html>