<?php
session_start();
$adminLogado = isset($_SESSION['usuario_id']);

require '../conexao.php';

$categoriasBrutas = [];
$resultadoCategorias = $conn->query(
    'SELECT id, nome, descricao FROM salao_categorias_produtos WHERE ativo = 1 ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultadoCategorias) {
    while ($linha = $resultadoCategorias->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $categoriasBrutas[] = $linha;
    }
    $resultadoCategorias->free();
}

$produtosBrutos = [];
$resultadoProdutos = $conn->query(
    'SELECT id, categoria_id, nome, descricao, preco, preco_promocional, sku, imagem
     FROM salao_produtos
     WHERE ativo = 1
     ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultadoProdutos) {
    while ($linha = $resultadoProdutos->fetch_assoc()) {
        $linha['id'] = (int) $linha['id'];
        $linha['categoria_id'] = isset($linha['categoria_id']) ? (int) $linha['categoria_id'] : null;
        $produtosBrutos[] = $linha;
    }
    $resultadoProdutos->free();
}

$categoriasDados = [];
$categoriasIndex = [];
foreach ($categoriasBrutas as $categoria) {
    $slug = 'cat-' . $categoria['id'];
    $categoriasDados[] = [
        'id'          => $categoria['id'],
        'nome'        => $categoria['nome'],
        'descricao'   => $categoria['descricao'],
        'slug'        => $slug,
        'produtos'    => [],
        'quantidade'  => 0,
    ];
    $categoriasIndex[$categoria['id']] = count($categoriasDados) - 1;
}

$produtosSemCategoria = [];
foreach ($produtosBrutos as $produto) {
    $categoriaId = $produto['categoria_id'];
    if ($categoriaId !== null && isset($categoriasIndex[$categoriaId])) {
        $indice = $categoriasIndex[$categoriaId];
        $categoriasDados[$indice]['produtos'][] = $produto;
    } else {
        $produtosSemCategoria[] = $produto;
    }
}

$totalProdutos = 0;
foreach ($categoriasDados as &$categoria) {
    $categoria['quantidade'] = count($categoria['produtos']);
    $totalProdutos += $categoria['quantidade'];
}
unset($categoria);

$categoriaOutros = null;
if (!empty($produtosSemCategoria)) {
    $categoriaOutros = [
        'id'         => null,
        'nome'       => 'Outros produtos',
        'descricao'  => '',
        'slug'       => 'sem-categoria',
        'produtos'   => $produtosSemCategoria,
        'quantidade' => count($produtosSemCategoria),
    ];
    $totalProdutos += $categoriaOutros['quantidade'];
}

$temProdutos = $totalProdutos > 0;

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

function formatarCodigoProduto(array $produto): string
{
    $sku = trim((string) ($produto['sku'] ?? ''));
    if ($sku !== '') {
        return $sku;
    }

    $id = isset($produto['id']) ? (int) $produto['id'] : 0;
    return str_pad((string) $id, 6, '0', STR_PAD_LEFT);
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
      padding: 70px 0 90px;
      background: linear-gradient(180deg, #f7ecc2 0%, #fef8ec 100%);
    }

    .produtos-container {
      max-width: 1180px;
      margin: 0 auto;
      padding: 0 26px;
    }

    .produtos-layout {
      display: grid;
      grid-template-columns: minmax(240px, 280px) minmax(0, 1fr);
      gap: 44px;
      align-items: start;
    }

    .categoria-sidebar {
      background: rgba(255, 255, 255, 0.94);
      border-radius: 28px;
      box-shadow: 0 24px 48px rgba(68, 48, 115, 0.12);
      padding: 24px 0 26px;
      align-self: start;
    }

    .categoria-lista {
      display: flex;
      flex-direction: column;
      gap: 0;
      padding: 0;
    }

    .categoria-divisor {
      height: 1px;
      margin: 14px 26px 12px;
      background: rgba(104, 84, 150, 0.16);
      border-radius: 1px;
    }

    .categoria-item {
      border: none;
      background: transparent;
      text-align: left;
      margin: 0 26px;
      padding: 14px 20px;
      border-radius: 16px;
      font-size: 1rem;
      font-weight: 600;
      color: #423169;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    .categoria-item .categoria-nome {
      flex: 1;
    }

    .categoria-item .categoria-quantidade {
      font-size: 0.9rem;
      color: #8c82b1;
      margin-left: 12px;
    }

    .categoria-item--todos {
      margin-bottom: 6px;
    }

    .categoria-item--todos .categoria-quantidade {
      color: #6b5baa;
      font-weight: 500;
    }

    .categoria-item--todos .categoria-quantidade::before {
      content: '(';
    }

    .categoria-item--todos .categoria-quantidade::after {
      content: ')';
    }

    .categoria-item:hover,
    .categoria-item.active {
      background: rgba(118, 88, 180, 0.16);
      color: #2d1753;
      box-shadow: inset 0 0 0 1px rgba(118, 88, 180, 0.25);
    }

    .categoria-item.active .categoria-quantidade {
      color: #4c3a85;
    }

    .produtos-area {
      background: transparent;
      display: flex;
      flex-direction: column;
      gap: 24px;
      align-self: start;
      min-width: 0;
    }

    .produtos-area-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 12px;
    }

    .produtos-area-header h2 {
      font-size: 2rem;
      font-weight: 700;
      color: #2d1753;
      margin: 0;
    }

    .produtos-area-header span {
      font-size: 1rem;
      color: #746a98;
    }

    .produtos-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 28px;
    }

    .produto-card {
      background: rgba(255, 255, 255, 0.96);
      border-radius: 26px;
      border: 1px solid rgba(120, 99, 176, 0.08);
      box-shadow: 0 22px 40px rgba(71, 52, 132, 0.15);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .produto-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 28px 52px rgba(71, 52, 132, 0.2);
    }

    .produto-card figure {
      width: 100%;
      margin: 0;
      padding-top: 60%;
      position: relative;
      background: linear-gradient(135deg, rgba(118, 88, 180, 0.08), rgba(255, 255, 255, 0.2));
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
      padding: 22px 24px 28px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      flex: 1;
      background: #fff;
    }

    .produto-card-body h3 {
      font-size: 1.21rem;
      font-weight: 700;
      color: #341a70;
      margin: 0;
    }

    .produto-descricao {
      font-size: 0.95rem;
      line-height: 1.55;
      color: #62569a;
    }

    .produto-codigo {
      font-size: 0.9rem;
      color: #7a6caa;
      font-weight: 600;
    }

    .produto-preco {
      margin-top: auto;
      display: flex;
      flex-direction: column;
      gap: 6px;
      font-size: 1.08rem;
      color: #35246d;
      font-weight: 700;
    }

    .produto-preco .preco-original {
      font-size: 0.92rem;
      color: #9a90c7;
      text-decoration: line-through;
      font-weight: 500;
    }

    .produtos-vazio {
      text-align: center;
      font-size: 1.2rem;
      color: #5f5f72;
      font-weight: 600;
    }

    @media (max-width: 992px) {
      .produtos-layout {
        display: flex;
        flex-direction: column;
        gap: 32px;
      }

      .categoria-sidebar {
        width: 100%;
        position: static;
        align-self: stretch;
      }

      .categoria-lista {
        padding: 14px;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 12px;
      }

      .categoria-divisor {
        display: none;
      }

      .categoria-item {
        margin: 0;
        flex: 1 1 calc(50% - 12px);
        justify-content: space-between;
      }
    }

    @media (max-width: 768px) {
      .produtos-banner {
        min-height: 260px;
      }

      .produtos-banner-conteudo h1 {
        font-size: 2.2rem;
      }

      .produtos-cards {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      }

      .categoria-item {
        flex: 1 1 100%;
        justify-content: space-between;
        padding: 12px 16px;
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
    <h1>Nossos produtos</h1>
    <p>Conheca as linhas que entregam cuidado e beleza com a assinatura do Salome Beleza.</p>
  </div>
</section>

<main class="produtos-publico-wrapper">
  <div class="produtos-container">
    <?php if (!$temProdutos): ?>
      <div class="produtos-vazio">Nenhum produto disponivel no momento. Volte em breve!</div>
    <?php else: ?>
      <div class="produtos-layout">
        <aside class="categoria-sidebar">
          <nav class="categoria-lista" aria-label="Categorias de produtos">
            <button
              type="button"
              class="categoria-item categoria-item--todos active"
              data-categoria="todos"
              data-nome="Todos os produtos"
            >
              <span class="categoria-nome">Todos os produtos</span>
              <span class="categoria-quantidade"><?= $totalProdutos; ?></span>
            </button>
            <div class="categoria-divisor" aria-hidden="true"></div>
            <?php foreach ($categoriasDados as $categoria): ?>
              <button
                type="button"
                class="categoria-item"
                data-categoria="<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                data-nome="<?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                <span class="categoria-nome"><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="categoria-quantidade"><?= $categoria['quantidade']; ?></span>
              </button>
            <?php endforeach; ?>
            <?php if ($categoriaOutros !== null): ?>
              <button
                type="button"
                class="categoria-item"
                data-categoria="<?= htmlspecialchars($categoriaOutros['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                data-nome="<?= htmlspecialchars($categoriaOutros['nome'], ENT_QUOTES, 'UTF-8'); ?>"
              >
                <span class="categoria-nome"><?= htmlspecialchars($categoriaOutros['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="categoria-quantidade"><?= $categoriaOutros['quantidade']; ?></span>
              </button>
            <?php endif; ?>
          </nav>
        </aside>

        <section class="produtos-area" aria-live="polite">
          <header class="produtos-area-header">
            <h2 id="produtosAreaTitulo">Todos os produtos</h2>
            <span id="produtosAreaTotal"><?= $totalProdutos; ?> produto(s)</span>
          </header>

          <div class="produtos-cards" id="produtosCards">
            <?php foreach ($categoriasDados as $categoria): ?>
              <?php foreach ($categoria['produtos'] as $produto): ?>
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
                  $codigo = formatarCodigoProduto($produto);
                ?>
                <article class="produto-card" data-categoria="<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php if ($imagemSrc !== ''): ?>
                    <figure>
                      <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>
                  <?php endif; ?>
                  <div class="produto-card-body">
                    <h3><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="produto-descricao"><?= $descricaoProduto; ?></div>
                    <div class="produto-codigo">Cod.: <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></div>
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
            <?php endforeach; ?>

            <?php if ($categoriaOutros !== null): ?>
              <?php foreach ($categoriaOutros['produtos'] as $produto): ?>
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
                  $codigo = formatarCodigoProduto($produto);
                ?>
                <article class="produto-card" data-categoria="<?= htmlspecialchars($categoriaOutros['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php if ($imagemSrc !== ''): ?>
                    <figure>
                      <img src="<?= htmlspecialchars($imagemSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>
                  <?php endif; ?>
                  <div class="produto-card-body">
                    <h3><?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="produto-descricao"><?= $descricaoProduto; ?></div>
                    <div class="produto-codigo">Cod.: <?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></div>
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
            <?php endif; ?>
          </div>

          <div class="produtos-vazio" id="produtosVazio" hidden>Nenhum produto disponivel nesta categoria.</div>
        </section>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../class/contatoFooter.php'; ?>
<?php include __DIR__ . '/../class/modais.php'; ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const categoriaBotoes = Array.from(document.querySelectorAll('.categoria-item'));
    const cards = Array.from(document.querySelectorAll('.produto-card'));
    const titulo = document.getElementById('produtosAreaTitulo');
    const total = document.getElementById('produtosAreaTotal');
    const vazio = document.getElementById('produtosVazio');

    const atualizarVisibilidade = (categoriaSlug) => {
      let visiveis = 0;
      cards.forEach((card) => {
        const corresponde = categoriaSlug === 'todos' || card.dataset.categoria === categoriaSlug;
        card.hidden = !corresponde;
        if (corresponde) {
          visiveis += 1;
        }
      });

      if (visiveis === 0) {
        vazio.hidden = false;
      } else {
        vazio.hidden = true;
      }

      const textoQuantidade = visiveis === 1 ? '1 produto' : visiveis + ' produtos';
      total.textContent = textoQuantidade;
    };

    categoriaBotoes.forEach((botao) => {
      botao.addEventListener('click', () => {
        if (botao.classList.contains('active')) {
          return;
        }

        categoriaBotoes.forEach((item) => item.classList.remove('active'));
        botao.classList.add('active');

        const categoriaSlug = botao.dataset.categoria;
        const categoriaNome = botao.dataset.nome || 'Produtos';

        titulo.textContent = categoriaNome;
        atualizarVisibilidade(categoriaSlug);
      });
    });

    atualizarVisibilidade('todos');
  });
</script>
</body>
</html>
