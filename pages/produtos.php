<?php
session_start();
$adminLogado = isset($_SESSION['usuario_id']);

require '../conexao.php';

$categorias = [];
$resultadoCategorias = $conn->query(
    'SELECT id, nome, descricao FROM salao_categorias_produtos WHERE ativo = 1 ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultadoCategorias) {
    while ($linha = $resultadoCategorias->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $categorias[] = $linha;
    }
    $resultadoCategorias->free();
}

$produtosPorCategoria = [];
$produtosSemCategoria = [];
$resultadoProdutos = $conn->query(
    'SELECT id, categoria_id, nome, descricao, preco, preco_promocional, imagem
     FROM salao_produtos
     WHERE ativo = 1
     ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultadoProdutos) {
    while ($linha = $resultadoProdutos->fetch_assoc()) {
        $categoriaId = isset($linha['categoria_id']) ? (int) $linha['categoria_id'] : null;
        if ($categoriaId !== null) {
            if (!isset($produtosPorCategoria[$categoriaId])) {
                $produtosPorCategoria[$categoriaId] = [];
            }
            $produtosPorCategoria[$categoriaId][] = $linha;
        } else {
            $produtosSemCategoria[] = $linha;
        }
    }
    $resultadoProdutos->free();
}

$temProdutosCategorizados = false;
foreach ($produtosPorCategoria as $lista) {
    if (!empty($lista)) {
        $temProdutosCategorizados = true;
        break;
    }
}
$temProdutos = $temProdutosCategorizados || !empty($produtosSemCategoria);

function resolverImagemProduto(?string $valor): string
{
    if ($valor === null || trim($valor) === '') {
        return '';
    }

    $valor = trim($valor);
    if (preg_match('/^(https?:)?\/\//i', $valor)) {
        return $valor;
    }

    if (strpos($valor, '../') === 0 || strpos($valor, '../../') === 0) {
        return $valor;
    }

    if ($valor !== '' && $valor[0] === '/') {
        return $valor;
    }

    return '../' . ltrim($valor, '/');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Produtos - Salome Beleza</title>
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilo.css">
  <style>
    .produtos-banner {
      position: relative;
      min-height: 320px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      background: url('../img/produtos/imagens/baner_alisamento.png') center/cover no-repeat;
    }

    .produtos-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
    }

    .produtos-banner-conteudo {
      position: relative;
      max-width: 720px;
      text-align: center;
      color: #fff;
    }

    .produtos-banner-conteudo h1 {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 18px;
    }

    .produtos-banner-conteudo p {
      font-size: 1.1rem;
      margin-bottom: 0;
    }

    .produtos-publico-wrapper {
      padding: 90px 0 80px;
      background: linear-gradient(180deg, #f7ecc2 0%, #fef8ec 100%);
    }

    .produtos-container {
      max-width: 1120px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .categoria-bloco {
      margin-bottom: 70px;
    }

    .categoria-titulo {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 32px;
      color: #2d1753;
    }

    .categoria-titulo h2 {
      font-size: 2.3rem;
      font-weight: 700;
      margin: 0;
    }

    .categoria-descricao {
      font-size: 1rem;
      color: #4c3d72;
      max-width: 640px;
    }

    .categoria-produtos-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 26px;
    }

    .produto-card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(33, 26, 86, 0.12);
      display: flex;
      flex-direction: column;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .produto-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 28px 48px rgba(33, 26, 86, 0.18);
    }

    .produto-card figure {
      margin: 0;
      width: 100%;
      padding-top: 62%;
      background: #f0f0f0;
      position: relative;
      overflow: hidden;
    }

    .produto-card figure img {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .produto-card-body {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      flex: 1;
    }

    .produto-card-body h3 {
      font-size: 1.4rem;
      font-weight: 700;
      color: #2d1753;
      margin: 0;
    }

    .produto-descricao {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #5f5f72;
    }

    .produto-preco {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: auto;
      font-weight: 600;
      color: #2f215b;
      font-size: 1.1rem;
    }

    .produto-preco span {
      display: inline-block;
    }

    .produto-preco .preco-original {
      color: #8072a6;
      text-decoration: line-through;
      font-size: 0.95rem;
      font-weight: 500;
    }

    .produtos-vazio {
      min-height: 40vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 1.4rem;
      color: #5f5f72;
      font-weight: 600;
    }

    .produtos-banner-descricao{
      text-transform: uppercase;
    }


    @media (max-width: 768px) {
      .produtos-banner {
        min-height: 260px;
        padding: 60px 20px;
      }

      .produtos-banner-conteudo h1 {
        font-size: 2.2rem;
      }

      .categoria-titulo h2 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>
<?php
$menuShowAdminIcon = false;
include __DIR__ . '/../class/menu.php';
?>

<section class="produtos-banner">
  <div class="produtos-banner-conteudo">
    <p class="produtos-banner-descricao">Qualidade Garantida</p>
    <h1>Nossos produtos</h1>
    <p>Conheca as linhas que entregam cuidado e beleza com a assinatura do Studio Salomé.</p>
  </div>
</section>

<main class="produtos-publico-wrapper">
  <div class="produtos-container">
    <?php if (!$temProdutos): ?>
      <div class="produtos-vazio">Nenhum produto disponivel no momento. Volte em breve!</div>
    <?php else: ?>
      <?php foreach ($categorias as $categoria): ?>
        <?php
          $lista = $produtosPorCategoria[$categoria['id']] ?? [];
          if (empty($lista)) {
              continue;
          }
          $descricaoCategoria = !empty($categoria['descricao'])
              ? nl2br(htmlspecialchars($categoria['descricao'], ENT_QUOTES, 'UTF-8'))
              : '';
        ?>
        <section class="categoria-bloco">
          <header class="categoria-titulo">
            <h2><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if ($descricaoCategoria !== ''): ?>
              <p class="categoria-descricao"><?= $descricaoCategoria; ?></p>
            <?php endif; ?>
          </header>
          <div class="categoria-produtos-grid">
            <?php foreach ($lista as $produto): ?>
              <?php
                $imagemSrc = resolverImagemProduto($produto['imagem'] ?? null);
                $descricaoProduto = !empty($produto['descricao'])
                    ? nl2br(htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8'))
                    : 'Descricao em breve.';
                $preco = 'R$ ' . number_format((float) $produto['preco'], 2, ',', '.');
                $precoPromocional = null;
                if (
                    isset($produto['preco_promocional']) &&
                    is_numeric($produto['preco_promocional']) &&
                    (float) $produto['preco_promocional'] > 0
                ) {
                    $precoPromocional = 'R$ ' . number_format((float) $produto['preco_promocional'], 2, ',', '.');
                }
              ?>
              <article class="produto-card">
                <?php if ($imagemSrc !== ''): ?>
                  <figure>
                    <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                  </figure>
                <?php endif; ?>
                <div class="produto-card-body">
                  <h3><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <div class="produto-descricao"><?= $descricaoProduto; ?></div>
                  <div class="produto-preco">
                    <?php if ($precoPromocional !== null): ?>
                      <span><?= $precoPromocional; ?></span>
                      <span class="preco-original"><?= $preco; ?></span>
                    <?php else: ?>
                      <span><?= $preco; ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

      <?php if (!empty($produtosSemCategoria)): ?>
        <section class="categoria-bloco">
          <header class="categoria-titulo">
            <h2>Outros produtos</h2>
          </header>
          <div class="categoria-produtos-grid">
            <?php foreach ($produtosSemCategoria as $produto): ?>
              <?php
                $imagemSrc = resolverImagemProduto($produto['imagem'] ?? null);
                $descricaoProduto = !empty($produto['descricao'])
                    ? nl2br(htmlspecialchars($produto['descricao'], ENT_QUOTES, 'UTF-8'))
                    : 'Descricao em breve.';
                $preco = 'R$ ' . number_format((float) $produto['preco'], 2, ',', '.');
                $precoPromocional = null;
                if (
                    isset($produto['preco_promocional']) &&
                    is_numeric($produto['preco_promocional']) &&
                    (float) $produto['preco_promocional'] > 0
                ) {
                    $precoPromocional = 'R$ ' . number_format((float) $produto['preco_promocional'], 2, ',', '.');
                }
              ?>
              <article class="produto-card">
                <?php if ($imagemSrc !== ''): ?>
                  <figure>
                    <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                  </figure>
                <?php endif; ?>
                <div class="produto-card-body">
                  <h3><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <div class="produto-descricao"><?= $descricaoProduto; ?></div>
                  <div class="produto-preco">
                    <?php if ($precoPromocional !== null): ?>
                      <span><?= $precoPromocional; ?></span>
                      <span class="preco-original"><?= $preco; ?></span>
                    <?php else: ?>
                      <span><?= $preco; ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../class/contatoFooter.php'; ?>
<?php include __DIR__ . '/../class/modais.php'; ?>
</body>
</html>
