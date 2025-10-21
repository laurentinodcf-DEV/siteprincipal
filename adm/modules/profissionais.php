<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

$diretorioImagensProfissionais = __DIR__ . '/../../img/profissional/imgcadastro';
if (!is_dir($diretorioImagensProfissionais)) {
    mkdir($diretorioImagensProfissionais, 0775, true);
}
$diretorioImagensProfissionais = realpath($diretorioImagensProfissionais) ?: $diretorioImagensProfissionais;
$webBaseImagensProfissionais = 'img/profissional/imgcadastro';
$tamanhoMaximoImagemBytes = 3 * 1024 * 1024; // 3 MB

function tratarUploadImagemProfissional(string $campo, string $destinoDir, string $webBaseDir, int $tamanhoMaximo, ?string &$erro): ?array
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

    $nomeArquivo = uniqid('profissional_', true) . '.' . $extFinal;
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

function removerImagemProfissional(?string $webPath, string $destinoDir, string $webBaseDir): void
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

// Função para gerenciar serviços do profissional
function gerenciarServicosProfissional($conn, int $profissionalId, array $servicosIds): bool
{
    // Primeiro, remover todos os serviços existentes
    $stmtDelete = $conn->prepare('DELETE FROM salao_profissional_servicos WHERE profissional_id = ?');
    if (!$stmtDelete) {
        return false;
    }
    
    $stmtDelete->bind_param('i', $profissionalId);
    if (!$stmtDelete->execute()) {
        $stmtDelete->close();
        return false;
    }
    $stmtDelete->close();
    
    // Inserir os novos serviços
    if (!empty($servicosIds)) {
        $stmtInsert = $conn->prepare('INSERT INTO salao_profissional_servicos (profissional_id, servico_id) VALUES (?, ?)');
        if (!$stmtInsert) {
            return false;
        }
        
        foreach ($servicosIds as $servicoId) {
            $servicoId = (int) $servicoId;
            $stmtInsert->bind_param('ii', $profissionalId, $servicoId);
            if (!$stmtInsert->execute()) {
                $stmtInsert->close();
                return false;
            }
        }
        $stmtInsert->close();
    }
    
    return true;
}

// Função para buscar serviços do profissional
function buscarServicosProfissional($conn, int $profissionalId): array
{
    $servicos = [];
    $stmt = $conn->prepare('SELECT servico_id FROM salao_profissional_servicos WHERE profissional_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $profissionalId);
        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            while ($linha = $resultado->fetch_assoc()) {
                $servicos[] = (int) $linha['servico_id'];
            }
        }
        $stmt->close();
    }
    return $servicos;
}

// Buscar serviços disponíveis para os checkboxes
$servicos = [];
$resultadoServicos = $conn->query('SELECT id, nome FROM salao_servicos WHERE ativo = 1 ORDER BY nome ASC');
if ($resultadoServicos) {
    while ($linha = $resultadoServicos->fetch_assoc()) {
        $servicos[] = $linha;
    }
    $resultadoServicos->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $servicosIds = $_POST['servicos'] ?? [];
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {
            $mensagemErro = 'Informe o nome do profissional.';
        } else {
            // Validar serviços selecionados
            if (!empty($servicosIds)) {
                $servicosIdsValidos = [];
                foreach ($servicosIds as $servicoId) {
                    $servicoId = (int) $servicoId;
                    if ($servicoId > 0) {
                        $servicosIdsValidos[] = $servicoId;
                    }
                }
                
                if (!empty($servicosIdsValidos)) {
                    $placeholders = str_repeat('?,', count($servicosIdsValidos) - 1) . '?';
                    $stmtValidaServicos = $conn->prepare("SELECT COUNT(*) as total FROM salao_servicos WHERE id IN ($placeholders) AND ativo = 1");
                    if ($stmtValidaServicos) {
                        $types = str_repeat('i', count($servicosIdsValidos));
                        $stmtValidaServicos->bind_param($types, ...$servicosIdsValidos);
                        $stmtValidaServicos->execute();
                        $resultado = $stmtValidaServicos->get_result();
                        $linha = $resultado->fetch_assoc();
                        if ((int) $linha['total'] !== count($servicosIdsValidos)) {
                            $mensagemErro = 'Um ou mais serviços selecionados não são válidos.';
                        }
                        $stmtValidaServicos->close();
                    }
                }
                $servicosIds = $servicosIdsValidos;
            }

            if ($mensagemErro === '') {
                // Buscar próximo número de ordem
                $resultadoOrdem = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM salao_profissionais');
                $proximaOrdem = 1;
                if ($resultadoOrdem) {
                    $linhaOrdem = $resultadoOrdem->fetch_assoc();
                    $proximaOrdem = (int) $linhaOrdem['proxima'];
                    $resultadoOrdem->free();
                }

                $uploadErro = null;
                $infoUpload = tratarUploadImagemProfissional(
                    'foto',
                    $diretorioImagensProfissionais,
                    $webBaseImagensProfissionais,
                    $tamanhoMaximoImagemBytes,
                    $uploadErro
                );

                if ($uploadErro !== null) {
                    $mensagemErro = $uploadErro;
                } else {
                    $fotoWebPath = null;
                    $fotoFisicaNova = null;
                    if ($infoUpload !== null) {
                        $fotoWebPath = $infoUpload['web'] ?? null;
                        $fotoFisicaNova = $infoUpload['fisico'] ?? null;
                    }

                    // Iniciar transação
                    $conn->begin_transaction();
                    
                    try {
                        $stmt = $conn->prepare(
                            'INSERT INTO salao_profissionais (nome, foto, ativo, ordem)
                             VALUES (?, ?, ?, ?)'
                        );

                        if ($stmt === false) {
                            throw new Exception('Erro ao preparar inserção do profissional.');
                        }

                        $fotoParam = $fotoWebPath !== null && $fotoWebPath !== '' ? $fotoWebPath : null;

                        $stmt->bind_param(
                            'ssii',
                            $nome,
                            $fotoParam,
                            $ativo,
                            $proximaOrdem
                        );

                        if (!$stmt->execute()) {
                            throw new Exception('Erro ao inserir profissional: ' . $stmt->error);
                        }
                        
                        $profissionalId = $conn->insert_id;
                        $stmt->close();
                        
                        // Associar serviços ao profissional
                        if (!empty($servicosIds)) {
                            if (!gerenciarServicosProfissional($conn, $profissionalId, $servicosIds)) {
                                throw new Exception('Erro ao associar serviços ao profissional.');
                            }
                        }
                        
                        $conn->commit();
                        $mensagemSucesso = 'Profissional cadastrado com sucesso.';
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $mensagemErro = $e->getMessage();
                        if ($fotoFisicaNova) {
                            @unlink($fotoFisicaNova);
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $servicosIds = $_POST['servicos'] ?? [];
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0 || $nome === '') {
            $mensagemErro = 'Profissional inválido ou dados obrigatórios faltando.';
        } else {
            // Validar serviços selecionados
            if (!empty($servicosIds)) {
                $servicosIdsValidos = [];
                foreach ($servicosIds as $servicoId) {
                    $servicoId = (int) $servicoId;
                    if ($servicoId > 0) {
                        $servicosIdsValidos[] = $servicoId;
                    }
                }
                
                if (!empty($servicosIdsValidos)) {
                    $placeholders = str_repeat('?,', count($servicosIdsValidos) - 1) . '?';
                    $stmtValidaServicos = $conn->prepare("SELECT COUNT(*) as total FROM salao_servicos WHERE id IN ($placeholders) AND ativo = 1");
                    if ($stmtValidaServicos) {
                        $types = str_repeat('i', count($servicosIdsValidos));
                        $stmtValidaServicos->bind_param($types, ...$servicosIdsValidos);
                        $stmtValidaServicos->execute();
                        $resultado = $stmtValidaServicos->get_result();
                        $linha = $resultado->fetch_assoc();
                        if ((int) $linha['total'] !== count($servicosIdsValidos)) {
                            $mensagemErro = 'Um ou mais serviços selecionados não são válidos.';
                        }
                        $stmtValidaServicos->close();
                    }
                }
                $servicosIds = $servicosIdsValidos;
            }

            if ($mensagemErro === '') {
                $fotoAtual = null;

                $stmtBusca = $conn->prepare('SELECT foto FROM salao_profissionais WHERE id = ?');
                if ($stmtBusca === false) {
                    $mensagemErro = 'Erro ao localizar profissional para edição.';
                } else {
                    $stmtBusca->bind_param('i', $id);
                    if ($stmtBusca->execute()) {
                        $stmtBusca->bind_result($fotoAtual);
                        if (!$stmtBusca->fetch()) {
                            $mensagemErro = 'Profissional não encontrado.';
                        }
                    } else {
                        $mensagemErro = 'Erro ao localizar profissional para edição.';
                    }
                    $stmtBusca->close();
                }

                if ($mensagemErro === '') {
                    $uploadErro = null;
                    $infoUpload = tratarUploadImagemProfissional(
                        'foto',
                        $diretorioImagensProfissionais,
                        $webBaseImagensProfissionais,
                        $tamanhoMaximoImagemBytes,
                        $uploadErro
                    );

                    if ($uploadErro !== null) {
                        $mensagemErro = $uploadErro;
                    } else {
                        $fotoWebPath = $fotoAtual;
                        $fotoFisicaNova = null;

                        if ($infoUpload !== null) {
                            $fotoWebPath = $infoUpload['web'] ?? null;
                            $fotoFisicaNova = $infoUpload['fisico'] ?? null;
                        }

                        // Iniciar transação
                        $conn->begin_transaction();
                        
                        try {
                            $stmt = $conn->prepare(
                                'UPDATE salao_profissionais
                                 SET nome = ?, foto = ?, ativo = ?
                                 WHERE id = ?'
                            );

                            if ($stmt === false) {
                                throw new Exception('Erro ao preparar atualização do profissional.');
                            }

                            $fotoParam = $fotoWebPath !== null && $fotoWebPath !== '' ? $fotoWebPath : null;

                            $stmt->bind_param(
                                'ssii',
                                $nome,
                                $fotoParam,
                                $ativo,
                                $id
                            );

                            if (!$stmt->execute()) {
                                throw new Exception('Erro ao atualizar profissional: ' . $stmt->error);
                            }
                            
                            $stmt->close();
                            
                            // Atualizar serviços do profissional
                            if (!gerenciarServicosProfissional($conn, $id, $servicosIds)) {
                                throw new Exception('Erro ao atualizar serviços do profissional.');
                            }
                            
                            $conn->commit();
                            $mensagemSucesso = 'Profissional atualizado.';
                            
                            if ($infoUpload !== null) {
                                removerImagemProfissional($fotoAtual, $diretorioImagensProfissionais, $webBaseImagensProfissionais);
                            }
                            
                        } catch (Exception $e) {
                            $conn->rollback();
                            $mensagemErro = $e->getMessage();
                            if ($fotoFisicaNova) {
                                @unlink($fotoFisicaNova);
                            }
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $mensagemErro = 'Profissional inválido para exclusão.';
        } else {
            $fotoRemover = null;
            $stmtBusca = $conn->prepare('SELECT foto FROM salao_profissionais WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar profissional para exclusão.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($fotoRemover);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Profissional não encontrado.';
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar profissional para exclusão.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                // A exclusão em cascata vai remover automaticamente os registros de salao_profissional_servicos
                // devido ao CONSTRAINT FOREIGN KEY com ON DELETE CASCADE
                $stmt = $conn->prepare('DELETE FROM salao_profissionais WHERE id = ?');
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar exclusão.';
                } else {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Profissional removido.';
                        removerImagemProfissional($fotoRemover, $diretorioImagensProfissionais, $webBaseImagensProfissionais);
                    } else {
                        $mensagemErro = 'Erro ao excluir profissional: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$profissionais = [];
$resultado = $conn->query(
    'SELECT p.id, p.nome, p.foto, p.ativo, p.ordem, p.criado_em, p.atualizado_em
     FROM salao_profissionais p
     ORDER BY p.ordem ASC, p.nome ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['ativo'] = isset($linha['ativo']) ? (int) $linha['ativo'] : 1;
        $linha['ordem'] = (int) $linha['ordem'];
        
        // Buscar serviços do profissional
        $profissionalId = $linha['id'];
        $resultadoServicos = $conn->query(
            "SELECT s.id, s.nome 
             FROM salao_servicos s 
             INNER JOIN salao_profissional_servicos ps ON s.id = ps.servico_id 
             WHERE ps.profissional_id = $profissionalId AND s.ativo = 1
             ORDER BY s.nome ASC"
        );
        
        $servicosProfissional = [];
        if ($resultadoServicos) {
            while ($servicoLinha = $resultadoServicos->fetch_assoc()) {
                $servicosProfissional[] = [
                    'id' => (int) $servicoLinha['id'],
                    'nome' => $servicoLinha['nome']
                ];
            }
            $resultadoServicos->free();
        }
        
        $linha['servicos'] = $servicosProfissional;
        $profissionais[] = $linha;
    }
    $resultado->free();
}


function renderizarProfissionalCard(array $p, array $servicos): void {
    $imgSrc = '';
    if (!empty($p['foto'])) {
        $path = str_replace('\\', '/', trim((string) $p['foto']));
        if ($path !== '') {
            if (strpos($path, 'img/') === 0) {
                $imgSrc = '../../' . $path;
            } else {
                $imgSrc = '../../img/profissional/imgcadastro/' . ltrim($path, '/');
            }
        }
    }
    
    // Preparar lista de serviços
    $servicosNomes = [];
    if (!empty($p['servicos'])) {
        foreach ($p['servicos'] as $servico) {
            $servicosNomes[] = $servico['nome'];
        }
    }
    $servicosTexto = !empty($servicosNomes) ? implode(', ', $servicosNomes) : 'Sem serviços vinculados';
    ?>
    <article class="servico-accordion-item">
        <header class="servico-accordion-header">
            <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                <span class="servico-accordion-title">
                    <strong><?= htmlspecialchars($p['nome'] ?? 'Sem nome', ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
                <span class="servico-status-pill <?= (int) ($p['ativo'] ?? 1) === 1 ? 'ativo' : 'inativo'; ?>">
                    <?= (int) ($p['ativo'] ?? 1) === 1 ? 'Ativo' : 'Inativo'; ?>
                </span>
                <span class="servico-accordion-icon">+</span>
            </button>
        </header>
        <div class="servico-accordion-content" aria-hidden="true">
            <div class="servico-card" data-profissional='<?= htmlspecialchars(json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>'>
                <div class="servico-card-inner">
                    <div class="servico-card-info">
                        <header class="servico-card-top">
                            <div>
                                <h3 class="servico-nome"><?= htmlspecialchars($p['nome'] ?? 'Sem nome', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <span class="servico-categoria-pill"><?= htmlspecialchars($servicosTexto, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </header>

                        <dl class="servico-propriedades">
                            <div>
                                <dt>Serviços:</dt>
                                <dd>
                                    <?php if (!empty($p['servicos'])): ?>
                                        <ul style="margin: 0; padding-left: 20px;">
                                            <?php foreach ($p['servicos'] as $servico): ?>
                                                <li><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <em>Nenhum serviço vinculado</em>
                                    <?php endif; ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Ordem de exibição:</dt>
                                <dd><?= htmlspecialchars((string) ($p['ordem'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Cadastrado em:</dt>
                                <dd><?= htmlspecialchars($p['criado_em'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Atualizado em:</dt>
                                <dd><?= htmlspecialchars($p['atualizado_em'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                        </dl>
                        <div class="servico-card-acoes">
                            <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarProfissional">Editar</button>
                            <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirProfissional">Excluir</button>
                        </div>
                    </div>
                    <figure class="servico-card-imagem" style="display:flex; align-items:center; justify-content:center;">
                        <?php if ($imgSrc !== ''): ?>
                            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto do profissional" style="max-width:100%; height:auto; display:block;">
                        <?php else: ?>
                            <div class="video-link-placeholder" style="text-align:center; padding:16px;">Sem foto</div>
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
    <title>Profissionais</title>
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
        .profissionais-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        .profissional-form-section,
        .profissional-lista-section {
            background: #fff;
            border-radius: 18px;
            padding: 28px 32px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }
        .profissional-form-section h2,
        .profissional-lista-section h2 {
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
        
        /* Estilo personalizado para modais de profissionais */
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
        
        /* Estilo para serviços selecionados */
        .servicos-selecionados-container {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            background: #f8f9fa;
        }
        
        .servico-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin: 2px;
        }
        
        .servico-tag .btn-remove {
            background: none;
            border: none;
            color: white;
            font-size: 14px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            margin-left: 4px;
        }
        
        .servico-tag .btn-remove:hover {
            opacity: 0.7;
        }
        
        .servicos-checkboxes {
            background-color: #f9fafb !important;
        }
        
        .servicos-checkboxes .form-check {
            margin-bottom: 8px !important;
            padding-left: 0 !important;
        }
        
        .servicos-checkboxes .form-check-input {
            margin-right: 8px !important;
            margin-top: 2px !important;
        }
        
        .servicos-checkboxes .form-check-label {
            font-size: 0.875rem !important;
            color: #374151 !important;
            cursor: pointer !important;
        }
        
        .servicos-checkboxes::-webkit-scrollbar {
            width: 6px;
        }
        
        .servicos-checkboxes::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .servicos-checkboxes::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .servicos-checkboxes::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="profissionais-wrapper">
        <section class="profissional-form-section">
            <h2>Novo profissional</h2>
            <form method="post" class="profissional-form card" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome do profissional*</label>
                        <input type="text" name="nome" class="form-control" required maxlength="255" autocomplete="off" value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Serviços vinculados</label>
                        <div class="servicos-selecionados-container">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalServicos">
                                <i class="bi bi-plus-circle"></i> Adicionar serviços
                            </button>
                            <div id="servicosSelecionados" class="mt-2">
                                <div class="text-muted fst-italic">Nenhum serviço selecionado</div>
                            </div>
                        </div>
                        <div class="text-muted mt-1">Opcional: selecione um ou mais serviços para vincular ao profissional</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto do profissional (até 3 MB)</label>
                        <input type="file" name="foto" class="form-control" accept="image/*" data-bs-toggle="tooltip" title="PNG, JPG ou WEBP até 3 MB">
                        <div class="text-muted">Foto não obrigatória</div>
                    </div>
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="novoProfissionalAtivo" name="ativo" checked>
                            <label class="form-check-label" for="novoProfissionalAtivo">
                                Profissional ativo
                            </label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar profissional</button>
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

        <section class="profissional-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="secao-titulo">Profissionais cadastrados</h2>
                <span class="texto-suave"><?= count($profissionais); ?> profissional(is) no sistema</span>
            </div>

            <?php 
            $profissionaisAtivos = [];
            $profissionaisInativos = [];
            foreach ($profissionais as $prof) {
                if ((int) ($prof['ativo'] ?? 1) === 1) {
                    $profissionaisAtivos[] = $prof;
                } else {
                    $profissionaisInativos[] = $prof;
                }
            }
            ?>

            <?php if (empty($profissionais)): ?>
                <div class="alert alert-info">Nenhum profissional cadastrado até o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Profissionais ativos</h3>
                        <span class="texto-suave"><?= count($profissionaisAtivos); ?> ativo(s)</span>
                    </div>
                    <?php if (empty($profissionaisAtivos)): ?>
                        <div class="alert alert-info">Nenhum profissional ativo cadastrado.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($profissionaisAtivos as $p) { renderizarProfissionalCard($p, $servicos); } ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="servico-subsecao mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Profissionais inativos</h3>
                        <span class="texto-suave"><?= count($profissionaisInativos); ?> inativo(s)</span>
                    </div>
                    <?php if (empty($profissionaisInativos)): ?>
                        <div class="alert alert-info">Nenhum profissional marcado como inativo.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($profissionaisInativos as $p) { renderizarProfissionalCard($p, $servicos); } ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Modal Edição -->
        <div class="modal fade" id="modalEditarProfissional" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editarProfissionalId">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar profissional</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nome do profissional*</label>
                                    <input type="text" name="nome" class="form-control" id="editarProfissionalNome" required maxlength="255">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Serviços vinculados</label>
                                    <div class="servicos-selecionados-container">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalServicosEditar">
                                            <i class="bi bi-plus-circle"></i> Adicionar serviços
                                        </button>
                                        <div id="editarServicosSelecionados" class="mt-2">
                                            <div class="text-muted fst-italic">Nenhum serviço selecionado</div>
                                        </div>
                                    </div>
                                    <div class="text-muted mt-1">Opcional: selecione um ou mais serviços para vincular ao profissional</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Foto do profissional (até 3 MB)</label>
                                    <input type="file" name="foto" class="form-control" id="editarProfissionalFoto" accept="image/*">
                                    <input type="hidden" name="foto_atual" id="editarProfissionalFotoAtual">
                                    <small class="form-text text-muted" id="editarProfissionalFotoInfo"></small>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="editarProfissionalAtivo" name="ativo">
                                        <label class="form-check-label" for="editarProfissionalAtivo">
                                            Profissional ativo
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

        <!-- Modal Seleção de Serviços - Cadastro -->
        <div class="modal fade" id="modalServicos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Selecionar Serviços</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <?php if (empty($servicos)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">Nenhum serviço ativo disponível para seleção.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($servicos as $servico): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input servico-checkbox" type="checkbox" 
                                                   value="<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                   id="modal_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                   data-nome="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <label class="form-check-label" for="modal_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmarServicos">Confirmar Seleção</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Seleção de Serviços - Edição -->
        <div class="modal fade" id="modalServicosEditar" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Selecionar Serviços</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <?php if (empty($servicos)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">Nenhum serviço ativo disponível para seleção.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($servicos as $servico): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input servico-checkbox-editar" type="checkbox" 
                                                   value="<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                   id="modal_editar_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                   data-nome="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <label class="form-check-label" for="modal_editar_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmarServicosEditar">Confirmar Seleção</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Exclusão -->
        <div class="modal fade" id="modalExcluirProfissional" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="excluirProfissionalId">
                        <div class="modal-header">
                            <h5 class="modal-title">Excluir profissional</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Deseja realmente excluir este profissional?</p>
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

            // Gerenciamento de serviços selecionados
            let servicosSelecionados = new Set();
            let servicosEditarSelecionados = new Set();

            // Função para atualizar a exibição dos serviços selecionados
            function atualizarServicosExibicao(container, servicosSet, isEditar = false) {
                const containerEl = document.getElementById(container);
                if (!containerEl) return;

                if (servicosSet.size === 0) {
                    containerEl.innerHTML = '<div class="text-muted fst-italic">Nenhum serviço selecionado</div>';
                    return;
                }

                let html = '';
                servicosSet.forEach(servicoId => {
                    const checkbox = document.getElementById((isEditar ? 'modal_editar_servico_' : 'modal_servico_') + servicoId);
                    const nomeServico = checkbox ? checkbox.dataset.nome : 'Serviço desconhecido';
                    
                    html += `
                        <span class="servico-tag">
                            ${nomeServico}
                            <button type="button" class="btn-remove" onclick="removerServico('${servicoId}', ${isEditar})">&times;</button>
                            <input type="hidden" name="servicos[]" value="${servicoId}">
                        </span>
                    `;
                });
                
                containerEl.innerHTML = html;
            }

            // Função global para remover serviço
            window.removerServico = function(servicoId, isEditar = false) {
                if (isEditar) {
                    servicosEditarSelecionados.delete(servicoId);
                    const checkbox = document.getElementById('modal_editar_servico_' + servicoId);
                    if (checkbox) checkbox.checked = false;
                    atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);
                } else {
                    servicosSelecionados.delete(servicoId);
                    const checkbox = document.getElementById('modal_servico_' + servicoId);
                    if (checkbox) checkbox.checked = false;
                    atualizarServicosExibicao('servicosSelecionados', servicosSelecionados);
                }
            };

            // Event listeners para os checkboxes da modal de cadastro
            document.querySelectorAll('.servico-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const servicoId = this.value;
                    if (this.checked) {
                        servicosSelecionados.add(servicoId);
                    } else {
                        servicosSelecionados.delete(servicoId);
                    }
                });
            });

            // Event listeners para os checkboxes da modal de edição
            document.querySelectorAll('.servico-checkbox-editar').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const servicoId = this.value;
                    if (this.checked) {
                        servicosEditarSelecionados.add(servicoId);
                    } else {
                        servicosEditarSelecionados.delete(servicoId);
                    }
                });
            });

            // Confirmar seleção de serviços - Cadastro
            document.getElementById('confirmarServicos')?.addEventListener('click', function() {
                atualizarServicosExibicao('servicosSelecionados', servicosSelecionados);
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalServicos'));
                modal.hide();
            });

            // Confirmar seleção de serviços - Edição
            document.getElementById('confirmarServicosEditar')?.addEventListener('click', function() {
                atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalServicosEditar'));
                modal.hide();
            });

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
            const editarModal = document.getElementById('modalEditarProfissional');
            const excluirModal = document.getElementById('modalExcluirProfissional');

            const preencherModalEdicao = (profissional) => {
                document.getElementById('editarProfissionalId').value = profissional.id;
                document.getElementById('editarProfissionalNome').value = profissional.nome || '';
                document.getElementById('editarProfissionalAtivo').checked = String(profissional.ativo) === '1';

                // Limpar seleções anteriores
                servicosEditarSelecionados.clear();
                document.querySelectorAll('.servico-checkbox-editar').forEach(checkbox => {
                    checkbox.checked = false;
                });

                // Marcar serviços do profissional
                if (profissional.servicos_ids && Array.isArray(profissional.servicos_ids)) {
                    profissional.servicos_ids.forEach(servicoId => {
                        servicosEditarSelecionados.add(String(servicoId));
                        const checkbox = document.getElementById('modal_editar_servico_' + servicoId);
                        if (checkbox) {
                            checkbox.checked = true;
                        }
                    });
                }

                atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);

                const inputArquivo = document.getElementById('editarProfissionalFoto');
                if (inputArquivo) { inputArquivo.value = ''; }

                const fotoAtualInput = document.getElementById('editarProfissionalFotoAtual');
                if (fotoAtualInput) { fotoAtualInput.value = profissional.foto || ''; }

                const fotoInfo = document.getElementById('editarProfissionalFotoInfo');
                if (fotoInfo) {
                    fotoInfo.textContent = profissional.foto ? 'Foto atual: ' + profissional.foto : 'Nenhuma foto cadastrada.';
                }
            };

            const prepararModalExclusao = (profissional) => {
                document.getElementById('excluirProfissionalId').value = profissional.id;
            };

            document.querySelectorAll('.servico-card').forEach((card) => {
                const dados = card.dataset.profissional ? JSON.parse(card.dataset.profissional) : null;
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
                    const fotoInfo = document.getElementById('editarProfissionalFotoInfo');
                    if (fotoInfo) { fotoInfo.textContent = ''; }
                    servicosEditarSelecionados.clear();
                    atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);
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