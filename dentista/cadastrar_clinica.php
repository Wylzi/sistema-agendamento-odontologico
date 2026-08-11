<?php
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

header('Content-Type: application/json');

$nome     = trim($_POST['nome'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');

$resultado = cadastrarClinica($nome, $endereco);
echo json_encode($resultado);