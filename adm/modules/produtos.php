<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

$diretorioImagensProdutos = __DIR__ . '/../../img/produtos/imgcadastro';
if (!is_dir($diretorioImagensProdutos)) {
    mkdir($diretorioImagensProdutos, 0775, true);
}
$diretorioImagensProdutos = realpath($diretorioImagensProdutos) ?: $diretorioImagensProdutos;
$webBaseImagensProdutos = 'img/produtos/imgcadastro';
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

function tratarUploadImagemProduto(string $campo, string $destinoDir, string $webBaseDir, int $tamanhoMaximo, ?string &$erro): ?array
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
        $erro = 'A imagem deve ter no mximo 2MB.';
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
        $erro = 'Formato de imagem no suportado. Utilize JPG, PNG, GIF ou WEBP.';
        return null;
    }

    $nomeArquivo = uniqid('produto_', true) . '.' . $extFinal;
    $destinoFisico = rtrim($destinoDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destinoFisico)) {
        $erro = 'No foi possvel salvar a imagem enviada.';
        return null;
    }

    $webBaseNormalizado = rtrim(str_replace('\\', '/', $webBaseDir), '/');

    return [
        'web'    => $webBaseNormalizado . '/' . $nomeArquivo,
        'fisico' => $destinoFisico,
    ];
}

function removerImagemProduto(?string $webPath, string $destinoDir, string $webBaseDir): void
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

function obterProximaOrdemProduto(mysqli $conn): int
{
    $resultado = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM salao_produtos WHERE ativo = 1');
    if ($resultado) {
        $linha = $resultado->fetch_assoc();
        $resultado->free();
        return (int) ($linha['proxima'] ?? 1);
    }
    return 1;
}

$categoriasProduto = [];
$resultadoCategorias = $conn->query('SELECT id, nome FROM salao_categorias_produtos WHERE ativo = 1 ORDER BY COALESCE(ordem, 2147483647), nome ASC');
if ($resultadoCategorias) {
    while ($cat = $resultadoCategorias->fetch_assoc()) {
        $categoriasProduto[] = $cat;
    }
    $resultadoCategorias->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $categoriaId = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? (int) $_POST['categoria_id'] : null;
        $preco = normalizarPreco((string) ($_POST['preco'] ?? '0'));
        $precoPromocional = trim((string) ($_POST['preco_promocional'] ?? ''));
        $precoPromocional = $precoPromocional === '' ? null : normalizarPreco($precoPromocional);
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $estoque = max(0, (int) ($_POST['estoque'] ?? 0));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '' || $preco < 0) {
            $mensagemErro = 'Informe pelo menos o nome do produto e um preo vlido.';
        } else {
            $uploadErro = null;
            $infoUpload = tratarUploadImagemProduto(
                'imagem',
                $diretorioImagensProdutos,
                $webBaseImagensProdutos,
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

                $ordemParam = $ativo === 1 ? obterProximaOrdemProduto($conn) : null;

                $stmt = $conn->prepare(
                    'INSERT INTO salao_produtos (categoria_id, nome, descricao, preco, preco_promocional, sku, estoque, imagem, ativo, ordem)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar insero.';
                    if ($imagemFisicaNova) {
                        @unlink($imagemFisicaNova);
                    }
                } else {
                    $descricaoParam = $descricao !== '' ? $descricao : null;
                    $skuParam = $sku !== '' ? $sku : null;

                    $stmt->bind_param(
                        'issddsisii',
                        $categoriaId,
                        $nome,
                        $descricaoParam,
                        $preco,
                        $precoPromocional,
                        $skuParam,
                        $estoque,
                        $imagemWebPath,
                        $ativo,
                        $ordemParam
                    );

                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Produto cadastrado com sucesso.';
                    } else {
                        $mensagemErro = 'Erro ao inserir produto: ' . $stmt->error;
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
        $categoriaId = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? (int) $_POST['categoria_id'] : null;
        $preco = normalizarPreco((string) ($_POST['preco'] ?? '0'));
        $precoPromocional = trim((string) ($_POST['preco_promocional'] ?? ''));
        $precoPromocional = $precoPromocional === '' ? null : normalizarPreco($precoPromocional);
        $sku = trim((string) ($_POST['sku'] ?? ''));
        $estoque = max(0, (int) ($_POST['estoque'] ?? 0));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0 || $nome === '' || $preco < 0) {
            $mensagemErro = 'Produto invlido ou dados obrigatrios faltando.';
        } else {
            $imagemAtual = null;
            $ativoAnterior = 0;
            $ordemAtual = null;

            $stmtBusca = $conn->prepare('SELECT imagem, ativo, ordem FROM salao_produtos WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar produto para edio.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemAtual, $ativoAnterior, $ordemAtual);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Produto no encontrado.';
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar produto para edio.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $uploadErro = null;
                $infoUpload = tratarUploadImagemProduto(
                    'imagem',
                    $diretorioImagensProdutos,
                    $webBaseImagensProdutos,
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
                            $ordemParam = obterProximaOrdemProduto($conn);
                        }
                    }

                    $stmt = $conn->prepare(
                        'UPDATE salao_produtos
                         SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, preco_promocional = ?, sku = ?, estoque = ?, imagem = ?, ativo = ?, ordem = ?
                         WHERE id = ?'
                    );

                    if ($stmt === false) {
                        $mensagemErro = 'Erro ao preparar atualizao.';
                        if ($imagemFisicaNova) {
                            @unlink($imagemFisicaNova);
                        }
                    } else {
                        $descricaoParam = $descricao !== '' ? $descricao : null;
                        $skuParam = $sku !== '' ? $sku : null;

                        $stmt->bind_param(
                            'issddsisiii',
                            $categoriaId,
                            $nome,
                            $descricaoParam,
                            $preco,
                            $precoPromocional,
                            $skuParam,
                            $estoque,
                            $imagemWebPath,
                            $ativo,
                            $ordemParam,
                            $id
                        );

                        if ($stmt->execute()) {
                            $mensagemSucesso = 'Produto atualizado.';
                            if ($infoUpload !== null) {
                                removerImagemProduto($imagemAtual, $diretorioImagensProdutos, $webBaseImagensProdutos);
                            }
                        } else {
                            $mensagemErro = 'Erro ao atualizar produto: ' . $stmt->error;
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
            $mensagemErro = 'Produto invlido para excluso.';
        } else {
            $imagemRemover = null;
            $stmtBusca = $conn->prepare('SELECT imagem FROM salao_produtos WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar produto para excluso.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemRemover);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Produto no encontrado.';
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar produto para excluso.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $stmt = $conn->prepare('DELETE FROM salao_produtos WHERE id = ?');
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar excluso.';
                } else {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Produto removido.';
                        removerImagemProduto($imagemRemover, $diretorioImagensProdutos, $webBaseImagensProdutos);
                    } else {
                        $mensagemErro = 'Erro ao excluir produto: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$produtos = [];
$resultado = $conn->query(
    'SELECT p.*, c.nome AS categoria_nome
     FROM salao_produtos p
     LEFT JOIN salao_categorias_produtos c ON c.id = p.categoria_id
     ORDER BY COALESCE(p.ordem, 2147483647), p.nome ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $produtos[] = $linha;
    }
    $resultado->free();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Produtos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/estilo.css">
    <style>
        body {
            margin: 0;
            padding: 32px;
            font-family: "Segoe UI", "Roboto", sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .produtos-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        .produto-form-section,
        .produto-lista-section {
            background: #fff;
            border-radius: 18px;
            padding: 28px 32px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }
        .produto-form-section h2,
        .produto-lista-section h2 {
            margin-bottom: 16px;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .botao-salvar {
            background: #2563eb;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 999px;
            padding: 10px 24px;
            cursor: pointer;
        }
        .botao-salvar:hover {
            background: #1d4ed8;
        }
        .texto-suave {
            color: #64748b;
        }
        
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
        
        /* Estilo personalizado para modais de produtos */
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
<body>
    <div class="produtos-wrapper">
        <section class="produto-form-section">
            <h2>Novo produto</h2>
            <form method="post" class="produto-form card" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome*</label>
                        <input type="text" name="nome" class="form-control" required maxlength="150" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Preo*</label>
                        <input type="text" name="preco" class="form-control" required placeholder="Ex: 199,90">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Preo promocional</label>
                        <input type="text" name="preco_promocional" class="form-control" placeholder="Ex: 149,90">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Categoria</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categoriasProduto as $categoria): ?>
                                <option value="<?= (int) $categoria['id']; ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" maxlength="50" placeholder="Identificador do produto">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estoque</label>
                        <input type="number" name="estoque" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrio</label>
                        <textarea name="descricao" class="form-control" rows="4" placeholder="Detalhes do produto"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Imagem principal</label>
                        <input type="file" name="imagem" class="form-control" accept="image/*">
                        <small class="form-text text-muted">Tamanho mximo: 2 MB.</small>
                    </div>
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="novoProdutoAtivo" name="ativo" checked>
                            <label class="form-check-label" for="novoProdutoAtivo">
                                Produto ativo
                            </label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar produto</button>
                    </div>
                </div>
            </form>
        </section>

        <?php if ($mensagemSucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="produto-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="secao-titulo">Produtos cadastrados</h2>
                <span class="texto-suave"><?= count($produtos); ?> produto(s) no sistema</span>
            </div>

            <?php 
            $produtosAtivos = [];
            $produtosInativos = [];
            foreach ($produtos as $prod) {
                if ((int) ($prod['ativo'] ?? 1) === 1) {
                    $produtosAtivos[] = $prod;
                } else {
                    $produtosInativos[] = $prod;
                }
            }
            ?>

            <?php if (empty($produtos)): ?>
                <div class="alert alert-info">Nenhum produto cadastrado at o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Produtos ativos</h3>
                        <span class="texto-suave"><?= count($produtosAtivos); ?> ativo(s)</span>
                    </div>
                    <?php if (empty($produtosAtivos)): ?>
                        <div class="alert alert-info">Nenhum produto ativo cadastrado.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($produtosAtivos as $produto): ?>
                        <?php
                            $produtoJson = htmlspecialchars(
                                json_encode($produto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            $imagemSrc = '';
                            if (!empty($produto['imagem'])) {
                                $imagemValor = (string) $produto['imagem'];
                                if (preg_match('/^(https?:)?\/\//i', $imagemValor)) {
                                    $imagemSrc = $imagemValor;
                                } elseif (strpos($imagemValor, '../') === 0 || strpos($imagemValor, '../../') === 0) {
                                    $imagemSrc = $imagemValor;
                                } elseif ($imagemValor !== '' && $imagemValor[0] === '/') {
                                    $imagemSrc = $imagemValor;
                                } else {
                                    $imagemSrc = '../../' . ltrim($imagemValor, '/');
                                }
                            }

                            $temImagem = $imagemSrc !== '';
                            $descricaoFormatada = !empty($produto['descricao'])
                                ? nl2br(htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8'))
                                : '<span class="texto-suave">Sem descrio cadastrada.</span>';
                            $temPromo = $produto['preco_promocional'] !== null && (float) $produto['preco_promocional'] > 0;
                        ?>
                        <article class="servico-accordion-item" data-produto='<?= $produtoJson; ?>'>
                            <header class="servico-accordion-header">
                                <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                                    <span class="servico-accordion-title">
                                        <strong><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </span>
                                    <span class="servico-status-pill <?= $produto['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?= $produto['ativo'] ? 'Ativo' : 'Inativo'; ?>
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
                                                    <h3 class="servico-nome"><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                    <?php if (!empty($produto['categoria_nome'])): ?>
                                                        <span class="servico-categoria-pill"><?= htmlspecialchars($produto['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </header>

                                            <dl class="servico-propriedades">
                                                <div>
                                                    <dt>Preo:</dt>
                                                    <dd class="servico-propriedade-valor"><?= 'R$ ' . number_format((float) $produto['preco'], 2, ',', '.'); ?></dd>
                                                </div>
                                                <div>
                                                    <dt>Promoo:</dt>
                                                    <dd><?= $temPromo ? 'R$ ' . number_format((float) $produto['preco_promocional'], 2, ',', '.') : ''; ?></dd>
                                                </div>
                                                <div>
                                                    <dt>SKU:</dt>
                                                    <dd><?= $produto['sku'] !== null && $produto['sku'] !== '' ? htmlspecialchars($produto['sku'], ENT_QUOTES, 'UTF-8') : ''; ?></dd>
                                                </div>
                                                <div>
                                                    <dt>Estoque:</dt>
                                                    <dd><?= (int) $produto['estoque']; ?></dd>
                                                </div>
                                                <div class="servico-descricao-bloco">
                                                    <dt>Descrio:</dt>
                                                    <dd class="servico-descricao-texto"><?= $descricaoFormatada; ?></dd>
                                                </div>
                                            </dl>

                                            <div class="servico-card-acoes">
                                                <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarProduto">Editar</button>
                                                <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirProduto">Excluir</button>
                                            </div>
                                        </div>

                                        <?php if ($temImagem): ?>
                                            <figure class="servico-card-imagem">
                                                <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                            </figure>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="servico-subsecao mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Produtos inativos</h3>
                        <span class="texto-suave"><?= count($produtosInativos); ?> inativo(s)</span>
                    </div>
                    <?php if (empty($produtosInativos)): ?>
                        <div class="alert alert-info">Nenhum produto marcado como inativo.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($produtosInativos as $produto): ?>
                        <?php
                            $produtoJson = htmlspecialchars(
                                json_encode($produto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            $imagemSrc = '';
                            if (!empty($produto['imagem'])) {
                                $imagemValor = (string) $produto['imagem'];
                                if (preg_match('/^(https?:)?\/\//i', $imagemValor)) {
                                    $imagemSrc = $imagemValor;
                                } elseif (strpos($imagemValor, '../') === 0 || strpos($imagemValor, '../../') === 0) {
                                    $imagemSrc = $imagemValor;
                                } elseif ($imagemValor !== '' && $imagemValor[0] === '/') {
                                    $imagemSrc = $imagemValor;
                                } else {
                                    $imagemSrc = '../../' . ltrim($imagemValor, '/');
                                }
                            }

                            $temImagem = $imagemSrc !== '';
                            $descricaoFormatada = !empty($produto['descricao'])
                                ? nl2br(htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8'))
                                : '<span class="texto-suave">Sem descrio cadastrada.</span>';
                            $temPromo = !empty($produto['preco_promocional']) && (float) $produto['preco_promocional'] > 0;
                        ?>
                        <article class="servico-accordion-item" data-produto='<?= $produtoJson; ?>'>
                            <header class="servico-accordion-header">
                                <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                                    <span class="servico-accordion-title">
                                        <strong><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </span>
                                    <span class="servico-status-pill <?= $produto['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?= $produto['ativo'] ? 'Ativo' : 'Inativo'; ?>
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
                                                    <h3 class="servico-nome"><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                    <?php if (!empty($produto['categoria_nome'])): ?>
                                                        <span class="servico-categoria-pill"><?= htmlspecialchars($produto['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </header>

                                            <dl class="servico-propriedades">
                                                <div>
                                                    <dt>Preo:</dt>
                                                    <dd class="servico-propriedade-valor"><?= 'R$ ' . number_format((float) $produto['preco'], 2, ',', '.'); ?></dd>
                                                </div>
                                                <div>
                                                    <dt>Promoo:</dt>
                                                    <dd><?= $temPromo ? 'R$ ' . number_format((float) $produto['preco_promocional'], 2, ',', '.') : ''; ?></dd>
                                                </div>
                                                <div>
                                                    <dt>SKU:</dt>
                                                    <dd><?= $produto['sku'] !== null && $produto['sku'] !== '' ? htmlspecialchars($produto['sku'], ENT_QUOTES, 'UTF-8') : ''; ?></dd>
                                                </div>
                                                <div>
                                                    <dt>Estoque:</dt>
                                                    <dd><?= (int) $produto['estoque']; ?></dd>
                                                </div>
                                                <div class="servico-descricao-bloco">
                                                    <dt>Descrio:</dt>
                                                    <dd class="servico-descricao-texto"><?= $descricaoFormatada; ?></dd>
                                                </div>
                                            </dl>

                                            <div class="servico-card-acoes">
                                                <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarProduto">Editar</button>
                                                <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirProduto">Excluir</button>
                                            </div>
                                        </div>

                                        <?php if ($temImagem): ?>
                                            <figure class="servico-card-imagem">
                                                <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                            </figure>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>


        <div class="modal fade" id="modalEditarProduto" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editarProdutoId">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar produto</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome*</label>
                                    <input type="text" name="nome" class="form-control" id="editarProdutoNome" required maxlength="150">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Preço*</label>
                                    <input type="text" name="preco" class="form-control" id="editarProdutoPreco" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Preço promocional</label>
                                    <input type="text" name="preco_promocional" class="form-control" id="editarProdutoPrecoPromocional">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Categoria</label>
                                    <select name="categoria_id" class="form-select" id="editarProdutoCategoria">
                                        <option value="">Sem categoria</option>
                                        <?php foreach ($categoriasProduto as $categoria): ?>
                                            <option value="<?= (int) $categoria['id']; ?>"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">SKU</label>
                                    <input type="text" name="sku" class="form-control" id="editarProdutoSku" maxlength="50">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Estoque</label>
                                    <input type="number" name="estoque" class="form-control" id="editarProdutoEstoque" min="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descrição</label>
                                    <textarea name="descricao" class="form-control" id="editarProdutoDescricao" rows="4"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Imagem principal</label>
                                    <input type="file" name="imagem" class="form-control" id="editarProdutoImagem" accept="image/*">
                                    <input type="hidden" name="imagem_atual" id="editarProdutoImagemAtual">
                                    <small class="form-text text-muted" id="editarProdutoImagemInfo"></small>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="editarProdutoAtivo" name="ativo">
                                        <label class="form-check-label" for="editarProdutoAtivo">
                                            Produto ativo
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

        <div class="modal fade" id="modalExcluirProduto" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="excluirProdutoId">
                        <div class="modal-header">
                            <h5 class="modal-title">Excluir produto</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Deseja realmente excluir este produto?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Não</button>
                            <button type="submit" class="btn btn-danger">Sim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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

            const editarModal = document.getElementById('modalEditarProduto');
            const excluirModal = document.getElementById('modalExcluirProduto');

            const preencherModalEdicao = (produto) => {
                document.getElementById('editarProdutoId').value = produto.id;
                document.getElementById('editarProdutoNome').value = produto.nome || '';
                document.getElementById('editarProdutoPreco').value = parseFloat(produto.preco ?? 0).toFixed(2).replace('.', ',');
                document.getElementById('editarProdutoPrecoPromocional').value =
                    produto.preco_promocional ? parseFloat(produto.preco_promocional).toFixed(2).replace('.', ',') : '';
                document.getElementById('editarProdutoCategoria').value = produto.categoria_id || '';
                document.getElementById('editarProdutoSku').value = produto.sku || '';
                document.getElementById('editarProdutoEstoque').value = produto.estoque || 0;
                document.getElementById('editarProdutoDescricao').value = produto.descricao || '';
                document.getElementById('editarProdutoAtivo').checked = String(produto.ativo) === '1';

                const inputArquivo = document.getElementById('editarProdutoImagem');
                if (inputArquivo) {
                    inputArquivo.value = '';
                }

                const imagemAtualInput = document.getElementById('editarProdutoImagemAtual');
                if (imagemAtualInput) {
                    imagemAtualInput.value = produto.imagem || '';
                }

                const imagemInfo = document.getElementById('editarProdutoImagemInfo');
                if (imagemInfo) {
                    imagemInfo.textContent = produto.imagem ? 'Imagem atual: ' + produto.imagem : 'Nenhuma imagem cadastrada.';
                }
            };

            const prepararModalExclusao = (produto) => {
                document.getElementById('excluirProdutoId').value = produto.id;
            };

            document.querySelectorAll('.servico-accordion-item').forEach((item) => {
                const dados = item.dataset.produto ? JSON.parse(item.dataset.produto) : null;
                if (!dados) {
                    return;
                }

                const botaoEditar = item.querySelector('.acao-editar');
                const botaoExcluir = item.querySelector('.acao-excluir');

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
                });
            }

            if (excluirModal) {
                excluirModal.addEventListener('hidden.bs.modal', () => {
                    const form = excluirModal.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                });
            }
        });
    </script>
</body>
</html>
