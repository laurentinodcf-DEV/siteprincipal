<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

require __DIR__ . '/conexao.php';

function respostaJson(bool $success, string $message, array $extra = []): void
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit;
}

function obterProximaOrdemVideo(mysqli $conn): int
{
    $resultado = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM salao_videos WHERE ativo = 1');
    if ($resultado) {
        $linha = $resultado->fetch_assoc();
        $resultado->free();
        return (int) ($linha['proxima'] ?? 1);
    }
    return 1;
}

// Rotas por acao (update/delete) usadas pelos modais
$acao = strtolower(trim((string)($_POST['action'] ?? '')));
if ($acao === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        respostaJson(false, 'Video invalido.');
    }

    // Buscar caminho para possivel remocao do arquivo
    $caminho = null;
    $stmtBusca = $conn->prepare('SELECT caminho FROM salao_videos WHERE id = ?');
    if ($stmtBusca) {
        $stmtBusca->bind_param('i', $id);
        if ($stmtBusca->execute()) {
            $stmtBusca->bind_result($caminho);
            $stmtBusca->fetch();
        }
        $stmtBusca->close();
    }

    $stmt = $conn->prepare('DELETE FROM salao_videos WHERE id = ?');
    if ($stmt === false) {
        respostaJson(false, 'Erro ao preparar exclusao.');
    }
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        // Remover arquivo fisico se for um upload local em videos/vdcadastro
        if ($caminho) {
            $p = str_replace('\\', '/', (string)$caminho);
            if (strpos($p, 'videos/vdcadastro/') === 0) {
                $caminhoFisico = __DIR__ . '/' . $p;
                if (is_file($caminhoFisico)) {
                    @unlink($caminhoFisico);
                }
            }
        }
        respostaJson(true, 'Video removido.');
    }
    respostaJson(false, 'Nao foi possivel excluir o video.');
}

if ($acao === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    // Aceita tanto id_categoria (editar) quanto categoria (criar antigo)
    $categoriaId = null;
    if (isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '') {
        $categoriaId = (int) $_POST['id_categoria'];
    } elseif (isset($_POST['categoria']) && $_POST['categoria'] !== '') {
        $categoriaId = (int) $_POST['categoria'];
    }
    $ativo = isset($_POST['ativo']) ? (int) $_POST['ativo'] : 0;

    if ($id <= 0 || $titulo === '') {
        respostaJson(false, 'Informe os dados obrigatorios.');
    }

    if ($categoriaId !== null) {
        $stmtCategoria = $conn->prepare('SELECT 1 FROM categoria_videos WHERE id = ? AND ativo = 1');
        if ($stmtCategoria === false) {
            respostaJson(false, 'Erro ao validar a categoria informada.');
        }
        $stmtCategoria->bind_param('i', $categoriaId);
        if (!$stmtCategoria->execute()) {
            $stmtCategoria->close();
            respostaJson(false, 'Erro ao validar a categoria informada.');
        }
        $stmtCategoria->store_result();
        if ($stmtCategoria->num_rows === 0) {
            $stmtCategoria->close();
            respostaJson(false, 'Categoria selecionada nao esta disponivel.');
        }
        $stmtCategoria->close();
    }

    // Buscar antigo ativo/ordem
    $ativoAnterior = 0;
    $ordemAtual = null;
    $stmtBusca = $conn->prepare('SELECT ativo, ordem FROM salao_videos WHERE id = ?');
    if ($stmtBusca) {
        $stmtBusca->bind_param('i', $id);
        if ($stmtBusca->execute()) {
            $stmtBusca->bind_result($ativoAnterior, $ordemAtual);
            $stmtBusca->fetch();
        }
        $stmtBusca->close();
    }

    $ordemParam = null;
    if ($ativo === 1) {
        if ((int)$ativoAnterior === 1 && $ordemAtual !== null) {
            $ordemParam = (int) $ordemAtual;
        } else {
            $ordemParam = obterProximaOrdemVideo($conn);
        }
    }

    $stmt = $conn->prepare(
        'UPDATE salao_videos
         SET titulo = ?, descricao = ?, id_categoria = ?, ativo = ?, ordem = ?
         WHERE id = ?'
    );
    if ($stmt === false) {
        respostaJson(false, 'Erro ao preparar atualizacao.');
    }
    $descricaoParam = $descricao !== '' ? $descricao : null;
    $categoriaParam = $categoriaId;
    $ordemBind = $ordemParam;
    $stmt->bind_param('ssiiii', $titulo, $descricaoParam, $categoriaParam, $ativo, $ordemBind, $id);
    if ($stmt->execute()) {
        respostaJson(true, 'Video atualizado com sucesso.');
    }
    respostaJson(false, 'Nao foi possivel atualizar o video.');
}

// Fluxo de criacao (inserir) por tipo: link|upload
$tipo = strtolower(trim((string) ($_POST['tipo'] ?? '')));
$titulo = trim((string) ($_POST['titulo'] ?? ''));
$descricao = trim((string) ($_POST['descricao'] ?? ''));
$categoriaId = isset($_POST['categoria']) && $_POST['categoria'] !== ''
    ? (int) $_POST['categoria']
    : null;

if ($tipo === '') {
    respostaJson(false, 'Tipo de operacao invalido.');
}

if ($titulo === '') {
    respostaJson(false, 'Informe um titulo.');
}

if ($categoriaId !== null) {
    $stmtCategoria = $conn->prepare(
        'SELECT 1 FROM categoria_videos WHERE id = ? AND ativo = 1'
    );
    if ($stmtCategoria === false) {
        respostaJson(false, 'Erro ao validar a categoria informada.');
    }
    $stmtCategoria->bind_param('i', $categoriaId);
    if (!$stmtCategoria->execute()) {
        $stmtCategoria->close();
        respostaJson(false, 'Erro ao validar a categoria informada.');
    }
    $stmtCategoria->store_result();
    if ($stmtCategoria->num_rows === 0) {
        $stmtCategoria->close();
        respostaJson(false, 'Categoria selecionada nao esta disponivel.');
    }
    $stmtCategoria->close();
}

if ($tipo === 'link') {
    $linkOriginal = trim((string) ($_POST['link'] ?? ''));
    if ($linkOriginal === '' || !filter_var($linkOriginal, FILTER_VALIDATE_URL)) {
        respostaJson(false, 'Informe um link valido para o video.');
    }

    if ($categoriaId !== null) {
        $stmt = $conn->prepare(
            'INSERT INTO salao_videos (titulo, descricao, url, id_categoria, ativo) VALUES (?, ?, ?, ?, 1)'
        );
        if (!$stmt) {
            respostaJson(false, 'Erro ao preparar a operacao.');
        }
        $stmt->bind_param('sssi', $titulo, $descricao, $linkOriginal, $categoriaId);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO salao_videos (titulo, descricao, url, ativo) VALUES (?, ?, ?, 1)'
        );
        if (!$stmt) {
            respostaJson(false, 'Erro ao preparar a operacao.');
        }
        $stmt->bind_param('sss', $titulo, $descricao, $linkOriginal);
    }

    if ($stmt->execute()) {
        respostaJson(true, 'Link inserido com sucesso.');
    }

    respostaJson(false, 'Nao foi possivel salvar o link informado.');
}

if ($tipo === 'upload') {
    if (!isset($_FILES['arquivo']) || !is_array($_FILES['arquivo'])) {
        respostaJson(false, 'Selecione um arquivo de video para enviar.');
    }

    $arquivo = $_FILES['arquivo'];
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        respostaJson(false, 'Falha ao receber o arquivo de video.');
    }

    $tamanhoMaximo = 15 * 1024 * 1024; // 15 MB
    if ($arquivo['size'] > $tamanhoMaximo) {
        respostaJson(false, 'O arquivo excede o limite de 15 MB.');
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $extensoesPermitidas = ['mp4', 'm4v', 'mov', 'webm', 'ogg', 'ogv', 'mkv', 'avi'];
    if ($extensao === '' || !in_array($extensao, $extensoesPermitidas, true)) {
        respostaJson(false, 'Formato de video nao suportado.');
    }

    $diretorioDestino = __DIR__ . '/videos/vdcadastro/';
    if (!is_dir($diretorioDestino) && !mkdir($diretorioDestino, 0775, true) && !is_dir($diretorioDestino)) {
        respostaJson(false, 'Nao foi possivel preparar o diretorio de videos.');
    }

    $nomeArquivo = uniqid('video_', true) . '.' . $extensao;
    $caminhoFisico = $diretorioDestino . $nomeArquivo;
    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFisico)) {
        respostaJson(false, 'Erro ao salvar o arquivo enviado.');
    }

    $caminhoRelativo = 'videos/vdcadastro/' . $nomeArquivo;

    if ($categoriaId !== null) {
        $stmt = $conn->prepare(
            'INSERT INTO salao_videos (titulo, descricao, caminho, id_categoria, ativo) VALUES (?, ?, ?, ?, 1)'
        );
        if (!$stmt) {
            respostaJson(false, 'Erro ao preparar a operacao.');
        }
        $stmt->bind_param('sssi', $titulo, $descricao, $caminhoRelativo, $categoriaId);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO salao_videos (titulo, descricao, caminho, ativo) VALUES (?, ?, ?, 1)'
        );
        if (!$stmt) {
            respostaJson(false, 'Erro ao preparar a operacao.');
        }
        $stmt->bind_param('sss', $titulo, $descricao, $caminhoRelativo);
    }

    if ($stmt->execute()) {
        respostaJson(true, 'Video enviado com sucesso.');
    }

    @unlink($caminhoFisico);
    respostaJson(false, 'Nao foi possivel salvar o video enviado.');
}

respostaJson(false, 'Tipo de operacao invalido.');
