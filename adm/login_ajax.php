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
        // Permitir redirecionamento opcional e seguro
        $redirect = $_POST['redirect'] ?? 'adm/dashboard.php';
        // Sanitização simples: apenas caminhos relativos dentro de 'adm/' e sem esquema/host
        $redirect = trim($redirect);
        if (
            strpos($redirect, '://') !== false ||
            str_starts_with($redirect, '//') ||
            !preg_match('#^adm\/[a-zA-Z0-9_\-/\.]+$#', $redirect)
        ) {
            $redirect = 'adm/dashboard.php';
        }

        echo json_encode([
            'success'  => true,
            'redirect' => $redirect
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
