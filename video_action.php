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

// Verificar se é uma ação de edição ou exclusão
$action = $_POST['action'] ?? '';

if ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $categoriaId = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== ''
        ? (int) $_POST['id_categoria']
        : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($id <= 0 || $titulo === '') {
        respostaJson(false, 'ID inválido ou título obrigatório.');
    }

    // Validar categoria se informada
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
            respostaJson(false, 'Categoria selecionada não está disponível.');
        }
        $stmtCategoria->close();
    }

    // Atualizar vídeo
    if ($categoriaId !== null) {
        $stmt = $conn->prepare(
            'UPDATE salao_videos SET titulo = ?, descricao = ?, id_categoria = ?, ativo = ? WHERE id = ?'
        );
        if (!$stmt) {
            respostaJson(false, 'Erro ao preparar a operação.');
        }
        $stmt->bind_param('ssiii', $titulo, $descricao, $categoriaId, $ativo, $id);
    } else {
        $stmt = $conn->prepare(
            'UPDATE salao_videos SET titulo = ?, descricao = ?, id_categoria = NULL, ativo = ? WHERE id = ?'
        );
        if (!$stmt) {
            respostaJson(false, 'Erro ao preparar a operação.');
        }
        $stmt->bind_param('ssii', $titulo, $descricao, $ativo, $id);
    }

    if ($stmt->execute()) {
        respostaJson(true, 'Vídeo atualizado com sucesso.');
    }

    respostaJson(false, 'Não foi possível atualizar o vídeo.');
}

if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        respostaJson(false, 'ID inválido para exclusão.');
    }

    // Buscar informações do vídeo para remover arquivo se necessário
    $stmtBusca = $conn->prepare('SELECT caminho FROM salao_videos WHERE id = ?');
    if ($stmtBusca === false) {
        respostaJson(false, 'Erro ao buscar informações do vídeo.');
    }
    $stmtBusca->bind_param('i', $id);
    if (!$stmtBusca->execute()) {
        $stmtBusca->close();
        respostaJson(false, 'Erro ao buscar informações do vídeo.');
    }
    $stmtBusca->bind_result($caminho);
    $stmtBusca->fetch();
    $stmtBusca->close();

    // Excluir vídeo do banco
    $stmt = $conn->prepare('DELETE FROM salao_videos WHERE id = ?');
    if (!$stmt) {
        respostaJson(false, 'Erro ao preparar a exclusão.');
    }
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        // Remover arquivo físico se existir
        if ($caminho && !preg_match('/^(https?:)?\/\//i', $caminho)) {
            $caminhoFisico = __DIR__ . '/' . $caminho;
            if (file_exists($caminhoFisico)) {
                @unlink($caminhoFisico);
            }
        }
        respostaJson(true, 'Vídeo excluído com sucesso.');
    }

    respostaJson(false, 'Não foi possível excluir o vídeo.');
}

// Processamento de inserção (código original)
$titulo = trim((string) ($_POST['titulo'] ?? ''));
if ($titulo === '') {
    respostaJson(false, 'Informe um titulo.');
}

$descricao = trim((string) ($_POST['descricao'] ?? ''));
$tipo = strtolower(trim((string) ($_POST['tipo'] ?? '')));
$categoriaId = isset($_POST['categoria']) && $_POST['categoria'] !== ''
    ? (int) $_POST['categoria']
    : null;

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
