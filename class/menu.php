<?php
// class/menu.php
?>

<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/estilo.css">


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
                        <li class="submenu-item"><a href="../pages/servicos.php">Serviços Oferecidos</a></li>
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
                <li class="li-menu-principal" id="home">
                    <span class="menu-principal"><a href="../index.php">Home</a></span>
                </li>
            </ul>

            <!----- CONTAINER DA LOGOMARCA -------------------------->
            <div id="container-logo">
                <img src="../img/logosomente.png" id="logo-menu" class="logo-menu"> <!-- imagem logo -->
            </div>

          </nav>


      <!-- JS do Sistema -->
      <script src="js/script.js"></script>
        
      <!-- JS do Bootstrap -->
      <script src="bootstrap/js/bootstrap.bundle.min.js"></script>