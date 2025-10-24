<?php
// Definição de timezone para o PHP (seguir sistema quando possível)
$tzIni = ini_get('date.timezone');
if (!$tzIni || !@date_default_timezone_set($tzIni)) {
    // Fallback Brasil
    date_default_timezone_set('America/Sao_Paulo');
}

$host = "localhost";     // geralmente "localhost" mesmo
$user = "root";
$pass = "";
$db   = "salaoqs";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Harmonizar fuso horário da sessão MySQL com o atual do PHP
// Ex.: "-03:00" ou "+00:00"
$offset = date('P');
@$conn->query("SET time_zone = '" . $conn->real_escape_string($offset) . "'");
?>
