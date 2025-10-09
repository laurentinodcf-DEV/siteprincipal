<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../conexao.php';

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

$diasSemana = [
    'segunda'  => 'Segunda',
    'terca'    => 'Terça',
    'quarta'   => 'Quarta',
    'quinta'   => 'Quinta',
    'sexta'    => 'Sexta',
    'sabado'   => 'Sábado',
    'domingo'  => 'Domingo',
    'feriados' => 'Feriados',
];

$mensagemSucesso = '';
$mensagemErro = '';

$horariosCadastrados = [];
foreach ($mesesAno as $numeroMes => $_) {
    foreach ($diasSemana as $slugDia => $_nomeDia) {
        $horariosCadastrados[$numeroMes][$slugDia] = [
            'mes'               => $numeroMes,
            'dia_semana'        => $slugDia,
            'horario_abertura'  => null,
            'horario_fechamento'=> null,
            'almoco_inicio'     => null,
            'almoco_fim'        => null,
            'aberto'            => 1,
        ];
    }
}

$resultado = $conn->query('SELECT * FROM salao_horarios_funcionamento_mes');
if ($resultado) {
    while ($linha = $resultado->fetch_assoc()) {
        $mes = (int) $linha['mes'];
        $dia = $linha['dia_semana'];
        if (isset($horariosCadastrados[$mes][$dia])) {
            $horariosCadastrados[$mes][$dia] = [
                'mes'               => $mes,
                'dia_semana'        => $dia,
                'horario_abertura'  => $linha['horario_abertura'],
                'horario_fechamento'=> $linha['horario_fechamento'],
                'almoco_inicio'     => $linha['almoco_inicio'],
                'almoco_fim'        => $linha['almoco_fim'],
                'aberto'            => (int) $linha['aberto'],
            ];
        }
    }
    $resultado->free();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $horariosPost = $_POST['horarios'] ?? [];
    $mesesPost = $_POST['meses'] ?? [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'INSERT INTO salao_horarios_funcionamento_mes
             (mes, dia_semana, horario_abertura, horario_fechamento, almoco_inicio, almoco_fim, aberto)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             horario_abertura = VALUES(horario_abertura),
             horario_fechamento = VALUES(horario_fechamento),
             almoco_inicio = VALUES(almoco_inicio),
             almoco_fim = VALUES(almoco_fim),
             aberto = VALUES(aberto)'
        );

        foreach ($mesesAno as $numeroMes => $_nomeMes) {
            $funcionandoMes = $mesesPost[$numeroMes]['funciona'] ?? 'sim';
            $mesAtivo = $funcionandoMes === 'sim';

            foreach ($diasSemana as $slugDia => $_labelDia) {
                $dadosDiaPost = $horariosPost[$numeroMes][$slugDia] ?? [];

                $aberto = $mesAtivo ? (isset($dadosDiaPost['fechado']) ? 0 : 1) : 0;
                $horarioAbertura = $dadosDiaPost['abertura'] ?? '';
                $horarioFechamento = $dadosDiaPost['fechamento'] ?? '';
                $almocoInicio = $dadosDiaPost['almoco_inicio'] ?? '';
                $almocoFim = $dadosDiaPost['almoco_fim'] ?? '';

                $horarioAbertura = trim((string) $horarioAbertura);
                $horarioFechamento = trim((string) $horarioFechamento);
                $almocoInicio = trim((string) $almocoInicio);
                $almocoFim = trim((string) $almocoFim);

                $horarioAbertura = $horarioAbertura === '' ? null : $horarioAbertura;
                $horarioFechamento = $horarioFechamento === '' ? null : $horarioFechamento;
                $almocoInicio = $almocoInicio === '' ? null : $almocoInicio;
                $almocoFim = $almocoFim === '' ? null : $almocoFim;

                if ($aberto === 0) {
                    $horarioAbertura = null;
                    $horarioFechamento = null;
                    $almocoInicio = null;
                    $almocoFim = null;
                }

                $stmt->bind_param(
                    'isssssi',
                    $numeroMes,
                    $slugDia,
                    $horarioAbertura,
                    $horarioFechamento,
                    $almocoInicio,
                    $almocoFim,
                    $aberto
                );

                if (!$stmt->execute()) {
                    throw new Exception('Erro ao salvar horários: ' . $stmt->error);
                }

                $horariosCadastrados[$numeroMes][$slugDia] = [
                    'mes'               => $numeroMes,
                    'dia_semana'        => $slugDia,
                    'horario_abertura'  => $horarioAbertura,
                    'horario_fechamento'=> $horarioFechamento,
                    'almoco_inicio'     => $almocoInicio,
                    'almoco_fim'        => $almocoFim,
                    'aberto'            => $aberto,
                ];
            }
        }

        $stmt->close();
        $conn->commit();
        $mensagemSucesso = 'Horários atualizados com sucesso.';
    } catch (Exception $ex) {
        $conn->rollback();
        $mensagemErro = $ex->getMessage();
    }
}

function mesEstaFuncionando(array $diasMes): bool
{
    foreach ($diasMes as $dadosDia) {
        if ((int) ($dadosDia['aberto'] ?? 0) === 1) {
            return true;
        }
    }
    return false;
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
            <p class="horarios-subtitle">Configure meses, dias e intervalos de almoço do salão.</p>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="alerta alerta-sucesso"><?= htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($mensagemErro): ?>
            <div class="alerta alerta-erro"><?= htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" class="horarios-formulario">
            <?php foreach ($mesesAno as $numeroMes => $nomeMes): ?>
                <?php
                    $diasMes = $horariosCadastrados[$numeroMes];
                    $mesAtivo = mesEstaFuncionando($diasMes);
                    $bodyMesId = 'mes-' . $numeroMes . '-body';
                ?>
                <section class="mes-card" data-mes="<?= (int) $numeroMes; ?>">
                    <div class="mes-card-header">
                        <h2><?= htmlspecialchars($nomeMes, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <div class="mes-funciona-opcao">
                            <span>Funcionando?</span>
                            <label>
                                <input
                                    type="radio"
                                    name="meses[<?= (int) $numeroMes; ?>][funciona]"
                                    value="sim"
                                    <?= $mesAtivo ? 'checked' : ''; ?>
                                >
                                Sim
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    name="meses[<?= (int) $numeroMes; ?>][funciona]"
                                    value="nao"
                                    <?= $mesAtivo ? '' : 'checked'; ?>
                                >
                                Não
                            </label>
                        </div>
                        <button
                            type="button"
                            class="mes-toggle"
                            aria-expanded="false"
                            aria-controls="<?= htmlspecialchars($bodyMesId, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                            <span class="mes-toggle-icon">+</span>
                        </button>
                    </div>
                    <div class="mes-card-body" id="<?= htmlspecialchars($bodyMesId, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                        <div class="mes-instrucoes">
                            <p>Abra os dias da semana para definir horários específicos.</p>
                        </div>
                        <div class="dias-container">
                            <?php foreach ($diasSemana as $slugDia => $nomeDia): ?>
                                <?php
                                    $dadosDia = $diasMes[$slugDia];
                                    $bodyId = 'mes-' . $numeroMes . '-' . $slugDia . '-body';
                                    $aberto = (int) ($dadosDia['aberto'] ?? 0) === 1;
                                    $valorAbertura = $dadosDia['horario_abertura'] ?? '';
                                    $valorFechamento = $dadosDia['horario_fechamento'] ?? '';
                                    $valorAlmocoInicio = $dadosDia['almoco_inicio'] ?? '';
                                    $valorAlmocoFim = $dadosDia['almoco_fim'] ?? '';
                                ?>
                                <article class="dia-card" data-mes="<?= (int) $numeroMes; ?>" data-dia="<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="dia-card-header">
                                        <h3><?= htmlspecialchars($nomeDia, ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <button
                                            type="button"
                                            class="dia-toggle"
                                            aria-expanded="false"
                                            aria-controls="<?= htmlspecialchars($bodyId, ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <span class="dia-toggle-icon">+</span>
                                        </button>
                                    </div>
                                    <div class="dia-card-body" id="<?= htmlspecialchars($bodyId, ENT_QUOTES, 'UTF-8'); ?>" hidden>
                                        <div class="linha-horarios">
                                            <div class="campo-horario">
                                                <label for="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-abertura">Horário - De</label>
                                                <input
                                                    type="time"
                                                    id="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-abertura"
                                                    name="horarios[<?= (int) $numeroMes; ?>][<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>][abertura]"
                                                    value="<?= htmlspecialchars($valorAbertura, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="input-horario"
                                                >
                                            </div>
                                            <div class="campo-horario">
                                                <label for="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-fechamento">Horário - Até</label>
                                                <input
                                                    type="time"
                                                    id="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-fechamento"
                                                    name="horarios[<?= (int) $numeroMes; ?>][<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>][fechamento]"
                                                    value="<?= htmlspecialchars($valorFechamento, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="input-horario"
                                                >
                                            </div>
                                            <div class="campo-horario">
                                                <label for="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-almoco-inicio">Almoço - De</label>
                                                <input
                                                    type="time"
                                                    id="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-almoco-inicio"
                                                    name="horarios[<?= (int) $numeroMes; ?>][<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>][almoco_inicio]"
                                                    value="<?= htmlspecialchars($valorAlmocoInicio, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="input-horario"
                                                >
                                            </div>
                                            <div class="campo-horario">
                                                <label for="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-almoco-fim">Almoço - Até</label>
                                                <input
                                                    type="time"
                                                    id="dia-<?= $numeroMes; ?>-<?= $slugDia; ?>-almoco-fim"
                                                    name="horarios[<?= (int) $numeroMes; ?>][<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>][almoco_fim]"
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
                                                    name="horarios[<?= (int) $numeroMes; ?>][<?= htmlspecialchars($slugDia, ENT_QUOTES, 'UTF-8'); ?>][fechado]"
                                                    class="opcao-fechado"
                                                    <?= $aberto ? '' : 'checked'; ?>
                                                >
                                                Fechado
                                            </label>
                                            <label class="opcao-item">
                                                <input type="checkbox" class="opcao-aplicar-mes">
                                                Aplicar horário a todos os dias do mês
                                            </label>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
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
            const mesCards = document.querySelectorAll('.mes-card');

            const actualizarIconeToggle = (botao, estadoColapsado) => {
                const icone = botao.querySelector('.mes-toggle-icon, .dia-toggle-icon');
                if (icone) {
                    icone.textContent = estadoColapsado ? '+' : '-';
                }
            };

            mesCards.forEach((mesCard) => {
                const toggleMes = mesCard.querySelector('.mes-toggle');
                const corpoMes = mesCard.querySelector('.mes-card-body');
                const radiosFuncionamento = mesCard.querySelectorAll('input[type="radio"][name^="meses"]');

                if (toggleMes && corpoMes) {
                    actualizarIconeToggle(toggleMes, true);
                    toggleMes.addEventListener('click', () => {
                        const oculto = corpoMes.hasAttribute('hidden');
                        if (oculto) {
                            corpoMes.removeAttribute('hidden');
                        } else {
                            corpoMes.setAttribute('hidden', 'hidden');
                        }
                        const estaColapsado = !oculto;
                        toggleMes.setAttribute('aria-expanded', oculto ? 'true' : 'false');
                        actualizarIconeToggle(toggleMes, estaColapsado);
                    });
                }

                const diasDoMes = mesCard.querySelectorAll('.dia-card');

                const aplicarEstadoMes = (funciona) => {
                    diasDoMes.forEach((diaCard) => {
                        const fechadoCheckbox = diaCard.querySelector('.opcao-fechado');
                        if (fechadoCheckbox) {
                            fechadoCheckbox.checked = !funciona;
                            fechadoCheckbox.dispatchEvent(new Event('change'));
                        }
                    });
                };

                radiosFuncionamento.forEach((radio) => {
                    radio.addEventListener('change', () => {
                        if (radio.value === 'sim' && radio.checked) {
                            aplicarEstadoMes(true);
                        }
                        if (radio.value === 'nao' && radio.checked) {
                            aplicarEstadoMes(false);
                        }
                    });
                });

                diasDoMes.forEach((diaCard) => {
                    const toggleDia = diaCard.querySelector('.dia-toggle');
                    const corpoDia = diaCard.querySelector('.dia-card-body');
                    const fechadoCheckbox = diaCard.querySelector('.opcao-fechado');
                    const vinteQuatroCheckbox = diaCard.querySelector('.opcao-24h');
                    const aplicarMesCheckbox = diaCard.querySelector('.opcao-aplicar-mes');
                    const inputsTempo = diaCard.querySelectorAll('.input-horario');
                    const aberturaInput = diaCard.querySelector('input[name$="[abertura]"]');
                    const fechamentoInput = diaCard.querySelector('input[name$="[fechamento]"]');
                    const almocoInicioInput = diaCard.querySelector('input[name$="[almoco_inicio]"]');
                    const almocoFimInput = diaCard.querySelector('input[name$="[almoco_fim]"]');

                    if (toggleDia && corpoDia) {
                        actualizarIconeToggle(toggleDia, true);
                        toggleDia.addEventListener('click', () => {
                            const oculto = corpoDia.hasAttribute('hidden');
                            if (oculto) {
                                corpoDia.removeAttribute('hidden');
                            } else {
                                corpoDia.setAttribute('hidden', 'hidden');
                            }
                            const estaColapsado = !oculto;
                            toggleDia.setAttribute('aria-expanded', oculto ? 'true' : 'false');
                            actualizarIconeToggle(toggleDia, estaColapsado);
                        });
                    }

                    const actualizarCampos = () => {
                        if (fechadoCheckbox.checked) {
                            inputsTempo.forEach((input) => {
                                input.value = '';
                                input.readOnly = true;
                            });
                            if (vinteQuatroCheckbox) {
                                vinteQuatroCheckbox.checked = false;
                            }
                            return;
                        }

                        if (vinteQuatroCheckbox && vinteQuatroCheckbox.checked) {
                            if (aberturaInput) {
                                aberturaInput.value = '00:00';
                            }
                            if (fechamentoInput) {
                                fechamentoInput.value = '23:59';
                            }
                            if (almocoInicioInput) {
                                almocoInicioInput.value = '';
                            }
                            if (almocoFimInput) {
                                almocoFimInput.value = '';
                            }
                            inputsTempo.forEach((input) => {
                                input.readOnly = true;
                            });
                        } else {
                            inputsTempo.forEach((input) => {
                                input.readOnly = false;
                            });
                        }
                    };

                    if (fechadoCheckbox) {
                        fechadoCheckbox.addEventListener('change', actualizarCampos);
                    }

                    if (vinteQuatroCheckbox) {
                        if (
                            aberturaInput &&
                            fechamentoInput &&
                            aberturaInput.value === '00:00' &&
                            fechamentoInput.value === '23:59'
                        ) {
                            vinteQuatroCheckbox.checked = true;
                        }

                        vinteQuatroCheckbox.addEventListener('change', () => {
                            if (vinteQuatroCheckbox.checked && fechadoCheckbox) {
                                fechadoCheckbox.checked = false;
                            }
                            actualizarCampos();
                        });
                    }

                    if (aplicarMesCheckbox) {
                        aplicarMesCheckbox.addEventListener('change', () => {
                            if (!aplicarMesCheckbox.checked) {
                                return;
                            }

                            const valoresOrigem = {
                                abertura: diaCard.querySelector('input[name$="[abertura]"]').value,
                                fechamento: diaCard.querySelector('input[name$="[fechamento]"]').value,
                                almocoInicio: diaCard.querySelector('input[name$="[almoco_inicio]"]').value,
                                almocoFim: diaCard.querySelector('input[name$="[almoco_fim]"]').value,
                                fechado: fechadoCheckbox.checked,
                                vinteQuatro: vinteQuatroCheckbox ? vinteQuatroCheckbox.checked : false,
                            };

                            diasDoMes.forEach((alvo) => {
                                if (alvo === diaCard) {
                                    return;
                                }

                                const fechadoAlvo = alvo.querySelector('.opcao-fechado');
                                const vinteQuatroAlvo = alvo.querySelector('.opcao-24h');
                                const inputsAlvo = alvo.querySelectorAll('.input-horario');

                                alvo.querySelector('input[name$="[abertura]"]').value = valoresOrigem.abertura;
                                alvo.querySelector('input[name$="[fechamento]"]').value = valoresOrigem.fechamento;
                                alvo.querySelector('input[name$="[almoco_inicio]"]').value = valoresOrigem.almocoInicio;
                                alvo.querySelector('input[name$="[almoco_fim]"]').value = valoresOrigem.almocoFim;

                                if (fechadoAlvo) {
                                    fechadoAlvo.checked = valoresOrigem.fechado;
                                    fechadoAlvo.dispatchEvent(new Event('change'));
                                }

                                if (vinteQuatroAlvo) {
                                    vinteQuatroAlvo.checked = valoresOrigem.vinteQuatro;
                                    vinteQuatroAlvo.dispatchEvent(new Event('change'));
                                }

                                if (valoresOrigem.vinteQuatro && !valoresOrigem.fechado) {
                                    const aberturaAlvo = alvo.querySelector('input[name$="[abertura]"]');
                                    const fechamentoAlvo = alvo.querySelector('input[name$="[fechamento]"]');
                                    if (aberturaAlvo) {
                                        aberturaAlvo.value = '00:00';
                                    }
                                    if (fechamentoAlvo) {
                                        fechamentoAlvo.value = '23:59';
                                    }
                                }
                            });

                            setTimeout(() => {
                                aplicarMesCheckbox.checked = false;
                            }, 150);
                        });
                    }

                    actualizarCampos();
                });
            });
        });
    </script>
</body>
</html>
