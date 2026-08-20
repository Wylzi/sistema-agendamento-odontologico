<?php
require_once __DIR__ . '/includes/functions.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    http_response_code(400);
    exit('Token nao informado.');
}

$usuario = buscarUsuarioPorTokenCalendario($token);

if (!$usuario) {
    http_response_code(404);
    exit('Token invalido.');
}

if ($usuario['tipo'] === 'admin') {
    $agenda = listarAgendamentos();
    $nomeCalendario = 'Protocolo Fast — Todas as equipes';
} elseif ($usuario['tipo'] === 'integrante') {
    $agenda = listarAgendamentos(['equipe_id' => (int) $usuario['equipe_id']]);
    $nomeCalendario = 'Protocolo Fast — ' . $usuario['nome'];
} else {
    http_response_code(403);
    exit('Esse perfil nao tem calendario.');
}

$ics = gerarIcsAgenda($agenda, $nomeCalendario);

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="agenda.ics"');
echo $ics;