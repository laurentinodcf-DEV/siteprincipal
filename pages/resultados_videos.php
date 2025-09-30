<?php
include '../conexao.php';
$videos = $conn->query("SELECT * FROM videos ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resultados - Vídeos</title>
</head>
<body>
<h2>Vídeos</h2>
<?php foreach($videos as $v): ?>
    <div style="margin-bottom:20px;">
        <h4><?= htmlspecialchars($v['titulo']) ?></h4>
        <p><?= nl2br(htmlspecialchars($v['descricao'])) ?></p>
        <?php if($v['tipo']=='link'): ?>
            <iframe width="560" height="315" src="<?= htmlspecialchars($v['link']) ?>" frameborder="0" allowfullscreen></iframe>
        <?php else: ?>
            <video width="560" height="315" controls>
                <source src="uploads/videos/<?= htmlspecialchars($v['arquivo']) ?>" type="video/mp4">
                Seu navegador não suporta video.
            </video>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</body>
</html>
