<?php
session_start();
$adminLogado = isset($_SESSION['usuario_id']);

require '../conexao.php';

function construirSlugCategoria(int $id): string
{
    return 'cat-' . $id;
}

function formatarDescricaoVideo(?string $texto): string
{
    if ($texto === null || trim($texto) === '') {
        return 'Descricao em breve.';
    }

    return nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
}

function detectarMimeVideo(?string $valor): string
{
    if ($valor === null) {
        return 'video/mp4';
    }

    $valor = str_replace('\\', '/', $valor);
    $extensao = strtolower(pathinfo($valor, PATHINFO_EXTENSION));

    $mapa = [
        'mp4'  => 'video/mp4',
        'm4v'  => 'video/mp4',
        'mov'  => 'video/quicktime',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'ogv'  => 'video/ogg',
        'mkv'  => 'video/x-matroska',
        'avi'  => 'video/x-msvideo',
    ];

    return $mapa[$extensao] ?? 'video/mp4';
}

function resolverCaminhoVideo(?string $valor): string
{
    if ($valor === null) {
        return '';
    }

    $valor = str_replace('\\', '/', trim($valor));
    if ($valor === '') {
        return '';
    }

    if (preg_match('/^(https?:)?\\/\\//i', $valor)) {
        return $valor;
    }

    if (strpos($valor, '../') === 0 || strpos($valor, './') === 0) {
        return $valor;
    }

    if ($valor[0] === '/') {
        return $valor;
    }

    if (strpos($valor, 'videos/') === 0) {
        return '../' . ltrim($valor, '/');
    }

    return '../videos/vdcadastro/' . ltrim($valor, '/');
}

function construirEmbedUrl(?string $url): ?string
{
    if ($url === null) {
        return null;
    }

    $url = trim($url);
    if ($url === '') {
        return null;
    }

    if (preg_match('~(?:https?:)?//(?:www\\.)?youtu\\.be/([^?&]+)~i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    if (preg_match('~(?:https?:)?//(?:www\\.)?youtube\\.com/(?:watch\\?v=|embed/|shorts/)([^?&]+)~i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    if (preg_match('~(?:https?:)?//vimeo\\.com/(\\d+)~i', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }

    if (preg_match('~(?:https?:)?//(?:www\\.)?dailymotion\\.com/video/([^_/?&]+)~i', $url, $matches)) {
        return 'https://www.dailymotion.com/embed/video/' . $matches[1];
    }

    if (preg_match('~(?:https?:)?//(?:www\\.)?facebook\\.com/.+/videos/(\\d+)~i', $url)) {
        return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url) . '&show_text=0';
    }

    return $url;
}

function resolverFonteVideo(array $video): array
{
    $urlOriginal = isset($video['url']) ? trim((string) $video['url']) : '';
    if ($urlOriginal !== '') {
        $embedUrl = construirEmbedUrl($urlOriginal);
        if ($embedUrl !== null) {
            return [
                'type' => 'iframe',
                'src'  => $embedUrl,
            ];
        }

        return [
            'type' => 'link',
            'href' => $urlOriginal,
        ];
    }

    $caminhoOriginal = isset($video['caminho']) ? (string) $video['caminho'] : '';
    $caminhoResolvido = resolverCaminhoVideo($caminhoOriginal);
    if ($caminhoResolvido !== '') {
        return [
            'type' => 'video',
            'src'  => $caminhoResolvido,
            'mime' => detectarMimeVideo($caminhoOriginal),
        ];
    }

    return [
        'type' => 'none',
    ];
}

$categoriasBrutas = [];
$resultadoCategorias = $conn->query(
    'SELECT id, nome, descricao
     FROM categoria_videos
     WHERE ativo = 1
     ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultadoCategorias) {
    while ($linha = $resultadoCategorias->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $categoriasBrutas[] = $linha;
    }
    $resultadoCategorias->free();
}

$videosBrutos = [];
$resultadoVideos = $conn->query(
    'SELECT id, titulo, descricao, caminho, url, id_categoria
     FROM salao_videos
     WHERE ativo = 1
     ORDER BY COALESCE(ordem, 2147483647), data_criacao DESC, titulo ASC'
);
if ($resultadoVideos) {
    while ($linha = $resultadoVideos->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['id_categoria'] = isset($linha['id_categoria']) ? (int) $linha['id_categoria'] : null;
        $videosBrutos[] = $linha;
    }
    $resultadoVideos->free();
}

$categoriasDados = [];
$categoriasIndex = [];
foreach ($categoriasBrutas as $categoria) {
    $slug = construirSlugCategoria($categoria['id']);
    $categoriasDados[] = [
        'id'         => $categoria['id'],
        'nome'       => $categoria['nome'],
        'descricao'  => $categoria['descricao'],
        'slug'       => $slug,
        'videos'     => [],
        'quantidade' => 0,
    ];
    $categoriasIndex[$categoria['id']] = count($categoriasDados) - 1;
}

$videosSemCategoria = [];
foreach ($videosBrutos as $video) {
    $categoriaId = $video['id_categoria'];
    if ($categoriaId !== null && isset($categoriasIndex[$categoriaId])) {
        $indice = $categoriasIndex[$categoriaId];
        $categoriasDados[$indice]['videos'][] = $video;
    } else {
        $videosSemCategoria[] = $video;
    }
}

$totalVideos = 0;
foreach ($categoriasDados as &$categoria) {
    $categoria['quantidade'] = count($categoria['videos']);
    $totalVideos += $categoria['quantidade'];
}
unset($categoria);

$categoriaOutros = null;
if (!empty($videosSemCategoria)) {
    $categoriaOutros = [
        'id'         => null,
        'nome'       => 'Outros videos',
        'descricao'  => '',
        'slug'       => 'sem-categoria',
        'videos'     => $videosSemCategoria,
        'quantidade' => count($videosSemCategoria),
    ];
    $totalVideos += $categoriaOutros['quantidade'];
}

$temVideos = $totalVideos > 0;
$categoriasRender = $categoriasDados;
if ($categoriaOutros !== null) {
    $categoriasRender[] = $categoriaOutros;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Videos - Salome Beleza</title>
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilo.css">
  <style>
    .videos-banner {
      position: relative;
      min-height: 350px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      background: url('../videos/imagens/banner-video.png') center / cover no-repeat;
    }

    .videos-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
    }

    .videos-banner-conteudo {
      position: relative;
      max-width: 720px;
      text-align: center;
      color: #fff;
    }

    .videos-banner-conteudo h1 {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 18px;
    }

    .videos-banner-conteudo p {
      font-size: 1.1rem;
      margin-bottom: 0;
    }

    .title-banner-pequeno {
      text-transform: uppercase;
    }

    .videos-wrapper {
      min-height: 100vh;
      padding: 70px 0 120px;
      background: linear-gradient(180deg, #0f172a 0%, #1f2937 60%, #0f172a 100%);
      color: #fff;
    }

    .videos-layout {
      display: grid;
      grid-template-columns: 300px minmax(0, 1fr);
      gap: 32px;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
    }

    .videos-sidebar {
      background: rgba(15, 23, 42, 0.85);
      border-radius: 22px;
      padding: 32px 24px;
      box-shadow: 0 20px 60px rgba(15, 23, 42, 0.55);
    }

    .videos-sidebar h2 {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .videos-categorias-lista {
      list-style: none;
      margin: 0;
      padding: 0;
      display: grid;
      gap: 10px;
    }

    .videos-categoria-item {
      width: 100%;
      border: 0;
      border-radius: 14px;
      padding: 12px 16px;
      font-size: 1rem;
      font-weight: 600;
      background: rgba(255, 255, 255, 0.08);
      color: #e5e7eb;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.2s ease, transform 0.2s ease;
    }

    .videos-categoria-item:hover {
      background: rgba(96, 165, 250, 0.45);
      color: #fff;
      transform: translateX(4px);
    }

    .videos-categoria-item.active {
      background: linear-gradient(135deg, #2563eb, #9333ea);
      color: #fff;
      box-shadow: 0 14px 38px rgba(79, 70, 229, 0.45);
    }

    .videos-main {
      background: rgba(15, 23, 42, 0.72);
      border-radius: 26px;
      padding: 36px 32px 44px;
      box-shadow: 0 24px 70px rgba(15, 23, 42, 0.55);
      backdrop-filter: blur(6px);
    }

    .videos-main-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 28px;
    }

    .videos-main-header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin: 0;
    }

    .videos-total {
      font-size: 0.95rem;
      font-weight: 600;
      color: #c7d2fe;
      background: rgba(79, 70, 229, 0.2);
      padding: 8px 16px;
      border-radius: 999px;
    }

    .videos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 26px;
    }

    .video-card {
      background: rgba(17, 24, 39, 0.92);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 18px 48px rgba(15, 23, 42, 0.55);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .video-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 26px 60px rgba(29, 78, 216, 0.6);
    }

    .video-player {
      background: rgba(30, 64, 175, 0.35);
    }

    .video-player iframe,
    .video-player video {
      width: 100%;
      height: 220px;
      display: block;
      border: 0;
    }

    .video-iframe-wrapper {
      position: relative;
      padding-bottom: 56.25%;
      height: 0;
      overflow: hidden;
    }

    .video-iframe-wrapper iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .video-link-placeholder {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 220px;
      padding: 24px;
      text-align: center;
      font-weight: 600;
      color: #dbeafe;
      background: rgba(96, 165, 250, 0.24);
    }

    .video-card-body {
      padding: 22px 24px 26px;
    }

    .video-card-body h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 12px;
      color: #f9fafb;
    }

    .video-card-descricao {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #d1d5db;
    }

    .video-link-fallback {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      color: #60a5fa;
      text-decoration: none;
      margin-top: 10px;
    }

    .video-link-fallback:hover {
      color: #93c5fd;
      text-decoration: underline;
    }

    .videos-vazio,
    .videos-vazio-categoria {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 50vh;
      font-size: 1.4rem;
      font-weight: 600;
      color: #cbd5f5;
      text-align: center;
    }

    @media (max-width: 1024px) {
      .videos-layout {
        grid-template-columns: 1fr;
      }

      .videos-sidebar {
        order: 2;
      }
    }

    @media (max-width: 768px) {
      .videos-banner {
        min-height: 260px;
      }

      .videos-banner-conteudo h1 {
        font-size: 2.2rem;
      }

      .videos-main {
        padding: 28px 20px;
      }

      .videos-main-header h1 {
        font-size: 1.6rem;
      }

      .video-player iframe,
      .video-player video {
        height: 190px;
      }
    }
  </style>
</head>
<body>
<?php
$menuShowAdminIcon = false;
include __DIR__ . '/../class/menu.php';
?>

<section class="videos-banner">
  <div class="videos-banner-conteudo">
    <p class="title-banner-pequeno">Veja a transformação acontecer</p>
    <h1>Resultados que inspiram</h1>
    <p>Resultados reais, conquistados com técnica, cuidado e paixão pela beleza</p>
  </div>
</section>

<main class="videos-wrapper">
  <?php if (!$temVideos): ?>
    <div class="videos-vazio">Nenhum video disponivel no momento.</div>
  <?php else: ?>
    <div class="videos-layout">
      <aside class="videos-sidebar">
        <h2>Categorias</h2>
        <ul class="videos-categorias-lista">
          <li>
            <button
              type="button"
              class="videos-categoria-item active"
              data-categoria="todos"
              data-nome="Todos os videos"
            >
              <span>Todos</span>
              <span><?= htmlspecialchars((string) $totalVideos, ENT_QUOTES, 'UTF-8'); ?></span>
            </button>
          </li>
          <?php foreach ($categoriasRender as $categoria): ?>
            <li>
              <button
                type="button"
                class="videos-categoria-item"
                data-categoria="<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                data-nome="<?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                <span><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span><?= htmlspecialchars((string) $categoria['quantidade'], ENT_QUOTES, 'UTF-8'); ?></span>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>

      <section class="videos-main">
        <header class="videos-main-header">
          <h1 id="videosAreaTitulo">Todos os videos</h1>
          <span class="videos-total" id="videosAreaTotal">
            <?= htmlspecialchars($totalVideos === 1 ? '1 video' : $totalVideos . ' videos', ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </header>

        <div class="videos-grid">
          <?php foreach ($categoriasRender as $categoria): ?>
            <?php foreach ($categoria['videos'] as $video): ?>
              <?php
                $fonte = resolverFonteVideo($video);
                $descricaoFormatada = formatarDescricaoVideo($video['descricao'] ?? null);
              ?>
              <article
                class="video-card"
                data-categoria="<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                <div class="video-player">
                  <?php if ($fonte['type'] === 'iframe'): ?>
                    <div class="video-iframe-wrapper">
                      <iframe
                        src="<?= htmlspecialchars($fonte['src'], ENT_QUOTES, 'UTF-8'); ?>"
                        title="<?= htmlspecialchars($video['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                      ></iframe>
                    </div>
                  <?php elseif ($fonte['type'] === 'video'): ?>
                    <video controls preload="metadata">
                      <source
                        src="<?= htmlspecialchars($fonte['src'], ENT_QUOTES, 'UTF-8'); ?>"
                        type="<?= htmlspecialchars($fonte['mime'], ENT_QUOTES, 'UTF-8'); ?>"
                      >
                      Seu navegador nao suporta reproducao de video.
                    </video>
                  <?php elseif ($fonte['type'] === 'link'): ?>
                    <div class="video-link-placeholder">
                      Video disponivel atraves de link externo.
                    </div>
                  <?php else: ?>
                    <div class="videos-vazio-categoria" style="min-height: 180px; font-size: 1rem;">
                      Video indisponivel.
                    </div>
                  <?php endif; ?>
                </div>
                <div class="video-card-body">
                  <h3><?= htmlspecialchars($video['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <div class="video-card-descricao"><?= $descricaoFormatada; ?></div>
                  <?php if ($fonte['type'] === 'link'): ?>
                    <a
                      class="video-link-fallback"
                      href="<?= htmlspecialchars($fonte['href'], ENT_QUOTES, 'UTF-8'); ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Abrir video em nova aba
                    </a>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </div>

        <div class="videos-vazio-categoria" id="videosVazio" hidden>
          Nenhum video disponivel nesta categoria.
        </div>
      </section>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../class/contatoFooter.php'; ?>
<?php include __DIR__ . '/../class/modais.php'; ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const categoriaBotoes = Array.from(document.querySelectorAll('.videos-categoria-item'));
    const cards = Array.from(document.querySelectorAll('.video-card'));
    const titulo = document.getElementById('videosAreaTitulo');
    const total = document.getElementById('videosAreaTotal');
    const vazio = document.getElementById('videosVazio');

    const atualizarVisibilidade = (categoriaSlug) => {
      let visiveis = 0;
      cards.forEach((card) => {
        const corresponde = categoriaSlug === 'todos' || card.dataset.categoria === categoriaSlug;
        card.hidden = !corresponde;
        if (corresponde) {
          visiveis += 1;
        }
      });

      vazio.hidden = visiveis !== 0;
      const textoQuantidade = visiveis === 1 ? '1 video' : visiveis + ' videos';
      total.textContent = textoQuantidade;
    };

    categoriaBotoes.forEach((botao) => {
      botao.addEventListener('click', () => {
        if (botao.classList.contains('active')) {
          return;
        }

        categoriaBotoes.forEach((item) => item.classList.remove('active'));
        botao.classList.add('active');

        const categoriaSlug = botao.dataset.categoria;
        const categoriaNome = botao.dataset.nome || 'Videos';

        titulo.textContent = categoriaNome;
        atualizarVisibilidade(categoriaSlug);
      });
    });

    atualizarVisibilidade('todos');
  });
</script>
</body>
</html>

