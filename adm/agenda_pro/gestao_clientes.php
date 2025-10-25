<?php
// Módulo Agenda Pro: Gestão de Clientes (replicado do painel, com paleta verde)
// Usa a mesma tabela: salao_clientes

if (!isset($conn)) {
    require_once __DIR__ . '/../../conexao.php';
}

$mensagemSucesso = '';
$mensagemErro = '';
// Controla se a seção de novo cliente deve iniciar aberta (desktop e após POST)
$formInitOpen = (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
) || ($mensagemSucesso !== '') || ($mensagemErro !== '');

$diretorioImagensClientes = __DIR__ . '/../../img/clientes/imgcadastro';
if (!is_dir($diretorioImagensClientes)) {
    @mkdir($diretorioImagensClientes, 0775, true);
}
$diretorioImagensClientes = realpath($diretorioImagensClientes) ?: $diretorioImagensClientes;
$webBaseImagensClientes = 'img/clientes/imgcadastro';
$tamanhoMaximoImagemBytes = 3 * 1024 * 1024; // 3 MB

function tratarUploadImagemCliente(string $campo, string $destinoDir, string $webBaseDir, int $tamanhoMaximo, ?string &$erro): ?array
{
    if (!isset($_FILES[$campo]) || !is_array($_FILES[$campo])) {
        return null;
    }

    $arquivo = $_FILES[$campo];

    if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
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

    $nomeArquivo = uniqid('cliente_', true) . '.' . $extFinal;
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

function removerImagemCliente(?string $webPath, string $destinoDir, string $webBaseDir): void
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

// Ações (create/update/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefone = trim((string) ($_POST['telefone'] ?? ''));
        $data_nascimento = trim((string) ($_POST['data_nascimento'] ?? ''));
        $logradouro = trim((string) ($_POST['logradouro'] ?? ''));
        $numero = trim((string) ($_POST['numero'] ?? ''));
        $bairro = trim((string) ($_POST['bairro'] ?? ''));
        $cidade = trim((string) ($_POST['cidade'] ?? ''));
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $indicacao = trim((string) ($_POST['indicacao'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {
            $mensagemErro = 'Informe o nome do cliente.';
        } else {
            $idade = null;
            if ($data_nascimento !== '') {
                $dt = DateTime::createFromFormat('Y-m-d', $data_nascimento);
                if (!$dt) {
                    $mensagemErro = 'Data de nascimento inválida.';
                } else {
                    $now = new DateTime('now');
                    $idade = (int) $now->diff($dt)->y;
                }
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mensagemErro = 'Email inválido.';
            }

            if ($numero !== '' && !preg_match('/^\d+$/', $numero)) {
                $mensagemErro = 'Número deve conter apenas dígitos.';
            }

            if ($mensagemErro === '') {
                $uploadErro = null;
                $infoUpload = tratarUploadImagemCliente(
                    'imagem',
                    $diretorioImagensClientes,
                    $webBaseImagensClientes,
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

                    $stmt = $conn->prepare(
                        'INSERT INTO salao_clientes (nome, logradouro, numero, bairro, cidade, estado, email, data_nascimento, telefone, indicacao, idade, imagem, ativo)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    if ($stmt === false) {
                        $mensagemErro = 'Erro ao preparar inserção.';
                        if ($imagemFisicaNova) { @unlink($imagemFisicaNova); }
                    } else {
                        $emailParam = $email !== '' ? $email : null;
                        $telefoneParam = $telefone !== '' ? $telefone : null;
                        $dataNascParam = $data_nascimento !== '' ? $data_nascimento : null;
                        $logradouroParam = $logradouro !== '' ? $logradouro : null;
                        $numeroParam = $numero !== '' ? $numero : null;
                        $bairroParam = $bairro !== '' ? $bairro : null;
                        $cidadeParam = $cidade !== '' ? $cidade : null;
                        $estadoParam = $estado !== '' ? $estado : null;
                        $indicacaoParam = $indicacao !== '' ? $indicacao : null;
                        $imagemParam = $imagemWebPath !== null && $imagemWebPath !== '' ? $imagemWebPath : null;

                        $stmt->bind_param(
                            'ssssssssssisi',
                            $nome,
                            $logradouroParam,
                            $numeroParam,
                            $bairroParam,
                            $cidadeParam,
                            $estadoParam,
                            $emailParam,
                            $dataNascParam,
                            $telefoneParam,
                            $indicacaoParam,
                            $idade,
                            $imagemParam,
                            $ativo
                        );

                        if ($stmt->execute()) {
                            $mensagemSucesso = 'Cliente cadastrado com sucesso.';
                        } else {
                            $mensagemErro = 'Erro ao inserir cliente: ' . $stmt->error;
                            if ($imagemFisicaNova) { @unlink($imagemFisicaNova); }
                        }
                        $stmt->close();
                    }
                }
            }
        }
    } elseif ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $telefone = trim((string) ($_POST['telefone'] ?? ''));
        $data_nascimento = trim((string) ($_POST['data_nascimento'] ?? ''));
        $logradouro = trim((string) ($_POST['logradouro'] ?? ''));
        $numero = trim((string) ($_POST['numero'] ?? ''));
        $bairro = trim((string) ($_POST['bairro'] ?? ''));
        $cidade = trim((string) ($_POST['cidade'] ?? ''));
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $indicacao = trim((string) ($_POST['indicacao'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0 || $nome === '') {
            $mensagemErro = 'Cliente inválido ou dados obrigatórios faltando.';
        } else {
            $imagemAtual = null;
            $stmtBusca = $conn->prepare('SELECT imagem FROM salao_clientes WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar cliente para edição.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemAtual);
                    if (!$stmtBusca->fetch()) { $mensagemErro = 'Cliente não encontrado.'; }
                } else {
                    $mensagemErro = 'Erro ao localizar cliente para edição.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $idade = null;
                if ($data_nascimento !== '') {
                    $dt = DateTime::createFromFormat('Y-m-d', $data_nascimento);
                    if (!$dt) {
                        $mensagemErro = 'Data de nascimento inválida.';
                    } else {
                        $now = new DateTime('now');
                        $idade = (int) $now->diff($dt)->y;
                    }
                }

                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mensagemErro = 'Email inválido.';
                }

                if ($numero !== '' && !preg_match('/^\d+$/', $numero)) {
                    $mensagemErro = 'Número deve conter apenas dígitos.';
                }

                if ($mensagemErro === '') {
                    $uploadErro = null;
                    $infoUpload = tratarUploadImagemCliente(
                        'imagem',
                        $diretorioImagensClientes,
                        $webBaseImagensClientes,
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

                        $stmt = $conn->prepare(
                            'UPDATE salao_clientes
                             SET nome = ?, logradouro = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, email = ?, data_nascimento = ?, telefone = ?, indicacao = ?, idade = ?, imagem = ?, ativo = ?
                             WHERE id = ?'
                        );

                        if ($stmt === false) {
                            $mensagemErro = 'Erro ao preparar atualização.';
                            if ($imagemFisicaNova) { @unlink($imagemFisicaNova); }
                        } else {
                            $emailParam = $email !== '' ? $email : null;
                            $telefoneParam = $telefone !== '' ? $telefone : null;
                            $dataNascParam = $data_nascimento !== '' ? $data_nascimento : null;
                            $logradouroParam = $logradouro !== '' ? $logradouro : null;
                            $numeroParam = $numero !== '' ? $numero : null;
                            $bairroParam = $bairro !== '' ? $bairro : null;
                            $cidadeParam = $cidade !== '' ? $cidade : null;
                            $estadoParam = $estado !== '' ? $estado : null;
                            $indicacaoParam = $indicacao !== '' ? $indicacao : null;
                            $imagemParam = $imagemWebPath !== null && $imagemWebPath !== '' ? $imagemWebPath : null;

                            $stmt->bind_param(
                                'ssssssssssisii',
                                $nome,
                                $logradouroParam,
                                $numeroParam,
                                $bairroParam,
                                $cidadeParam,
                                $estadoParam,
                                $emailParam,
                                $dataNascParam,
                                $telefoneParam,
                                $indicacaoParam,
                                $idade,
                                $imagemParam,
                                $ativo,
                                $id
                            );

                            if ($stmt->execute()) {
                                $mensagemSucesso = 'Cliente atualizado.';
                                if ($infoUpload !== null) {
                                    removerImagemCliente($imagemAtual, $diretorioImagensClientes, $webBaseImagensClientes);
                                }
                            } else {
                                $mensagemErro = 'Erro ao atualizar cliente: ' . $stmt->error;
                                if ($imagemFisicaNova) { @unlink($imagemFisicaNova); }
                            }
                            $stmt->close();
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $mensagemErro = 'Cliente inválido para exclusão.';
        } else {
            $imagemRemover = null;
            $stmtBusca = $conn->prepare('SELECT imagem FROM salao_clientes WHERE id = ?');
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar cliente para exclusão.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($imagemRemover);
                    if (!$stmtBusca->fetch()) { $mensagemErro = 'Cliente não encontrado.'; }
                } else {
                    $mensagemErro = 'Erro ao localizar cliente para exclusão.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $stmt = $conn->prepare('DELETE FROM salao_clientes WHERE id = ?');
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar exclusão.';
                } else {
                    $stmt->bind_param('i', $id);
                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Cliente removido.';
                        removerImagemCliente($imagemRemover, $diretorioImagensClientes, $webBaseImagensClientes);
                    } else {
                        $mensagemErro = 'Erro ao excluir cliente: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$clientes = [];
$resultado = $conn->query(
    'SELECT id, nome, logradouro, numero, bairro, cidade, estado, endereco, email, data_nascimento, telefone, indicacao, idade, imagem, ativo
     FROM salao_clientes
     ORDER BY nome ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['idade'] = isset($linha['idade']) ? (int) $linha['idade'] : null;
        $linha['ativo'] = isset($linha['ativo']) ? (int) $linha['ativo'] : 1;
        $clientes[] = $linha;
    }
    $resultado->free();
}

function renderizarClienteCard_ag(array $c): void {
    $imgSrc = '';
    if (!empty($c['imagem'])) {
        $p = str_replace('\\', '/', trim((string) $c['imagem']));
        if ($p !== '') {
            if (strpos($p, 'img/') === 0) {
                $imgSrc = '../' . $p; // relativo a /adm/agenda_profissional.php
            } else {
                $imgSrc = '../img/clientes/imgcadastro/' . ltrim($p, '/');
            }
        }
    }
    ?>
    <article class="servico-accordion-item">
        <header class="servico-accordion-header">
            <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                <span class="servico-accordion-title">
                    <strong><?= htmlspecialchars($c['nome'] ?? 'Sem nome', ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
                <span class="servico-status-pill <?= (int) ($c['ativo'] ?? 1) === 1 ? 'ativo' : 'inativo'; ?>">
                    <?= (int) ($c['ativo'] ?? 1) === 1 ? 'Ativo' : 'Inativo'; ?>
                </span>
                <span class="servico-accordion-icon">+</span>
            </button>
        </header>
        <div class="servico-accordion-content" aria-hidden="true">
            <div class="servico-card" data-cliente='<?= htmlspecialchars(json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); ?>'>
                <div class="servico-card-inner">
                    <div class="servico-card-info">
                        <header class="servico-card-top">
                            <div>
                                <h3 class="servico-nome"><?= htmlspecialchars($c['nome'] ?? 'Sem nome', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if (!empty($c['email'])): ?>
                                    <span class="servico-categoria-pill">Email: <?= htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </header>

                        <dl class="servico-propriedades">
                            <div>
                                <dt>Telefone:</dt>
                                <dd><?= htmlspecialchars($c['telefone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Data de nascimento:</dt>
                                <dd><?= htmlspecialchars($c['data_nascimento'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Idade (auto):</dt>
                                <dd><?= htmlspecialchars((string) ($c['idade'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Endereço:</dt>
                                <dd><?= htmlspecialchars($c['endereco'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                            <div>
                                <dt>Indicação:</dt>
                                <dd><?= htmlspecialchars($c['indicacao'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                            </div>
                        </dl>
                        <div class="servico-card-acoes">
                            <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarCliente">Editar</button>
                            <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirCliente">Excluir</button>
                        </div>
                    </div>
                    <figure class="servico-card-imagem" style="display:flex; align-items:center; justify-content:center;">
                        <?php if ($imgSrc !== ''): ?>
                            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto do cliente" style="max-width:100%; height:auto; display:block;">
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

<!-- Estilos locais (paleta Agenda Pro - verde) -->
<link rel="stylesheet" href="../css/estilo.css">
<style>
    .clientes-wrapper { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 32px; }
    .cliente-form-section, .cliente-lista-section { background: #fff; border-radius: 18px; padding: 28px 32px; border: 1px solid rgba(203,213,225,0.9); box-shadow: 0 16px 40px rgba(15,23,42,0.08); }
    .cliente-form-section h2, .cliente-lista-section h2 { margin-bottom: 16px; font-size: 1.75rem; font-weight: 700; }
    .botao-salvar { background: #28a745; border: none; color: #fff; font-weight: 600; border-radius: 999px; padding: 10px 24px; cursor: pointer; }
    .botao-salvar:hover { background: #218838; }
    .texto-suave { color: #64748b; }
    .invalid-feedback { display: block; font-size: 0.875rem; color: #dc3545; margin-top: 0.25rem; }
    .form-control.is-invalid { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); }

    /* Modais com tema verde */
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

    /* Toggle da seção Novo cliente (mobile-first) */
    .novo-cliente-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
    .toggle-form-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: none; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #fff; font-size: 20px; line-height: 1; cursor: pointer; box-shadow: 0 6px 16px rgba(40,167,69,0.35); }
    .toggle-form-btn:hover { filter: brightness(0.95); }
    .novo-cliente-form-wrapper.collapsed { display: none; }

    /* Cabeçalho Clientes cadastrados com contador à direita */
    .secao-titulo-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: nowrap; }
    .count-pill { white-space: nowrap; color: #157347; background: rgba(40,167,69,0.08); border: 1px solid rgba(40,167,69,0.25); padding: 6px 12px; border-radius: 999px; font-weight: 600; }

    /* Header com 3 colunas: título | contador (centro) | toggle (direita) */
    .clientes-lista-header { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 12px; }
    .clientes-lista-header .secao-titulo { justify-self: start; }
    .clientes-lista-header .count-pill { justify-self: center; }
    .clientes-lista-header .toggle-form-btn { justify-self: end; }
    .clientes-lista-wrapper.collapsed { display: none; }
</style>

<div class="clientes-wrapper">
    <section class="cliente-form-section" id="novoClienteSection" data-init-open="<?= $formInitOpen ? '1' : '0'; ?>">
        <div class="novo-cliente-header">
            <h2 class="mb-0">Novo cliente</h2>
            <button type="button" class="toggle-form-btn" id="toggleNovoClienteBtn" aria-controls="novoClienteForm" aria-expanded="<?= $formInitOpen ? 'true' : 'false'; ?>" aria-label="Mostrar/ocultar formulário de novo cliente">
                <span id="toggleNovoClienteIcon"><?= $formInitOpen ? '-' : '+'; ?></span>
            </button>
        </div>
        <div id="novoClienteForm" class="novo-cliente-form-wrapper<?= $formInitOpen ? '' : ' collapsed'; ?>">
        <form method="post" class="cliente-form card" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome*</label>
                    <input type="text" name="nome" class="form-control" required maxlength="100" autocomplete="off" value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="nome@exemplo.com" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="invalid-feedback" id="email-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefone</label>
                    <input type="tel" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($_POST['telefone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="invalid-feedback" id="telefone-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Data de nascimento</label>
                    <input type="date" name="data_nascimento" id="data_nascimento" class="form-control" value="<?= htmlspecialchars($_POST['data_nascimento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="invalid-feedback" id="data_nascimento-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Idade (calculada)</label>
                    <input type="number" name="idade" id="idade" class="form-control" readonly value="<?= htmlspecialchars($_POST['idade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logradouro</label>
                    <input type="text" name="logradouro" class="form-control" value="<?= htmlspecialchars($_POST['logradouro'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número</label>
                    <input type="text" name="numero" id="numero" class="form-control" inputmode="numeric" value="<?= htmlspecialchars($_POST['numero'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="invalid-feedback" id="numero-error"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" class="form-control" value="<?= htmlspecialchars($_POST['bairro'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($_POST['cidade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <input type="text" name="estado" class="form-control" value="<?= htmlspecialchars($_POST['estado'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Indicação</label>
                    <input type="text" name="indicacao" class="form-control" value="<?= htmlspecialchars($_POST['indicacao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagem (até 3 MB)</label>
                    <input type="file" name="imagem" class="form-control" accept="image/*" data-bs-toggle="tooltip" title="PNG, JPG ou WEBP até 3 MB">
                </div>
                <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="novoClienteAtivo" name="ativo" checked>
                        <label class="form-check-label" for="novoClienteAtivo">Cliente ativo</label>
                    </div>
                    <button type="submit" class="botao-salvar">Cadastrar cliente</button>
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

    <section class="cliente-lista-section">
        <div class="clientes-lista-header mb-3">
            <h2 class="secao-titulo mb-0">Clientes cadastrados</h2>
            <span class="count-pill"><?= count($clientes); ?> cliente(s) no sistema</span>
            <button type="button" class="toggle-form-btn" id="toggleClientesBtn" aria-controls="clientesListaWrapper" aria-expanded="true" aria-label="Mostrar/ocultar lista de clientes">
                <span id="toggleClientesIcon">-</span>
            </button>
        </div>
        <div id="clientesListaWrapper" class="clientes-lista-wrapper">

        <?php 
        $clientesAtivos = [];
        $clientesInativos = [];
        foreach ($clientes as $cl) {
            if ((int) ($cl['ativo'] ?? 1) === 1) { $clientesAtivos[] = $cl; } else { $clientesInativos[] = $cl; }
        }
        ?>

        <?php if (empty($clientes)): ?>
            <div class="alert alert-info">Nenhum cliente cadastrado até o momento.</div>
        <?php else: ?>
            <div class="servico-subsecao">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h3 class="servico-subtitulo">Clientes ativos</h3>
                    <span class="texto-suave"><?= count($clientesAtivos); ?> ativo(s)</span>
                </div>
                <?php if (empty($clientesAtivos)): ?>
                    <div class="alert alert-info">Nenhum cliente ativo cadastrado.</div>
                <?php else: ?>
                    <div class="servicos-grid">
                        <?php foreach ($clientesAtivos as $c) { renderizarClienteCard_ag($c); } ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="servico-subsecao mt-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h3 class="servico-subtitulo">Clientes inativos</h3>
                    <span class="texto-suave"><?= count($clientesInativos); ?> inativo(s)</span>
                </div>
                <?php if (empty($clientesInativos)): ?>
                    <div class="alert alert-info">Nenhum cliente marcado como inativo.</div>
                <?php else: ?>
                    <div class="servicos-grid">
                        <?php foreach ($clientesInativos as $c) { renderizarClienteCard_ag($c); } ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>
    </section>

    <!-- Modal Edição -->
    <div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" class="modal-body-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editarClienteId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome*</label>
                                <input type="text" name="nome" class="form-control" id="editarClienteNome" required maxlength="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" id="editarClienteEmail" placeholder="nome@exemplo.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefone</label>
                                <input type="tel" name="telefone" class="form-control" id="editarClienteTelefone">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data de nascimento</label>
                                <input type="date" name="data_nascimento" class="form-control" id="editarClienteDataNascimento">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Idade (calculada)</label>
                                <input type="number" name="idade" class="form-control" id="editarClienteIdade" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logradouro</label>
                                <input type="text" name="logradouro" class="form-control" id="editarClienteLogradouro">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Número</label>
                                <input type="text" name="numero" class="form-control" id="editarClienteNumero">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bairro</label>
                                <input type="text" name="bairro" class="form-control" id="editarClienteBairro">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cidade</label>
                                <input type="text" name="cidade" class="form-control" id="editarClienteCidade">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <input type="text" name="estado" class="form-control" id="editarClienteEstado">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Indicação</label>
                                <input type="text" name="indicacao" class="form-control" id="editarClienteIndicacao">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Imagem (até 3 MB)</label>
                                <input type="file" name="imagem" class="form-control" id="editarClienteImagem" accept="image/*">
                                <input type="hidden" name="imagem_atual" id="editarClienteImagemAtual">
                                <small class="form-text text-muted" id="editarClienteImagemInfo"></small>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="editarClienteAtivo" name="ativo">
                                    <label class="form-check-label" for="editarClienteAtivo">Cliente ativo</label>
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
    <div class="modal fade" id="modalExcluirCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" class="modal-body-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="excluirClienteId">
                    <div class="modal-header">
                        <h5 class="modal-title">Excluir cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Deseja realmente excluir este cliente?</p>
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
// Validações e interações (baseadas no módulo original)
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) { new bootstrap.Tooltip(tooltipTriggerEl); });

    // Controle de colapso do formulário "Novo cliente" (padrão fechado, abre após POST)
    const novoClienteSection = document.getElementById('novoClienteSection');
    const novoClienteFormWrap = document.getElementById('novoClienteForm');
    const toggleNovoBtn = document.getElementById('toggleNovoClienteBtn');
    const toggleNovoIcon = document.getElementById('toggleNovoClienteIcon');
    if (novoClienteSection && novoClienteFormWrap && toggleNovoBtn && toggleNovoIcon) {
        const initOpenData = (novoClienteSection.dataset.initOpen === '1');
        let open = initOpenData;
        const applyState = () => {
            toggleNovoBtn.setAttribute('aria-expanded', String(open));
            if (open) {
                novoClienteFormWrap.classList.remove('collapsed');
                toggleNovoIcon.textContent = '-';
            } else {
                novoClienteFormWrap.classList.add('collapsed');
                toggleNovoIcon.textContent = '+';
            }
        };
        applyState();
        toggleNovoBtn.addEventListener('click', () => { open = !open; applyState(); });
    }

    // Toggle da lista de clientes (padrão aberto)
    const clientesWrapper = document.getElementById('clientesListaWrapper');
    const toggleClientesBtn = document.getElementById('toggleClientesBtn');
    const toggleClientesIcon = document.getElementById('toggleClientesIcon');
    if (clientesWrapper && toggleClientesBtn && toggleClientesIcon) {
        let openClientes = true;
        const applyStateClientes = () => {
            toggleClientesBtn.setAttribute('aria-expanded', String(openClientes));
            if (openClientes) {
                clientesWrapper.classList.remove('collapsed');
                toggleClientesIcon.textContent = '-';
            } else {
                clientesWrapper.classList.add('collapsed');
                toggleClientesIcon.textContent = '+';
            }
        };
        applyStateClientes();
        toggleClientesBtn.addEventListener('click', () => { openClientes = !openClientes; applyStateClientes(); });
    }

    const emailInput = document.getElementById('email');
    const telefoneInput = document.getElementById('telefone');
    const numeroInput = document.getElementById('numero');
    const dataNascInput = document.getElementById('data_nascimento');
    const idadeInput = document.getElementById('idade');

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const v = this.value.trim();
            const el = document.getElementById('email-error');
            if (v === '') { this.classList.remove('is-invalid'); el.textContent = ''; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { this.classList.add('is-invalid'); el.textContent = 'Digite um email válido (ex: nome@exemplo.com)'; }
            else { this.classList.remove('is-invalid'); el.textContent = ''; }
        });
    }

    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(){
            const v = this.value.trim();
            const el = document.getElementById('telefone-error');
            if (v === '') { this.classList.remove('is-invalid'); el.textContent = ''; }
            else if (!/^[\d\s\(\)\+\-]{8,}$/.test(v)) { this.classList.add('is-invalid'); el.textContent = 'Use apenas números, espaços e símbolos ( ) + - (mínimo 8 caracteres)'; }
            else { this.classList.remove('is-invalid'); el.textContent = ''; }
        });
    }

    if (numeroInput) {
        numeroInput.addEventListener('input', function(){
            const v = this.value.trim();
            const el = document.getElementById('numero-error');
            if (v === '') { this.classList.remove('is-invalid'); el.textContent = ''; }
            else if (!/^\d+$/.test(v)) { this.classList.add('is-invalid'); el.textContent = 'Digite apenas números'; }
            else { this.classList.remove('is-invalid'); el.textContent = ''; }
        });
    }

    if (dataNascInput) {
        dataNascInput.addEventListener('change', function(){
            const el = document.getElementById('data_nascimento-error');
            const data = this.value;
            if (data === '') { this.classList.remove('is-invalid'); el.textContent = ''; }
            else {
                const d = new Date(data + 'T00:00:00');
                const hoje = new Date();
                if (d > hoje) { this.classList.add('is-invalid'); el.textContent = 'A data não pode ser futura'; }
                else if (hoje.getFullYear() - d.getFullYear() > 120) { this.classList.add('is-invalid'); el.textContent = 'Data muito antiga (máximo 120 anos)'; }
                else { this.classList.remove('is-invalid'); el.textContent = ''; }
            }
        });
    }

    const editarDataNascInput = document.getElementById('editarClienteDataNascimento');
    const editarIdadeInput = document.getElementById('editarClienteIdade');
    const calcAge = (dateStr) => {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        if (isNaN(d.getTime())) return '';
        const now = new Date();
        let age = now.getFullYear() - d.getFullYear();
        const m = now.getMonth() - d.getMonth();
        if (m < 0 || (m === 0 && now.getDate() < d.getDate())) age--;
        return age < 0 ? '' : String(age);
    };
    const updateAge = (input, output) => {
        if (input && output) {
            const up = () => {
                output.value = calcAge(input.value);
                if (input === dataNascInput) input.dispatchEvent(new Event('change'));
            };
            input.addEventListener('change', up);
            input.addEventListener('input', up);
        }
    };
    updateAge(dataNascInput, idadeInput);
    updateAge(editarDataNascInput, editarIdadeInput);

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

    // Modais editar/excluir
    const editarModal = document.getElementById('modalEditarCliente');
    const excluirModal = document.getElementById('modalExcluirCliente');

    const preencherModalEdicao = (cliente) => {
        document.getElementById('editarClienteId').value = cliente.id;
        document.getElementById('editarClienteNome').value = cliente.nome || '';
        document.getElementById('editarClienteEmail').value = cliente.email || '';
        document.getElementById('editarClienteTelefone').value = cliente.telefone || '';
        document.getElementById('editarClienteDataNascimento').value = cliente.data_nascimento || '';
        document.getElementById('editarClienteLogradouro').value = cliente.logradouro || '';
        document.getElementById('editarClienteNumero').value = cliente.numero || '';
        document.getElementById('editarClienteBairro').value = cliente.bairro || '';
        document.getElementById('editarClienteCidade').value = cliente.cidade || '';
        document.getElementById('editarClienteEstado').value = cliente.estado || '';
        document.getElementById('editarClienteIndicacao').value = cliente.indicacao || '';
        document.getElementById('editarClienteAtivo').checked = String(cliente.ativo) === '1';
        const inputArquivo = document.getElementById('editarClienteImagem'); if (inputArquivo) { inputArquivo.value = ''; }
        const imagemAtualInput = document.getElementById('editarClienteImagemAtual'); if (imagemAtualInput) { imagemAtualInput.value = cliente.imagem || ''; }
        const imagemInfo = document.getElementById('editarClienteImagemInfo'); if (imagemInfo) { imagemInfo.textContent = cliente.imagem ? 'Imagem atual: ' + cliente.imagem : 'Nenhuma imagem cadastrada.'; }
        const outIdade = document.getElementById('editarClienteIdade'); if (outIdade) { outIdade.value = calcAge(cliente.data_nascimento || ''); }
    };

    const prepararModalExclusao = (cliente) => {
        document.getElementById('excluirClienteId').value = cliente.id;
    };

    document.querySelectorAll('.servico-card').forEach((card) => {
        const dados = card.dataset.cliente ? JSON.parse(card.dataset.cliente) : null;
        if (!dados) return;
        const botaoEditar = card.querySelector('.acao-editar');
        const botaoExcluir = card.querySelector('.acao-excluir');
        if (botaoEditar) botaoEditar.addEventListener('click', () => preencherModalEdicao(dados));
        if (botaoExcluir) botaoExcluir.addEventListener('click', () => prepararModalExclusao(dados));
    });

    if (editarModal) {
        editarModal.addEventListener('hidden.bs.modal', () => {
            const form = editarModal.querySelector('form'); if (form) form.reset();
            const imagemInfo = document.getElementById('editarClienteImagemInfo'); if (imagemInfo) imagemInfo.textContent='';
        });
    }
    if (excluirModal) {
        excluirModal.addEventListener('hidden.bs.modal', () => { excluirModal.querySelector('form').reset(); });
    }
});
</script>
