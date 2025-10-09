<?php
session_start();
include("../conexao.php");

header('Content-Type: application/json');

$login = $_POST['login'] ?? '';
$senha = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM backend_users WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 1){
    $user = $result->fetch_assoc();
    if(password_verify($senha, $user['password'])){
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['username'];
        echo json_encode([
            'success'  => true,
            'redirect' => 'adm/dashboard.php'
        ]);
        exit;
    } else {
        echo json_encode(['success'=>false,'message'=>'Senha incorreta!']);
        exit;
    }
} else {
    echo json_encode(['success'=>false,'message'=>'Usuário não encontrado!']);
    exit;
}
