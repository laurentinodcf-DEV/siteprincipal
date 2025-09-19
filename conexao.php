<?php
$host = "localhost";     // geralmente "localhost" mesmo
$user = "root";
$pass = "";
$db   = "salaoqs";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
