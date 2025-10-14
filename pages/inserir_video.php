<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die('Acesso negado.');
}

require '../conexao.php';

// Processar acoes de edicao/exclusao (formulario dos modais)
$mensagemSucesso = '';
$mensagemErro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $tituloPost = trim((string) ($_POST['titulo'] ?? ''));
        $descricaoPost = trim((string) ($_POST['descricao'] ?? ''));
        $idCategoriaPost = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int) $_POST['id_categoria'] : null;
        $ativoPost = isset($_POST['ativo']) ? 1 : 0;

        if ($id > 0 && $tituloPost !== '') {
            // Validar categoria, se informada
            if ($idCategoriaPost !== null) {
                $stmtCat = $conn->prepare('SELECT 1 FROM categoria_videos WHERE id = ? AND ativo = 1');
                if ($stmtCat) {
                    $stmtCat->bind_param('i', $idCategoriaPost);
                    if (!$stmtCat->execute()) {
                        $idCategoriaPost = null; // se falhar, nao utiliza
                    }
                    $stmtCat->close();
                } else {
                    $idCategoriaPost = null;
                }
            }

            $stmt = $conn->prepare('UPDATE salao_videos SET titulo = ?, descricao = ?, id_categoria = ?, ativo = ? WHERE id = ?');
            if ($stmt) {
                $descricaoParam = $descricaoPost !== '' ? $descricaoPost : null;
                $stmt->bind_param('ssiii', $tituloPost, $descricaoParam, $idCategoriaPost, $ativoPost, $id);
                if ($stmt->execute()) {
                    $mensagemSucesso = 'Vídeo atualizado.';
                } else {
                    $mensagemErro = 'Erro ao atualizar o vídeo.';
                }
                $stmt->close();
            } else {
                $mensagemErro = 'Erro ao preparar atualização do vídeo.';
            }
        } else {
            $mensagemErro = 'Dados inválidos para atualização do vídeo.';
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            // Buscar caminho de arquivo (se houver) para remover apos exclusao
            $caminhoArquivo = null;
            $stmtBusca = $conn->prepare('SELECT caminho FROM salao_videos WHERE id = ?');
            if ($stmtBusca) {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($caminhoArquivo);
                    $stmtBusca->fetch();
                }
                $stmtBusca->close();
            }

            $stmt = $conn->prepare('DELETE FROM salao_videos WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $mensagemSucesso = 'Vídeo excluído.';
                    // Remover arquivo fisico se pertencer a pasta de uploads do sistema
                    if ($caminhoArquivo) {
                        $normalizado = str_replace('\\', '/', $caminhoArquivo);
                        if (strpos($normalizado, 'videos/vdcadastro/') === 0) {
                            $caminhoFisico = realpath(__DIR__ . '/../' . $normalizado);
                            if ($caminhoFisico && is_file($caminhoFisico)) {
                                @unlink($caminhoFisico);
                            }
                        }
                    }
                } else {
                    $mensagemErro = 'Erro ao excluir o vídeo.';
                }
                $stmt->close();
            } else {
                $mensagemErro = 'Erro ao preparar exclusão do vídeo.';
            }
        } else {
            $mensagemErro = 'Vídeo inválido para exclusão.';
        }
    }
}

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
            <?php
            $videoJson = htmlspecialchars(
                json_encode($video, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
            <article class="servico-accordion-item" data-video='<?= $videoJson; ?>'>
                <header class="servico-accordion-header">
                    <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                        <span class="servico-accordion-title">
                            <strong><?= htmlspecialchars($video['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </span>
                        <span class="servico-status-pill <?= (int) ($video['ativo'] ?? 1) === 1 ? 'ativo' : 'inativo'; ?>">
                            <?= (int) ($video['ativo'] ?? 1) === 1 ? 'Ativo' : 'Inativo'; ?>
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

                                <div class="servico-card-acoes">
                                    <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarVideo">Editar</button>
                                    <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirVideo">Excluir</button>
                                </div>
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

<!-- Sessao de cadastro (alterna entre link e upload) -->
<section id="cadastroWrapper" class="mb-4">
    <div id="cadastroLink" class="card d-none">
        <div class="card-header">
            <h5 class="mb-0">Inserir link de video</h5>
        </div>
        <div class="card-body">
            <form id="formLink" method="POST" novalidate>
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
                <input type="url" name="link" class="form-control mb-3" required placeholder="https://">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Salvar</button>
                    <button type="button" id="fecharLink" class="btn btn-outline-secondary">Fechar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="cadastroUpload" class="card d-none">
        <div class="card-header">
            <h5 class="mb-0">Upload de video</h5>
        </div>
        <div class="card-body">
            <form id="formUpload" method="POST" enctype="multipart/form-data" novalidate>
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
                <input type="file" name="arquivo" accept="video/*" class="form-control mb-2" required>
                <small class="form-text text-muted d-block mb-3">Tamanho maximo permitido: 15 MB.</small>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Salvar</button>
                    <button type="button" id="fecharUpload" class="btn btn-outline-secondary">Fechar</button>
                </div>
            </form>
        </div>
    </div>
</section>

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

<!-- Modal Edição de Vídeo -->
<div class="modal fade" id="modalEditarVideo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar vídeo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editarVideoId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título*</label>
                            <input type="text" name="titulo" id="editarVideoTitulo" class="form-control" required maxlength="200">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" id="editarVideoDescricao" class="form-control" rows="3" maxlength="1000"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoria</label>
                            <select name="id_categoria" id="editarVideoCategoria" class="form-control">
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categoriasVideo as $cat): ?>
                                    <option value="<?= (int) $cat['id']; ?>"><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="editarVideoAtivo" name="ativo">
                                <label class="form-check-label" for="editarVideoAtivo">
                                    Vídeo ativo
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Exclusão de Vídeo -->
<div class="modal fade" id="modalExcluirVideo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Excluir vídeo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="excluirVideoId">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o vídeo <strong id="excluirVideoTitulo"></strong>?</p>
                    <p class="text-muted">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const MAX_UPLOAD_SIZE = 15 * 1024 * 1024; // 15 MB

const cadastroLink = document.getElementById('cadastroLink');
const cadastroUpload = document.getElementById('cadastroUpload');
const btnLink = document.getElementById('btnLink');
const btnUpload = document.getElementById('btnUpload');
const fecharLink = document.getElementById('fecharLink');
const fecharUpload = document.getElementById('fecharUpload');

const mostrarSecao = (qual) => {
    if (qual === 'link') {
        cadastroLink.classList.remove('d-none');
        cadastroUpload.classList.add('d-none');
    } else if (qual === 'upload') {
        cadastroUpload.classList.remove('d-none');
        cadastroLink.classList.add('d-none');
    }
    // rola a tela ate o inicio da sessao de cadastro
    const wrapper = document.getElementById('cadastroWrapper');
    if (wrapper) {
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

btnLink.addEventListener('click', () => mostrarSecao('link'));
btnUpload.addEventListener('click', () => mostrarSecao('upload'));
if (fecharLink) { fecharLink.addEventListener('click', () => cadastroLink.classList.add('d-none')); }
if (fecharUpload) { fecharUpload.addEventListener('click', () => cadastroUpload.classList.add('d-none')); }

document.getElementById('formLink').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    formData.append('tipo', 'link');

    fetch('../video_action.php', { method: 'POST', body: formData })
        .then((res) => res.json())
        .then((data) => {
            alert(data.message || 'Operacao concluida.');
            if (data.success) {
                this.reset();
                cadastroLink.classList.add('d-none');
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
                this.reset();
                cadastroUpload.classList.add('d-none');
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

    // Preencher modal de edicao usando dataset com os dados do video
    document.querySelectorAll('.acao-editar').forEach((btn) => {
      btn.addEventListener('click', function () {
        const item = this.closest('.servico-accordion-item');
        if (!item) return;
        const data = item.dataset.video ? JSON.parse(item.dataset.video) : null;
        if (!data) return;

        const idInput = document.getElementById('editarVideoId');
        const tituloInput = document.getElementById('editarVideoTitulo');
        const descricaoInput = document.getElementById('editarVideoDescricao');
        const ativoInput = document.getElementById('editarVideoAtivo');
        const categoriaSelect = document.getElementById('editarVideoCategoria');

        if (idInput) idInput.value = String(data.id || '');
        if (tituloInput) tituloInput.value = data.titulo || '';
        if (descricaoInput) descricaoInput.value = data.descricao || '';
        if (ativoInput) ativoInput.checked = String(data.ativo) === '1';

        if (categoriaSelect) {
          const valorCategoria = data.id_categoria ? String(data.id_categoria) : '';
          categoriaSelect.value = valorCategoria;
        }
      });
    });

    // Preencher modal de exclusao
    document.querySelectorAll('.acao-excluir').forEach((btn) => {
      btn.addEventListener('click', function () {
        const item = this.closest('.servico-accordion-item');
        if (!item) return;
        const data = item.dataset.video ? JSON.parse(item.dataset.video) : null;
        if (!data) return;

        const idInput = document.getElementById('excluirVideoId');
        const tituloSpan = document.getElementById('excluirVideoTitulo');
        if (idInput) idInput.value = String(data.id || '');
        if (tituloSpan) tituloSpan.textContent = data.titulo || '';
      });
    });
  });
</script>

<style>
/* Corrigir modais transparentes */
.modal {
    z-index: 1055 !important;
}
.modal-backdrop {
    z-index: 1050 !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}
.modal-content {
    background-color: white !important;
    border: 1px solid #dee2e6 !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.modal-header {
    border-bottom: 1px solid #dee2e6 !important;
}
.modal-footer {
    border-top: 1px solid #dee2e6 !important;
}

/* Estilo personalizado para modais de vídeos */
.modal-content {
    border-radius: 12px !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    border-radius: 12px 12px 0 0 !important;
    border-bottom: none !important;
}

.modal-title {
    font-weight: 600 !important;
    color: white !important;
}

.btn-close {
    filter: brightness(0) invert(1) !important;
}

.form-label {
    font-weight: 500 !important;
    color: #374151 !important;
    margin-bottom: 0.5rem !important;
}

.form-control, .form-select {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    padding: 0.75rem !important;
    font-size: 0.875rem !important;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
}

.form-check-input:checked {
    background-color: #667eea !important;
    border-color: #667eea !important;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 0.75rem 1.5rem !important;
    font-weight: 500 !important;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
}

.btn-outline-secondary {
    border-radius: 8px !important;
    padding: 0.75rem 1.5rem !important;
    font-weight: 500 !important;
}

.btn-danger {
    border-radius: 8px !important;
    padding: 0.75rem 1.5rem !important;
    font-weight: 500 !important;
}

.text-muted {
    font-size: 0.8rem !important;
    color: #6b7280 !important;
}
</style>
</body>
</html>
