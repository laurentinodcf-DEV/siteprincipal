<?php
session_start();
$adminLogado = isset($_SESSION['usuario_id']);
?>

<!DOCTYPE html>
<html>
<head lang="pt-br">

    <meta charset="utf-8"/>
    <title>SALOME BELEZA E ESTÉTICA</title>
   
    <!-- CSS do Bootstrap -->
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">

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
                        <li class="submenu-item"><a href="pages/resultados_videos.php">Vídeos</a></li>
                        <li class="submenu-item">Imagens</li>
                        <li class="submenu-item">Depoimentos</li>
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
                <section id="secao-03" class="secao-03">
                    <div class="container-depoimentos">
                        <h2 class="titulo-depoimentos">O que nossos clientes dizem</h2>

                        <div class="depoimentos-carousel-container">
                            <!-- Botão anterior -->
                            <button class="depoimentos-btn prev" aria-label="Depoimento anterior">&#10094;</button>
                            
                            <!-- Carrossel de depoimentos -->
                            <div class="depoimentos-carousel">
                                <!-- Depoimento 1 -->
                                <div class="card-depoimento">
                                    <div class="avaliacao">★★★★★</div>
                                    <h3 class="titulo-depoimento">Transformou meu cabelo!</h3>
                                    <p class="texto-depoimento">
                                    O Serviço foi excepcional e superou minhas expectativas em todos os aspectos.
                                    </p>
                                    <div class="autor">
                                    <img src="img/clientes/ana.jpg" alt="Ana L." class="foto-autor">
                                    <p class="nome-autor">Ana L.</p>
                                    </div>
                                </div>

                                <!-- Depoimento 2 -->
                                <div class="card-depoimento">
                                    <div class="avaliacao">★★★★★</div>
                                    <h3 class="titulo-depoimento">Estou admirada!</h3>
                                    <p class="texto-depoimento">
                                    A experiência foi incrível, definitivamente voltarei e recomendarei a todos!
                                    </p>
                                    <div class="autor">
                                    <img src="img/clientes/carla.jpg" alt="Carla M." class="foto-autor">
                                    <p class="nome-autor">Carla M.</p>
                                    </div>
                                </div>
                                
                                <!-- Depoimento 3 -->
                                <div class="card-depoimento">
                                    <div class="avaliacao">★★★★★</div>
                                    <h3 class="titulo-depoimento">Impressionada!</h3>
                                    <p class="texto-depoimento">
                                    Impressionada com meu novo brilho e liso, voltarei e recomendarei a todos!
                                    </p>
                                    <div class="autor">
                                    <img src="img/clientes/renata.jpg" alt="Renata L." class="foto-autor">
                                    <p class="nome-autor">Renata L.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botão Próximo -->
                            <button class="depoimentos-btn next" aria-label="Próximo depoimento">&#10095;</button>
                        </div>
                        
                        <!-- Indicadores de slide -->
                        <div class="depoimentos-indicadores">
                            <span class="indicador active" data-slide="0"></span>
                            <span class="indicador" data-slide="1"></span>
                        </div>
                    </div>
                </section>

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
                                <h3>estética de Sobrancelhas</h3>
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
                    <img src="img/avatar/07.png" alt="Cliente destaque" class="avatar-destaque-img">
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



