<?php
// Módulo Agenda Pro: Controle de Profissionais (replicado do painel, com paleta verde)
// Usa as mesmas tabelas: salao_profissionais, salao_profissional_servicos, salao_servicos

if (!isset($conn)) {
    require_once __DIR__ . '/../../conexao.php';
}

$mensagemSucesso = '';
$mensagemErro = '';
// Controla se a seção de novo profissional deve iniciar aberta após POST/erros
$formInitOpen = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') || ($mensagemSucesso !== '') || ($mensagemErro !== '');

$diretorioImagensProfissionais = __DIR__ . '/../../img/profissional/imgcadastro';
if (!is_dir($diretorioImagensProfissionais)) {
    @mkdir($diretorioImagensProfissionais, 0775, true);
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

    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($arquivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $erro = 'Erro ao fazer upload da imagem.';
        return null;
    }

    if (($arquivo['size'] ?? 0) > $tamanhoMaximo) {
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
        $extOriginal = strtolower((string) pathinfo((string) ($arquivo['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extOriginal === 'jpeg') { $extOriginal = 'jpg'; }
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
    if ($webPath === null || $webPath === '') { return; }
    $normalizado = ltrim(str_replace('\\', '/', (string)$webPath), '/');
    $baseNormalizada = ltrim(str_replace('\\', '/', (string)$webBaseDir), '/');
    if ($baseNormalizada === '' || strpos($normalizado, $baseNormalizada) !== 0) { return; }
    $relativo = ltrim(substr($normalizado, strlen($baseNormalizada)), '/');
    if ($relativo === '') { return; }
    $caminhoFisico = rtrim($destinoDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativo);
    if (is_file($caminhoFisico)) { @unlink($caminhoFisico); }
}

// Funções para serviços vinculados
function gerenciarServicosProfissional($conn, int $profissionalId, array $servicosIds): bool
{
    $stmtDelete = $conn->prepare('DELETE FROM salao_profissional_servicos WHERE profissional_id = ?');
    if (!$stmtDelete) { return false; }
    $stmtDelete->bind_param('i', $profissionalId);
    if (!$stmtDelete->execute()) { $stmtDelete->close(); return false; }
    $stmtDelete->close();

    if (!empty($servicosIds)) {
        $stmtInsert = $conn->prepare('INSERT INTO salao_profissional_servicos (profissional_id, servico_id) VALUES (?, ?)');
        if (!$stmtInsert) { return false; }
        foreach ($servicosIds as $servicoId) {
            $sid = (int) $servicoId;
            $stmtInsert->bind_param('ii', $profissionalId, $sid);
            if (!$stmtInsert->execute()) { $stmtInsert->close(); return false; }
        }
        $stmtInsert->close();
    }
    return true;
}

function buscarServicosProfissional($conn, int $profissionalId): array
{
    $servs = [];
    if ($st = $conn->prepare('SELECT servico_id FROM salao_profissional_servicos WHERE profissional_id = ?')) {
        $st->bind_param('i', $profissionalId);
        if ($st->execute()) {
            $r = $st->get_result();
            while ($row = $r->fetch_assoc()) { $servs[] = (int)$row['servico_id']; }
        }
        $st->close();
    }
    return $servs;
}

// Buscar serviços disponíveis
$servicos = [];
if ($rs = $conn->query('SELECT id, nome FROM salao_servicos WHERE ativo = 1 ORDER BY nome ASC')) {
    while ($ln = $rs->fetch_assoc()) { $servicos[] = $ln; }
    $rs->free();
}

// Ações (create/update/delete)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $servicosIds = $_POST['servicos'] ?? [];
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {
            $mensagemErro = 'Informe o nome do profissional.';
        } else {
            // Validar serviços selecionados
            $servicosIdsValidos = [];
            if (!empty($servicosIds)) {
                foreach ($servicosIds as $sid) { $sid = (int)$sid; if ($sid > 0) $servicosIdsValidos[] = $sid; }
                if (!empty($servicosIdsValidos)) {
                    $ph = str_repeat('?,', count($servicosIdsValidos)-1) . '?';
                    if ($stv = $conn->prepare("SELECT COUNT(*) AS t FROM salao_servicos WHERE id IN ($ph) AND ativo = 1")) {
                        $types = str_repeat('i', count($servicosIdsValidos));
                        $stv->bind_param($types, ...$servicosIdsValidos);
                        $stv->execute();
                        $rr = $stv->get_result();
                        $row = $rr->fetch_assoc();
                        if ((int)$row['t'] !== count($servicosIdsValidos)) { $mensagemErro = 'Um ou mais serviços selecionados não são válidos.'; }
                        $stv->close();
                    }
                }
            }
            if ($mensagemErro === '') {
                // próxima ordem
                $proximaOrdem = 1;
                if ($ro = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM salao_profissionais')) {
                    $rw = $ro->fetch_assoc();
                    $proximaOrdem = (int)$rw['proxima'];
                    $ro->free();
                }

                $uploadErro = null;
                $infoUpload = tratarUploadImagemProfissional('foto', $diretorioImagensProfissionais, $webBaseImagensProfissionais, $tamanhoMaximoImagemBytes, $uploadErro);
                if ($uploadErro !== null) {
                    $mensagemErro = $uploadErro;
                } else {
                    $fotoWebPath = null; $fotoFisicaNova = null;
                    if ($infoUpload !== null) { $fotoWebPath = $infoUpload['web'] ?? null; $fotoFisicaNova = $infoUpload['fisico'] ?? null; }

                    $conn->begin_transaction();
                    try {
                        if (!($st = $conn->prepare('INSERT INTO salao_profissionais (nome, foto, ativo, ordem) VALUES (?, ?, ?, ?)'))) {
                            throw new Exception('Erro ao preparar inserção do profissional.');
                        }
                        $fotoParam = $fotoWebPath !== null && $fotoWebPath !== '' ? $fotoWebPath : null;
                        $st->bind_param('ssii', $nome, $fotoParam, $ativo, $proximaOrdem);
                        if (!$st->execute()) { throw new Exception('Erro ao inserir profissional: ' . $st->error); }
                        $profId = $conn->insert_id; $st->close();

                        if (!empty($servicosIdsValidos)) {
                            if (!gerenciarServicosProfissional($conn, (int)$profId, $servicosIdsValidos)) { throw new Exception('Erro ao associar serviços ao profissional.'); }
                        }
                        $conn->commit();
                        $mensagemSucesso = 'Profissional cadastrado com sucesso.';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $mensagemErro = $e->getMessage();
                        if ($fotoFisicaNova) { @unlink($fotoFisicaNova); }
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
            $servicosIdsValidos = [];
            if (!empty($servicosIds)) {
                foreach ($servicosIds as $sid) { $sid = (int)$sid; if ($sid > 0) $servicosIdsValidos[] = $sid; }
                if (!empty($servicosIdsValidos)) {
                    $ph = str_repeat('?,', count($servicosIdsValidos)-1) . '?';
                    if ($stv = $conn->prepare("SELECT COUNT(*) AS t FROM salao_servicos WHERE id IN ($ph) AND ativo = 1")) {
                        $types = str_repeat('i', count($servicosIdsValidos));
                        $stv->bind_param($types, ...$servicosIdsValidos);
                        $stv->execute();
                        $rr = $stv->get_result(); $row = $rr->fetch_assoc();
                        if ((int)$row['t'] !== count($servicosIdsValidos)) { $mensagemErro = 'Um ou mais serviços selecionados não são válidos.'; }
                        $stv->close();
                    }
                }
            }

            if ($mensagemErro === '') {
                $fotoAtual = null;
                if ($sb = $conn->prepare('SELECT foto FROM salao_profissionais WHERE id = ?')) {
                    $sb->bind_param('i', $id);
                    if ($sb->execute()) { $sb->bind_result($fotoAtual); $sb->fetch(); }
                    else { $mensagemErro = 'Erro ao localizar profissional para edição.'; }
                    $sb->close();
                } else { $mensagemErro = 'Erro ao localizar profissional para edição.'; }

                if ($mensagemErro === '') {
                    $uploadErro = null;
                    $infoUpload = tratarUploadImagemProfissional('foto', $diretorioImagensProfissionais, $webBaseImagensProfissionais, $tamanhoMaximoImagemBytes, $uploadErro);
                    if ($uploadErro !== null) { $mensagemErro = $uploadErro; }
                    else {
                        $fotoWebPath = $fotoAtual; $fotoFisicaNova = null;
                        if ($infoUpload !== null) { $fotoWebPath = $infoUpload['web'] ?? null; $fotoFisicaNova = $infoUpload['fisico'] ?? null; }

                        $conn->begin_transaction();
                        try {
                            if (!($st = $conn->prepare('UPDATE salao_profissionais SET nome = ?, foto = ?, ativo = ? WHERE id = ?'))) {
                                throw new Exception('Erro ao preparar atualização do profissional.');
                            }
                            $fotoParam = $fotoWebPath !== null && $fotoWebPath !== '' ? $fotoWebPath : null;
                            $st->bind_param('ssii', $nome, $fotoParam, $ativo, $id);
                            if (!$st->execute()) { throw new Exception('Erro ao atualizar profissional: ' . $st->error); }
                            $st->close();

                            if (!gerenciarServicosProfissional($conn, $id, $servicosIdsValidos)) { throw new Exception('Erro ao atualizar serviços do profissional.'); }
                            $conn->commit();
                            $mensagemSucesso = 'Profissional atualizado.';
                            if ($infoUpload !== null) { removerImagemProfissional($fotoAtual, $diretorioImagensProfissionais, $webBaseImagensProfissionais); }
                        } catch (Exception $e) {
                            $conn->rollback();
                            $mensagemErro = $e->getMessage();
                            if ($fotoFisicaNova) { @unlink($fotoFisicaNova); }
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) { $mensagemErro = 'Profissional inválido para exclusão.'; }
        else {
            $fotoRemover = null;
            if ($sb = $conn->prepare('SELECT foto FROM salao_profissionais WHERE id = ?')) {
                $sb->bind_param('i', $id);
                if ($sb->execute()) { $sb->bind_result($fotoRemover); $sb->fetch(); }
                else { $mensagemErro = 'Erro ao localizar profissional para exclusão.'; }
                $sb->close();
            } else { $mensagemErro = 'Erro ao localizar profissional para exclusão.'; }

            if ($mensagemErro === '') {
                if ($st = $conn->prepare('DELETE FROM salao_profissionais WHERE id = ?')) {
                    $st->bind_param('i', $id);
                    if ($st->execute()) { $mensagemSucesso = 'Profissional removido.'; removerImagemProfissional($fotoRemover, $diretorioImagensProfissionais, $webBaseImagensProfissionais); }
                    else { $mensagemErro = 'Erro ao excluir profissional: ' . $st->error; }
                    $st->close();
                } else { $mensagemErro = 'Erro ao preparar exclusão.'; }
            }
        }
    }
}

// Carregar profissionais
$profissionais = [];
if ($resultado = $conn->query('SELECT id, nome, foto, ativo, ordem, criado_em, atualizado_em FROM salao_profissionais ORDER BY ordem ASC, nome ASC')) {
    while ($linha = $resultado->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['ativo'] = isset($linha['ativo']) ? (int)$linha['ativo'] : 1;
        $linha['ordem'] = (int) ($linha['ordem'] ?? 0);
        // buscar serviços por profissional
        $servicosProf = [];
        $servicosIds = [];
        $profId = (int)$linha['id'];
        if ($rs2 = $conn->query("SELECT s.id, s.nome FROM salao_servicos s INNER JOIN salao_profissional_servicos ps ON s.id = ps.servico_id WHERE ps.profissional_id = $profId AND s.ativo = 1 ORDER BY s.nome ASC")) {
            while ($ln2 = $rs2->fetch_assoc()) { $servicosProf[] = ['id'=>(int)$ln2['id'], 'nome'=>$ln2['nome']]; $servicosIds[]=(int)$ln2['id']; }
            $rs2->free();
        }
        $linha['servicos'] = $servicosProf;
        $linha['servicos_ids'] = $servicosIds;
        $profissionais[] = $linha;
    }
    $resultado->free();
}

function renderizarProfissionalCard_ag(array $p): void {
    $imgSrc = '';
    if (!empty($p['foto'])) {
        $path = str_replace('\\', '/', trim((string)$p['foto']));
        if ($path !== '') {
            if (strpos($path, 'img/') === 0) { $imgSrc = '../' . $path; }
            else { $imgSrc = '../img/profissional/imgcadastro/' . ltrim($path, '/'); }
        }
    }
    $servicosNomes = [];
    if (!empty($p['servicos'])) { foreach ($p['servicos'] as $s) { $servicosNomes[] = $s['nome']; } }
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
            <div class="servico-card" data-profissional='<?= htmlspecialchars(json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, "UTF-8"); ?>'>
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
                                        <ul style="margin:0; padding-left:20px;">
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

<link rel="stylesheet" href="../css/estilo.css">
<style>
    .profissionais-wrapper { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px; }
    .profissional-form-section, .profissional-lista-section { background: #fff; border-radius: 18px; padding: 28px 32px; border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 16px 40px rgba(15,23,42,0.08); }
    .profissional-form-section h2, .profissional-lista-section h2 { margin-bottom: 16px; font-size: 1.75rem; font-weight: 700; }
    .botao-salvar { background: #28a745; border: none; color: #fff; font-weight: 600; border-radius: 999px; padding: 10px 24px; cursor: pointer; }
    .botao-salvar:hover { background: #218838; }
    .texto-suave { color: #64748b; }
    .invalid-feedback { display: block; font-size: 0.875rem; color: #dc3545; margin-top: 0.25rem; }
    .form-control.is-invalid { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); }

    .modal { z-index: 1055 !important; }
    .modal-backdrop { z-index: 1050 !important; background-color: rgba(0,0,0,0.5) !important; }
    .modal-content { background-color: #ffffff !important; border: 1px solid #dee2e6 !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; border-radius: 12px !important; }
    .modal-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; color: #fff !important; border-radius: 12px 12px 0 0 !important; border-bottom: none !important; }
    .modal-title { font-weight: 600 !important; color: #fff !important; }
    .btn-close { filter: brightness(0) invert(1) !important; }

    .form-label { font-weight: 500 !important; color: #374151 !important; margin-bottom: 0.5rem !important; }
    .form-control, .form-select { border-radius: 8px !important; border: 1px solid #d1d5db !important; padding: 0.75rem !important; font-size: 0.875rem !important; }
    .form-control:focus, .form-select:focus { border-color: #28a745 !important; box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important; }
    .form-check-input:checked { background-color: #28a745 !important; border-color: #28a745 !important; }

    .btn-primary { background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important; border: none !important; border-radius: 8px !important; padding: 0.75rem 1.5rem !important; font-weight: 500 !important; }
    .btn-primary:hover { background: linear-gradient(135deg, #218838 0%, #1da97e 100%) !important; transform: translateY(-1px) !important; box-shadow: 0 4px 12px rgba(40,167,69,0.35) !important; }
    .btn-outline-secondary, .btn-danger { border-radius: 8px !important; padding: 0.75rem 1.5rem !important; font-weight: 500 !important; }

    /* Toggle Novo profissional */
    .novo-prof-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .toggle-form-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: none; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #fff; font-size: 20px; line-height: 1; cursor: pointer; box-shadow: 0 6px 16px rgba(40,167,69,0.35); }
    .toggle-form-btn:hover { filter: brightness(0.95); }
    .novo-prof-form-wrapper.collapsed { display: none; }

    /* Cabeçalho Profissionais cadastrados: título | contador | toggle */
    .prof-lista-header { display: grid; grid-template-columns: 1fr auto 1fr; grid-template-areas: 'title count toggle'; align-items: center; gap: 12px; }
    .prof-lista-header .secao-titulo { justify-self: start; grid-area: title; }
    .count-pill { white-space: nowrap; color: #157347; background: rgba(40,167,69,0.08); border: 1px solid rgba(40,167,69,0.25); padding: 6px 12px; border-radius: 999px; font-weight: 600; }
    .prof-lista-header .count-pill { justify-self: center; grid-area: count; }
    .prof-lista-header .toggle-form-btn { justify-self: end; grid-area: toggle; }
    .prof-lista-wrapper.collapsed { display: none; }

    @media (max-width: 960px) {
        .prof-lista-header { grid-template-columns: 1fr auto; grid-template-rows: auto auto; grid-template-areas: 'title toggle' 'count count'; }
        .prof-lista-header .count-pill { justify-self: start; margin-top: 8px; white-space: normal; }
    }

    /* Tags de serviços selecionados */
    .servicos-selecionados-container { border: 1px solid #e9ecef; border-radius: 8px; padding: 12px; background: #f8f9fa; }
    .servico-tag { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; margin: 2px; }
    .servico-tag .btn-remove { background: none; border: none; color: white; font-size: 14px; line-height: 1; cursor: pointer; padding: 0; margin-left: 4px; }
    .servico-tag .btn-remove:hover { opacity: 0.7; }
</style>

<div class="profissionais-wrapper">
    <section class="profissional-form-section" id="novoProfSection" data-init-open="<?= $formInitOpen ? '1' : '0'; ?>">
        <div class="novo-prof-header">
            <h2 class="mb-0">Novo profissional</h2>
            <button type="button" class="toggle-form-btn" id="toggleNovoProfBtn" aria-controls="novoProfForm" aria-expanded="<?= $formInitOpen ? 'true' : 'false'; ?>" aria-label="Mostrar/ocultar formulário de novo profissional">
                <span id="toggleNovoProfIcon"><?= $formInitOpen ? '-' : '+'; ?></span>
            </button>
        </div>
        <div id="novoProfForm" class="novo-prof-form-wrapper<?= $formInitOpen ? '' : ' collapsed'; ?>">
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
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalServicos">
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
                            <input class="form-check-input" type="checkbox" value="1" id="novoProfAtivo" name="ativo" checked>
                            <label class="form-check-label" for="novoProfAtivo">Profissional ativo</label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar profissional</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <?php if ($mensagemSucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="profissional-lista-section">
        <div class="prof-lista-header mb-3">
            <h2 class="secao-titulo mb-0">Profissionais cadastrados</h2>
            <span class="count-pill"><?= count($profissionais); ?> profissional(is) no sistema</span>
            <button type="button" class="toggle-form-btn" id="toggleProfListaBtn" aria-controls="profListaWrapper" aria-expanded="false" aria-label="Mostrar/ocultar lista de profissionais">
                <span id="toggleProfListaIcon">+</span>
            </button>
        </div>
        <div id="profListaWrapper" class="prof-lista-wrapper collapsed">
        <?php 
        $profissionaisAtivos = [];
        $profissionaisInativos = [];
        foreach ($profissionais as $pf) {
            if ((int)($pf['ativo'] ?? 1) === 1) { $profissionaisAtivos[] = $pf; } else { $profissionaisInativos[] = $pf; }
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
                        <?php foreach ($profissionaisAtivos as $p) { renderizarProfissionalCard_ag($p); } ?>
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
                        <?php foreach ($profissionaisInativos as $p) { renderizarProfissionalCard_ag($p); } ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
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
                                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalServicosEditar">
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
                                    <label class="form-check-label" for="editarProfissionalAtivo">Profissional ativo</label>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecionar Serviços</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row servicos-checkboxes">
                        <?php if (empty($servicos)): ?>
                            <div class="col-12"><div class="alert alert-info">Nenhum serviço ativo disponível para seleção.</div></div>
                        <?php else: ?>
                            <?php foreach ($servicos as $servico): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input servico-checkbox" type="checkbox" value="<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>" id="modal_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>" data-nome="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <label class="form-check-label" for="modal_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></label>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selecionar Serviços</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row servicos-checkboxes">
                        <?php if (empty($servicos)): ?>
                            <div class="col-12"><div class="alert alert-info">Nenhum serviço ativo disponível para seleção.</div></div>
                        <?php else: ?>
                            <?php foreach ($servicos as $servico): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input servico-checkbox-editar" type="checkbox" value="<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>" id="modal_editar_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>" data-nome="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <label class="form-check-label" for="modal_editar_servico_<?= htmlspecialchars($servico['id'], ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></label>
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });

    // Toggle Novo profissional (padrão fechado salvo POST)
    const novoProfSection = document.getElementById('novoProfSection');
    const novoProfFormWrap = document.getElementById('novoProfForm');
    const toggleNovoProfBtn = document.getElementById('toggleNovoProfBtn');
    const toggleNovoProfIcon = document.getElementById('toggleNovoProfIcon');
    if (novoProfSection && novoProfFormWrap && toggleNovoProfBtn && toggleNovoProfIcon) {
        let open = (novoProfSection.dataset.initOpen === '1');
        const apply = () => {
            toggleNovoProfBtn.setAttribute('aria-expanded', String(open));
            if (open) { novoProfFormWrap.classList.remove('collapsed'); toggleNovoProfIcon.textContent = '-'; }
            else { novoProfFormWrap.classList.add('collapsed'); toggleNovoProfIcon.textContent = '+'; }
        };
        apply();
        toggleNovoProfBtn.addEventListener('click', () => { open = !open; apply(); });
    }

    // Toggle lista de profissionais (padrão fechado)
    const profListaWrapper = document.getElementById('profListaWrapper');
    const toggleProfListaBtn = document.getElementById('toggleProfListaBtn');
    const toggleProfListaIcon = document.getElementById('toggleProfListaIcon');
    if (profListaWrapper && toggleProfListaBtn && toggleProfListaIcon) {
        let openList = false;
        const applyList = () => {
            toggleProfListaBtn.setAttribute('aria-expanded', String(openList));
            if (openList) { profListaWrapper.classList.remove('collapsed'); toggleProfListaIcon.textContent = '-'; }
            else { profListaWrapper.classList.add('collapsed'); toggleProfListaIcon.textContent = '+'; }
        };
        applyList();
        toggleProfListaBtn.addEventListener('click', () => { openList = !openList; applyList(); });
    }

    // Acordeon
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

    // Gerenciamento de serviços selecionados
    let servicosSelecionados = new Set();
    let servicosEditarSelecionados = new Set();

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

    document.querySelectorAll('.servico-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const servicoId = this.value;
            if (this.checked) servicosSelecionados.add(servicoId); else servicosSelecionados.delete(servicoId);
        });
    });
    document.querySelectorAll('.servico-checkbox-editar').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const servicoId = this.value;
            if (this.checked) servicosEditarSelecionados.add(servicoId); else servicosEditarSelecionados.delete(servicoId);
        });
    });

    document.getElementById('confirmarServicos')?.addEventListener('click', function() {
        atualizarServicosExibicao('servicosSelecionados', servicosSelecionados);
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalServicos'));
        modal?.hide();
    });
    document.getElementById('confirmarServicosEditar')?.addEventListener('click', function() {
        atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalServicosEditar'));
        modal?.hide();
    });

    // Modais editar/excluir
    const editarModal = document.getElementById('modalEditarProfissional');
    const excluirModal = document.getElementById('modalExcluirProfissional');

    const preencherModalEdicao = (profissional) => {
        document.getElementById('editarProfissionalId').value = profissional.id;
        document.getElementById('editarProfissionalNome').value = profissional.nome || '';
        document.getElementById('editarProfissionalAtivo').checked = String(profissional.ativo) === '1';

        servicosEditarSelecionados.clear();
        document.querySelectorAll('.servico-checkbox-editar').forEach(cb => { cb.checked = false; });
        if (profissional.servicos_ids && Array.isArray(profissional.servicos_ids)) {
            profissional.servicos_ids.forEach(servicoId => {
                servicosEditarSelecionados.add(String(servicoId));
                const checkbox = document.getElementById('modal_editar_servico_' + servicoId);
                if (checkbox) { checkbox.checked = true; }
            });
        }
        atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);

        const inputArquivo = document.getElementById('editarProfissionalFoto'); if (inputArquivo) inputArquivo.value = '';
        const fotoAtualInput = document.getElementById('editarProfissionalFotoAtual'); if (fotoAtualInput) fotoAtualInput.value = profissional.foto || '';
        const fotoInfo = document.getElementById('editarProfissionalFotoInfo'); if (fotoInfo) fotoInfo.textContent = profissional.foto ? 'Foto atual: ' + profissional.foto : 'Nenhuma foto cadastrada.';
    };

    const prepararModalExclusao = (profissional) => {
        document.getElementById('excluirProfissionalId').value = profissional.id;
    };

    document.querySelectorAll('.servico-card').forEach((card) => {
        const dados = card.dataset.profissional ? JSON.parse(card.dataset.profissional) : null;
        if (!dados) return;
        const botaoEditar = card.querySelector('.acao-editar');
        const botaoExcluir = card.querySelector('.acao-excluir');
        if (botaoEditar) botaoEditar.addEventListener('click', () => preencherModalEdicao(dados));
        if (botaoExcluir) botaoExcluir.addEventListener('click', () => prepararModalExclusao(dados));
    });

    if (editarModal) {
        editarModal.addEventListener('hidden.bs.modal', () => {
            const form = editarModal.querySelector('form'); if (form) form.reset();
            const fotoInfo = document.getElementById('editarProfissionalFotoInfo'); if (fotoInfo) fotoInfo.textContent = '';
            servicosEditarSelecionados.clear();
            atualizarServicosExibicao('editarServicosSelecionados', servicosEditarSelecionados, true);
        });
    }
    if (excluirModal) {
        excluirModal.addEventListener('hidden.bs.modal', () => { excluirModal.querySelector('form').reset(); });
    }
});
</script>
