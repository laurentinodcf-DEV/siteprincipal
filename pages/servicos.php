<?php
session_start();
$adminLogado = isset($_SESSION['usuario_id']);

require '../conexao.php';

$servicos = [];
$resultado = $conn->query(
    'SELECT nome, descricao, imagem FROM salao_servicos WHERE ativo = 1 AND ordem IS NOT NULL ORDER BY ordem ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $servicos[] = $linha;
    }
    $resultado->free();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Servicos - Salome Beleza</title>
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/styleProjet.css">
  <link rel="stylesheet" href="../css/estilo.css">
  <style>
    .servicos-publico-wrapper {
      padding: 120px 0 80px;
      background: linear-gradient(180deg, #f5e3a3 0%, #c7993e 100%);
    }

    .servico-publico-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 48px;
      max-width: 1100px;
      margin: 0 auto 60px;
      padding: 60px 50px;
      border-radius: 26px;
      background: rgba(255, 255, 255, 0.12);
      box-shadow: 0 26px 80px rgba(0, 0, 0, 0.18);
      backdrop-filter: blur(4px);
    }

    .servico-publico-card:nth-child(even) {
      flex-direction: row-reverse;
      background: rgba(255, 255, 255, 0.18);
    }

    .servico-publico-texto {
      flex: 1;
      color: #fff;
    }

    .servico-publico-texto h2 {
      font-size: 2.6rem;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .servico-publico-texto p {
      font-size: 1.15rem;
      line-height: 1.6;
      margin-bottom: 0;
      max-width: 90%;
    }

    .servico-publico-imagem {
      flex-shrink: 0;
      width: 360px;
      height: 260px;
      border-radius: 22px;
      overflow: hidden;
      position: relative;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
    }

    .servico-publico-imagem img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    @media (max-width: 992px) {
      .servico-publico-card {
        flex-direction: column;
        text-align: center;
        padding: 40px 32px;
      }

      .servico-publico-card:nth-child(even) {
        flex-direction: column;
      }

      .servico-publico-texto p {
        max-width: 100%;
      }

      .servico-publico-imagem {
        width: 100%;
        height: 240px;
      }
    }

    .servicos-publico-vazio {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 60vh;
      font-size: 1.6rem;
      font-weight: 600;
      color: #4b5563;
      text-align: center;
    }
  </style>
</head>
<body>
<?php
$menuShowAdminIcon = false;
include __DIR__ . '/../class/menu.php';
?>

<main class="servicos-publico-wrapper">
  <?php if (empty($servicos)): ?>
    <div class="servicos-publico-vazio">Nao ha servicos cadastrados!</div>
  <?php else: ?>
    <?php foreach ($servicos as $servico): ?>
      <?php
        $imagemSrc = '';
        if (!empty($servico['imagem'])) {
            $imagemValor = (string) $servico['imagem'];
            if (preg_match('/^(https?:)?\/\//i', $imagemValor)) {
                $imagemSrc = $imagemValor;
            } elseif (strpos($imagemValor, '../') === 0) {
                $imagemSrc = $imagemValor;
            } elseif ($imagemValor !== '' && $imagemValor[0] === '/') {
                $imagemSrc = $imagemValor;
            } else {
                $imagemSrc = '../' . ltrim($imagemValor, '/');
            }
        }
        $descricaoFormatada = !empty($servico['descricao'])
            ? nl2br(htmlspecialchars($servico['descricao'], ENT_QUOTES, 'UTF-8'))
            : 'Descricao em breve.';
      ?>
      <section class="servico-publico-card">
        <div class="servico-publico-texto">
          <h2><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
          <p><?= $descricaoFormatada; ?></p>
        </div>
        <?php if ($imagemSrc !== ''): ?>
          <figure class="servico-publico-imagem">
            <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
          </figure>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/../class/contatoFooter.php'; ?>
<?php include __DIR__ . '/../class/modais.php'; ?>
</body>
</html>
