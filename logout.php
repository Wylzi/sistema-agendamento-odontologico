<?php
require_once __DIR__ . '/includes/auth.php';

fazerLogout();
header('Location: index.php');
exit;