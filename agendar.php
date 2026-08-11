<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$horarioId   = isset($_POST['horario_id']) ? (int) $_POST['horario_id'] : 0;
$fichaNumero = trim($_POST['ficha_numero'] ?? '');

if (!$horarioId || $fichaNumero === '') {
    echo json_encode(['sucesso' => false, 'erro' => 'Selecione um horário e informe a ficha.']);
    exit;
}

$resultado = criarAgendamento($horarioId, $fichaNumero);
echo json_encode($resultado);