<?php
require_once __DIR__ . '/includes/auth.php';
exigirLogin();

switch (tipoUsuario()) {
    case 'admin':
        header('Location: admin/agenda.php');
        break;
    case 'atendente':
        header('Location: atendente/calendario.php');
        break;
    case 'integrante':
        header('Location: equipe/agenda.php');
        break;
    default:
        fazerLogout();
        header('Location: index.php');
}
exit;