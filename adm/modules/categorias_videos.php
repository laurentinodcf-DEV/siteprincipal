<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

function obterProximaOrdemCategoriaVideo(mysqli $conn): int
{
    $resultado = $conn->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM categoria_videos WHERE ativo = 1');
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
            $ordemParam = $ativo === 1 ? obterProximaOrdemCategoriaVideo($conn) : null;
            $descricaoParam = $descricao !== '' ? $descricao : null;

            $stmt = $conn->prepare(
                'INSERT INTO categoria_videos (nome, descricao, ativo, ordem)
                 VALUES (?, ?, ?, ?)'
            );
            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar insercao.';
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
            $mensagemErro = 'Categoria invalida ou nome obrigatorio.';
        } else {
            $stmtBusca = $conn->prepare('SELECT ativo, ordem FROM categoria_videos WHERE id = ?');
            $ativoAnterior = 0;
            $ordemAtual = null;
            if ($stmtBusca === false) {
                $mensagemErro = 'Erro ao localizar categoria.';
            } else {
                $stmtBusca->bind_param('i', $id);
                if ($stmtBusca->execute()) {
                    $stmtBusca->bind_result($ativoAnterior, $ordemAtual);
                    if (!$stmtBusca->fetch()) {
                        $mensagemErro = 'Categoria nao encontrada.';
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
                        $ordemParam = obterProximaOrdemCategoriaVideo($conn);
                    }
                }

                $descricaoParam = $descricao !== '' ? $descricao : null;

                $stmt = $conn->prepare(
                    'UPDATE categoria_videos
                     SET nome = ?, descricao = ?, ativo = ?, ordem = ?
                     WHERE id = ?'
                );
                if ($stmt === false) {
                    $mensagemErro = 'Erro ao preparar atualizacao.';
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
            $mensagemErro = 'Categoria invalida para exclusao.';
        } else {
            $stmtLimpar = $conn->prepare('UPDATE salao_videos SET id_categoria = NULL WHERE id_categoria = ?');
            if ($stmtLimpar) {
                $stmtLimpar->bind_param('i', $id);
                $stmtLimpar->execute();
                $stmtLimpar->close();
            }

            $stmt = $conn->prepare('DELETE FROM categoria_videos WHERE id = ?');
            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar exclusao.';
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
    'SELECT c.id, c.nome, c.descricao, c.ativo, c.ordem,
            (SELECT COUNT(*) FROM salao_videos v WHERE v.id_categoria = c.id) AS total_videos
     FROM categoria_videos c
     ORDER BY COALESCE(c.ordem, 2147483647), c.nome ASC'
);
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $categorias[] = [
            'id' => (int) $linha['id'],
            'nome' => $linha['nome'],
            'descricao' => $linha['descricao'],
            'ativo' => (int) $linha['ativo'],
            'ordem' => $linha['ordem'] !== null ? (int) $linha['ordem'] : null,
            'total_videos' => isset($linha['total_videos']) ? (int) $linha['total_videos'] : 0,
        ];
    }
    $resultado->free();
}

// Separa em listas de ativas e inativas para replicar o comportamento de servicos/produtos
$categoriasAtivas = [];
$categoriasInativas = [];
foreach ($categorias as $categoria) {
    if ((int) ($categoria['ativo'] ?? 0) === 1) {
        $categoriasAtivas[] = $categoria;
    } else {
        $categoriasInativas[] = $categoria;
    }
}

// Renderiza a grade de categorias reutilizando o mesmo layout do bloco atual
function renderizarCategoriasVideoGrid(array $lista): void
{
    if (empty($lista)) {
        return;
    }
    ?>
    <div class="servicos-grid">
        <?php foreach ($lista as $categoria): ?>
            <?php
                $categoriaJson = htmlspecialchars(
                    json_encode($categoria, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $descricaoFormatada = !empty($categoria['descricao'])
                    ? nl2br(htmlspecialchars($categoria['descricao'], ENT_QUOTES, 'UTF-8'))
                    : '<span class="texto-suave">Sem descricao cadastrada.</span>';
            ?>
            <article class="servico-accordion-item">
                <header class="servico-accordion-header">
                    <button type="button" class="servico-accordion-toggle" aria-expanded="false">
                        <span class="servico-accordion-title">
                            <strong><?= htmlspecialchars($categoria['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <small class="d-block texto-suave"><?= (int) $categoria['total_videos']; ?> video(s)</small>
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
                                        <dt>Descricao:</dt>
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
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Categorias de videos</title>
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/painel.css">
</head>
<body class="painel-modulo">
    <div class="modulo-container">
        <header class="modulo-header">
            <h1>Cadastro de categorias de videos</h1>
            <p>Organize os videos do site agrupando-os em categorias.</p>
        </header>

        <?php if ($mensagemSucesso !== ''): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($mensagemErro !== ''): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="categoria-form-section mb-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Nova categoria</h2>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-6">
                            <label class="form-label">Nome*</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="novaCategoriaAtiva" name="ativo" checked>
                                <label class="form-check-label" for="novaCategoriaAtiva">
                                    Categoria ativa
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descricao</label>
                            <textarea name="descricao" class="form-control" rows="3" placeholder="Opcional"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Cadastrar categoria</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="categoria-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="h5 mb-0">Categorias cadastradas</h2>
                <span class="texto-suave"><?= count($categorias); ?> categoria(s)</span>
            </div>

            <?php if (empty($categorias)): ?>
                <div class="alert alert-info">Nenhuma categoria cadastrada ate o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Categorias ativas</h3>
                        <span class="texto-suave"><?= count($categoriasAtivas); ?> ativa(s)</span>
                    </div>
                    <?php if (empty($categoriasAtivas)): ?>
                        <div class="alert alert-info">Nenhuma categoria ativa cadastrada.</div>
                    <?php else: ?>
                        <?php renderizarCategoriasVideoGrid($categoriasAtivas); ?>
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
                        <?php renderizarCategoriasVideoGrid($categoriasInativas); ?>
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
                                <input type="text" name="nome" id="editarCategoriaNome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descricao</label>
                                <textarea name="descricao" id="editarCategoriaDescricao" class="form-control" rows="3"></textarea>
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
                            <button type="submit" class="btn btn-primary">Salvar alteracoes</button>
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
                            <p class="mb-0">Confirma a exclusao desta categoria? Os videos associados deixarao de ter categoria.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Nao</button>
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
