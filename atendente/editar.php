<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('atendente');

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$clinicaId = (int) $_SESSION['clinica_id'];

$agendamento = buscarAgendamento($id);

// Só pode mexer em agendamento da própria clínica
if (!$agendamento || (int) $agendamento['clinica_id'] !== $clinicaId) {
    header('Location: agendamentos.php');
    exit;
}

if ((int) $agendamento['cancelado'] === 1 || $agendamento['data'] < hojeIso()) {
    $_SESSION['flash'] = ['texto' => 'Esse agendamento não pode mais ser alterado.', 'tipo' => 'erro'];
    header('Location: agendamentos.php');
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrfValido();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cancelar') {
        $resultado = cancelarAgendamento($id, (int) $_SESSION['usuario_id'], $_POST['motivo'] ?? '');

        if ($resultado['sucesso']) {
            $_SESSION['flash'] = ['texto' => 'Agendamento cancelado.', 'tipo' => 'sucesso'];
            header('Location: agendamentos.php');
            exit;
        }

        $erro = $resultado['erro'];

    } elseif ($acao === 'salvar') {
        $resultado = editarAgendamento($id, [
            'data'              => $_POST['data'] ?? '',
            'paciente_nome'     => trim($_POST['paciente_nome'] ?? ''),
            'ficha_numero'      => trim($_POST['ficha_numero'] ?? ''),
            'carga'             => $_POST['carga'] ?? '',
            'dentista_operador' => trim($_POST['dentista_operador'] ?? ''),
            'telefone'          => trim($_POST['telefone'] ?? ''),
        ]);

        if ($resultado['sucesso']) {
            $_SESSION['flash'] = ['texto' => 'Agendamento atualizado.', 'tipo' => 'sucesso'];
            header('Location: agendamentos.php');
            exit;
        }

        $erro = $resultado['erro'];
        $agendamento = buscarAgendamento($id);
    }
}

// Configuração do calendário embutido
$dataDoAgendamento = new DateTime($agendamento['data']);

$calAno = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) $dataDoAgendamento->format('Y');
$calMes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) $dataDoAgendamento->format('n');

if ($calMes < 1 || $calMes > 12) {
    $calMes = (int) $dataDoAgendamento->format('n');
}

$calUrlBase = 'editar.php';
$calModo = 'selecao';
$calDataSelecionada = $agendamento['data'];
$calDataOriginal = $agendamento['data'];
$calIgnorarAgendamento = $id;
$calParametrosExtras = ['id' => $id];
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
    <title>Editar agendamento — Protocolo Fast</title>
</head>
<body>
    <div class="marca">
        <span class="marca-sorria">Sorria</span><span class="marca-goias">Goiás</span>
        <p class="marca-sub">Protocolo Fast</p>
    </div>

    <p><a href="agendamentos.php" class="link-voltar">‹ Voltar</a></p>

    <?php if ($erro): ?>
        <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="editar.php" class="card">
        <h2>Editar agendamento</h2>
        <?= campoCsrf() ?>

        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Data da cirurgia</label>
        <p class="texto-auxiliar">Clique num dia disponível. Horário fixo: 08:00</p>
        <?php require __DIR__ . '/../includes/_calendario.php'; ?>

        <label for="paciente_nome">Paciente</label>
        <input type="text" name="paciente_nome" id="paciente_nome" required
               value="<?= htmlspecialchars($agendamento['paciente_nome']) ?>">

        <label for="ficha_numero">Número da ficha</label>
        <input type="text" name="ficha_numero" id="ficha_numero" required
               value="<?= htmlspecialchars($agendamento['ficha_numero']) ?>">

        <label for="carga">Carga</label>
        <select name="carga" id="carga" required>
            <option value="superior" <?= $agendamento['carga'] === 'superior' ? 'selected' : '' ?>>Superior</option>
            <option value="inferior" <?= $agendamento['carga'] === 'inferior' ? 'selected' : '' ?>>Inferior</option>
            <option value="ambas" <?= $agendamento['carga'] === 'ambas' ? 'selected' : '' ?>>Ambas</option>
        </select>

        <label for="dentista_operador">Dentista que vai operar</label>
        <input type="text" name="dentista_operador" id="dentista_operador" required
               value="<?= htmlspecialchars($agendamento['dentista_operador']) ?>">

        <label for="telefone">Telefone de contato</label>
        <input type="tel" name="telefone" id="telefone"
               value="<?= htmlspecialchars($agendamento['telefone_contato'] ?? '') ?>">

        <button type="submit">Salvar alterações</button>
    </form>

    <div class="card">
        <h2>Cancelar agendamento</h2>
        <p class="texto-auxiliar">O registro fica guardado no histórico, mas a vaga é liberada.</p>

        <form method="post" action="editar.php"
              onsubmit="return confirm('Confirma o cancelamento desse agendamento?');">
            <?= campoCsrf() ?>
            <input type="hidden" name="acao" value="cancelar">
            <input type="hidden" name="id" value="<?= $id ?>">

            <label for="motivo">Motivo (opcional)</label>
            <input type="text" name="motivo" id="motivo" placeholder="Ex: paciente desmarcou">

            <button type="submit" class="btn-cancelar">Cancelar agendamento</button>
        </form>
    </div>

    <script>
        function marcarDia(radio) {
            document.querySelectorAll('.dia-selecionado').forEach(el => el.classList.remove('dia-selecionado'));
            radio.closest('.dia').classList.add('dia-selecionado');
        }
    </script>
</body>
</html>