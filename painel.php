<?php
session_start();
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Bem-vindo, <?php echo $_SESSION["usuario_nome"]; ?></h2>
    <a href="logout.php" class="btn btn-danger">Sair</a>

    <hr>
    <h3>Gerenciar Conteúdos</h3>
    <ul>
        <li><a href="gerenciar_videos.php">Vídeos</a></li>
        <li><a href="gerenciar_imagens.php">Imagens</a></li>
        <li><a href="gerenciar_produtos.php">Produtos</a></li>
    </ul>
</body>
</html>
