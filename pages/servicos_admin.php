<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

$diretorioImagensServicos = __DIR__ . '/../img/servicos/imgcadastro';
if (!is_dir($diretorioImagensServicos)) {
    mkdir($diretorioImagensServicos, 0775, true);
}
$diretorioImagensServicos = realpath($diretorioImagensServicos) ?: $diretorioImagensServicos;
$webBaseImagensServicos = 'img/servicos/imgcadastro';
$tamanhoMaximoImagemBytes = 2 * 1024 * 1024; // 2 MB

function normalizarPreco(string $valorBruto): float
{
    $limpo = preg_replace('/[^0-9,\.]/', '', $valorBruto);
    if ($limpo === null || $limpo === '') {
        return 0.0;
    }
    $limpo = str_replace('.', '', $limpo);
    $limpo = str_replace(',', '.', $limpo);
    return (float) $limpo;
}

function tratarUploadImagemServico(string $campo, string $destinoDir, string $webBaseDir, int $tamanhoMaximo, ?string &$erro): ?array
{
    if (!isset($_FILES[$campo]) || !is_array($_FILES[$campo])) {
        return null;
    }

    $arquivo = $_FILES[$campo];

    if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Erro ao fazer upload da imagem.';
        return null;
    }

    if ($arquivo['size'] > $tamanhoMaximo) {
        $erro = 'A imagem deve ter no maximo 2MB.';
        return null;
    }

    $mapaMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $extFinal = null;
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo ? $finfo->file($arquivo['tmp_name']) : null;
        if ($mime && isset($mapaMime[$mime])) {
            $extFinal = $mapaMime[$mime];
        }
    }

    if ($extFinal === null) {
        $extOriginal = strtolower((string) pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        if ($extOriginal === 'jpeg') {
            $extOriginal = 'jpg';
        }
        if ($extOriginal !== '' && in_array($extOriginal, $mapaMime, true)) {
            $extFinal = $extOriginal;
        }
    }

    if ($extFinal === null) {
        $erro = 'Formato de imagem nao suportado. Utilize JPG, PNG, GIF ou WEBP.';
        return null;
    }

    $nomeArquivo = uniqid('servico_', true) . '.' . $extFinal;
    $destinoFisico = rtrim($destinoDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destinoFisico)) {
        $erro = 'Nao foi possivel salvar a imagem enviada.';
        return null;
    }

    $webBaseNormalizado = rtrim(str_replace('\\', '/', $webBaseDir), '/');

    return [
        'web'    => $webBaseNormalizado . '/' . $nomeArquivo,
        'fisico' => $destinoFisico,
    ];
}

function removerImagemServico(?string $webPath, string $destinoDir, string $webBaseDir): void
{
    if ($webPath === null || $webPath === '') {
        return;
    }

    $normalizado = ltrim(str_replace('\\', '/', $webPath), '/');
    $baseNormalizada = ltrim(str_replace('\\', '/', $webBaseDir), '/');

    if ($baseNormalizada === '' || strpos($normalizado, $baseNormalizada) !== 0) {
        return;
    }

    $relativo = ltrim(substr($normalizado, strlen($baseNormalizada)), '/');
    if ($relativo === '') {
        return;
    }

    $caminhoFisico = rtrim($destinoDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo);

    if (is_file($caminhoFisico)) {
        @unlink($caminhoFisico);
    }
}

function obterProximaOrdem(mysqli $conn): int
{
    $resultado = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM salao_servicos WHERE ativo = 1');
    if ($resultado) {
        $linha = $resultado->fetch_assoc();
        $resultado->free();
        return (int) ($linha['proxima'] ?? 1);
    }
    return 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $duracao = (int) ($_POST['duracao'] ?? 0);
        $preco = normalizarPreco((string) ($_POST['preco'] ?? '0'));
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '' || $duracao <= 0 || $preco < 0) {
            $mensagemErro = 'Preencha nome, duracao (em minutos) e preco valido.';
        } else {
            $uploadErro = null;
            $infoUpload = tratarUploadImagemServico(
                'imagem',
                $diretorioImagensServicos,
                $webBaseImagensServicos,
                $tamanhoMaximoImagemBytes,
                $uploadErro
            );

            if ($uploadErro !== null) {
                $mensagemErro = $uploadErro;
            } else {
                $imagemWebPath = null;
                $imagemFisicaNova = null;
                if ($infoUpload !== null) {
                    $imagemWebPath = $infoUpload['web'] ?? null;
                    $imagemFisicaNova = $infoUpload['fisico'] ?? null;
                }

                $ordemParam = null;
                if ($ativo === 1) {
                    $ordemParam = obterProximaOrdem($conn);
                }

                $stmt = $conn->prepare(
                    'INSERT INTO salao_servicos (nome, descricao, duracao, preco, categoria, imagem, ativo, ordem)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );

                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar insercao.';
                    if ($imagemFisicaNova) {
                        @unlink($imagemFisicaNova);
                    }
                } else {
                    $descricaoParam = $descricao !== '' ? $descricao : null;
                    $categoriaParam = $categoria !== '' ? $categoria : null;
                    $imagemParam = $imagemWebPath !== null && $imagemWebPath !== '' ? $imagemWebPath : null;

                    $stmt->bind_param(
                        'ssidssii',
                        $nome,
                        $descricaoParam,
                        $duracao,
                        $preco,
                        $categoriaParam,
                        $imagemParam,
                        $ativo,
                        $ordemParam
                    );

                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Servico cadastrado com sucesso.';
                    } else {
                        $mensagemErro = 'Erro ao inserir servico: ' . $stmt->error;
                        if ($imagemFisicaNova) {
                            @unlink($imagemFisicaNova);
                        }
                    }

                    $stmt->close();
                }
            }
        }
    } elseif ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $duracao = (int) ($_POST['duracao'] ?? 0);
        $preco = normalizarPreco((string) ($_POST['preco'] ?? '0'));
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0) {
            $mensagemErro = 'Servico invalido para edicao.';
        } elseif ($nome === '' || $duracao <= 0 || $preco < 0) {
            $mensagemErro = 'Preencha nome, duracao (em minutos) e preco valido.';
        } else {
            $imagemAtual = null;
            $ativoAnterior = 0;
            $ordemAtual = null;
            $stmtBusca = $conn->prepare('SELECT imagem, ativo, ordem FROM salao_servicos WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar servico para edicao.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemAtual, $ativoAnterior, $ordemAtual);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Servico nao encontrado.';
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar servico para edicao.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $uploadErro = null;
                $infoUpload = tratarUploadImagemServico(
                    'imagem',
                    $diretorioImagensServicos,
                    $webBaseImagensServicos,
                    $tamanhoMaximoImagemBytes,
                    $uploadErro
                );

                if ($uploadErro !== null) {
                    $mensagemErro = $uploadErro;
                } else {
                    $imagemWebPath = $imagemAtual;
                    $imagemFisicaNova = null;

                    if ($infoUpload !== null) {
                        $imagemWebPath = $infoUpload['web'] ?? null;
                        $imagemFisicaNova = $infoUpload['fisico'] ?? null;
                    }

                    $ordemParam = null;
                    if ($ativo === 1) {
                        if ((int) $ativoAnterior === 1 && $ordemAtual !== null) {
                            $ordemParam = (int) $ordemAtual;
                        } else {
                            $ordemParam = obterProximaOrdem($conn);
                        }
                    }

                    $stmt = $conn->prepare(
                        'UPDATE salao_servicos
                         SET nome = ?, descricao = ?, duracao = ?, preco = ?, categoria = ?, imagem = ?, ativo = ?, ordem = ?
                         WHERE id = ?'
                    );

                    if ($stmt === false) {
                        $mensagemErro = 'Erro ao preparar atualizacao.';
                        if ($imagemFisicaNova) {
                            @unlink($imagemFisicaNova);
                        }
                    } else {
                        $descricaoParam = $descricao !== '' ? $descricao : null;
                        $categoriaParam = $categoria !== '' ? $categoria : null;
                        $imagemParam = $imagemWebPath !== null && $imagemWebPath !== '' ? $imagemWebPath : null;

                        $stmt->bind_param(
                            'ssidssiii',
                            $nome,
                            $descricaoParam,
                            $duracao,
                            $preco,
                            $categoriaParam,
                            $imagemParam,
                            $ativo,
                            $ordemParam,
                            $id
                        );

                        if ($stmt->execute()) {
                            $mensagemSucesso = 'Servico atualizado.';
                            if ($infoUpload !== null) {
                                removerImagemServico($imagemAtual, $diretorioImagensServicos, $webBaseImagensServicos);
                            }
                        } else {
                            $mensagemErro = 'Erro ao atualizar servico: ' . $stmt->error;
                            if ($imagemFisicaNova) {
                                @unlink($imagemFisicaNova);
                            }
                        }

                        $stmt->close();
                    }
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $mensagemErro = 'Servico invalido para exclusao.';
        } else {
            $imagemParaRemover = null;
            $registroEncontrado = false;
            $stmtBusca = $conn->prepare('SELECT imagem FROM salao_servicos WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar servico para exclusao.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemParaRemover);
                    $registroEncontrado = (bool) $stmtBusca->fetch();
                } else {
                    $mensagemErro = 'Erro ao localizar servico para exclusao.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '' && !$registroEncontrado) {
                $mensagemErro = 'Servico nao encontrado.';
            }

            if ($mensagemErro === '') {
                $stmt = $conn->prepare('DELETE FROM salao_servicos WHERE id = ?');
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar exclusao.';
                } else {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Servico removido.';
                        removerImagemServico($imagemParaRemover, $diretorioImagensServicos, $webBaseImagensServicos);
                    } else {
                        $mensagemErro = 'Erro ao excluir servico: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$servicos = [];
$resultado = $conn->query('SELECT * FROM salao_servicos ORDER BY COALESCE(ordem, 2147483647), criado_em DESC');
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $servicos[] = $linha;
    }
    $resultado->free();
}

$servicosAtivos = [];
$servicosInativos = [];
foreach ($servicos as $servico) {
    if ((int) ($servico['ativo'] ?? 1) === 1) {
        $servicosAtivos[] = $servico;
    } else {
        $servicosInativos[] = $servico;
    }
}

function formatarPreco(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function renderizarServicosGrid(array $servicosLista): void
{
    if (empty($servicosLista)) {
        return;
    }
    ?>
    <div class="servicos-grid">
        <?php foreach ($servicosLista as $servico): ?>
            <?php
            $servicoJson = htmlspecialchars(
                json_encode($servico, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                ENT_QUOTES,
                'UTF-8'
            );

            $imagemSrc = '';
            if (!empty($servico['imagem'])) {
                $imagemValor = (string) $servico['imagem'];
                if (preg_match('/^(https?:)?\/\//i', $imagemValor)) {
                    $imagemSrc = $imagemValor;
                } elseif (strpos($imagemValor, '../') === 0) {
                    $imagemSrc = $imagemValor;
                } elseif ($imagemValor !== '' && $imagemValor[0] === '/') {
                    $imagemSrc = $imagemValor;
                } else {
                    $imagemSrc = '../' . ltrim($imagemValor, '/');
                }
            }

            $temImagem = $imagemSrc !== '';
            $descricaoFormatada = !empty($servico['descricao'])
                ? nl2br(htmlspecialchars($servico['descricao'], ENT_QUOTES, 'UTF-8'))
                : '<span class="texto-suave">Sem descricao cadastrada.</span>';
            ?>
            <article class="servico-accordion-item">
                <header class="servico-accordion-header">
                    <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                        <span class="servico-accordion-title">
                            <strong><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </span>
                        <span class="servico-status-pill <?= $servico['ativo'] ? 'ativo' : 'inativo'; ?>">
                            <?= $servico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                        </span>
                        <span class="servico-accordion-icon">+</span>
                    </button>
                </header>
                <div class="servico-accordion-content" aria-hidden="true">
                    <div class="servico-card" data-servico='<?= $servicoJson; ?>'>
                        <div class="servico-card-inner">
                            <div class="servico-card-info">
                                <header class="servico-card-top">
                                    <div>
                                        <h3 class="servico-nome"><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <?php if (!empty($servico['categoria'])): ?>
                                            <span class="servico-categoria-pill"><?= htmlspecialchars($servico['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </header>

                                <dl class="servico-propriedades">
                                    <div>
                                        <dt>Categoria:</dt>
                                        <dd><?= !empty($servico['categoria']) ? htmlspecialchars($servico['categoria'], ENT_QUOTES, 'UTF-8') : 'Nao informada'; ?></dd>
                                    </div>
                                    <div>
                                        <dt>Tempo:</dt>
                                        <dd><?= (int) $servico['duracao']; ?> min</dd>
                                    </div>
                                    <div>
                                        <dt>Valor:</dt>
                                        <dd class="servico-propriedade-valor"><?= formatarPreco((float) $servico['preco']); ?></dd>
                                    </div>
                                    <div class="servico-descricao-bloco">
                                        <dt>Descricao:</dt>
                                        <dd class="servico-descricao-texto"><?= $descricaoFormatada; ?></dd>
                                    </div>
                                </dl>

                                <div class="servico-card-acoes">
                                    <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarServico">Editar</button>
                                    <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirServico">Excluir</button>
                                </div>
                            </div>

                            <?php if ($temImagem): ?>
                                <figure class="servico-card-imagem">
                                    <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                </figure>
                            <?php endif; ?>
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
    <title>Gestao de Servicos</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilo.css">
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
        
        /* Estilo personalizado para modais de serviços */
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
</head>
<body class="pagina-admin">
    <div class="horarios-wrapper servicos-wrapper">
        <header class="horarios-header">
            <h1>Catalogo de Servicos</h1>
            <p class="horarios-subtitle">Cadastre, edite e organize os servicos oferecidos pelo salao.</p>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="servico-form-section">
            <h2 class="secao-titulo">Novo servico</h2>
            <form method="post" class="servico-formulario card" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome*</label>
                        <input type="text" name="nome" class="form-control" required maxlength="100" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duracao (min)*</label>
                        <input type="number" name="duracao" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Preco*</label>
                        <input type="text" name="preco" class="form-control" required placeholder="Ex: 120,00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="categoria" class="form-control" maxlength="50" placeholder="Ex: Cabelo, Estetica">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload imagem</label>
                        <input type="file" name="imagem" class="form-control" accept="image/*">
                        <small class="form-text text-muted">Tamanho maximo: 2 MB.</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descricao</label>
                        <textarea name="descricao" class="form-control" rows="4" placeholder="Detalhes do servico"></textarea>
                    </div>
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="novoServicoAtivo" name="ativo" checked>
                            <label class="form-check-label" for="novoServicoAtivo">
                                Servico ativo
                            </label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar servico</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="servico-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="secao-titulo">Servicos cadastrados</h2>
                <span class="texto-suave"><?= count($servicos); ?> servico(s) no sistema</span>
            </div>

            <?php if (empty($servicos)): ?>
                <div class="alerta alerta-informacao">Nenhum servico cadastrado ate o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Servicos ativos</h3>
                        <span class="texto-suave"><?= count($servicosAtivos); ?> ativo(s)</span>
                    </div>
                    <?php if (empty($servicosAtivos)): ?>
                        <div class="alerta alerta-informacao">Nenhum servico ativo cadastrado.</div>
                    <?php else: ?>
                        <?php renderizarServicosGrid($servicosAtivos); ?>
                    <?php endif; ?>
                </div>

                <div class="servico-subsecao mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Servicos inativos</h3>
                        <span class="texto-suave"><?= count($servicosInativos); ?> inativo(s)</span>
                    </div>
                    <?php if (empty($servicosInativos)): ?>
                        <div class="alerta alerta-informacao">Nenhum servico marcado como inativo.</div>
                    <?php else: ?>
                        <?php renderizarServicosGrid($servicosInativos); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Modal Edicao -->
    <div class="modal fade" id="modalEditarServico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" class="modal-body-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editarServicoId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar servico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome*</label>
                                <input type="text" name="nome" class="form-control" id="editarServicoNome" required maxlength="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Duracao (min)*</label>
                                <input type="number" name="duracao" class="form-control" id="editarServicoDuracao" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Preco*</label>
                                <input type="text" name="preco" class="form-control" id="editarServicoPreco" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <input type="text" name="categoria" class="form-control" id="editarServicoCategoria" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload imagem</label>
                                <input type="file" name="imagem" class="form-control" id="editarServicoImagem" accept="image/*">
                                <input type="hidden" name="imagem_atual" id="editarServicoImagemAtual">
                                <small class="form-text text-muted" id="editarServicoImagemInfo"></small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descricao</label>
                                <textarea name="descricao" class="form-control" id="editarServicoDescricao" rows="4"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="editarServicoAtivo" name="ativo">
                                    <label class="form-check-label" for="editarServicoAtivo">
                                        Servico ativo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Exclusao -->
    <div class="modal fade" id="modalExcluirServico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" class="modal-body-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="excluirServicoId">
                    <div class="modal-header">
                        <h5 class="modal-title">Excluir servico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Deseja mesmo excluir esse servico?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Nao</button>
                        <button type="submit" class="btn btn-danger" id="botaoConfirmarExclusao">Sim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editarModal = document.getElementById('modalEditarServico');
            const excluirModal = document.getElementById('modalExcluirServico');

            document.querySelectorAll('.servico-accordion-toggle').forEach((toggle) => {
                const item = toggle.closest('.servico-accordion-item');
                if (!item) {
                    return;
                }
                const content = item.querySelector('.servico-accordion-content');
                const icon = toggle.querySelector('.servico-accordion-icon');
                if (!content || !icon) {
                    return;
                }

                toggle.addEventListener('click', () => {
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    const newState = !expanded;
                    toggle.setAttribute('aria-expanded', String(newState));
                    content.setAttribute('aria-hidden', String(!newState));
                    icon.textContent = newState ? '-' : '+';
                });
            });

            const preencherModalEdicao = (servico) => {
                document.getElementById('editarServicoId').value = servico.id;
                document.getElementById('editarServicoNome').value = servico.nome || '';
                document.getElementById('editarServicoDuracao').value = servico.duracao || '';
                document.getElementById('editarServicoPreco').value = parseFloat(servico.preco ?? 0).toFixed(2).replace('.', ',');
                document.getElementById('editarServicoCategoria').value = servico.categoria || '';
                document.getElementById('editarServicoDescricao').value = servico.descricao || '';
                document.getElementById('editarServicoAtivo').checked = String(servico.ativo) === '1';

                const inputArquivo = document.getElementById('editarServicoImagem');
                if (inputArquivo) {
                    inputArquivo.value = '';
                }

                const imagemAtualInput = document.getElementById('editarServicoImagemAtual');
                if (imagemAtualInput) {
                    imagemAtualInput.value = servico.imagem || '';
                }

                const imagemInfo = document.getElementById('editarServicoImagemInfo');
                if (imagemInfo) {
                    imagemInfo.textContent = servico.imagem ? `Imagem atual: ${servico.imagem}` : 'Nenhuma imagem cadastrada.';
                }
            };

            const prepararModalExclusao = (servico) => {
                document.getElementById('excluirServicoId').value = servico.id;
            };

            document.querySelectorAll('.servico-card').forEach((card) => {
                const dados = card.dataset.servico ? JSON.parse(card.dataset.servico) : null;
                if (!dados) {
                    return;
                }

                const botaoEditar = card.querySelector('.acao-editar');
                const botaoExcluir = card.querySelector('.acao-excluir');

                if (botaoEditar) {
                    botaoEditar.addEventListener('click', () => preencherModalEdicao(dados));
                }

                if (botaoExcluir) {
                    botaoExcluir.addEventListener('click', () => prepararModalExclusao(dados));
                }
            });

            if (editarModal) {
                editarModal.addEventListener('hidden.bs.modal', () => {
                    const form = editarModal.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                    const imagemInfo = document.getElementById('editarServicoImagemInfo');
                    if (imagemInfo) {
                        imagemInfo.textContent = '';
                    }
                });
            }

            if (excluirModal) {
                excluirModal.addEventListener('hidden.bs.modal', () => {
                    excluirModal.querySelector('form').reset();
                });
            }
        });
    </script>
</body>
</html>
