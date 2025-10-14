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

// Tratamento de atualizacao e exclusao (modais de edicao/exclusao)
$acao = isset($_POST['action']) ? strtolower(trim((string) $_POST['action'])) : '';
if ($acao === 'update' || $acao === 'delete') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        respostaJson(false, 'Identificador do video invalido.');
    }

    if ($acao === 'delete') {
        // Buscar caminho para excluir arquivo local, se existir
        $stmtBusca = $conn->prepare('SELECT caminho FROM salao_videos WHERE id = ?');
        if ($stmtBusca) {
            $stmtBusca->bind_param('i', $id);
            if ($stmtBusca->execute()) {
                $resultado = $stmtBusca->get_result();
                if ($resultado && $resultado->num_rows > 0) {
                    $row = $resultado->fetch_assoc();
                    $caminho = isset($row['caminho']) ? (string) $row['caminho'] : '';
                    if ($caminho !== '' && !preg_match('~^https?://~i', $caminho)) {
                        $fisico = __DIR__ . '/' . ltrim($caminho, '/');
                        if (is_file($fisico)) {
                            @unlink($fisico);
                        }
                    }
                }
            }
            $stmtBusca->close();
        }

        $stmt = $conn->prepare('DELETE FROM salao_videos WHERE id = ?');
        if (!$stmt) {
            respostaJson(false, 'Nao foi possivel preparar a exclusao.');
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            respostaJson(true, 'Video excluido com sucesso.');
        }
        $erro = $stmt->error;
        $stmt->close();
        respostaJson(false, 'Erro ao excluir o video: ' . $erro);
    }

    // UPDATE
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    if ($titulo === '') {
        respostaJson(false, 'Informe um titulo.');
    }
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    // Em editar, o select usa name="id_categoria"
    $categoriaIdEditar = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== ''
        ? (int) $_POST['id_categoria']
        : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if ($categoriaIdEditar !== null) {
        // Valida categoria ativa
        $stmtCategoria = $conn->prepare('SELECT 1 FROM categoria_videos WHERE id = ? AND ativo = 1');
        if (!$stmtCategoria) {
            respostaJson(false, 'Erro ao validar a categoria informada.');
        }
        $stmtCategoria->bind_param('i', $categoriaIdEditar);
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

    if ($categoriaIdEditar === null) {
        $stmt = $conn->prepare('UPDATE salao_videos SET titulo = ?, descricao = ?, id_categoria = NULL, ativo = ? WHERE id = ?');
        if (!$stmt) {
            respostaJson(false, 'Nao foi possivel preparar a atualizacao.');
        }
        $stmt->bind_param('ssii', $titulo, $descricao, $ativo, $id);
    } else {
        $stmt = $conn->prepare('UPDATE salao_videos SET titulo = ?, descricao = ?, id_categoria = ?, ativo = ? WHERE id = ?');
        if (!$stmt) {
            respostaJson(false, 'Nao foi possivel preparar a atualizacao.');
        }
        $stmt->bind_param('ssiii', $titulo, $descricao, $categoriaIdEditar, $ativo, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        respostaJson(true, 'Video atualizado com sucesso.');
    }
    $erro = $stmt->error;
    $stmt->close();
    respostaJson(false, 'Nao foi possivel atualizar o video: ' . $erro);
}

// Fluxo de criacao (inserir por link/upload)
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
