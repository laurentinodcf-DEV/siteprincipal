<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$modules = [
    'overview' => [
        'label' => 'Painel geral',
        'description' => 'Visão rápida das principais ações administrativas.',
        'src' => 'modules/overview.php',
    ],
    'videos' => [
        'label' => 'Inserir vídeos',
        'description' => 'Cadastre novos vídeos para os destaques do site.',
        'src' => '../pages/inserir_video.php',
    ],
    'servicos' => [
        'label' => 'Serviços',
        'description' => 'Gerencie o catálogo de serviços oferecidos.',
        'src' => '../pages/servicos_admin.php',
    ],
    'produtos' => [
        'label' => 'Produtos',
        'description' => 'Organize os produtos e materiais disponibilizados.',
        'src' => 'modules/produtos.php',
    ],
    'horarios' => [
        'label' => 'Horário de funcionamento',
        'description' => 'Configure meses, dias e períodos de atendimento.',
        'src' => '../pages/horarios_funcionamento.php',
    ],
];

$navigation = [
    [
        'title' => 'Principal',
        'items' => ['overview'],
    ],
    [
        'title' => 'Inserir',
        'items' => ['videos', 'servicos', 'produtos'],
    ],
    [
        'title' => 'Operação',
        'items' => ['horarios'],
    ],
];

$module = $_GET['module'] ?? 'overview';
if (!array_key_exists($module, $modules)) {
    $module = 'overview';
}

$moduleData = $modules[$module];
$usuario = $_SESSION['usuario_nome'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Painel administrativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/painel.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <span>QS</span>
                <div>
                    <div>Painel administrativo</div>
                    <small>Studio Salomé</small>
                </div>
            </div>

            <div class="sidebar-user">
                <strong><?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>Acesso administrativo do site</span>
            </div>

            <?php foreach ($navigation as $section): ?>
                <div class="sidebar-section"><?php echo htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php foreach ($section['items'] as $itemKey):
                    $item = $modules[$itemKey];
                    $isActive = $itemKey === $module;
                ?>
                    <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>" href="?module=<?php echo urlencode($itemKey); ?>">
                        <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="sidebar-footer">
                <a class="btn-site" href="../index.php">Ver site</a>
                <a class="btn-logout" href="logout.php">Sair</a>
            </div>
        </aside>

        <main class="content-area">
            <div class="content-header">
                <h1><?php echo htmlspecialchars($moduleData['label'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars($moduleData['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="content-frame">
                <iframe src="<?php echo htmlspecialchars($moduleData['src'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($moduleData['label'], ENT_QUOTES, 'UTF-8'); ?>"></iframe>
            </div>
        </main>
    </div>
</body>
</html>
