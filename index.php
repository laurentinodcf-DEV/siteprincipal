<!DOCTYPE html>
<html>
<head lang="pt-br">

    <meta charset="utf-8"/>
    <title>SALOME BELEZA E ESTÉTICA</title>
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
                <li class="li-menu-principal"><span class="menu-principal">Home</span>
                    <ul class="submenu">
                        <li class="submenu-item">Login</li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Serviços</span>
                    <ul class="submenu">
                        <li class="submenu-item">Um</li>
                        <li class="submenu-item">Dois</li>
                        <li class="submenu-item">Três</li>
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
                        <li class="submenu-item">Videos</li>
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
            </ul>
          </nav>

        <!-- Modal -->
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

        <div id="mapModal" class="mapModal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Local:</h2>
                    <span class="close-btn" id="closeMapModal">&times;</span>
                </div>
                <div class="modal-body">

                    <!-- Carrossel -->
                    <div class="carousel-container">
                        <button class="prev">&#10094;</button>
                        <div class="carousel">
                            <img src="img/frente.jpg" alt="Frente do local">
                            <img src="img/lado.jpg" alt="Vista lateral">
                            <img src="img/interior.jpg" alt="Interior">
                        </div>
                        <button class="next">&#10095;</button>
                    </div>

                    <p>
                        <strong>Endereço:</strong>
                        <a href="https://www.google.com/maps/search/?api=1&query=Av.+Professor+Lucas+Machado,+442,+Asteca,+Santa+Luzia+-+MG" target="_blank">
                            Av. Professor Lucas Machado - N° 442 - Asteca, Santa Luzia - MG
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <script src="js/script.js"></script>


          <!----- IMAGEM DO MENU DE NAVEGAÇÃO -------------------------->
           <img src="img/salao03.jpg" id="banner-menu">

          <!----- CONTAINER DA LOGOMARCA -------------------------->
           <div id="container-logo">
            <img src="img/logosomente.png" id="logo-menu"> <!-- imagem logo -->
           </div>
      
      </div>



  </body>
</html>