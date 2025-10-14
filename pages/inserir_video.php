<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die('Acesso negado.');
}

require '../conexao.php';

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
// Buscar vídeos cadastrados (ativos e inativos)
$videos = [];
$resultadoVideos = $conn->query(
    'SELECT v.*, c.nome AS categoria_nome
     FROM salao_videos v
     LEFT JOIN categoria_videos c ON c.id = v.id_categoria
     ORDER BY COALESCE(v.ordem, 2147483647), v.data_criacao DESC, v.titulo ASC'
);
if ($resultadoVideos) {
    while ($linha = $resultadoVideos->fetch_assoc()) {
        $videos[] = $linha;
    }
    $resultadoVideos->free();
}

$videosAtivos = [];
$videosInativos = [];
foreach ($videos as $vid) {
    if ((int) ($vid['ativo'] ?? 0) === 1) {
        $videosAtivos[] = $vid;
    } else {
        $videosInativos[] = $vid;
    }
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

function detectarMimeVideo(?string $valor): string
{
    if ($valor === null) {
        return 'video/mp4';
    }
    $valor = str_replace('\\', '/', $valor);
    $ext = strtolower(pathinfo($valor, PATHINFO_EXTENSION));
    $map = [
        'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'mov' => 'video/quicktime',
        'webm' => 'video/webm', 'ogg' => 'video/ogg', 'ogv' => 'video/ogg',
        'mkv' => 'video/x-matroska', 'avi' => 'video/x-msvideo',
    ];
    return $map[$ext] ?? 'video/mp4';
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

function resolverFonteVideoAdmin(array $video): array
{
    $url = isset($video['url']) ? trim((string) $video['url']) : '';
    if ($url !== '') {
        $embed = construirEmbedUrl($url);
        if ($embed !== null) {
            return ['type' => 'iframe', 'src' => $embed];
        }
        return ['type' => 'link', 'href' => $url];
    }
    $caminho = isset($video['caminho']) ? (string) $video['caminho'] : '';
    $src = resolverCaminhoVideo($caminho);
    if ($src !== '') {
        return ['type' => 'video', 'src' => $src, 'mime' => detectarMimeVideo($caminho)];
    }
    return ['type' => 'none'];
}

function renderizarVideosGrid(array $lista): void
{
    if (empty($lista)) {
        return;
    }
    ?>
    <div class="servicos-grid">
        <?php foreach ($lista as $video): ?>
            <?php
            $titulo = htmlspecialchars((string) ($video['titulo'] ?? ''), ENT_QUOTES, 'UTF-8');
            $categoriaNome = htmlspecialchars((string) ($video['categoria_nome'] ?? ''), ENT_QUOTES, 'UTF-8');
            $descricao = isset($video['descricao']) && $video['descricao'] !== ''
                ? nl2br(htmlspecialchars((string) $video['descricao'], ENT_QUOTES, 'UTF-8'))
                : '<span class="texto-suave">Sem descricao cadastrada.</span>';
            $ativo = (int) ($video['ativo'] ?? 0) === 1;
            $fonte = resolverFonteVideoAdmin($video);
            ?>
            <article class="servico-accordion-item">
                <header class="servico-accordion-header">
                    <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                        <span class="servico-accordion-title">
                            <strong><?= $titulo; ?></strong>
                        </span>
                        <span class="servico-status-pill <?= $ativo ? 'ativo' : 'inativo'; ?>">
                            <?= $ativo ? 'Ativo' : 'Inativo'; ?>
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
                                        <h3 class="servico-nome"><?= $titulo; ?></h3>
                                        <?php if ($categoriaNome !== ''): ?>
                                            <span class="servico-categoria-pill"><?= $categoriaNome; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </header>
                                <dl class="servico-propriedades">
                                    <div>
                                        <dt>Categoria:</dt>
                                        <dd><?= $categoriaNome !== '' ? $categoriaNome : 'Sem categoria'; ?></dd>
                                    </div>
                                    <div>
                                        <dt>Fonte:</dt>
                                        <dd>
                                            <?php
                                            echo $fonte['type'] === 'iframe' ? 'Plataforma externa' : (
                                                $fonte['type'] === 'video' ? 'Arquivo' : (
                                                $fonte['type'] === 'link' ? 'Link' : 'Indisponivel'
                                            ));
                                            ?>
                                        </dd>
                                    </div>
                                    <div class="servico-descricao-bloco">
                                        <dt>Descricao:</dt>
                                        <dd class="servico-descricao-texto"><?= $descricao; ?></dd>
                                    </div>
                                </dl>
                            </div>
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
    <link rel="stylesheet" href="../css/estilo.css">
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4">
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

<section class="servico-lista-section mt-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <h2 class="secao-titulo">Videos cadastrados</h2>
      <span class="texto-suave"><?= count($videos); ?> video(s) no sistema</span>
  </div>

  <?php if (empty($videos)): ?>
      <div class="alerta alerta-informacao">Nenhum video cadastrado ate o momento.</div>
  <?php else: ?>
      <div class="servico-subsecao">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
              <h3 class="servico-subtitulo">Videos ativos</h3>
              <span class="texto-suave"><?= count($videosAtivos); ?> ativo(s)</span>
          </div>
          <?php if (empty($videosAtivos)): ?>
              <div class="alerta alerta-informacao">Nenhum video ativo cadastrado.</div>
          <?php else: ?>
              <?php renderizarVideosGrid($videosAtivos); ?>
          <?php endif; ?>
      </div>

      <div class="servico-subsecao mt-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
              <h3 class="servico-subtitulo">Videos inativos</h3>
              <span class="texto-suave"><?= count($videosInativos); ?> inativo(s)</span>
          </div>
          <?php if (empty($videosInativos)): ?>
              <div class="alerta alerta-informacao">Nenhum video marcado como inativo.</div>
          <?php else: ?>
              <?php renderizarVideosGrid($videosInativos); ?>
          <?php endif; ?>
      </div>
  <?php endif; ?>
</section>

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

// Acordeon: expandir/retrair detalhes
document.querySelectorAll('.servico-accordion-toggle').forEach((toggle) => {
    const item = toggle.closest('.servico-accordion-item');
    if (!item) return;
    const content = item.querySelector('.servico-accordion-content');
    const icon = toggle.querySelector('.servico-accordion-icon');
    if (!content || !icon) return;

    toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        const newState = !expanded;
        toggle.setAttribute('aria-expanded', String(newState));
        content.setAttribute('aria-hidden', String(!newState));
        icon.textContent = newState ? '-' : '+';
    });
});
</script>
</body>
</html>
