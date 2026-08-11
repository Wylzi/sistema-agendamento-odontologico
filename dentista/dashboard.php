<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$agenda = listarAgendaDentista($_SESSION['dentista_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda</title>
</head>
<body>
    <h1>Olá, <?= htmlspecialchars($_SESSION['dentista_nome']) ?></h1>

    <nav>
        <a href="dashboard.php">Ver agenda</a> |
        <a href="gerenciar.php">Cadastrar horários</a>
    </nav>

    <h2>Próximos atendimentos</h2>

    <?php if (empty($agenda)): ?>
        <p>Nenhum atendimento agendado ainda.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>Dia</th>
                <th>Data</th>
                <th>Horário</th>
                <th>Clínica</th>
                <th>Ficha</th>
            </tr>
            <?php foreach ($agenda as $item): ?>
                <tr>
                    <td><?= htmlspecialchars(diaDaSemanaBr($item['data'])) ?></td>
                    <td><?= htmlspecialchars(formatarDataBr($item['data'])) ?></td>
                    <td><?= htmlspecialchars(formatarHoraBr($item['hora'])) ?></td>
                    <td><?= htmlspecialchars($item['clinica_nome']) ?></td>
                    <td><?= htmlspecialchars($item['ficha_numero']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <p><a href="logout.php">Sair</a></p>
</body>
</html>