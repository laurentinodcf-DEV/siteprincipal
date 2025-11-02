<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Módulos do sistema de agenda
$modules = [
    'agenda_overview' => [
        'label' => 'Visão Geral',
        'description' => 'Dashboard principal com resumo dos agendamentos.',
        'content' => 'agenda_overview',
    ],
    'novo_agendamento' => [
        'label' => 'Novo Agendamento',
        'description' => 'Criar um novo agendamento para cliente.',
        'content' => 'novo_agendamento',
    ],
    'agendamentos_hoje' => [
        'label' => 'Agendamentos Hoje',
        'description' => 'Visualizar todos os agendamentos do dia atual.',
        'content' => 'agendamentos_hoje',
    ],
    'calendario' => [
        'label' => 'Calendário',
        'description' => 'Visualização em calendário de todos os agendamentos.',
        'content' => 'calendario',
    ],
    'clientes_agenda' => [
        'label' => 'Clientes',
        'description' => 'Buscar e gerenciar clientes para agendamentos.',
        'content' => 'clientes_agenda',
    ],
    'horarios_disponiveis' => [
        'label' => 'Horários Disponíveis',
        'description' => 'Configurar disponibilidade de horários por profissional.',
        'content' => 'horarios_disponiveis',
    ],
    'relatorios' => [
        'label' => 'Relatórios',
        'description' => 'Relatórios e estatísticas de agendamentos.',
        'content' => 'relatorios',
    ],
];

// Navegação do sistema de agenda
$navigation = [
    [
        'title' => 'Dashboard',
        'items' => ['agenda_overview'],
    ],
    [
        'title' => 'Agendamentos',
        'items' => ['novo_agendamento', 'agendamentos_hoje', 'calendario'],
    ],
    [
        'title' => 'Gestão',
        'items' => ['clientes_agenda', 'horarios_disponiveis'],
    ],
    [
        'title' => 'Relatórios',
        'items' => ['relatorios'],
    ],
];

$module = $_GET['module'] ?? 'agenda_overview';
if (!array_key_exists($module, $modules)) {
    $module = 'agenda_overview';
}

$moduleData = $modules[$module];
$usuario = $_SESSION['usuario_nome'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Sistema de Agenda - Studio Salomé</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/styleProjet.css">
    <link rel="stylesheet" href="css/painel.css">
    <style>
        /* Customizações específicas do sistema de agenda */
        .sidebar-header span {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 12px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            margin-right: 16px;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            color: white !important;
        }
        
        .content-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 18px;
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .content-header h1 {
            color: white;
            margin-bottom: 8px;
        }
        
        .content-header p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0;
        }
        
        .btn-voltar {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-voltar:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .agenda-content {
            background: white;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(203, 213, 225, 0.9);
        }
        
        .status-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #28a745;
        }
        
        .status-card h3 {
            color: #28a745;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .status-card p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <span>SA</span>
                <div>
                    <div>Sistema de Agenda</div>
                    <small>Studio Salomé</small>
                </div>
            </div>

            <div class="sidebar-user">
                <strong><?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?></strong>
                <span>Sistema de Agendamentos</span>
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
                <a class="btn-site" href="dashboard.php">Painel Admin</a>
                <a class="btn-logout" href="logout.php">Sair</a>
            </div>
        </aside>

        <main class="content-area">
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1><?php echo htmlspecialchars($moduleData['label'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p><?php echo htmlspecialchars($moduleData['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <a href="dashboard.php" class="btn-voltar">← Voltar ao Painel</a>
                </div>
            </div>
            
            <div class="agenda-content">
                <?php
                // Incluir o conteúdo específico baseado no módulo selecionado
                switch ($moduleData['content']) {
                    case 'agenda_overview':
                        include 'agenda/overview.php';
                        break;
                    case 'novo_agendamento':
                        include 'agenda/novo_agendamento.php';
                        break;
                    case 'agendamentos_hoje':
                        include 'agenda/agendamentos_hoje.php';
                        break;
                    case 'calendario':
                        include 'agenda/calendario.php';
                        break;
                    case 'clientes_agenda':
                        include 'agenda/clientes.php';
                        break;
                    case 'horarios_disponiveis':
                        include 'agenda/horarios_disponiveis.php';
                        break;
                    case 'relatorios':
                        include 'agenda/relatorios.php';
                        break;
                    default:
                        echo '<div class="alert alert-warning">Módulo não encontrado.</div>';
                        break;
                }
                ?>
            </div>
        </main>
    </div>
    
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>