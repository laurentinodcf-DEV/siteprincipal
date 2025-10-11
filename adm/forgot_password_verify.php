<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/password_reset_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    format_reset_response(false, 'Método não suportado.');
    exit;
}

$identifier = isset($_POST['identifier']) ? trim((string)$_POST['identifier']) : '';
$code = isset($_POST['code']) ? trim((string)$_POST['code']) : '';

if ($identifier === '' || $code === '') {
    format_reset_response(false, 'Informe o e-mail/usuário e o código enviado.');
    exit;
}

if (!preg_match('/^\d{6}$/', $code)) {
    format_reset_response(false, 'O código deve conter 6 dígitos numéricos.');
    exit;
}

$user = fetch_user_by_identifier($conn, $identifier);

if ($user === null) {
    format_reset_response(false, 'Código inválido ou expirado.');
    exit;
}

ensure_password_reset_table_exists($conn);

$stmt = $conn->prepare('SELECT id, token_hash, expires_at FROM password_reset_tokens WHERE user_id = ? AND used = 0 ORDER BY created_at DESC LIMIT 1');

if (!$stmt) {
    format_reset_response(false, 'Erro interno ao validar o código.');
    exit;
}

$stmt->bind_param('i', $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$tokenRow = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$tokenRow) {
    format_reset_response(false, 'Código inválido ou expirado.');
    exit;
}

$expiresAt = DateTime::createFromFormat('Y-m-d H:i:s', $tokenRow['expires_at']);

if ($expiresAt === false || $expiresAt < new DateTime()) {
    $markExpired = $conn->prepare('UPDATE password_reset_tokens SET used = 1, used_at = NOW() WHERE id = ?');
    if ($markExpired) {
        $markExpired->bind_param('i', $tokenRow['id']);
        $markExpired->execute();
        $markExpired->close();
    }
    format_reset_response(false, 'Código inválido ou expirado.');
    exit;
}

if (!password_verify($code, $tokenRow['token_hash'])) {
    format_reset_response(false, 'Código inválido ou expirado.');
    exit;
}

$updateStmt = $conn->prepare('UPDATE password_reset_tokens SET used = 1, used_at = NOW() WHERE id = ?');
if ($updateStmt) {
    $updateStmt->bind_param('i', $tokenRow['id']);
    $updateStmt->execute();
    $updateStmt->close();
}

$_SESSION['password_reset_user_id'] = (int)$user['id'];
$_SESSION['password_reset_verified_at'] = time();
unset($_SESSION['password_reset_pending_user_id'], $_SESSION['password_reset_pending_until']);

format_reset_response(true, 'Código validado com sucesso. Redirecionando...', [
    'redirect' => 'adm/reset_password.php',
]);
