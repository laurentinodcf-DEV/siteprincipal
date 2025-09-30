<?php
session_start();
if(!isset($_SESSION['usuario_id'])){
    echo json_encode(['success'=>false,'message'=>'Acesso negado']);
    exit;
}

include '../conexao.php';

$titulo = $_POST['titulo'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$tipo = $_POST['tipo'] ?? '';

if($tipo == 'link'){
    $link = $_POST['link'] ?? '';
    $stmt = $conn->prepare("INSERT INTO videos (titulo, descricao, tipo, link) VALUES (?, ?, 'link', ?)");
    $stmt->bind_param("sss", $titulo, $descricao, $link);
    if($stmt->execute()){
        echo json_encode(['success'=>true,'message'=>'Link inserido com sucesso']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Erro ao inserir']);
    }

}elseif($tipo == 'upload'){
    if(isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0){
        // Inserir registro inicial para pegar id
        $stmt = $conn->prepare("INSERT INTO videos (titulo, descricao, tipo) VALUES (?, ?, 'upload')");
        $stmt->bind_param("ss", $titulo, $descricao);
        $stmt->execute();
        $id = $conn->insert_id;

        $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
        $novoNome = $id . '.' . $ext;
        $dir = '../uploads/videos/';
        if(!is_dir($dir)) mkdir($dir, 0777, true);
        $destino = $dir . $novoNome;

        if(move_uploaded_file($_FILES['arquivo']['tmp_name'], $destino)){
            $stmt = $conn->prepare("UPDATE videos SET arquivo=? WHERE id=?");
            $stmt->bind_param("si", $novoNome, $id);
            $stmt->execute();
            echo json_encode(['success'=>true,'message'=>'Vídeo enviado com sucesso']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Erro ao mover arquivo']);
        }
    } else {
        echo json_encode(['success'=>false,'message'=>'Arquivo inválido']);
    }

}else{
    echo json_encode(['success'=>false,'message'=>'Tipo inválido']);
}
