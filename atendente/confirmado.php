<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('atendente');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$pdo = getConexao();
$stmt = $pdo->prepare(
    'SELECT a.data, a.paciente_nome, a.ficha_numero, a.carga,
            a.dentista_operador, a.telefone_contato,
            c.nome AS clinica_nome, u.nome AS marcado_por
     FROM agendamentos a
     JOIN clinicas c ON c.id = a.clinica_id
     JOIN usuarios u ON u.id = a.marcado_por_usuario_id
     WHERE a.id = :id AND a.clinica_id = :clinica_id'
);
$stmt->execute(['id' => $id, 'clinica_id' => (int) $_SESSION['clinica_id']]);
$ag = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ag) {
    header('Location: calendario.php');
    exit;
}

$cargaLabel = ['superior' => 'Superior', 'inferior' => 'Inferior', 'ambas' => 'Ambas'];
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
    <title>Agendamento confirmado — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <div class="card confirmacao-topo">
        <div class="confirmacao-icone">&#10003;</div>
        <h2>Agendamento confirmado</h2>
    </div>

    <div class="card">
        <div class="linha-dado">
            <span class="linha-rotulo">Data</span>
            <span class="linha-valor"><?= ucfirst(diaDaSemanaBr($ag['data'])) ?>, <?= formatarDataBr($ag['data']) ?></span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Horário</span>
            <span class="linha-valor">08:00</span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Clínica</span>
            <span class="linha-valor"><?= htmlspecialchars($ag['clinica_nome']) ?></span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Paciente</span>
            <span class="linha-valor"><?= htmlspecialchars($ag['paciente_nome']) ?></span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Ficha</span>
            <span class="linha-valor"><?= htmlspecialchars($ag['ficha_numero']) ?></span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Carga</span>
            <span class="linha-valor"><?= $cargaLabel[$ag['carga']] ?? '' ?></span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Opera</span>
            <span class="linha-valor"><?= htmlspecialchars($ag['dentista_operador']) ?></span>
        </div>
        <div class="linha-dado">
            <span class="linha-rotulo">Marcado por</span>
            <span class="linha-valor"><?= htmlspecialchars($ag['marcado_por']) ?></span>
        </div>
        <?php if ($ag['telefone_contato']): ?>
            <div class="linha-dado">
                <span class="linha-rotulo">Contato</span>
                <span class="linha-valor"><?= htmlspecialchars($ag['telefone_contato']) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <p><a href="calendario.php" class="link-voltar">‹ Voltar ao calendário</a></p>
</body>
</html>