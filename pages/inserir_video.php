<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die('Acesso negado.');
}

require '../conexao.php';

// Categorias (ativas para o select)
$categoriasVideo = [];
$resultadoCategorias = $conn->query(
    'SELECT id, nome
     FROM categoria_videos
     WHERE ativo = 1
     ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultadoCategorias) {
    while ($linha = $resultadoCategorias->fetch_assoc()) {
        $categoriasVideo[] = [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
        ];
    }
    $resultadoCategorias->free();
}

// Mapa de categorias (todas) para exibir nome no grid
$categoriasMapa = [];
$resultadoCategoriasTodas = $conn->query(
    'SELECT id, nome FROM categoria_videos ORDER BY nome ASC'
);
if ($resultadoCategoriasTodas) {
    while ($linha = $resultadoCategoriasTodas->fetch_assoc()) {
        $categoriasMapa[(int) $linha['id']] = (string) $linha['nome'];
    }
    $resultadoCategoriasTodas->free();
}

// Utilitarios de video (preview e formatacao)
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
    if (preg_match('/^(https?:)?\/\//i', $valor)) {
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
    if (preg_match('~(?:https?:)?//(?:www\.)?youtu\.be/([^?&]+)~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('~(?:https?:)?//(?:www\.)?youtube\.com/(?:watch\?v=|embed/|shorts/)([^?&]+)~i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('~(?:https?:)?//vimeo\.com/(\d+)~i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    if (preg_match('~(?:https?:)?//(?:www\.)?dailymotion\.com/video/([^_/?&]+)~i', $url, $m)) {
        return 'https://www.dailymotion.com/embed/video/' . $m[1];
    }
    if (preg_match('~(?:https?:)?//(?:www\.)?facebook\.com/.+/videos/(\d+)~i', $url)) {
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
            return ['type' => 'iframe', 'src' => $embedUrl];
        }
        return ['type' => 'link', 'href' => $urlOriginal];
    }
    $caminhoOriginal = isset($video['caminho']) ? (string) $video['caminho'] : '';
    $caminhoResolvido = resolverCaminhoVideo($caminhoOriginal);
    if ($caminhoResolvido !== '') {
        return ['type' => 'video', 'src' => $caminhoResolvido, 'mime' => detectarMimeVideo($caminhoOriginal)];
    }
    return ['type' => 'none'];
}

// Buscar videos e separar por status
$videos = [];
$resultadoVideos = $conn->query(
    'SELECT id, titulo, descricao, caminho, url, id_categoria, ativo, ordem, data_criacao
     FROM salao_videos
     ORDER BY COALESCE(ordem, 2147483647), data_criacao DESC, titulo ASC'
);
if ($resultadoVideos) {
    while ($linha = $resultadoVideos->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['id_categoria'] = isset($linha['id_categoria']) ? (int) $linha['id_categoria'] : null;
        $linha['ativo'] = (int) ($linha['ativo'] ?? 0);
        $videos[] = $linha;
    }
    $resultadoVideos->free();
}

$videosAtivos = [];
$videosInativos = [];
foreach ($videos as $vd) {
    if ($vd['ativo'] === 1) {
        $videosAtivos[] = $vd;
    } else {
        $videosInativos[] = $vd;
    }
}

function renderizarVideosGrid(array $lista, array $categoriasMapa): void
{
    if (empty($lista)) {
        return;
    }
    ?>
    <div class="servicos-grid">
        <?php foreach ($lista as $video): ?>
            <?php
            $fonte = resolverFonteVideo($video);
            $descricaoFormatada = formatarDescricaoVideo($video['descricao'] ?? null);
            $categoriaNome = '';
            if (isset($video['id_categoria']) && $video['id_categoria'] !== null && isset($categoriasMapa[(int) $video['id_categoria']])) {
                $categoriaNome = (string) $categoriasMapa[(int) $video['id_categoria']];
            }
            ?>
            <article class="servico-accordion-item">
                <header class="servico-accordion-header">
                    <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                        <span class="servico-accordion-title">
                            <strong><?= htmlspecialchars($video['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </span>
                        <span class="servico-status-pill <?= (int) ($video['ativo'] ?? 0) === 1 ? 'ativo' : 'inativo'; ?>">
                            <?= (int) ($video['ativo'] ?? 0) === 1 ? 'Ativo' : 'Inativo'; ?>
                        </span>
                        <span class="servico-accordion-icon">+</span>
                    </button>
                </header>
                <div class="servico-accordion-content" aria-hidden="true">
                    <div class="servico-card">
                        <div class="servico-card-inner">
                            <div class="servico-card-info">
                                <header class="servico-card-top">
                                    <div>
                                        <h3 class="servico-nome"><?= htmlspecialchars($video['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <?php if ($categoriaNome !== ''): ?>
                                            <span class="servico-categoria-pill"><?= htmlspecialchars($categoriaNome, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </header>

                                <dl class="servico-propriedades">
                                    <div>
                                        <dt>Categoria:</dt>
                                        <dd><?= $categoriaNome !== '' ? htmlspecialchars($categoriaNome, ENT_QUOTES, 'UTF-8') : 'Nao informada'; ?></dd>
                                    </div>
                                    <div>
                                        <dt>Fonte:</dt>
                                        <dd><?= htmlspecialchars(strtoupper($fonte['type']), ENT_QUOTES, 'UTF-8'); ?></dd>
                                    </div>
                                    <div class="servico-descricao-bloco">
                                        <dt>Descricao:</dt>
                                        <dd class="servico-descricao-texto"><?= $descricaoFormatada; ?></dd>
                                    </div>
                                </dl>
                            </div>

                            <figure class="servico-card-imagem" style="display: flex; align-items: center; justify-content: center;">
                                <?php if ($fonte['type'] === 'iframe'): ?>
                                    <iframe src="<?= htmlspecialchars($fonte['src'], ENT_QUOTES, 'UTF-8'); ?>"
                                            title="<?= htmlspecialchars($video['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            loading="lazy"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                            style="width:100%; height:100%; border:0;"></iframe>
                                <?php elseif ($fonte['type'] === 'video'): ?>
                                    <video controls preload="metadata" style="width:100%; height:100%; display:block;">
                                        <source src="<?= htmlspecialchars($fonte['src'], ENT_QUOTES, 'UTF-8'); ?>" type="<?= htmlspecialchars($fonte['mime'], ENT_QUOTES, 'UTF-8'); ?>">
                                        Seu navegador nao suporta reproducao de video.
                                    </video>
                                <?php elseif ($fonte['type'] === 'link'): ?>
                                    <div class="video-link-placeholder" style="text-align:center; padding:16px;">
                                        Video por link externo. <a href="<?= htmlspecialchars($fonte['href'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir</a>
                                    </div>
                                <?php else: ?>
                                    <div class="video-link-placeholder" style="text-align:center; padding:16px;">Video indisponivel.</div>
                                <?php endif; ?>
                            </figure>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Inserir Video</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/estilo.css" rel="stylesheet">
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="pagina-admin p-4">
<h2 class="mb-4">Inserir Video</h2>

<div class="d-flex gap-3 mb-4">
    <button id="btnLink" class="btn btn-primary">Inserir link</button>
    <button id="btnUpload" class="btn btn-success">Upload de arquivo</button>
</div>

<div id="modalLink" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formLink" method="POST" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Inserir link de video</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
            <label class="form-label">Titulo:</label>
            <input type="text" name="titulo" class="form-control mb-3" required>
            <label class="form-label">Descricao:</label>
            <textarea name="descricao" class="form-control mb-3" rows="4"></textarea>
            <label class="form-label">Categoria:</label>
            <select name="categoria" class="form-select mb-3">
                <option value="">Sem categoria</option>
                <?php foreach ($categoriasVideo as $categoria): ?>
                    <option value="<?= $categoria['id']; ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($categoriasVideo)): ?>
                <p class="text-muted small mb-3">Nenhuma categoria ativa cadastrada.</p>
            <?php endif; ?>
            <label class="form-label">Link do video:</label>
            <input type="url" name="link" class="form-control" required placeholder="https://">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="modalUpload" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUpload" method="POST" enctype="multipart/form-data" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Upload de video</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
            <label class="form-label">Titulo:</label>
            <input type="text" name="titulo" class="form-control mb-3" required>
            <label class="form-label">Descricao:</label>
            <textarea name="descricao" class="form-control mb-3" rows="4"></textarea>
            <label class="form-label">Categoria:</label>
            <select name="categoria" class="form-select mb-3">
                <option value="">Sem categoria</option>
                <?php foreach ($categoriasVideo as $categoria): ?>
                    <option value="<?= $categoria['id']; ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($categoriasVideo)): ?>
                <p class="text-muted small mb-3">Nenhuma categoria ativa cadastrada.</p>
            <?php endif; ?>
            <label class="form-label">Arquivo de video:</label>
            <input
                type="file"
                name="arquivo"
                accept="video/*"
                class="form-control"
                required
            >
            <small class="form-text text-muted">Tamanho maximo permitido: 15 MB.</small>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
// Secao de listagem abaixo dos formularios
?>
<div class="horarios-wrapper servicos-wrapper mt-4">
    <section class="servico-lista-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="secao-titulo">Videos cadastrados</h2>
            <span class="texto-suave"><?php echo count($videos); ?> video(s) no sistema</span>
        </div>

        <?php if (empty($videos)): ?>
            <div class="alerta alerta-informacao">Nenhum video cadastrado ate o momento.</div>
        <?php else: ?>
            <div class="servico-subsecao">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h3 class="servico-subtitulo">Videos ativos</h3>
                    <span class="texto-suave"><?php echo count($videosAtivos); ?> ativo(s)</span>
                </div>
                <?php if (empty($videosAtivos)): ?>
                    <div class="alerta alerta-informacao">Nenhum video ativo cadastrado.</div>
                <?php else: ?>
                    <?php renderizarVideosGrid($videosAtivos, $categoriasMapa); ?>
                <?php endif; ?>
            </div>

            <div class="servico-subsecao mt-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h3 class="servico-subtitulo">Videos inativos</h3>
                    <span class="texto-suave"><?php echo count($videosInativos); ?> inativo(s)</span>
                </div>
                <?php if (empty($videosInativos)): ?>
                    <div class="alerta alerta-informacao">Nenhum video marcado como inativo.</div>
                <?php else: ?>
                    <?php renderizarVideosGrid($videosInativos, $categoriasMapa); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
const MAX_UPLOAD_SIZE = 15 * 1024 * 1024; // 15 MB

const modalLink = new bootstrap.Modal(document.getElementById('modalLink'));
const modalUpload = new bootstrap.Modal(document.getElementById('modalUpload'));

document.getElementById('btnLink').addEventListener('click', () => modalLink.show());
document.getElementById('btnUpload').addEventListener('click', () => modalUpload.show());

document.getElementById('formLink').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    formData.append('tipo', 'link');

    fetch('../video_action.php', { method: 'POST', body: formData })
        .then((res) => res.json())
        .then((data) => {
            alert(data.message || 'Operacao concluida.');
            if (data.success) {
                modalLink.hide();
                this.reset();
                location.reload();
            }
        })
        .catch(() => {
            alert('Nao foi possivel enviar os dados. Tente novamente.');
        });
});

document.getElementById('formUpload').addEventListener('submit', function (event) {
    event.preventDefault();
    const arquivoInput = this.querySelector('input[name="arquivo"]');
    if (arquivoInput && arquivoInput.files.length > 0) {
        const arquivo = arquivoInput.files[0];
        if (arquivo.size > MAX_UPLOAD_SIZE) {
            alert('O arquivo selecionado excede o limite de 15 MB.');
            return;
        }
    }

    const formData = new FormData(this);
    formData.append('tipo', 'upload');

    fetch('../video_action.php', { method: 'POST', body: formData })
        .then((res) => res.json())
        .then((data) => {
            alert(data.message || 'Operacao concluida.');
            if (data.success) {
                modalUpload.hide();
                this.reset();
                location.reload();
            }
        })
        .catch(() => {
            alert('Nao foi possivel enviar os dados. Tente novamente.');
        });
});
</script>

<script>
  // Controle do acordeon (mesmo comportamento dos modulos de servicos/produtos)
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.servico-accordion-toggle').forEach((toggle) => {
      const item = toggle.closest('.servico-accordion-item');
      if (!item) { return; }
      const content = item.querySelector('.servico-accordion-content');
      const icon = toggle.querySelector('.servico-accordion-icon');
      if (!content || !icon) { return; }
      toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        const newState = !expanded;
        toggle.setAttribute('aria-expanded', String(newState));
        content.setAttribute('aria-hidden', String(!newState));
        icon.textContent = newState ? '-' : '+';
      });
    });
  });
</script>
</body>
</html>
