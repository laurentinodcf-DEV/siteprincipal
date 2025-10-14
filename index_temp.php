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
     WHERE d.ativo = 1
     ORDER BY d.data_criacao DESC'
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
                        <li class="submenu-item">Imagens</li>
                        <li class="submenu-item">Depoimentos</li>
                    </ul>
                </li>
                <li class="li-menu-principal"><span class="menu-principal">Sobre</span>
                    <ul class="submenu">
                        <li class="submenu-item" id="openModal">Contato</li>
                        <li class="submenu-item"><a href="pages/horarios_funcionamento.php">Horários</a></li>
                    </ul>
                </li>
                <?php if ($adminLogado): ?>
                <li class="li-menu-principal"><span class="menu-principal">Admin</span>
                    <ul class="submenu">
                        <li class="submenu-item"><a href="adm/dashboard.php">Painel Admin</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

                <!-- SEÇÃO 01 - Banner Principal -->
                <section id="secao-01" class="secao-01">
                    <div class="banner-principal">
                        <div class="banner-conteudo">
                            <h1>SALOMÉ BELEZA E ESTÉTICA</h1>
                            <p>Transforme sua beleza com nossos tratamentos exclusivos</p>
                            <a href="#servicos" class="btn-banner">Conheça nossos serviços</a>
                        </div>
                    </div>
                </section>

                <!-- SEÇÃO 02 - Sobre o Studio -->
                <section id="secao-02" class="secao-02">
                <div class="container-sobre-02">
                    <!-- Coluna Imagem -->
                    <div class="sobre-imagem">
                        <img src="img/banner01.png" alt="Studio Salomé">
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


                <!-- SEÇÃO 03 - Depoimentos -->
                <?php if ($mostrarDepoimentos): ?>
                <section id="secao-03" class="secao-03">
                    <div class="container-depoimentos">
                        <h2 class="titulo-depoimentos">O que nossos clientes dizem</h2>

                        <div class="depoimentos-carousel-container">
                            <!-- Botão anterior -->
                            <button class="depoimentos-btn prev" aria-label="Depoimento anterior">&#10094;</button>
                            
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
                            </div>
                            
                            <!-- Botão Próximo -->
                            <button class="depoimentos-btn next" aria-label="Próximo depoimento">&#10095;</button>
                        </div>
                        
                        <!-- Indicadores de slide -->
                        <div class="depoimentos-indicadores">
                            <?php for ($i = 0; $i < count($depoimentosAtivos); $i++): ?>
                                <span class="indicador <?= $i === 0 ? 'active' : ''; ?>" data-slide="<?= $i; ?>"></span>
                            <?php endfor; ?>
                        </div>
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
                                <img src="img/servicos/sobrancelha.png" alt="Design de Sobrancelhas">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Design de Sobrancelhas</h3>
                                <p>Nossos especialistas garantem sobrancelhas perfeitamente moldadas e bem cuidadas.</p>
                            </div>
                        </article>

                        <article class="card-servico-04">
                            <div class="card-servico-04-imagem">
                                <img src="img/servicos/tratamentos.png" alt="Tratamentos Capilares">
                            </div>
                            <div class="card-servico-04-info">
                                <h3>Tratamentos Capilares</h3>
                                <p>Recupere a saúde e vitalidade dos seus cabelos com nossos tratamentos especializados.</p>
                            </div>
                        </article>
                    </div>

                    <!-- Botão próximo -->
                    <button class="carousel-btn next" aria-label="Próximo serviço">&#10095;</button>

                    </div>
                </div>
                </section>

                <!-- SEÇÃO 05 - Produtos -->
                <section id="secao-05" class="secao-05">
                <div class="container-produtos-05">

                    <!-- Título e descrição -->
                    <div class="produtos-header">
                    <h2>Nossos Produtos</h2>
                    <p>Produtos de alta qualidade para cuidar da sua beleza em casa.</p>
                    </div>

                    <!-- Grid de produtos -->
                    <div class="produtos-grid">
                        <article class="card-produto-05">
                            <div class="card-produto-05-imagem">
                                <img src="img/produtos/imagens/baner_alisamento.png" alt="Produto 1">
                            </div>
                            <div class="card-produto-05-info">
                                <h3>Produto Premium</h3>
                                <p>Descrição do produto de alta qualidade para cuidados especiais.</p>
                                <span class="preco">R$ 89,90</span>
                            </div>
                        </article>

                        <article class="card-produto-05">
                            <div class="card-produto-05-imagem">
                                <img src="img/produtos/imagens/baner_alisamento.png" alt="Produto 2">
                            </div>
                            <div class="card-produto-05-info">
                                <h3>Produto Especial</h3>
                                <p>Produto especializado para tratamentos específicos e resultados duradouros.</p>
                                <span class="preco">R$ 129,90</span>
                            </div>
                        </article>

                        <article class="card-produto-05">
                            <div class="card-produto-05-imagem">
                                <img src="img/produtos/imagens/baner_alisamento.png" alt="Produto 3">
                            </div>
                            <div class="card-produto-05-info">
                                <h3>Produto Natural</h3>
                                <p>Produtos naturais e orgânicos para quem busca cuidados mais suaves.</p>
                                <span class="preco">R$ 79,90</span>
                            </div>
                        </article>
                    </div>
                </div>
                </section>

                <!-- SEÇÃO 06 - Vídeos -->
                <section id="secao-06" class="secao-06">
                <div class="container-videos-06">

                    <!-- Título e descrição -->
                    <div class="videos-header">
                    <h2>Resultados em Vídeo</h2>
                    <p>Veja os resultados dos nossos tratamentos em ação.</p>
                    </div>

                    <!-- Grid de vídeos -->
                    <div class="videos-grid">
                        <article class="card-video-06">
                            <div class="card-video-06-imagem">
                                <video controls>
                                    <source src="videos/lavagem.mp4" type="video/mp4">
                                    Seu navegador não suporta vídeos HTML5.
                                </video>
                            </div>
                            <div class="card-video-06-info">
                                <h3>Tratamento de Lavagem</h3>
                                <p>Veja como realizamos nossos tratamentos de lavagem especializada.</p>
                            </div>
                        </article>

                        <article class="card-video-06">
                            <div class="card-video-06-imagem">
                                <img src="videos/imagens/banner-video.png" alt="Vídeo 2">
                                <div class="play-overlay">
                                    <span class="play-icon">▶</span>
                                </div>
                            </div>
                            <div class="card-video-06-info">
                                <h3>Resultados de Alisamento</h3>
                                <p>Transformações incríveis com nossos tratamentos de alisamento.</p>
                            </div>
                        </article>
                    </div>
                </div>
                </section>

                <!-- SEÇÃO 07 - Contato -->
                <section id="secao-07" class="secao-07">
                <div class="container-contato-07">

                    <!-- Título -->
                    <div class="contato-header">
                    <h2>Entre em Contato</h2>
                    <p>Agende seu horário e transforme sua beleza conosco.</p>
                    </div>

                    <!-- Informações de contato -->
                    <div class="contato-info">
                        <div class="contato-item">
                            <img src="img/whats-logo.png" alt="WhatsApp">
                            <div>
                                <h3>WhatsApp</h3>
                                <p>(11) 99999-9999</p>
                            </div>
                        </div>

                        <div class="contato-item">
                            <img src="img/instagram_logo.png" alt="Instagram">
                            <div>
                                <h3>Instagram</h3>
                                <p>@salomebeleza</p>
                            </div>
                        </div>

                        <div class="contato-item">
                            <img src="img/email-logo.png" alt="Email">
                            <div>
                                <h3>Email</h3>
                                <p>contato@salomebeleza.com</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de agendamento -->
                    <div class="contato-cta">
                        <a href="https://wa.me/5511999999999" class="btn-agendamento" target="_blank">
                            Agendar Horário
                        </a>
                    </div>
                </div>
                </section>

                <!-- FOOTER -->
                <footer class="footer">
                <div class="container-footer">
                    <div class="footer-content">
                        <div class="footer-logo">
                            <img src="img/LOGO MELHOR nome.png" alt="Salomé Beleza e Estética">
                        </div>
                        
                        <div class="footer-info">
                            <p>&copy; 2024 Salomé Beleza e Estética. Todos os direitos reservados.</p>
                            <p>Transformando sua beleza com excelência e cuidado.</p>
                        </div>
                        
                        <div class="footer-social">
                            <a href="#" target="_blank"><img src="img/whats-logo.png" alt="WhatsApp"></a>
                            <a href="#" target="_blank"><img src="img/instagram_logo.png" alt="Instagram"></a>
                        </div>
                    </div>
                </div>
                </footer>

      </div>

      <!-- MODAL DE CONTATO -->
      <div id="modalContato" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Entre em Contato</h2>
            <form class="form-contato">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" required>
                </div>
                <div class="form-group">
                    <label for="mensagem">Mensagem:</label>
                    <textarea id="mensagem" name="mensagem" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn-enviar">Enviar Mensagem</button>
            </form>
        </div>
      </div>

      <!-- JavaScript -->
      <script src="js/script.js"></script>
  </body>
</html>
