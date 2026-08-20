<?php
require_once __DIR__ . '/../includes/auth.php';
exigirTipo('admin');

$histClinicaFixa = null;
$histEquipeFixa = null;
$histPodeFiltrar = true;
$histUrlBase = 'historico.php';

$tituloPagina = 'Histórico — Protocolo Fast';
require __DIR__ . '/_cabecalho.php';
require __DIR__ . '/../includes/_historico.php';
require __DIR__ . '/_rodape.php';