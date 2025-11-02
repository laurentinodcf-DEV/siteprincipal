<?php
session_start();
$adminLogado = isset($_SESSION['usuario_id']);

// Verificar se há depoimentos ativos para mostrar a seção
$mostrarDepoimentos = false;
$depoimentosAtivos = [];
require 'conexao.php';

$resultado = $conn->query(
    'SELECT d.id, d.estrelas, d.titulo, d.descricao, d.imagem_reserva, c.nome as cliente_nome, c.imagem as cliente_imagem
     FROM salao_depoimentos d
     INNER JOIN salao_clientes c ON d.id_cliente = c.id
     WHERE d.ativo = 1 AND d.ordem IS NOT NULL
     ORDER BY d.ordem ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $depoimentosAtivos[] = $linha;
    }
    $resultado->free();
    $mostrarDepoimentos = count($depoimentosAtivos) > 0;
}
?>

<!DOCTYPE html>
<html>
<head lang="pt-br">

    <meta charset="utf-8"/>
    <title>SALOME BELEZA E ESTÉTICA</title>
   
    <!-- CSS do Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styleProjet.css">

    <link rel="stylesheet" type="text/css" href="css/estilo.css"> 

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="img/qs_logo.png"> 


</head>
  <body>

    <!----- CONTAINER GERAL DA PAGINA -------------------------->
      <div id="container-page">
              <!----- MENU DE NAVEGAÇÃO -------------------------->
          <nav class="nav-menu-principal">
            <ul class="ul-menu-principal">
                <li class="li-menu-principal"><span class="menu-principal">Serviços</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="pages/servicos.php">Serviços oferecidos</a></li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Produtos</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="pages/produtos.php">Meus Produtos</a></li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Resultados</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="pages/videos.php">Vídeos</a></li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Sobre</span>
                    <ul class="submenu">
                        <li class="submenu-item" id="openModal">Contato</li>
                        <li class="submenu-item" id="openMapModal">Endereço</li>
                        <li class="submenu-item">Profissional</li>
                        <li class="submenu-item">Fotos</li>
                    </ul>
                </li>
            </ul>

            <!----- CONTAINER DA LOGOMARCA -------------------------->
            <div class="admin-icon-container">
                <button
                    type="button"
                    id="adminAccessTrigger"
                    class="menu-principal admin-menu-trigger"
                    aria-label="Área administrativa"
                >
                    <img src="img/icons/person_login.png" alt="Área administrativa" class="admin-menu-icon">
                </button>
            </div>
            <div id="container-logo">
                <img src="img/logosomente.png" id="logo-menu" class="logo-menu" alt="Logomarca">
            </div>

          </nav>

            <!-- MODAL DE CONTATOS -->
            <div id="modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Contatos:</h2>
                        <span class="close-btn" id="closeContactModal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <p><strong>WhatsApp:</strong> 
                            <img src="img/whats-logo.png" class="whats-logo" alt="WhatsApp">
                            <span class="title-contact">(31) 99189-2974</span>
                        </p>
                        <p><strong>Instagram:</strong> 
                            <img src="img/instagram_logo.png" class="instagram-logo" alt="Instagram">
                            <span class="title-contact">
                            <a href="https://www.instagram.com/_queniasalome?igsh=cTZmNjVjaTVnM25i" style="text-decoration: none;"  target="_blank" rel="noopener noreferrer">
                            @_queniasalome
                            </a>
                            </span>
                        </p>
                        <p><strong>Email:</strong> 
                            <img src="img/email-logo.png" class="email-logo" alt="Email">
                            <span class="title-contact">exemplo@email.com</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- MODAL DO LOCAL, IMAGENS DO LOCAL E Endereço -->
            <div id="mapModal" class="mapModal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Local:</h2>
                        <span class="close-btn" id="closeMapModal">&times;</span>
                    </div>
                    <div class="modal-body">

                        <!-- CARROSSEL DE IMAGENS DO LOCAL -->
                        <div class="carousel-container">
                            <button class="prev">&#10094;</button>
                            <div class="carousel">
                                <img src="img/salao/entrada.jpg" alt="Entrada do local">
                                <img src="img/salao/frente.jpg" alt="Frente do local">
                                <img src="img/salao/interior01.jpg" alt="Interior">
                                <img src="img/salao/interior02.jpg" alt="Interior">
                            </div>
                            <button class="next">&#10095;</button>
                        </div>

                        <p>
                            <!-- Endereço LIGADO AO GOOGLE -->
                            <strong>Endereço:</strong>
                            <a href="https://www.google.com/maps/search/?api=1&query=Av.+Professor+Lucas+Machado,+442,+Asteca,+Santa+Luzia+-+MG" target="_blank">
                                Av. Professor Lucas Machado - Nº 442 - Asteca, Santa Luzia - MG
                            </a>
                        </p>
                    </div>
                </div>
            </div>


            <div id="primeirafoto" class="primeirafoto">
                 <!-- HERO / BANNER top -->
                <section id="secao-01" class="secao-01">
                    <video class="hero-video" autoplay muted loop playsinline>
                        <source src="videos/lavagem.mp4" type="video/mp4">
                    </video>
                    <div class="hero-overlay"></div>

                    <div class="hero-content">
                        <h1>Transforme sua beleza com confian&ccedil;a</h1>
                        <p>Servi&ccedil;os de alisamento e cuidados capilares de alta qualidade.</p>
                        <a href="#sobre-01" class="btn-sobre-01">Saiba mais</a>
                    </div>

                    <div class="hero-highlights">
                        <div class="highlight-card">
                            <h3>Cuidados Capilares</h3>
                            <p>Tratamentos personalizados para todos os tipos de cabelo.</p>
                        </div>
                        <div class="highlight-card">
                            <h3>Cuidados de Pele</h3>
                            <p>Produtos profissionais para resultados duradouros.</p>
                        </div>
                        <div class="highlight-card">
                            <h3>Linha de Produtos</h3>
                            <p>Consultoria especializada em cuidados capilares.</p>
                        </div>
                    </div>
                </section>

                <!-- SEÇÃO 02 - Serviços -->
                <section id="secao-02" class="secao-02">
                <div class="container-sobre">
                    <!-- Coluna Imagem + Depoimento -->
                    <div class="sobre-imagem">
                         <img src="img/salao/interior02.jpg" alt="Studio Salomé">
                    <div class="sobre-depoimento">
                        <p class="depoimento-texto">"Transformou meu visual!"</p>
                        <span class="depoimento-autor">Ana Clara</span>
                    </div>
                    </div>          

                    <!-- Coluna Texto -->
                    <div class="sobre-texto">
                        <h2>Sobre o Studio</h2>
                        <p>
                            Oferecemos tratamentos exclusivos para cuidar da sua beleza com excelência, combinando o melhor da estética moderna e produtos de alta qualidade. Nossa equipe especializada está pronta para proporcionar experiências únicas em cada atendimento.
                        </p>
                        <a href="#servicos" class="btn-sobre">Conheça nossos Serviços</a>
                    </div>
                </div>
                </section>


                <!-- SEÇÃO 03 - Sobre o Studio -->
                <?php if ($mostrarDepoimentos): ?>
                <section id="secao-03" class="secao-03">
                    <div class="container-depoimentos">
                        <h2 class="titulo-depoimentos">O que nossos clientes dizem</h2>

                        <div class="depoimentos-carousel-container">
                            <!-- Botão anterior -->
                            <?php if (count($depoimentosAtivos) > 2): ?>
                            <button class="depoimentos-btn prev" aria-label="Depoimento anterior">&#10094;</button>
                            <?php endif; ?>
                            
                            <!-- Carrossel de depoimentos -->
                            <div class="depoimentos-carousel">
                                <?php foreach ($depoimentosAtivos as $depoimento): 
                                    // Determinar qual imagem usar (priorizar imagem do cliente)
                                    $imagemSrc = '';
                                    if (!empty($depoimento['cliente_imagem'])) {
                                        $p = str_replace('\\', '/', trim($depoimento['cliente_imagem']));
                                        if ($p !== '') {
                                            if (strpos($p, 'img/') === 0) {
                                                $imagemSrc = $p;
                                            } else {
                                                $imagemSrc = 'img/clientes/imgcadastro/' . ltrim($p, '/');
                                            }
                                        }
                                    } elseif (!empty($depoimento['imagem_reserva'])) {
                                        $p = str_replace('\\', '/', trim($depoimento['imagem_reserva']));
                                        if ($p !== '') {
                                            if (strpos($p, 'img/') === 0) {
                                                $imagemSrc = $p;
                                            } else {
                                                $imagemSrc = 'img/depoimentos/' . ltrim($p, '/');
                                            }
                                        }
                                    }
                                    
                                    // Gerar estrelas baseado na avaliação
                                    $estrelas = (int) $depoimento['estrelas'];
                                    $estrelasHtml = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $estrelas) {
                                            $estrelasHtml .= '★';
                                        } else {
                                            $estrelasHtml .= '☆';
                                        }
                                    }
                                ?>
                                <div class="card-depoimento">
                                    <div class="avaliacao"><?= $estrelasHtml; ?></div>
                                    <h3 class="titulo-depoimento"><?= htmlspecialchars($depoimento['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="texto-depoimento">
                                        <?= htmlspecialchars($depoimento['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <div class="autor">
                                        <?php if ($imagemSrc !== ''): ?>
                                            <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($depoimento['cliente_nome'], ENT_QUOTES, 'UTF-8'); ?>" class="foto-autor">
                                        <?php else: ?>
                                            <div class="foto-autor-placeholder">👤</div>
                                        <?php endif; ?>
                                        <p class="nome-autor"><?= htmlspecialchars($depoimento['cliente_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            
                            <?php if (count($depoimentosAtivos) > 2): ?>

                            
                            <!-- Botão Próximo -->

                            
                            <button class="depoimentos-btn next" aria-label="Próximo depoimento">&#10095;</button>

                            
                            <?php endif; ?>
                        </div>
                        
                        <?php if (count($depoimentosAtivos) > 2): ?>
                        <!-- Indicadores de slide -->
                        <div class="depoimentos-indicadores">
                            <?php for ($i = 0; $i < count($depoimentosAtivos); $i++): ?>
                                <span class="indicador <?= $i === 0 ? 'active' : ''; ?>" data-slide="<?= $i; ?>"></span>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- SEÇÃO 04 - Nossos Serviços -->
                <section id="secao-04" class="secao-04">
                <div class="container-servicos-04">

                    <!-- Título e descrição -->
                    <div class="servicos-header">
                    <h2>Nossos Serviços</h2>
                    <p>Oferecemos tratamentos de beleza para cabelos e sobrancelhas com qualidade excepcional.</p>
                    </div>

                    <!-- Wrapper do carrossel -->
                    <div class="carousel-wrapper">

                    <!-- Botão anterior -->
                    <button class="carousel-btn prev" aria-label="Serviço anterior">&#10094;</button>

                    <!-- Carrossel rolável -->
                    <div class="servicos-carousel">
                        <article class="card-servico-04">
                            <div class="card-servico-04-imagem">
                                <img src="img/servicos/alisamento02.png" alt="Alisamento de Cabelos">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Alisamento de Cabelos</h3>
                                <p>Transforme seus cabelos com nossos tratamentos de alisamento de alta qualidade e durabilidade.</p>
                            </div>
                        </article>

                        <article class="card-servico-04">
                            <div class="card-servico-04-imagem">
                                <img src="img/servicos/escova.png" alt="Escova capilar">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Escova Capilar</h3>
                                <p>Nossos especialistas garantem sobrancelhas perfeitamente moldadas e bem cuidadas.</p>
                            </div>
                        </article>

                        <article class="card-servico-04">
                            <div class="card-servico-04-imagem">
                                <img src="img/servicos/tratamentos.png" alt="Spa Capilar">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Spa Capilar</h3>
                                <p>Relaxe e cuide da saúde dos seus fios com nossos tratamentos capilares premium.</p>
                            </div>
                        </article>

                        <article class="card-servico-04">
                            <div class="card-servico-04-imagem">
                                <img src="img/servicos/produtos.png" alt="Produtos Exclusivos">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Produtos Exclusivos</h3>
                                <p>Utilizamos apenas produtos de alta performance para garantir resultados impecáveis e duradouros.</p>
                            </div>
                        </article>

                        <article class="card-servico-04">
                            <div class="card-servico-04-imagem">
                                <img src="img/servicos/sobrancelha02.png" alt="estética de Sobrancelhas">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Estética de Sobrancelhas</h3>
                                <p>Realce sua beleza com nossos Serviços de design e cuidados especiais para sobrancelhas.</p>
                            </div>
                        </article>

                    </div>

                    <!-- Botão Próximo -->
                    <button class="carousel-btn next" aria-label="Próximo Serviço">&#10095;</button>
                    </div>
                    <br>
                    <div class="sobre-texto-sesao-01">
                        <a href="#sobre-01" class="btn-sobre-01">Saiba mais</a>
                    </div>
                </div>
                </section>


                <div class="avatar-destaque">
                    <img src="img/avatar/avatar-demonstrando.png" alt="Cliente destaque" class="avatar-destaque-img">
                </div>

                <!-- Foto da Profissional -->
                <section id="secao-foto" class="secao-foto">
                    <img src="img/profissional/quenia01.png" alt="Quênia Salomé" class="foto-quenia">
                </section>

                <?php include 'class/contatoFooter.php'; ?>
            </div>  

      </div>

        <!-- Modal de Login -->
        <div id="loginModal" class="modal login-modal" style="display:none;">
            <div class="modal-content login-modal-content">
                <button type="button" class="close-btn login-modal-close" id="closeLoginModal" aria-label="Fechar modal">&times;</button>
                <div class="login-modal-card">
                    <div class="login-welcome-panel">
                        <h2>Painel administrativo!</h2>
                        <p>Login com credenciais administrativas.</p>
                    </div>
                    <div class="login-form-panel">
                        <header class="login-form-header">
                            <h3>Login</h3>
                            <p>Acesse sua conta do painel administrativo.</p>
                        </header>

                        <p id="loginError" class="login-error-message" style="display:none;"></p>

                        <form id="loginForm">
                            <div class="login-input-group">
                                <label for="loginUsername">Usuário / Email</label>
                                <div class="login-input-wrapper">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <input type="text" id="loginUsername" name="login" required autocomplete="username">
                                </div>
                            </div>

                            <div class="login-input-group">
                                <label for="loginPassword">Senha</label>
                                <div class="login-input-wrapper">
                                    <span class="login-input-icon" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
                                    <button type="button" class="toggle-password" aria-label="Mostrar senha">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 12C2.8 7 7 4 12 4C17 4 21.2 7 23 12C21.2 17 17 20 12 20C7 20 2.8 17 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="login-form-options">
                                <a href="#" class="login-forgot-link">Esqueceu a senha?</a>
                            </div>

                            <button type="submit" class="login-submit-button">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <!-- Modal de Confirmação Logout -->
        <div id="forgotPasswordModal" class="modal-recupera" style="display:none;">
            <div class="modal-content reset-modal-content reset-split-modal">
                <div class="reset-layout">
                    <div class="reset-info-panel">
                        <div class="reset-info-inner">
                            <h2>Recuperação de senha</h2>
                            <p>
                                Informe o seu usuário ou</br> e-mail cadastrado para enviarmos um código de verificação.
                            </p>
                        </div>
                    </div>
                    <div class="reset-form-panel">
                        <button type="button" class="reset-close-button" id="closeForgotPasswordModal" aria-label="Fechar modal">
                            &times;
                        </button>
                        <form id="forgotPasswordForm" class="reset-form" autocomplete="off">
                            <div class="reset-input-group">
                                <label for="resetIdentifier" class="reset-form-label">Usuário / Email</label>
                                <div class="reset-input-wrapper">
                                    <span class="reset-input-icon" aria-hidden="true">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <input
                                        type="text"
                                        id="resetIdentifier"
                                        name="identifier"
                                        class="reset-input"
                                        required
                                        autocomplete="username"
                                        placeholder="Digite seu usuário ou e-mail"
                                    >
                                </div>
                            </div>
                            <div class="reset-feedback" id="forgotPasswordFeedback" role="alert" style="display:none;"></div>
                            <div class="reset-actions">
                                <button type="submit" class="btn-reset-primary">Enviar código</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

                <div id="verifyCodeModal" class="modal" style="display:none;">
            <div class="modal-content-numero reset-modal-content-numero reset-split-modal">
                <div class="reset-layout">
                    <div class="reset-info-panel">
                        <div class="reset-info-inner">
                            <h2>Digite o código enviado</h2>
                            <p id="codeTimer" class="reset-timer"></p>
                        </div>
                    </div>
                    <div class="reset-form-panel">
                        <button type="button" class="reset-close-button" id="closeVerifyCodeModal" aria-label="Fechar modal">
                            &times;
                        </button>
                        <form id="verifyCodeForm" class="reset-form reset-code-form" autocomplete="off">
                            <div class="reset-code-grid" aria-hidden="true">
                                <input type="text" class="code-digit-preview" readonly>
                                <input type="text" class="code-digit-preview" readonly>
                                <input type="text" class="code-digit-preview" readonly>
                                <input type="text" class="code-digit-preview" readonly>
                                <input type="text" class="code-digit-preview" readonly>
                                <input type="text" class="code-digit-preview" readonly>
                            </div>
                            <label class="visually-hidden" for="resetCode">Código de 6 dígitos</label>
                            <input
                                type="text"
                                id="resetCode"
                                name="code"
                                class="hidden-code-input"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                maxlength="6"
                                required
                                autocomplete="one-time-code"
                                aria-label="Código de 6 dígitos"
                            >
                            <div class="reset-feedback" id="verifyCodeFeedback" role="alert" style="display:none;"></div>
                            <div class="reset-actions">
                                <button type="submit" class="btn-reset-primary btn-pill">OK</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="logoutModal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Confirmar Logout</h2>
                    <span class="close-btn" id="closeLogoutModal">&times;</span>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja sair?</p>
                    <button id="confirmLogout">Sim</button>
                    <button id="cancelLogout">não</button>
                </div>
            </div>
        </div>



    <script>
        const adminLogado = <?= $adminLogado ? 'true' : 'false' ?>;
    </script>

      <!-- JS do Sistema -->
      <script src="js/script.js"></script>
        
      <!-- JS do Bootstrap -->
      <script src="bootstrap/js/bootstrap.bundle.min.js"></script>


  </body>
</html>


