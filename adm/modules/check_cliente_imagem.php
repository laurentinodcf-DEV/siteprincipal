<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

require '../../conexao.php';

$cliente_id = (int) ($_GET['cliente_id'] ?? 0);

if ($cliente_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do cliente inválido']);
    exit;
}

$stmt = $conn->prepare('SELECT imagem FROM salao_clientes WHERE id = ?');
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao preparar consulta']);
    exit;
}

$stmt->bind_param('i', $cliente_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao executar consulta']);
    exit;
}

$stmt->bind_result($imagem);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Cliente não encontrado']);
    exit;
}

$stmt->close();

$tem_imagem = !empty($imagem);

header('Content-Type: application/json');
echo json_encode(['tem_imagem' => $tem_imagem]);
?>
