<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

function obterProximaOrdemCategoria(mysqli $conn): int
{
    $resultado = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM salao_categorias_produtos WHERE ativo = 1');
    if ($resultado) {
        $linha = $resultado->fetch_assoc();
        $resultado->free();
        return (int) ($linha['proxima'] ?? 1);
    }
    return 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '') {
            $mensagemErro = 'Informe um nome para a categoria.';
        } else {
            $ordemParam = $ativo === 1 ? obterProximaOrdemCategoria($conn) : null;
            $descricaoParam = $descricao !== '' ? $descricao : null;

            $stmt = $conn->prepare(
                'INSERT INTO salao_categorias_produtos (nome, descricao, ativo, ordem)
                 VALUES (?, ?, ?, ?)'
            );
            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar insero.';
            } else {
                $stmt->bind_param(
                    'ssii',
                    $nome,
                    $descricaoParam,
                    $ativo,
                    $ordemParam
                );
                if ($stmt->execute()) {
                    $mensagemSucesso = 'Categoria cadastrada com sucesso.';
                } else {
                    $mensagemErro = 'Erro ao inserir categoria: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    } elseif ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0 || $nome === '') {
            $mensagemErro = 'Categoria invlida ou nome obrigatrio.';
        } else {
            $stmtBusca = $conn->prepare('SELECT ativo, ordem FROM salao_categorias_produtos WHERE id = ?');
            $ativoAnterior = 0;
            $ordemAtual = null;
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar categoria.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($ativoAnterior, $ordemAtual);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Categoria no encontrada.';
                    }
                } else {
                    $mensagemErro = 'Erro ao localizar categoria.';
                }
                $stmtBusca->close();
            }

            if ($mensagemErro === '') {
                $ordemParam = null;
                if ($ativo === 1) {
                    if ((int) $ativoAnterior === 1 && $ordemAtual !== null) {
                        $ordemParam = (int) $ordemAtual;
                    } else {
                        $ordemParam = obterProximaOrdemCategoria($conn);
                    }
                }

                $descricaoParam = $descricao !== '' ? $descricao : null;

                $stmt = $conn->prepare(
                    'UPDATE salao_categorias_produtos
                     SET nome = ?, descricao = ?, ativo = ?, ordem = ?
                     WHERE id = ?'
                );
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar atualizao.';
                } else {
                    $stmt->bind_param(
                        'ssiii',
                        $nome,
                        $descricaoParam,
                        $ativo,
                        $ordemParam,
                        $id
                    );
                    if ($stmt->execute()) {
                        $mensagemSucesso = 'Categoria atualizada.';
                    } else {
                        $mensagemErro = 'Erro ao atualizar categoria: ' . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $mensagemErro = 'Categoria invlida para excluso.';
        } else {
            $stmt = $conn->prepare('DELETE FROM salao_categorias_produtos WHERE id = ?');
            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar excluso.';
            } else {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $mensagemSucesso = 'Categoria removida.';
                } else {
                    $mensagemErro = 'Erro ao excluir categoria: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$categorias = [];
$resultado = $conn->query(
    'SELECT * FROM salao_categorias_produtos
     ORDER BY COALESCE(ordem, 2147483647), nome ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $categorias[] = $linha;
    }
    $resultado->free();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Categorias de produtos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/estilo.css">
    <style>
        body {
            margin: 0;
            padding: 32px;
            font-family: "Segoe UI", "Roboto", sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .categorias-wrapper {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }
        .categoria-form-section,
        .categoria-lista-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px 28px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .categoria-form-section h2,
        .categoria-lista-section h2 {
            margin-bottom: 14px;
            font-size: 1.6rem;
            font-weight: 700;
        }
        .botao-salvar {
            background: #2563eb;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 999px;
            padding: 10px 24px;
            cursor: pointer;
        }
        .botao-salvar:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="categorias-wrapper">
        <section class="categoria-form-section">
            <h2>Nova categoria</h2>
            <form method="post" class="card p-3">
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome*</label>
                        <input type="text" name="nome" class="form-control" required maxlength="100" autocomplete="off">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrio</label>
                        <textarea name="descricao" class="form-control" rows="3" placeholder="Breve descrio para organizar os produtos"></textarea>
                    </div>
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="novaCategoriaAtiva" name="ativo" checked>
                            <label class="form-check-label" for="novaCategoriaAtiva">
                                Categoria ativa
                            </label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar categoria</button>
                    </div>
                </div>
            </form>
        </section>

        <?php if ($mensagemSucesso): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="categoria-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2>Categorias cadastradas</h2>
                <span class="texto-suave"><?= count($categorias); ?> categoria(s)</span>
            </div>

            <?php 
            $categoriasAtivas = [];
            $categoriasInativas = [];
            foreach ($categorias as $cat) {
                if ((int) ($cat['ativo'] ?? 1) === 1) {
                    $categoriasAtivas[] = $cat;
                } else {
                    $categoriasInativas[] = $cat;
                }
            }
            ?>

            <?php if (empty($categorias)): ?>
                <div class="alert alert-info">Nenhuma categoria cadastrada at o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Categorias ativas</h3>
                        <span class="texto-suave"><?= count($categoriasAtivas); ?> ativa(s)</span>
                    </div>
                    <?php if (empty($categoriasAtivas)): ?>
                        <div class="alert alert-info">Nenhuma categoria ativa cadastrada.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($categoriasAtivas as $categoria): ?>
                        <?php
                            $categoriaJson = htmlspecialchars(
                                json_encode($categoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            $descricaoFormatada = !empty($categoria['descricao'])
                                ? nl2br(htmlspecialchars($categoria['descricao'], ENT_QUOTES, 'UTF-8'))
                                : '<span class="texto-suave">Sem descrio cadastrada.</span>';
                        ?>
                        <article class="servico-accordion-item">
                            <header class="servico-accordion-header">
                                <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                                    <span class="servico-accordion-title">
                                        <strong><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </span>
                                    <span class="servico-status-pill <?= $categoria['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?= $categoria['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                    <span class="servico-accordion-icon">+</span>
                                </button>
                            </header>
                            <div class="servico-accordion-content" aria-hidden="true">
                                <div class="servico-card" data-categoria='<?= $categoriaJson; ?>'>
                                    <div class="servico-card-inner">
                                        <div class="servico-card-info" style="flex:1">
                                            <dl class="servico-propriedades">
                                                <div>
                                                    <dt>Ordem:</dt>
                                                    <dd><?= $categoria['ordem'] !== null ? (int) $categoria['ordem'] : ''; ?></dd>
                                                </div>
                                                <div class="servico-descricao-bloco">
                                                    <dt>Descrio:</dt>
                                                    <dd class="servico-descricao-texto"><?= $descricaoFormatada; ?></dd>
                                                </div>
                                            </dl>

                                            <div class="servico-card-acoes">
                                                <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria">Editar</button>
                                                <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirCategoria">Excluir</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="servico-subsecao mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Categorias inativas</h3>
                        <span class="texto-suave"><?= count($categoriasInativas); ?> inativa(s)</span>
                    </div>
                    <?php if (empty($categoriasInativas)): ?>
                        <div class="alert alert-info">Nenhuma categoria marcada como inativa.</div>
                    <?php else: ?>
                        <div class="servicos-grid">
                            <?php foreach ($categoriasInativas as $categoria): ?>
                        <?php
                            $categoriaJson = htmlspecialchars(
                                json_encode($categoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            $descricaoFormatada = !empty($categoria['descricao'])
                                ? nl2br(htmlspecialchars($categoria['descricao'], ENT_QUOTES, 'UTF-8'))
                                : '<span class="texto-suave">Sem descrio cadastrada.</span>';
                        ?>
                        <article class="servico-accordion-item">
                            <header class="servico-accordion-header">
                                <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                                    <span class="servico-accordion-title">
                                        <strong><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </span>
                                    <span class="servico-status-pill <?= $categoria['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?= $categoria['ativo'] ? 'Ativa' : 'Inativa'; ?>
                                    </span>
                                    <span class="servico-accordion-icon">+</span>
                                </button>
                            </header>
                            <div class="servico-accordion-content" aria-hidden="true">
                                <div class="servico-card">
                                    <div class="servico-card-inner">
                                        <header class="servico-card-header">
                                            <div class="servico-card-titulo">
                                                <h3><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                            </div>
                                        </header>

                                        <dl class="servico-propriedades">
                                            <div class="servico-descricao-bloco">
                                                <dt>Descrio:</dt>
                                                <dd class="servico-descricao-texto"><?= $descricaoFormatada; ?></dd>
                                            </div>
                                        </dl>

                                        <div class="servico-card-acoes">
                                            <button type="button" class="botao-primario acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarCategoria">Editar</button>
                                            <button type="button" class="botao-perigo acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirCategoria">Excluir</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editarCategoriaId">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar categoria</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nome*</label>
                                <input type="text" name="nome" class="form-control" id="editarCategoriaNome" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrio</label>
                                <textarea name="descricao" class="form-control" id="editarCategoriaDescricao" rows="3"></textarea>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="editarCategoriaAtiva" name="ativo">
                                <label class="form-check-label" for="editarCategoriaAtiva">
                                    Categoria ativa
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar alteraes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalExcluirCategoria" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="post" class="modal-body-form">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="excluirCategoriaId">
                        <div class="modal-header">
                            <h5 class="modal-title">Excluir categoria</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">Confirma a excluso desta categoria? Produtos associados permanecero sem categoria.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No</button>
                            <button type="submit" class="btn btn-danger">Sim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.servico-accordion-toggle').forEach((toggle) => {
                const item = toggle.closest('.servico-accordion-item');
                if (!item) {
                    return;
                }
                const content = item.querySelector('.servico-accordion-content');
                const icon = toggle.querySelector('.servico-accordion-icon');
                if (!content || !icon) {
                    return;
                }

                toggle.addEventListener('click', () => {
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    const newState = !expanded;
                    toggle.setAttribute('aria-expanded', String(newState));
                    content.setAttribute('aria-hidden', String(!newState));
                    icon.textContent = newState ? '-' : '+';
                });
            });

            const editarModal = document.getElementById('modalEditarCategoria');
            const excluirModal = document.getElementById('modalExcluirCategoria');

            const preencherModalEdicao = (categoria) => {
                document.getElementById('editarCategoriaId').value = categoria.id;
                document.getElementById('editarCategoriaNome').value = categoria.nome || '';
                document.getElementById('editarCategoriaDescricao').value = categoria.descricao || '';
                document.getElementById('editarCategoriaAtiva').checked = String(categoria.ativo) === '1';
            };

            const prepararModalExclusao = (categoria) => {
                document.getElementById('excluirCategoriaId').value = categoria.id;
            };

            document.querySelectorAll('.servico-card').forEach((card) => {
                const dados = card.dataset.categoria ? JSON.parse(card.dataset.categoria) : null;
                if (!dados) {
                    return;
                }

                const botaoEditar = card.querySelector('.acao-editar');
                const botaoExcluir = card.querySelector('.acao-excluir');

                if (botaoEditar) {
                    botaoEditar.addEventListener('click', () => preencherModalEdicao(dados));
                }

                if (botaoExcluir) {
                    botaoExcluir.addEventListener('click', () => prepararModalExclusao(dados));
                }
            });

            if (editarModal) {
                editarModal.addEventListener('hidden.bs.modal', () => {
                    const form = editarModal.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                });
            }

            if (excluirModal) {
                excluirModal.addEventListener('hidden.bs.modal', () => {
                    const form = excluirModal.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                });
            }
        });
    </script>
</body>
</html>

