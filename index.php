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
                    <!-- Direita com texto -->
                    <div id="secao-01" class="secao-01">
                        <div class="sobre-texto-sesao-01">
                            <h2>Transforme sua beleza com estilo</h2>
                            <p class="p-belaza-estilo">
                                “No Studio Salomé, cada detalhe é pensado para valorizar a sua beleza única.
                                Oferecemos técnicas modernas, ambiente sofisticado e atendimento personalizado
                                para que você viva uma experiência inesquecível de cuidado e transformação.”
                                                        </p>
                            <a href="#sobre-01" class="btn-sobre-01">Saiba mais</a>
                        </div>
                    </div>
                </section>

                <!-- SEÇÃO 02 - Serviços -->
                <section id="secao-02" class="secao-02">
                <div class="container-servicos">
                    <div class="sobre-texto">
                        <h2>Sobre os Serviços</h2>
                        <p class="p-servicos">
                            Oferecemos tratamentos completos de beleza e estética, 
                            com técnicas modernas e personalizadas para valorizar sua beleza natural.
                        </p>
                        <a href="#sobre" class="btn-sobre">Saiba mais</a>
                    </div>
                    <div class="card-servico">
                    <img src="img/servicos/alisamento01.png" alt="Alisamento">
                    <p>Alisamento de cabelos com técnica especializada</p>
                    </div>
                    <div class="card-servico">
                    <img src="img/servicos/sobrancelha.png" alt="Sobrancelhas">
                    <p>Cuidados personalizados para sobrancelhas</p>
                    </div>
                    <div class="card-servico">
                    <img src="img/servicos/cuidado.png" alt="Tratamentos estéticos">
                    <p>Tratamentos estéticos de alta qualidade</p>
                    </div>
                </div>
                </section>

                <!-- SEÇÃO 03 - Sobre o Studio -->
                <section id="secao-03" class="secao-03">
                <div class="container-sobre">
                    <!-- Coluna Imagem + Depoimento -->
                    <div class="sobre-imagem">
                         <img src="img/salao/interior02.jpg" alt="Studio Salomé">
                    <div class="sobre-depoimento">
                        <p class="depoimento-texto">“Transformou meu visual!”</p>
                        <span class="depoimento-autor">Ana Clara</span>
                    </div>
                    </div>          
                    <!-- Coluna Texto -->
                    <div class="sobre-texto">
                        <h2>Sobre o Studio Salomé</h2>
                            <p>
                                No Studio Salomé, oferecemos serviços de beleza e estética
                                especializados em alisamento e cuidados para cabelos e sobrancelhas,
                                com um toque de sofisticação.
                            </p>
                        <a href="#sobre" class="btn-sobre">Saiba mais</a>
                    </div>
                </div>
                </section>



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