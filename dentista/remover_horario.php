<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

header('Content-Type: application/json');

$horarioId = isset($_POST['horario_id']) ? (int) $_POST['horario_id'] : 0;
$dentistaId = $_SESSION['dentista_id'];

$resultado = removerHorario($dentistaId, $horarioId);
echo json_encode($resultado);