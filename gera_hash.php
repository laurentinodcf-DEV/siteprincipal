<?php
$senha = "0712quenia2007";

// gera o hash e guarda na variável
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// mostra o hash
echo $senhaHash;
