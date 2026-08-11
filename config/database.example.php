<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'agendamento_dentista');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConexao(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    return new PDO($dsn, DB_USER, DB_PASS);
}