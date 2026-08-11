<?php
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    http_response_code(400);
    exit('Token não informado.');
}

$dentista = buscarDentistaPorTokenCalendario($token);

if (!$dentista) {
    http_response_code(404);
    exit('Token inválido.');
}

$agenda = listarAgendaDentista($dentista['id']);
$ics = gerarIcsAgenda($agenda, $dentista['nome']);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="agenda.ics"');
echo $ics;