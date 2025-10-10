<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

function buscarServicosAtivos(mysqli $conn): array
{
    $dados = [];
    $resultado = $conn->query(
        'SELECT id, nome, ordem FROM salao_servicos WHERE ativo = 1 ORDER BY ordem ASC, nome ASC'
    );
    if ($resultado) {
        while ($linha = $resultado->fetch_assoc()) {
            $dados[] = $linha;
        }
        $resultado->free();
    }
    return $dados;
}

$servicosAtivos = buscarServicosAtivos($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($servicosAtivos)) {
    $novasOrdens = $_POST['ordem'] ?? [];
    if (!is_array($novasOrdens)) {
        $novasOrdens = [];
    }

    $erros = [];
    $ordensUsadas = [];
    $atualizacoes = [];

    foreach ($servicosAtivos as $servico) {
        $id = (int) $servico['id'];
        $ordemAtual = (int) ($servico['ordem'] ?? 0);
        $valorBruto = $novasOrdens[$id] ?? '';
        $valorBruto = trim((string) $valorBruto);

        if ($valorBruto === '') {
            $erros[] = 'Informe um valor de ordem para todos os serviços.';
            break;
        }
        if (!ctype_digit($valorBruto)) {
            $erros[] = 'As posições devem ser números inteiros positivos.';
            break;
        }

        $ordemNova = (int) $valorBruto;
        if ($ordemNova <= 0) {
            $erros[] = 'As posições devem ser maiores que zero.';
            break;
        }

        if (isset($ordensUsadas[$ordemNova])) {
            $erros[] = 'Existem posições repetidas. Cada serviço deve ter uma posição única.';
            break;
        }
        $ordensUsadas[$ordemNova] = true;

        if ($ordemNova !== $ordemAtual) {
            $atualizacoes[$id] = $ordemNova;
        }
    }

    if (empty($erros) && empty($atualizacoes)) {
        $mensagemSucesso = 'Nenhuma alteração identificada.';
    } elseif (empty($erros)) {
        $stmt = $conn->prepare('UPDATE salao_servicos SET ordem = ? WHERE id = ?');
        if ($stmt === false) {
            $mensagemErro = 'Não foi possível preparar a atualização das posições.';
        } else {
            foreach ($atualizacoes as $servicoId => $novaOrdem) {
                $stmt->bind_param('ii', $novaOrdem, $servicoId);
                if (!$stmt->execute()) {
                    $erros[] = 'Erro ao atualizar ordem do serviço ID ' . $servicoId . ': ' . $stmt->error;
                    break;
                }
            }
            $stmt->close();

            if (empty($erros)) {
                $mensagemSucesso = 'Ordem atualizada com sucesso!';
                $servicosAtivos = buscarServicosAtivos($conn);
            } else {
                $mensagemErro = implode(' ', $erros);
            }
        }
    } else {
        $mensagemErro = implode(' ', $erros);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Ordenar serviços</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .ordenacao-wrapper {
            padding: 40px;
            background: #f1f5f9;
            min-height: 100vh;
        }
        .ordenacao-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            max-width: 720px;
            margin: 0 auto;
        }
        .ordenacao-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }
        .ordenacao-header p {
            margin: 0;
            color: #64748b;
        }
        .ordenacao-lista {
            margin-top: 24px;
        }
        .ordenacao-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 12px;
            background: #f8fafc;
        }
        .ordenacao-item + .ordenacao-item {
            margin-top: 14px;
        }
        .ordenacao-item strong {
            font-size: 16px;
            color: #0f172a;
        }
        .ordenacao-item span {
            color: #64748b;
            font-size: 14px;
        }
        .ordenacao-item input[type="number"] {
            width: 90px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.6);
            padding: 6px 10px;
        }
        .ordenacao-acoes {
            margin-top: 24px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .botao-salvar-ordenacao {
            background: #2563eb;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 999px;
            padding: 10px 24px;
            cursor: pointer;
        }
        .botao-salvar-ordenacao:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="ordenacao-wrapper">
        <div class="ordenacao-card">
            <div class="ordenacao-header">
                <h2>Ordenar serviços ativos</h2>
                <p>Defina abaixo a posição de exibição de cada serviço ativo. Não são permitidos números repetidos ou menores que 1.</p>
            </div>

            <?php if ($mensagemSucesso): ?>
                <div class="alert alert-success mt-3"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($mensagemErro): ?>
                <div class="alert alert-danger mt-3"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (empty($servicosAtivos)): ?>
                <p class="mt-4 text-center text-muted">Nenhum serviço ativo disponível para ordenar.</p>
            <?php else: ?>
                <form method="post">
                    <div class="ordenacao-lista">
                        <?php foreach ($servicosAtivos as $servico): ?>
                            <div class="ordenacao-item">
                                <div>
                                    <strong><?= htmlspecialchars($servico['nome'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                    <span>Posição atual: <?= (int) ($servico['ordem'] ?? 0); ?></span>
                                </div>
                                <input
                                    type="number"
                                    name="ordem[<?= (int) $servico['id']; ?>]"
                                    value="<?= (int) ($servico['ordem'] ?? 0); ?>"
                                    min="1"
                                    required
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="ordenacao-acoes">
                        <button type="submit" class="botao-salvar-ordenacao">Salvar ordenação</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
