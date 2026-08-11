<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$clinicaId = isset($_GET['clinica_id']) ? (int) $_GET['clinica_id'] : 0;

if (!$clinicaId) {
    echo json_encode([]);
    exit;
}

$horarios = listarHorariosDisponiveis($clinicaId);
echo json_encode($horarios);