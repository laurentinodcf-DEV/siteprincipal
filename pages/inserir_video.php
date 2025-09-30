<?php
session_start();
if(!isset($_SESSION['usuario_id'])){
    die("Acesso negado.");
}
include '../conexao.php'; // ajusta o caminho
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inserir Vídeo</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<h2>Inserir Vídeo</h2>

<!-- Botões de escolha -->
<button id="btnLink" class="btn btn-primary">Inserir Link</button>
<button id="btnUpload" class="btn btn-success">Upload de Arquivo</button>

<!-- Modal Link -->
<div id="modalLink" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formLink" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Inserir Link de Vídeo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <label>Título:</label>
            <input type="text" name="titulo" class="form-control" required>
            <label>Descrição:</label>
            <textarea name="descricao" class="form-control"></textarea>
            <label>Link do vídeo:</label>
            <input type="url" name="link" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Upload -->
<div id="modalUpload" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUpload" method="POST" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Upload de Vídeo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <label>Título:</label>
            <input type="text" name="titulo" class="form-control" required>
            <label>Descrição:</label>
            <textarea name="descricao" class="form-control"></textarea>
            <label>Arquivo de vídeo:</label>
            <input type="file" name="arquivo" accept="video/*" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Mostrar modais
document.getElementById('btnLink').addEventListener('click', () => new bootstrap.Modal(document.getElementById('modalLink')).show());
document.getElementById('btnUpload').addEventListener('click', () => new bootstrap.Modal(document.getElementById('modalUpload')).show());

// Enviar formulário Link
document.getElementById('formLink').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('tipo','link');

    fetch('../video_action.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) location.reload();
        });
});

// Enviar formulário Upload
document.getElementById('formUpload').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('tipo','upload');

    fetch('../video_action.php', { method:'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.success) location.reload();
        });
});
</script>

</body>
</html>
