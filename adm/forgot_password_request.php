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

if ($identifier === '') {
    format_reset_response(false, 'Informe seu usuário ou e-mail para continuar.');
    exit;
}

$user = fetch_user_by_identifier($conn, $identifier);

if ($user === null) {
    // Mensagem genérica para evitar revelar quais logins existem.
    format_reset_response(true, 'Se os dados estiverem corretos, enviaremos o código em instantes.');
    exit;
}

$email = $user['email'] ?? null;

if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    format_reset_response(false, 'Não foi possível localizar um e-mail válido para este usuário. Entre em contato com o administrador.');
    exit;
}

ensure_password_reset_table_exists($conn);

$deleteStmt = $conn->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
if ($deleteStmt) {
    $deleteStmt->bind_param('i', $user['id']);
    $deleteStmt->execute();
    $deleteStmt->close();
}

$code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$codeHash = password_hash($code, PASSWORD_DEFAULT);
$expiresAt = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');

$insertStmt = $conn->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)');

if (!$insertStmt) {
    format_reset_response(false, 'Erro interno ao registrar a solicitação. Tente novamente mais tarde.');
    exit;
}

$insertStmt->bind_param('iss', $user['id'], $codeHash, $expiresAt);
$insertStmt->execute();
$insertStmt->close();

$_SESSION['password_reset_pending_user_id'] = $user['id'];
$_SESSION['password_reset_pending_until'] = strtotime($expiresAt);

$subject = 'Código de recuperação de senha';
$message = "Olá {$user['username']}!\r\n\r\n";
$message .= "Recebemos uma solicitação para redefinir a senha do painel administrativo.\r\n";
$message .= "Use o código a seguir para continuar com a redefinição:\r\n\r\n";
$message .= "{$code}\r\n\r\n";
$message .= "Este código é válido por apenas 5 minutos.\r\n";
$message .= "Se você não solicitou a mudança, ignore esta mensagem.\r\n\r\n";
$message .= "Atenciosamente,\r\n";
$message .= "Equipe Salomé Beleza e Estética";

$headers = "From: noreply@salomebeleza.local\r\n";
$headers .= "Reply-To: noreply@salomebeleza.local\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mailSent = mail($email, $subject, $message, $headers);

if (!$mailSent) {
    // Guardamos o código em sessão para facilitar testes locais.
    $_SESSION['password_reset_last_code'] = $code;
    format_reset_response(false, 'Não foi possível enviar o e-mail. Verifique a configuração do servidor de e-mail ou utilize o código informado ao administrador.', [
        'codePreview' => $code,
    ]);
    exit;
}

format_reset_response(true, 'Enviamos um código de verificação para o seu e-mail. Verifique sua caixa de entrada e digite-o abaixo.', [
    'expiresAt' => $expiresAt,
]);
