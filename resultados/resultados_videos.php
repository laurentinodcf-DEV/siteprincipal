<?php
include "conexao.php";
$sql = "SELECT titulo, url, descricao FROM videos ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vídeos</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2>Vídeos</h2>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="mb-4">
            <h4><?php echo $row["titulo"]; ?></h4>
            <p><?php echo $row["descricao"]; ?></p>
            <iframe width="560" height="315" src="<?php echo $row["url"]; ?>" frameborder="0" allowfullscreen></iframe>
        </div>
    <?php endwhile; ?>
</body>
</html>
