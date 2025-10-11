<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    die('Acesso negado.');
}

require '../conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Inserir Video</title>
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4">
<h2 class="mb-4">Inserir Video</h2>

<div class="d-flex gap-3 mb-4">
    <button id="btnLink" class="btn btn-primary">Inserir link</button>
    <button id="btnUpload" class="btn btn-success">Upload de arquivo</button>
</div>

<div id="modalLink" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formLink" method="POST" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Inserir link de video</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
            <label class="form-label">Titulo:</label>
            <input type="text" name="titulo" class="form-control mb-3" required>
            <label class="form-label">Descricao:</label>
            <textarea name="descricao" class="form-control mb-3" rows="4"></textarea>
            <label class="form-label">Link do video:</label>
            <input type="url" name="link" class="form-control" required placeholder="https://">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="modalUpload" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formUpload" method="POST" enctype="multipart/form-data" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Upload de video</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
            <label class="form-label">Titulo:</label>
            <input type="text" name="titulo" class="form-control mb-3" required>
            <label class="form-label">Descricao:</label>
            <textarea name="descricao" class="form-control mb-3" rows="4"></textarea>
            <label class="form-label">Arquivo de video:</label>
            <input
                type="file"
                name="arquivo"
                accept="video/*"
                class="form-control"
                required
            >
            <small class="form-text text-muted">Tamanho maximo permitido: 15 MB.</small>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const MAX_UPLOAD_SIZE = 15 * 1024 * 1024; // 15 MB

const modalLink = new bootstrap.Modal(document.getElementById('modalLink'));
const modalUpload = new bootstrap.Modal(document.getElementById('modalUpload'));

document.getElementById('btnLink').addEventListener('click', () => modalLink.show());
document.getElementById('btnUpload').addEventListener('click', () => modalUpload.show());

document.getElementById('formLink').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    formData.append('tipo', 'link');

    fetch('../video_action.php', { method: 'POST', body: formData })
        .then((res) => res.json())
        .then((data) => {
            alert(data.message || 'Operacao concluida.');
            if (data.success) {
                modalLink.hide();
                this.reset();
                location.reload();
            }
        })
        .catch(() => {
            alert('Nao foi possivel enviar os dados. Tente novamente.');
        });
});

document.getElementById('formUpload').addEventListener('submit', function (event) {
    event.preventDefault();
    const arquivoInput = this.querySelector('input[name="arquivo"]');
    if (arquivoInput && arquivoInput.files.length > 0) {
        const arquivo = arquivoInput.files[0];
        if (arquivo.size > MAX_UPLOAD_SIZE) {
            alert('O arquivo selecionado excede o limite de 15 MB.');
            return;
        }
    }

    const formData = new FormData(this);
    formData.append('tipo', 'upload');

    fetch('../video_action.php', { method: 'POST', body: formData })
        .then((res) => res.json())
        .then((data) => {
            alert(data.message || 'Operacao concluida.');
            if (data.success) {
                modalUpload.hide();
                this.reset();
                location.reload();
            }
        })
        .catch(() => {
            alert('Nao foi possivel enviar os dados. Tente novamente.');
        });
});
</script>
</body>
</html>
