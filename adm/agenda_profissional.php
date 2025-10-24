<?php
session_start();
require_once '../conexao.php';

// Garantir que a hora siga o fuso do sistema/servidor (fallback Brasil)
$tz = ini_get('date.timezone');
if (!$tz || !@date_default_timezone_set($tz)) {
    date_default_timezone_set('America/Sao_Paulo');
}

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar se um módulo foi especificado
$modulo = isset($_GET['modulo']) ? $_GET['modulo'] : 'dashboard';

// Lista de módulos válidos
$modulos_validos = [
    'dashboard',
    'agendamento_inteligente',
    'gestao_clientes',
    'controle_profissionais',
    'integracao_whatsapp',
    'pagamentos_financeiro',
    'relatorios_estatisticas',
    'configuracoes'
];

// Verificar se o módulo é válido
if (!in_array($modulo, $modulos_validos)) {
    $modulo = 'dashboard';
}

// Mapear módulos para títulos
$titulos_modulos = [
    'dashboard' => 'Painel de Controle',
    'agendamento_inteligente' => 'Sistema de Agendamento Inteligente',
    'gestao_clientes' => 'Gestão de Clientes',
    'controle_profissionais' => 'Controle de Profissionais',
    'integracao_whatsapp' => 'Integração WhatsApp',
    'pagamentos_financeiro' => 'Pagamentos e Financeiro',
    'relatorios_estatisticas' => 'Relatórios e Estatísticas',
    'configuracoes' => 'Configurações'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agenda Profissional - Salão</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
        }

        body {
            background: linear-gradient(135deg, #1a472a 0%, #2d7a3d 50%, #28a745 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .page-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            width: 320px;
            flex-shrink: 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-right: 3px solid rgba(40, 167, 69, 0.2);
        }
        
        .main-content {
            flex: 1;
            padding: 25px;
        }
        
        .logo-section {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 25px 20px;
            margin-bottom: 20px;
            border-radius: 0 0 20px 20px;
            text-align: center;
        }
        
        .logo-section h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.4rem;
        }
        
        .logo-section small {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .nav-section {
            padding: 0 15px;
            margin-bottom: 20px;
        }
        
        .nav-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 10px;
            margin-left: 15px;
            letter-spacing: 0.5px;
        }
        
        .nav-link {
            color: #495057;
            padding: 15px 20px;
            margin: 3px 5px;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
        }
        
        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            color: #155724;
            transform: translateX(8px);
            border-color: rgba(40, 167, 69, 0.2);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            transform: translateX(8px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .nav-link.return-btn {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            font-weight: 600;
        }
        
        .nav-link.return-btn:hover {
            background: linear-gradient(135deg, #495057, #343a40);
            transform: translateX(8px);
        }
        
        .nav-link.logout-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .nav-link.logout-btn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateX(8px);
        }
        
        .content-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 5px solid #28a745;
        }
        
        .content-header h2 {
            margin: 0;
            color: #2c3e50;
            font-weight: 700;
        }
        
        .content-header .breadcrumb {
            margin: 0;
            background: none;
            padding: 0;
            font-size: 0.9rem;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .btn-toggle-sidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 10px;
            padding: 10px 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 992px) {
            .page-layout {
                flex-direction: column;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.25s ease;
                width: 280px;
                z-index: 1000;
                overflow-y: auto; /* permitir scroll no menu mobile */
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .logo-section {
                width: 280px;
                position: relative; /* em mobile não fica fixo para permitir scroll */
            }
            
            .nav-container {
                padding-top: 0; /* remover padding em mobile já que logo não é fixa */
            }
            
            .main-content {
                padding: 80px 15px 25px 15px;
            }
            
            .btn-toggle-sidebar {
                display: block;
            }
        }
        
        @media (min-width: 769px) {
            .btn-toggle-sidebar {
                display: none;
            }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
    </style>
</head>
<body>
    <!-- Botão para toggle do sidebar em mobile -->
    <button class="btn btn-toggle-sidebar" id="toggleSidebar">
        <i class="bi bi-list fs-5"></i>
    </button>

    <div class="page-layout">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="logo-section">
            <div class="d-flex align-items-center justify-content-center mb-2">
                <i class="bi bi-calendar-heart fs-1 me-3"></i>
                <div>
                    <h3>Agenda Pro</h3>
                    <small>Sistema Profissional</small>
                </div>
            </div>
        </div>
        
        <nav class="nav flex-column">
            <!-- Seção Principal -->
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a class="nav-link <?php echo $modulo == 'dashboard' ? 'active' : ''; ?>" href="?modulo=dashboard">
                    <i class="bi bi-speedometer2 me-3"></i> Painel de Controle
                </a>
            </div>
            
            <!-- Seção Agendamentos -->
            <div class="nav-section">
                <div class="nav-section-title">Agendamentos</div>
                <a class="nav-link <?php echo $modulo == 'agendamento_inteligente' ? 'active' : ''; ?>" href="?modulo=agendamento_inteligente">
                    <i class="bi bi-calendar-check me-3"></i> Sistema Inteligente
                </a>
            </div>
            
            <!-- Seção Gestão -->
            <div class="nav-section">
                <div class="nav-section-title">Gestão</div>
                <a class="nav-link <?php echo $modulo == 'gestao_clientes' ? 'active' : ''; ?>" href="?modulo=gestao_clientes">
                    <i class="bi bi-people me-3"></i> Gestão de Clientes
                </a>
                <a class="nav-link <?php echo $modulo == 'controle_profissionais' ? 'active' : ''; ?>" href="?modulo=controle_profissionais">
                    <i class="bi bi-person-workspace me-3"></i> Controle de Profissionais
                </a>
            </div>
            
            <!-- Seção Automação -->
            <div class="nav-section">
                <div class="nav-section-title">Automação</div>
                <a class="nav-link <?php echo $modulo == 'integracao_whatsapp' ? 'active' : ''; ?>" href="?modulo=integracao_whatsapp">
                    <i class="bi bi-whatsapp me-3"></i> Integração WhatsApp
                </a>
            </div>
            
            <!-- Seção Financeiro -->
            <div class="nav-section">
                <div class="nav-section-title">Financeiro</div>
                <a class="nav-link <?php echo $modulo == 'pagamentos_financeiro' ? 'active' : ''; ?>" href="?modulo=pagamentos_financeiro">
                    <i class="bi bi-credit-card me-3"></i> Pagamentos & Financeiro
                </a>
            </div>
            
            <!-- Seção Relatórios -->
            <div class="nav-section">
                <div class="nav-section-title">Análises</div>
                <a class="nav-link <?php echo $modulo == 'relatorios_estatisticas' ? 'active' : ''; ?>" href="?modulo=relatorios_estatisticas">
                    <i class="bi bi-graph-up me-3"></i> Relatórios & Estatísticas
                </a>
            </div>
            
            <!-- Seção Configurações -->
            <div class="nav-section">
                <div class="nav-section-title">Sistema</div>
                <a class="nav-link <?php echo $modulo == 'configuracoes' ? 'active' : ''; ?>" href="?modulo=configuracoes">
                    <i class="bi bi-gear me-3"></i> Configurações
                </a>
            </div>
            
            <hr class="mx-3 my-4">
            
            <!-- Seção Navegação -->
            <div class="nav-section">
                <a class="nav-link return-btn" href="dashboard.php">
                    <i class="bi bi-arrow-left me-3"></i> Voltar ao Painel Admin
                </a>
                <a class="nav-link logout-btn" href="logout.php">
                    <i class="bi bi-box-arrow-right me-3"></i> Sair do Sistema
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><?php echo $titulos_modulos[$modulo]; ?></h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="?modulo=dashboard">Agenda Pro</a></li>
                            <li class="breadcrumb-item active"><?php echo $titulos_modulos[$modulo]; ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    <span id="clockLocal" aria-label="Relógio local">--/--/---- --:--</span>
                </div>
            </div>
        </div>
        
        <div class="content-area">
            <?php
            $arquivo_modulo = "agenda_pro/" . $modulo . ".php";
            if (file_exists($arquivo_modulo)) {
                include $arquivo_modulo;
            } else {
                echo "<div class='alert alert-warning'>
                        <i class='bi bi-exclamation-triangle'></i> 
                        Módulo '$modulo' em desenvolvimento. Em breve estará disponível!
                      </div>";
            }
            ?>
        </div>
    </div>
    </div>
    
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
        
        // Fechar sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggleBtn.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Efeito de carregamento suave
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Relógio local do sistema (cliente)
            const clockEl = document.getElementById('clockLocal');
            function updateClock() {
                try {
                    const now = new Date();
                    const fmt = new Intl.DateTimeFormat('pt-BR', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                    clockEl.textContent = fmt.format(now);
                } catch (e) {
                    // Fallback
                    const pad = n => String(n).padStart(2, '0');
                    const d = new Date();
                    clockEl.textContent = `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
                }
            }
            updateClock();
            setInterval(updateClock, 30000); // atualiza a cada 30s
        });
    </script>
</body>
</html>