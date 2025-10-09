<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../conexao.php';

$diasSemana = [
    'segunda'   => 'Segunda',
    'terca'     => 'Terça',
    'quarta'    => 'Quarta',
    'quinta'    => 'Quinta',
    'sexta'     => 'Sexta',
    'sabado'    => 'Sábado',
    'domingo'   => 'Domingo',
    'feriados'  => 'Feriados',
];

$mesesAno = [
    1  => 'Janeiro',
    2  => 'Fevereiro',
    3  => 'Março',
    4  => 'Abril',
    5  => 'Maio',
    6  => 'Junho',
    7  => 'Julho',
    8  => 'Agosto',
    9  => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];

$mensagemSucesso = '';
$mensagemErro = '';

$horariosCadastrados = [];
foreach ($diasSemana as $slugDia => $rotuloDia) {
    $horariosCadastrados[$slugDia] = [
        'dia_semana'        => $slugDia,
        'mes_inicio'        => 1,
        'mes_fim'           => 12,
        'horario_abertura'  => null,
        'horario_fechamento'=> null,
        'almoco_inicio'     => null,
        'almoco_fim'        => null,
        'aberto'            => 1,
    ];
}

$resultado = $conn->query('SELECT * FROM salao_horarios_funcionamento');
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $diaTabela = $linha['dia_semana'];
        if (isset($horariosCadastrados[$diaTabela])) {
            $horariosCadastrados[$diaTabela] = $linha;
        }
    }
    $resultado->free();
}

function calcularMesesSelecionados(?int $inicio, ?int $fim): array
{
    if (!$inicio || !$fim) {
        return range(1, 12);
    }

    if ($inicio <= $fim) {
        return range($inicio, $fim);
    }

    return array_merge(range($inicio, 12), range(1, $fim));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        $stmtBusca = $conn->prepare('SELECT id FROM salao_horarios_funcionamento WHERE dia_semana = ? LIMIT 1');
        $stmtAtualiza = $conn->prepare(
            'UPDATE salao_horarios_funcionamento
             SET mes_inicio = ?, mes_fim = ?, horario_abertura = ?, horario_fechamento = ?, almoco_inicio = ?, almoco_fim = ?, aberto = ?
             WHERE id = ?'
        );
        $stmtInsere = $conn->prepare(
            'INSERT INTO salao_horarios_funcionamento
             (dia_semana, mes_inicio, mes_fim, horario_abertura, horario_fechamento, almoco_inicio, almoco_fim, aberto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($diasSemana as $slugDia => $rotuloDia) {
            $prefixo = $slugDia . '_';

            $selecionados = isset($_POST[$prefixo . 'meses'])
                ? array_map('intval', (array) $_POST[$prefixo . 'meses'])
                : [];
            $selecionados = array_values(array_filter($selecionados, static function ($mes) {
                return $mes >= 1 && $mes <= 12;
            }));
            sort($selecionados);

            if (empty($selecionados)) {
                $mesInicio = 1;
                $mesFim = 12;
            } else {
                $mesInicio = $selecionados[0];
                $mesFim = $selecionados[count($selecionados) - 1];
            }

            $estaFechado = isset($_POST[$prefixo . 'fechado']) ? 1 : 0;
            $aberto = $estaFechado ? 0 : 1;

            $horarioAbertura = isset($_POST[$prefixo . 'abertura']) ? trim((string) $_POST[$prefixo . 'abertura']) : '';
            $horarioFechamento = isset($_POST[$prefixo . 'fechamento']) ? trim((string) $_POST[$prefixo . 'fechamento']) : '';
            $almocoInicio = isset($_POST[$prefixo . 'almoco_inicio']) ? trim((string) $_POST[$prefixo . 'almoco_inicio']) : '';
            $almocoFim = isset($_POST[$prefixo . 'almoco_fim']) ? trim((string) $_POST[$prefixo . 'almoco_fim']) : '';

            $horarioAbertura = $horarioAbertura === '' ? null : $horarioAbertura;
            $horarioFechamento = $horarioFechamento === '' ? null : $horarioFechamento;
            $almocoInicio = $almocoInicio === '' ? null : $almocoInicio;
            $almocoFim = $almocoFim === '' ? null : $almocoFim;

            if (!$aberto) {
                $horarioAbertura = null;
                $horarioFechamento = null;
                $almocoInicio = null;
                $almocoFim = null;
            }

            $stmtBusca->bind_param('s', $slugDia);
            if (!$stmtBusca->execute()) {
                throw new Exception('Erro ao consultar horários existentes: ' . $stmtBusca->error);
            }

            $idExistente = null;
            $stmtBusca->bind_result($idExistente);
            $temRegistro = $stmtBusca->fetch();
            $stmtBusca->free_result();

            if ($temRegistro) {
                $stmtAtualiza->bind_param(
                    'iissssii',
                    $mesInicio,
                    $mesFim,
                    $horarioAbertura,
                    $horarioFechamento,
                    $almocoInicio,
                    $almocoFim,
                    $aberto,
                    $idExistente
                );

                if (!$stmtAtualiza->execute()) {
                    throw new Exception('Erro ao atualizar horários: ' . $stmtAtualiza->error);
                }
            } else {
                $stmtInsere->bind_param(
                    'siissssi',
                    $slugDia,
                    $mesInicio,
                    $mesFim,
                    $horarioAbertura,
                    $horarioFechamento,
                    $almocoInicio,
                    $almocoFim,
                    $aberto
                );

                if (!$stmtInsere->execute()) {
                    throw new Exception('Erro ao inserir horários: ' . $stmtInsere->error);
                }
            }

            $horariosCadastrados[$slugDia] = [
                'dia_semana'         => $slugDia,
                'mes_inicio'         => $mesInicio,
                'mes_fim'            => $mesFim,
                'horario_abertura'   => $horarioAbertura,
                'horario_fechamento' => $horarioFechamento,
                'almoco_inicio'      => $almocoInicio,
                'almoco_fim'         => $almocoFim,
                'aberto'             => $aberto,
            ];
        }

        $stmtBusca->close();
        $stmtAtualiza->close();
        $stmtInsere->close();

        $conn->commit();
        $mensagemSucesso = 'Horários atualizados com sucesso.';
    } catch (Exception $ex) {
        $conn->rollback();
        $mensagemErro = $ex->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Horário de Funcionamento</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/estilo.css">
</head>
<body class="pagina-admin">
    <div class="horarios-wrapper">
        <header class="horarios-header">
            <h1>Horário de Funcionamento</h1>
            <p class="horarios-subtitle">Configure os períodos, horários e intervalos de almoço para cada dia.</p>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" class="horarios-formulario">
            <?php foreach ($diasSemana as $slugDia => $rotuloDia): ?>
                <?php
                    $dadosDia = $horariosCadastrados[$slugDia];
                    $selecionados = calcularMesesSelecionados(
                        (int) $dadosDia['mes_inicio'],
                        (int) $dadosDia['mes_fim']
                    );
                    $aberto = (int) $dadosDia['aberto'] === 1;
                    $valorAbertura = $dadosDia['horario_abertura'] ?? '';
                    $valorFechamento = $dadosDia['horario_fechamento'] ?? '';
                    $valorAlmocoInicio = $dadosDia['almoco_inicio'] ?? '';
                    $valorAlmocoFim = $dadosDia['almoco_fim'] ?? '';
                ?>
                <section class="dia-card" data-dia="<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="dia-card-header">
                        <h2><?= htmlspecialchars($rotuloDia, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                    <div class="dia-card-body">
                        <div class="periodo-ano">
                            <span class="periodo-titulo">Período do ano</span>
                            <div class="meses-grade">
                                <?php foreach ($mesesAno as $numeroMes => $rotuloMes): ?>
                                    <label class="mes-item">
                                        <input
                                            type="checkbox"
                                            name="<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>_meses[]"
                                            value="<?= (int) $numeroMes; ?>"
                                            <?= in_array($numeroMes, $selecionados, true) ? 'checked' : ''; ?>
                                        >
                                        <?= htmlspecialchars($rotuloMes, ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="linha-horarios">
                            <div class="campo-horario">
                                <label for="<?= $slugDia; ?>_abertura">Horário - De</label>
                                <input
                                    type="time"
                                    id="<?= $slugDia; ?>_abertura"
                                    name="<?= $slugDia; ?>_abertura"
                                    value="<?= htmlspecialchars($valorAbertura, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="input-horario"
                                >
                            </div>
                            <div class="campo-horario">
                                <label for="<?= $slugDia; ?>_fechamento">Horário - Até</label>
                                <input
                                    type="time"
                                    id="<?= $slugDia; ?>_fechamento"
                                    name="<?= $slugDia; ?>_fechamento"
                                    value="<?= htmlspecialchars($valorFechamento, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="input-horario"
                                >
                            </div>
                            <div class="campo-horario">
                                <label for="<?= $slugDia; ?>_almoco_inicio">Almoço - De</label>
                                <input
                                    type="time"
                                    id="<?= $slugDia; ?>_almoco_inicio"
                                    name="<?= $slugDia; ?>_almoco_inicio"
                                    value="<?= htmlspecialchars($valorAlmocoInicio, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="input-horario"
                                >
                            </div>
                            <div class="campo-horario">
                                <label for="<?= $slugDia; ?>_almoco_fim">Almoço - Até</label>
                                <input
                                    type="time"
                                    id="<?= $slugDia; ?>_almoco_fim"
                                    name="<?= $slugDia; ?>_almoco_fim"
                                    value="<?= htmlspecialchars($valorAlmocoFim, ENT_QUOTES, 'UTF-8'); ?>"
                                    class="input-horario"
                                >
                            </div>
                        </div>

                        <div class="dia-opcoes">
                            <label class="opcao-item">
                                <input type="checkbox" class="opcao-24h">
                                24 horas
                            </label>
                            <label class="opcao-item">
                                <input
                                    type="checkbox"
                                    name="<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>_fechado"
                                    class="opcao-fechado"
                                    <?= $aberto ? '' : 'checked'; ?>
                                >
                                Fechado
                            </label>
                            <label class="opcao-item">
                                <input type="checkbox" class="opcao-aplicar-todos">
                                Aplicar horário a todos os dias
                            </label>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="acoes-formulario">
                <button type="submit" class="botao-salvar">Salvar</button>
                <a href="../index.php" class="botao-voltar">Voltar ao site</a>
            </div>
        </form>
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cartoesDia = document.querySelectorAll('.dia-card');

            const coletarValores = (card) => {
                const meses = Array.from(card.querySelectorAll('.mes-item input'))
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);

                return {
                    meses,
                    abertura: card.querySelector('input[name$="_abertura"]').value,
                    fechamento: card.querySelector('input[name$="_fechamento"]').value,
                    almocoInicio: card.querySelector('input[name$="_almoco_inicio"]').value,
                    almocoFim: card.querySelector('input[name$="_almoco_fim"]').value,
                    fechado: card.querySelector('.opcao-fechado').checked,
                };
            };

            const aplicarValores = (card, valores) => {
                const mesesInputs = card.querySelectorAll('.mes-item input');
                mesesInputs.forEach((checkbox) => {
                    checkbox.checked = valores.meses.includes(checkbox.value);
                });

                card.querySelector('input[name$="_abertura"]').value = valores.abertura;
                card.querySelector('input[name$="_fechamento"]').value = valores.fechamento;
                card.querySelector('input[name$="_almoco_inicio"]').value = valores.almocoInicio;
                card.querySelector('input[name$="_almoco_fim"]').value = valores.almocoFim;

                const fechadoCheckbox = card.querySelector('.opcao-fechado');
                fechadoCheckbox.checked = valores.fechado;
                atualizarEstado(card);
            };

            const aplicarHorarioEmTodos = (origem) => {
                const origemDia = origem.dataset.dia;
                const valoresOrigem = coletarValores(origem);

                cartoesDia.forEach((card) => {
                    if (card.dataset.dia === origemDia) {
                        return;
                    }
                    aplicarValores(card, valoresOrigem);
                });
            };

            const atualizarEstado = (card) => {
                const inputsTempo = card.querySelectorAll('.input-horario');
                const fechadoCheckbox = card.querySelector('.opcao-fechado');
                const vinteQuatroHorasCheckbox = card.querySelector('.opcao-24h');
                const campoAbertura = card.querySelector('input[name$="_abertura"]');
                const campoFechamento = card.querySelector('input[name$="_fechamento"]');

                if (fechadoCheckbox.checked) {
                    inputsTempo.forEach((input) => {
                        input.value = '';
                        input.readOnly = true;
                    });
                    vinteQuatroHorasCheckbox.checked = false;
                    return;
                }

                if (vinteQuatroHorasCheckbox.checked) {
                    campoAbertura.value = '00:00';
                    campoFechamento.value = '23:59';
                    inputsTempo.forEach((input) => {
                        input.readOnly = true;
                    });
                } else {
                    inputsTempo.forEach((input) => {
                        input.readOnly = false;
                    });
                }
            };

            cartoesDia.forEach((card) => {
                const fechadoCheckbox = card.querySelector('.opcao-fechado');
                const vinteQuatroHorasCheckbox = card.querySelector('.opcao-24h');
                const aplicarTodosCheckbox = card.querySelector('.opcao-aplicar-todos');
                const campoAbertura = card.querySelector('input[name$="_abertura"]');
                const campoFechamento = card.querySelector('input[name$="_fechamento"]');

                if (campoAbertura.value === '00:00' && campoFechamento.value === '23:59') {
                    vinteQuatroHorasCheckbox.checked = true;
                }

                atualizarEstado(card);

                fechadoCheckbox.addEventListener('change', () => {
                    atualizarEstado(card);
                });

                vinteQuatroHorasCheckbox.addEventListener('change', () => {
                    if (vinteQuatroHorasCheckbox.checked) {
                        fechadoCheckbox.checked = false;
                    }
                    atualizarEstado(card);
                });

                aplicarTodosCheckbox.addEventListener('change', () => {
                    if (aplicarTodosCheckbox.checked) {
                        aplicarHorarioEmTodos(card);
                        setTimeout(() => {
                            aplicarTodosCheckbox.checked = false;
                        }, 150);
                    }
                });
            });
        });
    </script>
</body>
</html>
