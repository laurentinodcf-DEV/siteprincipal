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
                <li class="li-menu-principal"><span class="menu-principal">ADM</span>
                    <ul class="submenu">
                        <li class="submenu-item" id="openLoginModal">Login</li>
                        <li class="submenu-item" id="logoutMenu" style="display:none;">Logout</li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Serviços</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="pages/servicos.php">Serviços Oferecidos</a></li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Produtos</span>
                    <ul class="submenu">
                        <li class="submenu-item">Um</li>
                        <li class="submenu-item">Dois</li>
                        <li class="submenu-item">Três</li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Resultados</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="pages/resultados_videos.php">Vídeos</a></li>
                        <li class="submenu-item">Imagens</li>
                        <li class="submenu-item">Depoimentos</li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Agenda</span>
                    <ul class="submenu">
                        <li class="submenu-item">Um</li>
                        <li class="submenu-item">Dois</li>
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
                <li class="li-menu-principal" id="menuInserir" style="display:none;">
                    <span class="menu-principal">Inserir</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="pages/inserir_video.php">Inserir Vídeo</a></li>
                        <li class="submenu-item"><a href="pages/horarios_funcionamento.php">Horario de Funcionamento</a></li>
                        <li class="submenu-item"><a href="pages/servicos_admin.php">Servi�os</a></li>
                        <li class="submenu-item"><a href="produtos.php">Produtos</a></li>
                    </ul>
                </li>
            </ul>

            <!----- CONTAINER DA LOGOMARCA -------------------------->
            <div id="container-logo">
                <img src="img/logosomente.png" id="logo-menu" class="logo-menu"> <!-- imagem logo -->
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

            <!-- MODAL DO LOCAL, IMAGENS DO LOCAL E ENDEREÇO -->
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
                            <!-- ENDEREÇO LIGADO AO GOOGLE -->
                            <strong>Endereço:</strong>
                            <a href="https://www.google.com/maps/search/?api=1&query=Av.+Professor+Lucas+Machado,+442,+Asteca,+Santa+Luzia+-+MG" target="_blank">
                                Av. Professor Lucas Machado - N° 442 - Asteca, Santa Luzia - MG
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
                        <a href="#servicos" class="btn-sobre">Conheça nossos serviços</a>
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
                                    O serviço foi excepcional e superou minhas expectativas em todos os aspectos.
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
                            
                            <!-- Botão próximo -->
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
                                <img src="img/servicos/sobrancelha02.png" alt="Estética de Sobrancelhas">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Estética de Sobrancelhas</h3>
                                <p>Realce sua beleza com nossos serviços de design e cuidados especiais para sobrancelhas.</p>
                            </div>
                        </article>

                    </div>

                    <!-- Botão próximo -->
                    <button class="carousel-btn next" aria-label="Próximo serviço">&#10095;</button>
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
        <div id="loginModal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Login do Administrador</h2>
                    <span class="close-btn" id="closeLoginModal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <label>Login:</label><br>
                        <input type="text" name="login" required autocomplete="off"><br><br>

                        <label>Senha:</label><br>
                        <input type="password" name="password" required autocomplete="new-password"><br>

                        <label>
                            <input type="checkbox" id="showPassword"> Visualizar senha
                        </label><br><br>


                        <button type="submit">Entrar</button>
                    </form>
                    <p id="loginError" style="color:red; display:none;"></p>
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
                    <button id="cancelLogout">Não</button>
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
