<?php
session_start();
include("../conexao.php"); // conexão com o banco

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $senha = $_POST['password'];

    // consulta na tabela backend_users
    $stmt = $conn->prepare("SELECT * FROM backend_users WHERE login = ? LIMIT 1");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // verifica a senha com hash
        if (password_verify($senha, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['username'];

            // redireciona para a página principal ou painel
            header("Location: dashboard.php");
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login do Administrador</title>
</head>
<body>
    <h2>Login do Administrador</h2>

    <?php if (!empty($erro)) echo "<p style='color:red;'>$erro</p>"; ?>

    <form method="post" action="">
        <label>Login:</label><br>
        <input type="text" name="login" required autocomplete="off"><br><br>

        <label>Senha:</label><br>
        <input type="password" name="password" required required autocomplete="new-password"><br><br>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>

