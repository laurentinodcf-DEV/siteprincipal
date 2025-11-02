<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

$diretorioImagensDepoimentos = __DIR__ . '/../../img/depoimentos';
if (!is_dir($diretorioImagensDepoimentos)) {
    mkdir($diretorioImagensDepoimentos, 0775, true);
}
$diretorioImagensDepoimentos = realpath($diretorioImagensDepoimentos) ?: $diretorioImagensDepoimentos;
$webBaseImagensDepoimentos = 'img/depoimentos';
$tamanhoMaximoImagemBytes = 3 * 1024 * 1024; // 3 MB

function tratarUploadImagemDepoimento(string $campo, string $destinoDir, string $webBaseDir, int $tamanhoMaximo, ?string &$erro): ?array
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
        $erro = 'A imagem deve ter no máximo 3MB.';
        return null;
    }

    $mapaMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
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
        $erro = 'Formato de imagem não suportado. Utilize JPG, PNG ou WEBP.';
        return null;
    }

    $nomeArquivo = uniqid('depoimento_', true) . '.' . $extFinal;
    $destinoFisico = rtrim($destinoDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destinoFisico)) {
        $erro = 'Não foi possível salvar a imagem enviada.';
        return null;
    }

    $webBaseNormalizado = rtrim(str_replace('\\', '/', $webBaseDir), '/');

    return [
        'web'    => $webBaseNormalizado . '/' . $nomeArquivo,
        'fisico' => $destinoFisico,
    ];
}

function removerImagemDepoimento(?string $webPath, string $destinoDir, string $webBaseDir): void
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $id_cliente = (int) ($_POST['id_cliente'] ?? 0);
        $estrelas = (int) ($_POST['estrelas'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id_cliente <= 0) {
            $mensagemErro = 'Selecione um cliente.';
        } elseif ($titulo === '') {
            $mensagemErro = 'Informe o título do depoimento.';
        } elseif ($descricao === '') {
            $mensagemErro = 'Informe a descrição do depoimento.';
        } elseif ($estrelas < 0 || $estrelas > 5) {
            $mensagemErro = 'Selecione uma avaliação de 0 a 5 estrelas.';
        } else {
            // Verificar se o cliente já tem imagem
            $stmtCliente = $conn->prepare('SELECT imagem FROM salao_clientes WHERE id = ?');
            if ($stmtCliente === false) {
                $mensagemErro = 'Erro ao verificar dados do cliente.';
            } else {
                $stmtCliente->bind_param('i', $id_cliente);
                if ($stmtCliente->execute()) {
                    $stmtCliente->bind_result($imagemCliente);
                    if (!$stmtCliente->fetch()) {
                        $mensagemErro = 'Cliente não encontrado.';
                    } else {
                        // Só permitir upload se cliente não tem imagem
                        $uploadErro = null;
                        $infoUpload = null;
                        
                        if (empty($imagemCliente)) {
                            $infoUpload = tratarUploadImagemDepoimento(
                                'imagem_reserva',
                                $diretorioImagensDepoimentos,
                                $webBaseImagensDepoimentos,
                                $tamanhoMaximoImagemBytes,
                                $uploadErro
                            );
                            
                            if ($uploadErro !== null) {
                                $mensagemErro = $uploadErro;
                            }
                        }

                        if ($mensagemErro === '') {
                            $imagemReserva = null;
                            if ($infoUpload !== null) {
                                $imagemReserva = $infoUpload['web'] ?? null;
                            }

                            $stmtCliente->close(); // Fechar antes de preparar nova query
                            
                            // Obter a próxima ordem (maior ordem + 1)
                            $proximaOrdem = 1;
                            $resultadoOrdem = $conn->query('SELECT MAX(ordem) as max_ordem FROM salao_depoimentos WHERE ordem IS NOT NULL');
                            if ($resultadoOrdem) {
                                $linhaOrdem = $resultadoOrdem->fetch_assoc();
                                if ($linhaOrdem['max_ordem'] !== null) {
                                    $proximaOrdem = (int) $linhaOrdem['max_ordem'] + 1;
                                }
                                $resultadoOrdem->free();
                            }
                            
                            $stmt = $conn->prepare(
                                'INSERT INTO salao_depoimentos (id_cliente, estrelas, titulo, descricao, imagem_reserva, ativo, ordem)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)'
                            );

                            if ($stmt === false) {
                                $mensagemErro = 'Erro ao preparar inserção.';
                                if ($infoUpload && $infoUpload['fisico']) {
                                    @unlink($infoUpload['fisico']);
                                }
                            } else {
                                $stmt->bind_param('iisssii', $id_cliente, $estrelas, $titulo, $descricao, $imagemReserva, $ativo, $proximaOrdem);

                                if ($stmt->execute()) {
                                    $mensagemSucesso = 'Depoimento cadastrado com sucesso.';
                                } else {
                                    $mensagemErro = 'Erro ao inserir depoimento: ' . $stmt->error;
                                    if ($infoUpload && $infoUpload['fisico']) {
                                        @unlink($infoUpload['fisico']);
                                    }
                                }

                                $stmt->close();
                            }
                        }
                    }
                } else {
                    $mensagemErro = 'Erro ao verificar dados do cliente.';
                }
                // Não fechar aqui pois já foi fechado acima
            }
        }
    } elseif ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $id_cliente = (int) ($_POST['id_cliente'] ?? 0);
        $estrelas = (int) ($_POST['estrelas'] ?? 0);
        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0 || $id_cliente <= 0) {
            $mensagemErro = 'Depoimento ou cliente inválido.';
        } elseif ($titulo === '') {
            $mensagemErro = 'Informe o título do depoimento.';
        } elseif ($descricao === '') {
            $mensagemErro = 'Informe a descrição do depoimento.';
        } elseif ($estrelas < 0 || $estrelas > 5) {
            $mensagemErro = 'Selecione uma avaliação de 0 a 5 estrelas.';
        } else {
            // Buscar dados atuais do depoimento
            $stmtBusca = $conn->prepare('SELECT imagem_reserva FROM salao_depoimentos WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar depoimento para edição.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemReservaAtual);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Depoimento não encontrado.';
                    } else {
                        $stmtBusca->close(); // Fechar antes de preparar nova query
                        
                        // Verificar se o cliente tem imagem
                        $stmtCliente = $conn->prepare('SELECT imagem FROM salao_clientes WHERE id = ?');
                        if ($stmtCliente === false) {
                            $mensagemErro = 'Erro ao verificar dados do cliente.';
                        } else {
                            $stmtCliente->bind_param('i', $id_cliente);
                            if ($stmtCliente->execute()) {
                                $stmtCliente->bind_result($imagemCliente);
                                if (!$stmtCliente->fetch()) {
                                    $mensagemErro = 'Cliente não encontrado.';
                                } else {
                                    // Só permitir upload se cliente não tem imagem
                                    $uploadErro = null;
                                    $infoUpload = null;
                                    $imagemReserva = $imagemReservaAtual;
                                    
                                    if (empty($imagemCliente)) {
                                        $infoUpload = tratarUploadImagemDepoimento(
                                            'imagem_reserva',
                                            $diretorioImagensDepoimentos,
                                            $webBaseImagensDepoimentos,
                                            $tamanhoMaximoImagemBytes,
                                            $uploadErro
                                        );
                                        
                                        if ($uploadErro !== null) {
                                            $mensagemErro = $uploadErro;
                                        } elseif ($infoUpload !== null) {
                                            $imagemReserva = $infoUpload['web'] ?? null;
                                        }
                                    }

                                    if ($mensagemErro === '') {
                                        $stmtCliente->close(); // Fechar antes de preparar nova query
                                        
                                        // Gerenciar ordem baseado no status ativo
                                        $novaOrdem = null;
                                        if ($ativo == 1) {
                                            // Se está ativando, verificar se já tem ordem
                                            $stmtVerificarOrdem = $conn->prepare('SELECT ordem FROM salao_depoimentos WHERE id = ?');
                                            $stmtVerificarOrdem->bind_param('i', $id);
                                            $stmtVerificarOrdem->execute();
                                            $stmtVerificarOrdem->bind_result($ordemAtual);
                                            $stmtVerificarOrdem->fetch();
                                            $stmtVerificarOrdem->close();
                                            
                                            if ($ordemAtual === null) {
                                                // Não tem ordem, atribuir próxima ordem
                                                $resultadoOrdem = $conn->query('SELECT MAX(ordem) as max_ordem FROM salao_depoimentos WHERE ordem IS NOT NULL');
                                                if ($resultadoOrdem) {
                                                    $linhaOrdem = $resultadoOrdem->fetch_assoc();
                                                    $novaOrdem = $linhaOrdem['max_ordem'] !== null ? (int) $linhaOrdem['max_ordem'] + 1 : 1;
                                                    $resultadoOrdem->free();
                                                }
                                            } else {
                                                // Já tem ordem, manter
                                                $novaOrdem = $ordemAtual;
                                            }
                                        } else {
                                            // Se está desativando, ordem = null
                                            $novaOrdem = null;
                                        }
                                        
                                        $stmt = $conn->prepare(
                                            'UPDATE salao_depoimentos
                                             SET id_cliente = ?, estrelas = ?, titulo = ?, descricao = ?, imagem_reserva = ?, ativo = ?, ordem = ?
                                             WHERE id = ?'
                                        );

                                        if ($stmt === false) {
                                            $mensagemErro = 'Erro ao preparar atualização.';
                                            if ($infoUpload && $infoUpload['fisico']) {
                                                @unlink($infoUpload['fisico']);
                                            }
                                        } else {
                                            $stmt->bind_param('iisssiii', $id_cliente, $estrelas, $titulo, $descricao, $imagemReserva, $ativo, $novaOrdem, $id);

                                            if ($stmt->execute()) {
                                                $mensagemSucesso = 'Depoimento atualizado.';
                                                if ($infoUpload !== null) {
                                                    removerImagemDepoimento($imagemReservaAtual, $diretorioImagensDepoimentos, $webBaseImagensDepoimentos);
                                                }
                                            } else {
                                                $mensagemErro = 'Erro ao atualizar depoimento: ' . $stmt->error;
                                                if ($infoUpload && $infoUpload['fisico']) {
                                                    @unlink($infoUpload['fisico']);
                                                }
                                            }

                                            $stmt->close();
                                        }
                                    }
                                }
                            } else {
                                $mensagemErro = 'Erro ao verificar dados do cliente.';
                            }
                            // Não fechar aqui pois já foi fechado acima
                        }
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar depoimento para edição.';
                    $stmtBusca->close();
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $mensagemErro = 'Depoimento inválido para exclusão.';
        } else {
            $imagemRemover = null;
            $stmtBusca = $conn->prepare('SELECT imagem_reserva FROM salao_depoimentos WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar depoimento para exclusão.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemRemover);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Depoimento não encontrado.';
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar depoimento para exclusão.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $stmt = $conn->prepare('DELETE FROM salao_depoimentos WHERE id = ?');
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar exclusão.';
                } else {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Depoimento removido.';
                        removerImagemDepoimento($imagemRemover, $diretorioImagensDepoimentos, $webBaseImagensDepoimentos);
                    } else {
                        $mensagemErro = 'Erro ao excluir depoimento: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Buscar depoimentos
$depoimentos = [];
$resultado = $conn->query(
    'SELECT d.id, d.id_cliente, d.estrelas, d.titulo, d.descricao, d.imagem_reserva, d.ativo, d.ordem, c.nome as cliente_nome, c.imagem as cliente_imagem
     FROM salao_depoimentos d
     INNER JOIN salao_clientes c ON d.id_cliente = c.id
     ORDER BY COALESCE(d.ordem, 2147483647), d.data_criacao DESC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['id_cliente'] = (int) $linha['id_cliente'];
        $linha['estrelas'] = (int) $linha['estrelas'];
        $linha['ativo'] = isset($linha['ativo']) ? (int) $linha['ativo'] : 1;
        $linha['ordem'] = isset($linha['ordem']) ? (int) $linha['ordem'] : null;
        $depoimentos[] = $linha;
    }
    $resultado->free();
}

// Buscar clientes para o select
$clientes = [];
$resultadoClientes = $conn->query('SELECT id, nome FROM salao_clientes ORDER BY nome ASC');
if ($resultadoClientes) {
    while ($linha = $resultadoClientes->fetch_assoc()) {
        $clientes[] = $linha;
    }
    $resultadoClientes->free();
}

function renderizarDepoimentoCard(array $d): void {
    $imgSrc = '';
    // Usar imagem do cliente se disponível, senão usar imagem_reserva
    if (!empty($d['cliente_imagem'])) {
        $p = str_replace('\\', '/', trim((string) $d['cliente_imagem']));
        if ($p !== '') {
            if (strpos($p, 'img/') === 0) {
                $imgSrc = '../../' . $p;
            } else {
                $imgSrc = '../../img/clientes/imgcadastro/' . ltrim($p, '/');
            }
        }
    } elseif (!empty($d['imagem_reserva'])) {
        $p = str_replace('\\', '/', trim((string) $d['imagem_reserva']));
        if ($p !== '') {
            if (strpos($p, 'img/') === 0) {
                $imgSrc = '../../' . $p;
            } else {
                $imgSrc = '../../img/depoimentos/' . ltrim($p, '/');
            }
        }
    }
    ?>
    <article class="servico-accordion-item">
        <header class="servico-accordion-header">
            <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                <span class="servico-accordion-title">
                    <strong><?= htmlspecialchars($d['titulo'] ?? 'Sem título', ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
                <span class="servico-status-pill <?= (int) ($d['ativo'] ?? 1) === 1 ? 'ativo' : 'inativo'; ?>">
                    <?= (int) ($d['ativo'] ?? 1) === 1 ? 'Ativo' : 'Inativo'; ?>
                </span>
                <span class="servico-accordion-icon">+</span>
            </button>
        </header>
        <div class="servico-accordion-content" aria-hidden="true">
            <div class="servico-card" data-depoimento='<?= htmlspecialchars(json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>'>
                <div class="servico-card-inner">
                    <div class="servico-card-info">
                        <header class="servico-card-top">
                            <div>
                                <h3 class="servico-nome"><?= htmlspecialchars($d['titulo'] ?? 'Sem título', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <span class="servico-categoria-pill">Cliente: <?= htmlspecialchars($d['cliente_nome'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </header>

                        <dl class="servico-propriedades">
                            <div>
                                <dt>Avaliação:</dt>
                                <dd>
                                    <?php 
                                    $estrelas = (int) ($d['estrelas'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++): 
                                    ?>
                                        <span class="estrela <?= $i <= $estrelas ? 'preenchida' : 'vazia'; ?>">★</span>
                                    <?php endfor; ?>
                                    (<?= $estrelas; ?> estrela<?= $estrelas !== 1 ? 's' : ''; ?>)
                                </dd>
                            </div>
                            <div>
                                <dt>Descrição:</dt>
                                <dd><?= htmlspecialchars($d['descricao'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Ordem:</dt>
                                <dd><?= $d['ordem'] !== null ? '#' . $d['ordem'] : '—'; ?></dd>
                            </div>
                        </dl>
                        <div class="servico-card-acoes">
                            <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarDepoimento">Editar</button>
                            <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirDepoimento">Excluir</button>
                        </div>
                    </div>
                    <figure class="servico-card-imagem" style="display:flex; align-items:center; justify-content:center;">
                        <?php if ($imgSrc !== ''): ?>
                            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto do cliente" style="max-width:100%; height:auto; display:block; border-radius:50%; width:80px; height:80px; object-fit:cover;">
                        <?php else: ?>
                            <div class="video-link-placeholder" style="text-align:center; padding:16px;">Sem imagem</div>
                        <?php endif; ?>
                    </figure>
                </div>
            </div>
        </div>
    </article>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Depoimentos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/styleProjet.css">
    <link rel="stylesheet" href="../../css/estilo.css">
    <style>
        body {
            margin: 0;
            padding: 32px;
            font-family: "Segoe UI", "Roboto", sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .depoimentos-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        .depoimento-form-section,
        .depoimento-lista-section {
            background: #fff;
            border-radius: 18px;
            padding: 28px 32px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }
        .depoimento-form-section h2,
        .depoimento-lista-section h2 {
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
        .invalid-feedback {
            display: block;
            font-size: 0.875rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        /* Estrelas */
        .estrela {
            font-size: 1.2rem;
            margin-right: 2px;
        }
        .estrela.preenchida {
            color: #fbbf24;
        }
        .estrela.vazia {
            color: transparent;
            text-shadow: 0 0 0 #374151;
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
        
        /* Estilo personalizado para modais de depoimentos */
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
    <div class="depoimentos-wrapper">
        <section class="depoimento-form-section">
            <h2>Novo depoimento</h2>
            <form method="post" class="depoimento-form card" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Cliente*</label>
                        <select name="id_cliente" class="form-select" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id']; ?>"><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Avaliação (0-5 estrelas)*</label>
                        <select name="estrelas" class="form-select" required>
                            <option value="0">0 estrelas (sem avaliação)</option>
                            <option value="1">1 estrela</option>
                            <option value="2">2 estrelas</option>
                            <option value="3">3 estrelas</option>
                            <option value="4">4 estrelas</option>
                            <option value="5">5 estrelas (máxima)</option>
                        </select>
                        <small class="text-muted">Selecione de 0 (sem estrelas) a 5 (máxima avaliação)</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Título*</label>
                        <input type="text" name="titulo" class="form-control" required maxlength="255" autocomplete="off" value="<?= htmlspecialchars($_POST['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição*</label>
                        <textarea name="descricao" class="form-control" required rows="4" maxlength="1000" placeholder="Descreva o depoimento do cliente..."><?= htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Imagem (até 3 MB)</label>
                        <input type="file" name="imagem_reserva" class="form-control" accept="image/*" data-bs-toggle="tooltip" title="PNG, JPG ou WEBP até 3 MB">
                        <small class="text-muted">Apenas será solicitado se o cliente não possuir imagem cadastrada</small>
                    </div>
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="novoDepoimentoAtivo" name="ativo" checked>
                            <label class="form-check-label" for="novoDepoimentoAtivo">
                                Depoimento ativo
                            </label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar depoimento</button>
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

        <section class="depoimento-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="secao-titulo">Depoimentos cadastrados</h2>
                <span class="texto-suave"><?= count($depoimentos); ?> depoimento(s) no sistema</span>
            </div>

            <?php 
            $depoimentosAtivos = [];
            $depoimentosInativos = [];
            foreach ($depoimentos as $dep) {
                if ((int) ($dep['ativo'] ?? 1) === 1) {
                    $depoimentosAtivos[] = $dep;
                } else {
                    $depoimentosInativos[] = $dep;
                }
            }
            ?>

            <?php if (empty($depoimentos)): ?>
                <div class="alert alert-info">Nenhum depoimento cadastrado até o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Depoimentos ativos</h3>
                        <span class="texto-suave"><?= count($depoimentosAtivos); ?> ativo(s)</span>
                    </div>
                    <?php if (empty($depoimentosAtivos)): ?>
                        <div class="alert alert-info">Nenhum depoimento ativo cadastrado.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($depoimentosAtivos as $d) { renderizarDepoimentoCard($d); } ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="servico-subsecao mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Depoimentos inativos</h3>
                        <span class="texto-suave"><?= count($depoimentosInativos); ?> inativo(s)</span>
                    </div>
                    <?php if (empty($depoimentosInativos)): ?>
                        <div class="alert alert-info">Nenhum depoimento marcado como inativo.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($depoimentosInativos as $d) { renderizarDepoimentoCard($d); } ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Modal Edição -->
        <div class="modal fade" id="modalEditarDepoimento" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editarDepoimentoId">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar depoimento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Cliente*</label>
                                    <select name="id_cliente" class="form-select" id="editarDepoimentoCliente" required>
                                        <option value="">Selecione um cliente</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?= $cliente['id']; ?>"><?= htmlspecialchars($cliente['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Avaliação (0-5 estrelas)*</label>
                                    <select name="estrelas" class="form-select" id="editarDepoimentoEstrelas" required>
                                        <option value="0">0 estrelas (sem avaliação)</option>
                                        <option value="1">1 estrela</option>
                                        <option value="2">2 estrelas</option>
                                        <option value="3">3 estrelas</option>
                                        <option value="4">4 estrelas</option>
                                        <option value="5">5 estrelas (máxima)</option>
                                    </select>
                                    <small class="text-muted">Selecione de 0 (sem estrelas) a 5 (máxima avaliação)</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Título*</label>
                                    <input type="text" name="titulo" class="form-control" id="editarDepoimentoTitulo" required maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Descrição*</label>
                                    <textarea name="descricao" class="form-control" id="editarDepoimentoDescricao" required rows="4" maxlength="1000"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Imagem (até 3 MB)</label>
                                    <input type="file" name="imagem_reserva" class="form-control" id="editarDepoimentoImagem" accept="image/*">
                                    <input type="hidden" name="imagem_atual" id="editarDepoimentoImagemAtual">
                                    <small class="form-text text-muted" id="editarDepoimentoImagemInfo"></small>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="editarDepoimentoAtivo" name="ativo">
                                        <label class="form-check-label" for="editarDepoimentoAtivo">
                                            Depoimento ativo
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

        <!-- Modal Exclusão -->
        <div class="modal fade" id="modalExcluirDepoimento" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="excluirDepoimentoId">
                        <div class="modal-header">
                            <h5 class="modal-title">Excluir depoimento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Deseja realmente excluir este depoimento?</p>
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
            // Tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) { new bootstrap.Tooltip(tooltipTriggerEl); });

            // Verificar se cliente tem imagem ao selecionar
            const selectCliente = document.querySelector('select[name="id_cliente"]');
            const inputImagem = document.querySelector('input[name="imagem_reserva"]');
            
            if (selectCliente && inputImagem) {
                selectCliente.addEventListener('change', function() {
                    const clienteId = this.value;
                    if (clienteId) {
                        // Fazer requisição AJAX para verificar se cliente tem imagem
                        fetch(`check_cliente_imagem.php?cliente_id=${clienteId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.tem_imagem) {
                                    inputImagem.disabled = true;
                                    inputImagem.placeholder = 'Cliente já possui imagem cadastrada';
                                } else {
                                    inputImagem.disabled = false;
                                    inputImagem.placeholder = 'PNG, JPG ou WEBP até 3 MB';
                                }
                            })
                            .catch(error => {
                                console.error('Erro ao verificar imagem do cliente:', error);
                            });
                    } else {
                        inputImagem.disabled = false;
                        inputImagem.placeholder = 'PNG, JPG ou WEBP até 3 MB';
                    }
                });
            }

            // Acordeon
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

            // Modais
            const editarModal = document.getElementById('modalEditarDepoimento');
            const excluirModal = document.getElementById('modalExcluirDepoimento');

            const preencherModalEdicao = (depoimento) => {
                document.getElementById('editarDepoimentoId').value = depoimento.id;
                document.getElementById('editarDepoimentoCliente').value = depoimento.id_cliente;
                document.getElementById('editarDepoimentoTitulo').value = depoimento.titulo || '';
                document.getElementById('editarDepoimentoDescricao').value = depoimento.descricao || '';
                document.getElementById('editarDepoimentoAtivo').checked = String(depoimento.ativo) === '1';

                // Selecionar estrelas
                const estrelas = parseInt(depoimento.estrelas) || 0;
                const selectEstrelas = document.getElementById('editarDepoimentoEstrelas');
                if (selectEstrelas) {
                    selectEstrelas.value = estrelas;
                }

                // Verificar se cliente tem imagem para o modal de edição
                const selectClienteEdicao = document.getElementById('editarDepoimentoCliente');
                const inputImagemEdicao = document.getElementById('editarDepoimentoImagem');
                
                if (selectClienteEdicao && inputImagemEdicao) {
                    const clienteId = depoimento.id_cliente;
                    fetch(`check_cliente_imagem.php?cliente_id=${clienteId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.tem_imagem) {
                                inputImagemEdicao.disabled = true;
                                inputImagemEdicao.placeholder = 'Cliente já possui imagem cadastrada';
                            } else {
                                inputImagemEdicao.disabled = false;
                                inputImagemEdicao.placeholder = 'PNG, JPG ou WEBP até 3 MB';
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao verificar imagem do cliente:', error);
                        });
                }

                const inputArquivo = document.getElementById('editarDepoimentoImagem');
                if (inputArquivo) { inputArquivo.value = ''; }

                const imagemAtualInput = document.getElementById('editarDepoimentoImagemAtual');
                if (imagemAtualInput) { imagemAtualInput.value = depoimento.imagem_reserva || ''; }

                const imagemInfo = document.getElementById('editarDepoimentoImagemInfo');
                if (imagemInfo) {
                    imagemInfo.textContent = depoimento.imagem_reserva ? 'Imagem atual: ' + depoimento.imagem_reserva : 'Nenhuma imagem cadastrada.';
                }
            };

            const prepararModalExclusao = (depoimento) => {
                document.getElementById('excluirDepoimentoId').value = depoimento.id;
            };

            document.querySelectorAll('.servico-card').forEach((card) => {
                const dados = card.dataset.depoimento ? JSON.parse(card.dataset.depoimento) : null;
                if (!dados) { return; }

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
                    if (form) { form.reset(); }
                    const imagemInfo = document.getElementById('editarDepoimentoImagemInfo');
                    if (imagemInfo) { imagemInfo.textContent = ''; }
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
