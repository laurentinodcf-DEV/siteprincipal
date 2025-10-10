<?php
// class/menu.php
?>

<link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="../css/estilo.css">

<nav class="nav-menu-principal">
    <ul class="ul-menu-principal">
        <li class="li-menu-principal"><span class="menu-principal">Serviços</span>
            <ul class="submenu">
                <li class="submenu-item"><a href="../pages/servicos.php">Serviços oferecidos</a></li>
            </ul>
        </li>
        <li class="li-menu-principal"><span class="menu-principal">Produtos</span>
            <ul class="submenu">
                <li class="submenu-item"><a href="pages/produtos.php">Meus Produtos</a></li>
            </ul>
        </li>
        <li class="li-menu-principal"><span class="menu-principal">Resultados</span>
            <ul class="submenu">
                <li class="submenu-item"><a href="../pages/resultados_videos.php">Vídeos</a></li>
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
        <li class="li-menu-principal" id="home">
            <a href="../index.php" class="menu-principal home-link" aria-label="Página inicial">
                <img src="../img/icons/botao_home.png" alt="Página inicial" class="admin-menu-icon">
            </a>
        </li>
    </ul>

    <div id="container-logo">
        <img src="../img/logosomente.png" id="logo-menu" class="logo-menu" alt="Logomarca">
    </div>
</nav>

<script src="../js/script.js"></script>
<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
