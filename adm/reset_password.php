<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/password_reset_utils.php';

$userId = $_SESSION['password_reset_user_id'] ?? null;
$verifiedAt = $_SESSION['password_reset_verified_at'] ?? null;

if (!$userId || !$verifiedAt || (time() - (int)$verifiedAt) > 900) {
    header('Location: ../index.php');
    exit;
}

$userId = (int)$userId;

$stmt = $conn->prepare('SELECT id, username FROM backend_users WHERE id = ? LIMIT 1');
if (!$stmt) {
    $username = 'usuário';
} else {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    $username = $userData['username'] ?? 'usuário';
}

$errors = [];
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (trim($password) === '' || trim($confirmPassword) === '') {
        $errors[] = 'Informe e confirme a nova senha.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'As senhas digitadas não coincidem.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'A nova senha deve ter pelo menos 8 caracteres.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE backend_users SET password = ? WHERE id = ?');

        if (!$updateStmt) {
            $errors[] = 'Não foi possível atualizar a senha. Tente novamente.';
        } else {
            $updateStmt->bind_param('si', $hash, $userId);
            if ($updateStmt->execute()) {
                $successMessage = 'Senha redefinida com sucesso! Você já pode acessar o painel com a nova senha.';
                unset($_SESSION['password_reset_user_id'], $_SESSION['password_reset_verified_at']);
            } else {
                $errors[] = 'Não foi possível atualizar a senha. Tente novamente.';
            }
            $updateStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir senha</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styleProjet.css">
    <style>
        body {
            background: #f4f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Segoe UI", Roboto, sans-serif;
        }

        .reset-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 38px rgba(31, 45, 61, 0.15);
            padding: 40px 48px;
            max-width: 420px;
            width: 100%;
        }

        .reset-card h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1f2d3d;
        }

        .reset-card p.description {
            color: #5f6c7b;
            margin-bottom: 24px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6a5acd, #836fff);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }

        .btn-primary:focus,
        .btn-primary:hover {
            background: linear-gradient(135deg, #5948c7, #6c54ff);
        }

        .extra-links {
            margin-top: 18px;
            text-align: center;
        }

        .extra-links a {
            text-decoration: none;
            color: #6a5acd;
            font-weight: 600;
        }
    </style>
</head>
<body>
<main class="reset-card">
    <h1>Redefinir senha</h1>
    <p class="description">
        <?php echo "Olá, " . htmlspecialchars($username) . "! Informe uma nova senha para finalizar o processo."; ?>
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <div class="extra-links">
            <a href="../index.php">Voltar ao site principal</a>
        </div>
    <?php else: ?>
        <form method="post">
            <div class="mb-3">
                <label for="password" class="form-label">Nova senha</label>
                <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar nova senha</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Atualizar senha</button>
        </form>
        <div class="extra-links">
            <a href="../index.php">Cancelar e voltar ao login</a>
        </div>
    <?php endif; ?>
</main>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
