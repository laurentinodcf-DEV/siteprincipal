<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

function normalizarPreco(string $valorBruto): float
{
    $limpo = preg_replace('/[^0-9,\.]/', '', $valorBruto);
    if ($limpo === null || $limpo === '') {
        return 0.0;
    }
    $limpo = str_replace('.', '', $limpo);
    $limpo = str_replace(',', '.', $limpo);
    return (float) $limpo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create') {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $duracao = (int) ($_POST['duracao'] ?? 0);
        $preco = normalizarPreco((string) ($_POST['preco'] ?? '0'));
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        $imagem = trim((string) ($_POST['imagem'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome === '' || $duracao <= 0 || $preco < 0) {
            $mensagemErro = 'Preencha nome, duracao (em minutos) e preco valido.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO salao_servicos (nome, descricao, duracao, preco, categoria, imagem, ativo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar insercao.';
            } else {
                $descricaoParam = $descricao !== '' ? $descricao : null;
                $categoriaParam = $categoria !== '' ? $categoria : null;
                $imagemParam = $imagem !== '' ? $imagem : null;

                $stmt->bind_param(
                    'ssidssi',
                    $nome,
                    $descricaoParam,
                    $duracao,
                    $preco,
                    $categoriaParam,
                    $imagemParam,
                    $ativo
                );

                if ($stmt->execute()) {
                    $mensagemSucesso = 'Servico cadastrado com sucesso.';
                } else {
                    $mensagemErro = 'Erro ao inserir servico: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    } elseif ($acao === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $descricao = trim((string) ($_POST['descricao'] ?? ''));
        $duracao = (int) ($_POST['duracao'] ?? 0);
        $preco = normalizarPreco((string) ($_POST['preco'] ?? '0'));
        $categoria = trim((string) ($_POST['categoria'] ?? ''));
        $imagem = trim((string) ($_POST['imagem'] ?? ''));
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id <= 0) {
            $mensagemErro = 'Servico invalido para edicao.';
        } elseif ($nome === '' || $duracao <= 0 || $preco < 0) {
            $mensagemErro = 'Preencha nome, duracao (em minutos) e preco valido.';
        } else {
            $stmt = $conn->prepare(
                'UPDATE salao_servicos
                 SET nome = ?, descricao = ?, duracao = ?, preco = ?, categoria = ?, imagem = ?, ativo = ?
                 WHERE id = ?'
            );

            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar atualizacao.';
            } else {
                $descricaoParam = $descricao !== '' ? $descricao : null;
                $categoriaParam = $categoria !== '' ? $categoria : null;
                $imagemParam = $imagem !== '' ? $imagem : null;

                $stmt->bind_param(
                    'ssidssii',
                    $nome,
                    $descricaoParam,
                    $duracao,
                    $preco,
                    $categoriaParam,
                    $imagemParam,
                    $ativo,
                    $id
                );

                if ($stmt->execute()) {
                    $mensagemSucesso = 'Servico atualizado.';
                } else {
                    $mensagemErro = 'Erro ao atualizar servico: ' . $stmt->error;
                }

                $stmt->close();
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
        $mensagemErro = 'Servico invalido para exclusao.';
        } else {
            $stmt = $conn->prepare('DELETE FROM salao_servicos WHERE id = ?');
            if ($stmt === false) {
                $mensagemErro = 'Erro ao preparar exclusao.';
            } else {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $mensagemSucesso = 'Servico removido.';
                } else {
                    $mensagemErro = 'Erro ao excluir servico: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

$servicos = [];
$resultado = $conn->query('SELECT * FROM salao_servicos ORDER BY criado_em DESC');
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $servicos[] = $linha;
    }
    $resultado->free();
}

$servicosAtivos = [];
$servicosInativos = [];
foreach ($servicos as $servico) {
    if ((int) ($servico['ativo'] ?? 0) === 1) {
        $servicosAtivos[] = $servico;
    } else {
        $servicosInativos[] = $servico;
    }
}

function formatarPreco(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function renderizarServicosGrid(array $servicosLista): void
{
    if (empty($servicosLista)) {
        return;
    }
    ?>
    <div class="servicos-grid">
        <?php foreach ($servicosLista as $servico): ?>
            <?php
            $servicoJson = htmlspecialchars(
                json_encode($servico, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
            <article class="servico-card" data-servico='<?= $servicoJson; ?>'>
                <div class="servico-card-header">
                    <div>
                        <h3><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if (!empty($servico['categoria'])): ?>
                            <span class="servico-categoria"><?= htmlspecialchars($servico['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="servico-status <?= $servico['ativo'] ? 'ativo' : 'inativo'; ?>">
                        <?= $servico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </div>
                <?php if (!empty($servico['imagem'])): ?>
                    <div class="servico-imagem">
                        <img src="<?= htmlspecialchars($servico['imagem'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                <?php endif; ?>
                <div class="servico-detalhes">
                    <p class="servico-preco"><?= formatarPreco((float) $servico['preco']); ?></p>
                    <p class="servico-duracao"><?= (int) $servico['duracao']; ?> min</p>
                </div>
                <?php if (!empty($servico['descricao'])): ?>
                    <p class="servico-descricao"><?= nl2br(htmlspecialchars($servico['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
                <?php endif; ?>
                <div class="servico-acoes">
                    <button type="button" class="btn btn-light btn-sm acao-editar" data-bs-toggle="modal" data-bs-target="#modalEditarServico">Editar</button>
                    <button type="button" class="btn btn-outline-danger btn-sm acao-excluir" data-bs-toggle="modal" data-bs-target="#modalExcluirServico">Excluir</button>
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
    <title>Gestao de Servicos</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body class="pagina-admin">
    <div class="horarios-wrapper servicos-wrapper">
        <header class="horarios-header">
            <h1>Catalogo de Servicos</h1>
            <p class="horarios-subtitle">Cadastre, edite e organize os servicos oferecidos pelo salao.</p>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="servico-form-section">
            <h2 class="secao-titulo">Novo servico</h2>
            <form method="post" class="servico-formulario card">
                <input type="hidden" name="action" value="create">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome*</label>
                        <input type="text" name="nome" class="form-control" required maxlength="100" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duracao (min)*</label>
                        <input type="number" name="duracao" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Preco*</label>
                        <input type="text" name="preco" class="form-control" required placeholder="Ex: 120,00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="categoria" class="form-control" maxlength="50" placeholder="Ex: Cabelo, Estetica">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Imagem (URL ou caminho)</label>
                        <input type="text" name="imagem" class="form-control" maxlength="255" placeholder="https://...">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descricao</label>
                        <textarea name="descricao" class="form-control" rows="4" placeholder="Detalhes do servico"></textarea>
                    </div>
                    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="novoServicoAtivo" name="ativo" checked>
                            <label class="form-check-label" for="novoServicoAtivo">
                                Servico ativo
                            </label>
                        </div>
                        <button type="submit" class="botao-salvar">Cadastrar servico</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="servico-lista-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="secao-titulo">Servicos cadastrados</h2>
                <span class="texto-suave"><?= count($servicos); ?> servico(s) no sistema</span>
            </div>

            <?php if (empty($servicos)): ?>
                <div class="alerta alerta-informacao">Nenhum servico cadastrado ate o momento.</div>
            <?php else: ?>
                <div class="servico-subsecao">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Servicos ativos</h3>
                        <span class="texto-suave"><?= count($servicosAtivos); ?> ativo(s)</span>
                    </div>
                    <?php if (empty($servicosAtivos)): ?>
                        <div class="alerta alerta-informacao">Nenhum servico ativo cadastrado.</div>
                    <?php else: ?>
                        <?php renderizarServicosGrid($servicosAtivos); ?>
                    <?php endif; ?>
                </div>

                <div class="servico-subsecao mt-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h3 class="servico-subtitulo">Servicos inativos</h3>
                        <span class="texto-suave"><?= count($servicosInativos); ?> inativo(s)</span>
                    </div>
                    <?php if (empty($servicosInativos)): ?>
                        <div class="alerta alerta-informacao">Nenhum servico marcado como inativo.</div>
                    <?php else: ?>
                        <?php renderizarServicosGrid($servicosInativos); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Modal Edicao -->
    <div class="modal fade" id="modalEditarServico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="post" class="modal-body-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editarServicoId">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar servico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome*</label>
                                <input type="text" name="nome" class="form-control" id="editarServicoNome" required maxlength="100">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Duracao (min)*</label>
                                <input type="number" name="duracao" class="form-control" id="editarServicoDuracao" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Preco*</label>
                                <input type="text" name="preco" class="form-control" id="editarServicoPreco" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Categoria</label>
                                <input type="text" name="categoria" class="form-control" id="editarServicoCategoria" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Imagem (URL ou caminho)</label>
                                <input type="text" name="imagem" class="form-control" id="editarServicoImagem" maxlength="255">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descricao</label>
                                <textarea name="descricao" class="form-control" id="editarServicoDescricao" rows="4"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="editarServicoAtivo" name="ativo">
                                    <label class="form-check-label" for="editarServicoAtivo">
                                        Servico ativo
                                    </label>
                                </div>
                            </div>
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

    <!-- Modal Exclusao -->
    <div class="modal fade" id="modalExcluirServico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" class="modal-body-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="excluirServicoId">
                    <div class="modal-header">
                        <h5 class="modal-title">Excluir servico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Deseja mesmo excluir esse servico?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Nao</button>
                        <button type="submit" class="btn btn-danger" id="botaoConfirmarExclusao">Sim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editarModal = document.getElementById('modalEditarServico');
            const excluirModal = document.getElementById('modalExcluirServico');

            const preencherModalEdicao = (servico) => {
                document.getElementById('editarServicoId').value = servico.id;
                document.getElementById('editarServicoNome').value = servico.nome || '';
                document.getElementById('editarServicoDuracao').value = servico.duracao || '';
                document.getElementById('editarServicoPreco').value = parseFloat(servico.preco ?? 0).toFixed(2).replace('.', ',');
                document.getElementById('editarServicoCategoria').value = servico.categoria || '';
                document.getElementById('editarServicoImagem').value = servico.imagem || '';
                document.getElementById('editarServicoDescricao').value = servico.descricao || '';
                document.getElementById('editarServicoAtivo').checked = String(servico.ativo) === '1';
            };

            const prepararModalExclusao = (servico) => {
                document.getElementById('excluirServicoId').value = servico.id;
            };

            document.querySelectorAll('.servico-card').forEach((card) => {
                const dados = card.dataset.servico ? JSON.parse(card.dataset.servico) : null;
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
                    editarModal.querySelector('form').reset();
                });
            }

            if (excluirModal) {
                excluirModal.addEventListener('hidden.bs.modal', () => {
                    excluirModal.querySelector('form').reset();
                });
            }
        });
    </script>
</body>
</html>
