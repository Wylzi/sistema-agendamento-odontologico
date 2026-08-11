<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

$agenda = listarAgendaDentista($_SESSION['dentista_id']);
$tokenCalendario = obterOuCriarTokenCalendario($_SESSION['dentista_id']);

$dirAtual = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$dirRaiz = dirname($dirAtual);
$protocolo = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlCalendario = $protocolo . '://' . $_SERVER['HTTP_HOST'] . $dirRaiz . '/calendario.php?token=' . urlencode($tokenCalendario);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
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

    <div class="card">
        <h2>Sincronizar com meu calendário</h2>
        <p style="font-size:0.88rem;color:var(--cor-texto-suave);margin-bottom:12px;">
            Adicione esse link uma vez no Google Calendar ou no Calendário do iPhone — ele atualiza sozinho conforme novos agendamentos chegarem.
        </p>
        <input type="text" readonly value="<?= htmlspecialchars($urlCalendario) ?>" onclick="this.select()">
        <button type="button" id="btn-copiar-link">Copiar link</button>
        <div id="area-copiado"></div>
    </div>

    <p><a href="logout.php" class="link-sair">Sair</a></p>

    <script>
        document.getElementById('btn-copiar-link').addEventListener('click', function () {
            const input = this.previousElementSibling;
            const resultado = document.getElementById('area-copiado');

            navigator.clipboard.writeText(input.value).then(() => {
                resultado.innerHTML = '<p style="color:var(--cor-primaria);font-size:0.85rem;">Link copiado!</p>';
            });
        });
    </script>
</body>
</html>