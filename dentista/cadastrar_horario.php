<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

header('Content-Type: application/json');

$clinicaId = isset($_POST['clinica_id']) ? (int) $_POST['clinica_id'] : 0;
$data      = trim($_POST['data'] ?? '');
$hora      = trim($_POST['hora'] ?? '');
$vagas     = isset($_POST['vagas']) ? (int) $_POST['vagas'] : 0;
$dentistaId = $_SESSION['dentista_id'];

$resultado = cadastrarHorario($dentistaId, $clinicaId, $data, $hora, $vagas);
echo json_encode($resultado);