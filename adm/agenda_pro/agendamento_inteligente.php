<?php
// Módulo: Sistema de Agendamento Inteligente
// Usa a tabela salao_agendamentos conforme o schema informado

if (!isset($conn)) {
    require_once __DIR__ . '/../../conexao.php';
}

$mensagemSucesso = '';
$mensagemErro = '';

// Helpers
function so_numeros(string $v): string { return preg_replace('/\D+/', '', $v) ?? ''; }

// Safe binder to avoid fatal if types length mismatches
function bindParamsSafe(mysqli_stmt $stmt, string $types, &...$vars): bool {
    $expected = strlen($types);
    $given = count($vars);
    if ($expected !== $given) {
        // Fallback: bind all as strings to prevent ArgumentCountError
        $types = str_repeat('s', $given);
    }
    return $stmt->bind_param($types, ...$vars);
}

function addMinutosHora(string $horaHHMM, int $minutos): string {
    try {
        $dt = new DateTime('1970-01-01 ' . $horaHHMM);
        if ($minutos !== 0) {
            $dt->modify(($minutos >= 0 ? '+' : '') . $minutos . ' minutes');
        }
        return $dt->format('H:i:s');
    } catch (Throwable $e) {
        return '00:00:00';
    }
}

function normalizarDataPost(?string $data): ?string {
    if (!$data) return null;
    $data = trim($data);
    // Se vier em dd/mm/yyyy, converter
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data)) {
        [$d,$m,$y] = explode('/', $data);
        return $y . '-' . $m . '-' . $d;
    }
    // Se já vier yyyy-mm-dd, aceitar
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) return $data;
    return null;
}

function validarSobreposicao(mysqli $conn, int $profissionalId, string $dataAg, string $horaIni, string $horaFim, ?int $ignorarId = null): bool {
    $sql = 'SELECT COUNT(*) AS total FROM salao_agendamentos WHERE profissional_id = ? AND data_agendamento = ? AND NOT (hora_fim <= ? OR hora_inicio >= ?)';
    if ($ignorarId) {
        $sql .= ' AND id <> ?';
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    if ($ignorarId) {
        $stmt->bind_param('isssi', $profissionalId, $dataAg, $horaIni, $horaFim, $ignorarId);
    } else {
        $stmt->bind_param('isss', $profissionalId, $dataAg, $horaIni, $horaFim);
    }
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : ['total' => 0];
    $stmt->close();
    return ((int)($row['total'] ?? 0)) === 0;
}

// Carregar listas base
$profissionais = [];
if ($r = $conn->query("SELECT id, nome FROM salao_profissionais WHERE ativo = 1 ORDER BY nome")) {
    while ($row = $r->fetch_assoc()) { $profissionais[] = $row; }
    $r->free();
}

$servicos = [];
if ($r = $conn->query("SELECT id, nome, duracao, preco FROM salao_servicos WHERE ativo = 1 ORDER BY nome")) {
    while ($row = $r->fetch_assoc()) { $servicos[] = $row; }
    $r->free();
}

$clientes = [];
if ($r = $conn->query("SELECT id, nome FROM salao_clientes ORDER BY nome")) {
    while ($row = $r->fetch_assoc()) { $clientes[] = $row; }
    $r->free();
}

// Mapas úteis
$mapServicoDuracao = [];
foreach ($servicos as $s) { $mapServicoDuracao[(int)$s['id']] = (int)($s['duracao'] ?? 0); }
// Preços dos serviços
$mapServicoPreco = [];
foreach ($servicos as $s) { $mapServicoPreco[(int)$s['id']] = (float)($s['preco'] ?? 0); }

// Filtros
$dataSelecionada = isset($_GET['data']) ? normalizarDataPost($_GET['data']) : date('Y-m-d');
if (!$dataSelecionada) { $dataSelecionada = date('Y-m-d'); }
$profFiltro = isset($_GET['profissional']) ? (int) $_GET['profissional'] : 0;

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';

    if ($acao === 'create' || $acao === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $cliente_id = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== '' ? (int)$_POST['cliente_id'] : null;
        $nome_cliente = trim((string)($_POST['nome_cliente'] ?? '')) ?: null;
        $telefone_cliente = trim((string)($_POST['telefone_cliente'] ?? '')) ?: null;
        $profissional_id = (int)($_POST['profissional_id'] ?? 0);
        $servico_id = (int)($_POST['servico_id'] ?? 0);
        $data_agendamento = normalizarDataPost($_POST['data_agendamento'] ?? '');
        $hora_inicio = trim((string)($_POST['hora_inicio'] ?? ''));
        $status = $_POST['status'] ?? 'agendado';
        $observacoes = trim((string)($_POST['observacoes'] ?? '')) ?: null;

        // Duração: prevista vem do serviço; real opcional do formulário
        $duracao_prevista = $mapServicoDuracao[$servico_id] ?? 0;
        $duracao_real = isset($_POST['duracao_real']) && $_POST['duracao_real'] !== '' ? (int)$_POST['duracao_real'] : null;
        $duracao_para_fim = $duracao_real !== null && $duracao_real > 0 ? $duracao_real : $duracao_prevista;

        // Pagamento (a partir dos requisitos)
        $valor_servico = $mapServicoPreco[$servico_id] ?? 0.0; // snapshot do valor do serviço
        $valor_pago = isset($_POST['valor_pago']) && $_POST['valor_pago'] !== '' ? (float)str_replace(',', '.', $_POST['valor_pago']) : 0.0;
        $forma_pagamento = $_POST['forma_pagamento'] ?? null; // dinheiro, cartao, pix, outro
        $status_pagamento = $_POST['status_pagamento'] ?? null; // pendente, pago, parcelado, cancelado
        $parceladoFlagPost = 0;
        $quantidade_parcelas = 0;
        $valor_parcela = null;
        $dia_vencimento = null;
        if ($status === 'concluido') {
            if ($status_pagamento === 'pago') {
                // ok
                if ($valor_pago <= 0) {
                    $mensagemErro = 'Para concluir com pagamento PAGO, informe um valor a ser pago maior que 0.';
                }
                if (!$forma_pagamento) {
                    $mensagemErro = $mensagemErro ?: 'Para concluir com pagamento PAGO, selecione a forma de pagamento.';
                }
            } elseif ($status_pagamento === 'parcelado') {
                $parceladoFlagPost = 1;
                $quantidade_parcelas = isset($_POST['numero_parcelas']) ? (int)$_POST['numero_parcelas'] : 0;
                $valor_parcela = isset($_POST['valor_parcela']) && $_POST['valor_parcela'] !== '' ? (float)str_replace(',', '.', $_POST['valor_parcela']) : 0.0;
                $dia_vencimento = isset($_POST['dia_vencimento']) ? (int)$_POST['dia_vencimento'] : 0;
                if ($quantidade_parcelas < 2) {
                    $mensagemErro = 'Informe ao menos 2 parcelas para pagamento parcelado.';
                } elseif ($valor_parcela <= 0) {
                    $mensagemErro = 'Informe o valor de cada parcela (maior que 0).';
                } elseif ($dia_vencimento < 1 || $dia_vencimento > 31) {
                    $mensagemErro = 'Informe um dia de vencimento entre 1 e 31.';
                }
                // Para parcelado, valor_pago do agendamento fica 0; o faturamento será reconhecido nas parcelas
                $valor_pago = 0.0;
            } else {
                // pendente ou cancelado não podem deixar concluir
                if ($status_pagamento === null || $status_pagamento === '' || $status_pagamento === 'pendente' || $status_pagamento === 'cancelado') {
                    $mensagemErro = 'Nao e permitido concluir com status de pagamento Pendente/Cancelado. Selecione PAGO ou PARCELADO.';
                }
            }
        }

        // Normalizar hora_inicio para HH:MM:SS
        if ($hora_inicio && strlen($hora_inicio) === 5) { $hora_inicio .= ':00'; }

        if ($profissional_id <= 0 || $servico_id <= 0 || !$data_agendamento || !$hora_inicio || $duracao_prevista <= 0) {
            $mensagemErro = 'Preencha os campos obrigatórios: profissional, serviço, data, hora e verifique a duração do serviço.';
        } else {
            $hora_fim = addMinutosHora($hora_inicio, $duracao_para_fim);

            // Checar conflito
            $semConflito = validarSobreposicao($conn, $profissional_id, $data_agendamento, $hora_inicio, $hora_fim, $acao === 'update' ? $id : null);
            if (!$semConflito) {
                $mensagemErro = 'Conflito de horário: já existe um agendamento para este profissional nesse período.';
            } else {
                if ($acao === 'create') {
                    $stmt = $conn->prepare('INSERT INTO salao_agendamentos (cliente_id, nome_cliente, telefone_cliente, profissional_id, servico_id, valor_servico, valor_pago, data_pagamento, forma_pagamento, status_pagamento, parcelado, quantidade_parcelas, data_agendamento, hora_inicio, hora_fim, duracao_prevista, duracao_real, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $types = 'issiiddsssiissssiiiss';
                        // i: cliente_id, s: nome, s: telefone, i: prof, i: serv, d: valor_servico, d: valor_pago, s: data_pag (Y-m-d or null), s: forma, s: status_pagamento, i: parcelado, i: qtd_parc, s: data_ag, s: hora_inicio, s: hora_fim, i: dur_prev, i: dur_real, s: observ, s: status
                        $data_pagamento = null;
                        if ($status === 'concluido' && $status_pagamento === 'pago') {
                            $data_pagamento = date('Y-m-d');
                        }
                        bindParamsSafe($stmt, $types,
                            $cliente_id,
                            $nome_cliente,
                            $telefone_cliente,
                            $profissional_id,
                            $servico_id,
                            $valor_servico,
                            $valor_pago,
                            $data_pagamento,
                            $forma_pagamento,
                            $status_pagamento,
                            $parceladoFlagPost,
                            $quantidade_parcelas,
                            $data_agendamento,
                            $hora_inicio,
                            $hora_fim,
                            $duracao_prevista,
                            $duracao_real,
                            $observacoes,
                            $status
                        );
                        if ($stmt->execute()) {
                            $mensagemSucesso = 'Agendamento criado com sucesso.';
                            // Redefinir data selecionada para a data do agendamento salvo
                            $dataSelecionada = $data_agendamento;
                            if ($profFiltro === 0) { $profFiltro = $profissional_id; }

                            // Se parcelado, gerar parcelas
                            if ($status === 'concluido' && $status_pagamento === 'parcelado' && $parceladoFlagPost === 1 && $quantidade_parcelas > 1) {
                                $novoAgId = $conn->insert_id;
                                // Gerar vencimentos a partir de data_agendamento e dia_vencimento escolhido
                                $datas = [];
                                try {
                                    $base = new DateTime($data_agendamento);
                                    $diaBase = (int)$base->format('j');
                                    $mesBase = (int)$base->format('n');
                                    $anoBase = (int)$base->format('Y');
                                    // Determinar primeiro vencimento: se dia_vencimento >= diaBase => este mês, senão próximo mês
                                    $primeiroMes = $dia_vencimento >= $diaBase ? $mesBase : $mesBase + 1;
                                    $ano = $anoBase;
                                    for ($i=0; $i<$quantidade_parcelas; $i++) {
                                        $mes = $primeiroMes + $i;
                                        // ajustar ano/mes
                                        $anoAdj = $ano + intdiv($mes-1, 12);
                                        $mesAdj = (($mes-1) % 12) + 1;
                                        // clamp dia para último dia do mês
                                        $dt = DateTime::createFromFormat('Y-n-j', $anoAdj.'-'.$mesAdj.'-1');
                                        $ultimoDia = (int)$dt->format('t');
                                        $dia = min($dia_vencimento, $ultimoDia);
                                        $dt->setDate($anoAdj, $mesAdj, $dia);
                                        $datas[] = $dt->format('Y-m-d');
                                    }
                                } catch (Throwable $e) {}

                                if (!empty($datas)) {
                                    $stmtParc = $conn->prepare('INSERT INTO salao_agendamento_parcelas (agendamento_id, numero_parcela, valor_parcela, data_vencimento, data_pagamento, forma_pagamento, status) VALUES (?, ?, ?, ?, NULL, NULL, NULL)');
                                    if ($stmtParc) {
                                        foreach ($datas as $idx => $venc) {
                                            $num = $idx + 1;
                                            $stmtParc->bind_param('iids', $novoAgId, $num, $valor_parcela, $venc);
                                            $stmtParc->execute();
                                        }
                                        $stmtParc->close();
                                    }
                                }
                            }
                        } else {
                            $mensagemErro = 'Erro ao salvar: ' . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $mensagemErro = 'Erro ao preparar inserção.';
                    }
                } else {
                    if ($id <= 0) {
                        $mensagemErro = 'Agendamento inválido para edição.';
                    } else {
                        // Buscar status anterior e existência de parcelas para lógica de reset ao sair de concluído
                        $statusAnterior = null; $temParcelasExistentes = 0;
                        if ($stx = $conn->prepare('SELECT status FROM salao_agendamentos WHERE id = ?')) {
                            $stx->bind_param('i', $id);
                            if ($stx->execute()) { $rx = $stx->get_result(); $rowx = $rx->fetch_assoc(); $statusAnterior = $rowx['status'] ?? null; }
                            $stx->close();
                        }
                        if ($rsx = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamento_parcelas WHERE agendamento_id = ?')) {
                            $rsx->bind_param('i', $id);
                            if ($rsx->execute()) { $rr = $rsx->get_result(); $rrow = $rr->fetch_assoc(); $temParcelasExistentes = (int)($rrow['t'] ?? 0); }
                            $rsx->close();
                        }
                        $resetPagamentoAoSairDeConcluido = ($statusAnterior === 'concluido' && $status !== 'concluido');
                        if ($resetPagamentoAoSairDeConcluido) {
                            // Resetar campos de pagamento para default
                            $valor_pago = 0.0;
                            $forma_pagamento = 'dinheiro';
                            $status_pagamento = 'pendente';
                            $parceladoFlagPost = 0;
                            $quantidade_parcelas = 0;
                            // sem data de pagamento
                        }
                        $stmt = $conn->prepare('UPDATE salao_agendamentos SET cliente_id = ?, nome_cliente = ?, telefone_cliente = ?, profissional_id = ?, servico_id = ?, valor_servico = ?, valor_pago = ?, data_pagamento = ?, forma_pagamento = ?, status_pagamento = ?, parcelado = ?, quantidade_parcelas = ?, data_agendamento = ?, hora_inicio = ?, hora_fim = ?, duracao_prevista = ?, duracao_real = ?, observacoes = ?, status = ? WHERE id = ?');
                        if ($stmt) {
                            $types = 'issiiddsssiissssiiissi';
                            $data_pagamento = null;
                            if ($status === 'concluido' && $status_pagamento === 'pago') { $data_pagamento = date('Y-m-d'); }
                            bindParamsSafe($stmt, $types,
                                $cliente_id,
                                $nome_cliente,
                                $telefone_cliente,
                                $profissional_id,
                                $servico_id,
                                $valor_servico,
                                $valor_pago,
                                $data_pagamento,
                                $forma_pagamento,
                                $status_pagamento,
                                $parceladoFlagPost,
                                $quantidade_parcelas,
                                $data_agendamento,
                                $hora_inicio,
                                $hora_fim,
                                $duracao_prevista,
                                $duracao_real,
                                $observacoes,
                                $status,
                                $id
                            );
                            if ($stmt->execute()) {
                                $mensagemSucesso = 'Agendamento atualizado.';
                                $dataSelecionada = $data_agendamento;
                                if ($profFiltro === 0) { $profFiltro = $profissional_id; }

                                // Se parcelado e não há parcelas ainda, criar
                                if ($status === 'concluido' && $status_pagamento === 'parcelado' && $parceladoFlagPost === 1 && $quantidade_parcelas > 1) {
                                    // Verificar existência de parcelas
                                    $jaTem = 0;
                                    if ($rs = $conn->query('SELECT COUNT(*) AS t FROM salao_agendamento_parcelas WHERE agendamento_id = '.(int)$id)) {
                                        $row = $rs->fetch_assoc();
                                        $jaTem = (int)($row['t'] ?? 0);
                                        $rs->free();
                                    }
                                    if ($jaTem === 0) {
                                        $datas = [];
                                        try {
                                            $base = new DateTime($data_agendamento);
                                            $diaBase = (int)$base->format('j');
                                            $mesBase = (int)$base->format('n');
                                            $anoBase = (int)$base->format('Y');
                                            $primeiroMes = $dia_vencimento >= $diaBase ? $mesBase : $mesBase + 1;
                                            $ano = $anoBase;
                                            for ($i=0; $i<$quantidade_parcelas; $i++) {
                                                $mes = $primeiroMes + $i;
                                                $anoAdj = $ano + intdiv($mes-1, 12);
                                                $mesAdj = (($mes-1) % 12) + 1;
                                                $dt = DateTime::createFromFormat('Y-n-j', $anoAdj.'-'.$mesAdj.'-1');
                                                $ultimoDia = (int)$dt->format('t');
                                                $dia = min($dia_vencimento, $ultimoDia);
                                                $dt->setDate($anoAdj, $mesAdj, $dia);
                                                $datas[] = $dt->format('Y-m-d');
                                            }
                                        } catch (Throwable $e) {}
                                        if (!empty($datas)) {
                                            $stmtParc = $conn->prepare('INSERT INTO salao_agendamento_parcelas (agendamento_id, numero_parcela, valor_parcela, data_vencimento, data_pagamento, forma_pagamento, status) VALUES (?, ?, ?, ?, NULL, NULL, NULL)');
                                            if ($stmtParc) {
                                                foreach ($datas as $idx => $venc) {
                                                    $num = $idx + 1;
                                                    $stmtParc->bind_param('iids', $id, $num, $valor_parcela, $venc);
                                                    $stmtParc->execute();
                                                }
                                                $stmtParc->close();
                                            }
                                        }
                                    }
                                }
                                // Se saiu de concluído para outro status: deletar parcelas existentes
                                if ($resetPagamentoAoSairDeConcluido && $temParcelasExistentes > 0) {
                                    if ($del = $conn->prepare('DELETE FROM salao_agendamento_parcelas WHERE agendamento_id = ?')) {
                                        $del->bind_param('i', $id);
                                        $del->execute();
                                        $del->close();
                                    }
                                }
                            } else {
                                $mensagemErro = 'Erro ao atualizar: ' . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $mensagemErro = 'Erro ao preparar atualização.';
                        }
                    }
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM salao_agendamentos WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    $mensagemSucesso = 'Agendamento excluído.';
                } else {
                    $mensagemErro = 'Erro ao excluir: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensagemErro = 'Erro ao preparar exclusão.';
            }
        } else {
            $mensagemErro = 'Agendamento inválido para exclusão.';
        }
    }
}

// Buscar agendamentos do dia selecionado
$params = [$dataSelecionada];
$types = 's';
$sql = "SELECT a.*,
           c.nome AS cliente_nome_cad,
           p.nome AS profissional_nome,
           s.nome AS servico_nome,
           ap.parcelas_qtd,
           ap.valor_parcela_medio,
           ap.dia_vencimento
    FROM salao_agendamentos a
    LEFT JOIN salao_clientes c ON c.id = a.cliente_id
    INNER JOIN salao_profissionais p ON p.id = a.profissional_id
    INNER JOIN salao_servicos s ON s.id = a.servico_id
    LEFT JOIN (
        SELECT agendamento_id,
           COUNT(*) AS parcelas_qtd,
           AVG(valor_parcela) AS valor_parcela_medio,
           DAY(MIN(data_vencimento)) AS dia_vencimento
        FROM salao_agendamento_parcelas
        GROUP BY agendamento_id
    ) ap ON ap.agendamento_id = a.id
    WHERE a.data_agendamento = ?";
if ($profFiltro > 0) {
    $sql .= ' AND a.profissional_id = ?';
    $types .= 'i';
    $params[] = $profFiltro;
}
$sql .= ' ORDER BY a.hora_inicio ASC';

$stmt = $conn->prepare($sql);
$agendamentosDia = [];
if ($stmt) {
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $agendamentosDia[] = $row; }
    }
    $stmt->close();
}

// Navegação de data
$dtSel = DateTime::createFromFormat('Y-m-d', $dataSelecionada) ?: new DateTime();
$prev = clone $dtSel; $prev->modify('-1 day');
$next = clone $dtSel; $next->modify('+1 day');

?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="?modulo=agendamento_inteligente&data=<?php echo $prev->format('Y-m-d'); ?>&profissional=<?php echo (int)$profFiltro; ?>"><i class="bi bi-arrow-left"></i></a>
            <strong><?php echo htmlspecialchars(date('d/m/Y', $dtSel->getTimestamp()), ENT_QUOTES, 'UTF-8'); ?></strong>
            <a class="btn btn-outline-secondary btn-sm" href="?modulo=agendamento_inteligente&data=<?php echo $next->format('Y-m-d'); ?>&profissional=<?php echo (int)$profFiltro; ?>"><i class="bi bi-arrow-right"></i></a>
            <a class="btn btn-outline-primary btn-sm" href="?modulo=agendamento_inteligente&data=<?php echo date('Y-m-d'); ?>&profissional=<?php echo (int)$profFiltro; ?>">Hoje</a>
        </div>
        <form method="get" class="d-flex align-items-center gap-2">
            <input type="hidden" name="modulo" value="agendamento_inteligente">
            <input type="date" class="form-control form-control-sm" name="data" value="<?php echo htmlspecialchars($dataSelecionada, ENT_QUOTES, 'UTF-8'); ?>">
            <select name="profissional" class="form-select form-select-sm" style="min-width: 220px">
                <option value="0">Todos os profissionais</option>
                <?php foreach ($profissionais as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" <?php echo $profFiltro === (int)$p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-success btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
        </form>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovoAgendamento"><i class="bi bi-plus-lg"></i> Novo</button>
    </div>

    <div class="card-body">
        <?php if ($mensagemSucesso): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensagemSucesso, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensagemErro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (empty($agendamentosDia)): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle"></i> Nenhum agendamento para este dia.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Serviço</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentosDia as $ag): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(substr($ag['hora_inicio'],0,5).' - '.substr($ag['hora_fim'],0,5), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars($ag['nome_cliente'] ?: ($ag['cliente_nome_cad'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($ag['profissional_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($ag['servico_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php 
                                    $st = $ag['status'] ?? 'agendado';
                                    $classes = [
                                        'agendado' => 'secondary',
                                        'em_andamento' => 'info',
                                        'concluido' => 'success',
                                        'cancelado' => 'danger'
                                    ];
                                    $cls = $classes[$st] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $cls; ?>"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarAgendamento" data-ag='<?php echo htmlspecialchars(json_encode($ag), ENT_QUOTES, 'UTF-8'); ?>'><i class="bi bi-pencil"></i></button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Excluir este agendamento?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$ag['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Novo Agendamento -->
<div class="modal fade" id="modalNovoAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Data*</label>
                            <input type="date" name="data_agendamento" class="form-control" required value="<?php echo htmlspecialchars($dataSelecionada, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora início*</label>
                            <input type="text" name="hora_inicio" id="novo_hora_inicio" class="form-control" placeholder="hh:mm" maxlength="5" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Profissional*</label>
                            <select name="profissional_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($profissionais as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>" <?php echo $profFiltro === (int)$p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Serviço*</label>
                            <select name="servico_id" id="novo_servico_id" class="form-select" required>
                                <option value="">Selecione</option>
                                <?php foreach ($servicos as $s): ?>
                                    <option value="<?php echo (int)$s['id']; ?>" data-duracao="<?php echo (int)$s['duracao']; ?>" data-preco="<?php echo htmlspecialchars(number_format((float)$s['preco'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($s['nome'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$s['duracao']; ?> min)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duração/Tempo (hh:mm)</label>
                            <input type="text" name="duracao_hhmm" id="novo_duracao_hhmm" class="form-control" inputmode="numeric" maxlength="5" placeholder="hh:mm (00:00 a 24:00)" pattern="^(?:[01]?\d|2[0-3]):[0-5]\d$|^24:00$">
                            <div class="form-text">Converte para minutos automaticamente</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duração real (min)</label>
                            <input type="number" name="duracao_real" id="novo_duracao_real" class="form-control" min="1" readonly data-alwaysreadonly="1">
                            <div class="form-text">Calculado a partir do campo acima</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="novo_status" class="form-select">
                                <option value="agendado">Agendado</option>
                                <option value="em_andamento">Em andamento</option>
                                <option value="concluido">Concluído</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div id="novo_pagamento_section" class="card border-success d-none">
                                <div class="card-header bg-success-subtle">
                                    <strong>Pagamento</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Valor Serviço</label>
                                            <input type="text" id="novo_valor_servico_view" class="form-control" value="" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Valor a ser pago</label>
                                            <input type="number" step="0.01" min="0" name="valor_pago" id="novo_valor_pago" class="form-control" placeholder="0,00">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Forma pagamento</label>
                                            <select name="forma_pagamento" id="novo_forma_pagamento" class="form-select">
                                                <option value="">Selecione</option>
                                                <option value="dinheiro">Dinheiro</option>
                                                <option value="cartao">Cartão</option>
                                                <option value="pix">PIX</option>
                                                <option value="outro">Outro</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Status pagamento</label>
                                            <select name="status_pagamento" id="novo_status_pagamento" class="form-select">
                                                <option value="">Selecione</option>
                                                <option value="pago">Pago</option>
                                                <option value="parcelado">Parcelado</option>
                                                <option value="pendente">Pendente</option>
                                                <option value="cancelado">Cancelado</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>

                                        <div class="col-12 d-none" id="novo_parcelado_fields">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">Número de parcelas</label>
                                                    <input type="number" min="2" max="36" name="numero_parcelas" id="novo_numero_parcelas" class="form-control" placeholder="ex: 6">
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Valor da parcela</label>
                                                    <input type="number" step="0.01" min="0" name="valor_parcela" id="novo_valor_parcela" class="form-control" placeholder="0,00">
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Dia vencimento</label>
                                                    <input type="number" min="1" max="31" name="dia_vencimento" id="novo_dia_vencimento" class="form-control" placeholder="1..31">
                                                    <div class="form-text">Usado para gerar o vencimento das próximas parcelas.</div>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cliente (cadastrado)</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">— sem vínculo —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nome (rápido)</label>
                            <input type="text" name="nome_cliente" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone_cliente" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Anotações do atendimento"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Agendamento -->
<div class="modal fade" id="modalEditarAgendamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="formEditarAgendamento">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="original_status" id="edit_original_status" value="">
                <input type="hidden" name="tem_parcelas" id="edit_tem_parcelas" value="0">
                <input type="hidden" name="confirm_reset_pagamento" id="edit_confirm_reset_pagamento" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Agendamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Data*</label>
                            <input type="date" name="data_agendamento" id="edit_data" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hora início*</label>
                            <input type="text" name="hora_inicio" id="edit_hora_inicio" class="form-control" placeholder="hh:mm" maxlength="5" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Profissional*</label>
                            <select name="profissional_id" id="edit_profissional_id" class="form-select" required>
                                <?php foreach ($profissionais as $p): ?>
                                    <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Serviço*</label>
                            <select name="servico_id" id="edit_servico_id" class="form-select" required>
                                <?php foreach ($servicos as $s): ?>
                                    <option value="<?php echo (int)$s['id']; ?>" data-duracao="<?php echo (int)$s['duracao']; ?>" data-preco="<?php echo htmlspecialchars(number_format((float)$s['preco'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($s['nome'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$s['duracao']; ?> min)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duração/Tempo (hh:mm)</label>
                            <input type="text" name="duracao_hhmm" id="edit_duracao_hhmm" class="form-control" inputmode="numeric" maxlength="5" placeholder="hh:mm (00:00 a 24:00)" pattern="^(?:[01]?\d|2[0-3]):[0-5]\d$|^24:00$">
                            <div class="form-text">Converte para minutos automaticamente</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Duração real (min)</label>
                            <input type="number" name="duracao_real" id="edit_duracao_real" class="form-control" min="1" readonly data-alwaysreadonly="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="agendado">Agendado</option>
                                <option value="em_andamento">Em andamento</option>
                                <option value="concluido">Concluído</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div id="edit_pagamento_section" class="card border-success d-none">
                                <div class="card-header bg-success-subtle">
                                    <strong>Pagamento</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Valor Serviço</label>
                                            <input type="text" id="edit_valor_servico_view" class="form-control" value="" readonly>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Valor a ser pago</label>
                                            <input type="number" step="0.01" min="0" name="valor_pago" id="edit_valor_pago" class="form-control" placeholder="0,00">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Forma pagamento</label>
                                            <select name="forma_pagamento" id="edit_forma_pagamento" class="form-select">
                                                <option value="">Selecione</option>
                                                <option value="dinheiro">Dinheiro</option>
                                                <option value="cartao">Cartão</option>
                                                <option value="pix">PIX</option>
                                                <option value="outro">Outro</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Status pagamento</label>
                                            <select name="status_pagamento" id="edit_status_pagamento" class="form-select">
                                                <option value="">Selecione</option>
                                                <option value="pago">Pago</option>
                                                <option value="parcelado">Parcelado</option>
                                                <option value="pendente">Pendente</option>
                                                <option value="cancelado">Cancelado</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>

                                        <div class="col-12 d-none" id="edit_parcelado_fields">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">Número de parcelas</label>
                                                    <input type="number" min="2" max="36" name="numero_parcelas" id="edit_numero_parcelas" class="form-control">
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Valor da parcela</label>
                                                    <input type="number" step="0.01" min="0" name="valor_parcela" id="edit_valor_parcela" class="form-control">
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Dia vencimento</label>
                                                    <input type="number" min="1" max="31" name="dia_vencimento" id="edit_dia_vencimento" class="form-control">
                                                    <div class="form-text">Usado para gerar o vencimento das próximas parcelas.</div>
                                                    <div class="invalid-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Cliente (cadastrado)</label>
                            <select name="cliente_id" id="edit_cliente_id" class="form-select">
                                <option value="">— sem vínculo —</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nome (rápido)</label>
                            <input type="text" name="nome_cliente" id="edit_nome_cliente" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone_cliente" id="edit_telefone_cliente" class="form-control" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" id="edit_observacoes" class="form-control" rows="3" placeholder="Anotações do atendimento"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function(){
    // Prefill duração real com duração do serviço (como dica)
    const mapDuracao = {};
    <?php foreach ($servicos as $s): ?>
        mapDuracao["<?php echo (int)$s['id']; ?>"] = <?php echo (int)$s['duracao']; ?>;
    <?php endforeach; ?>
    const mapPreco = {};
    <?php foreach ($servicos as $s): ?>
        mapPreco["<?php echo (int)$s['id']; ?>"] = parseFloat("<?php echo htmlspecialchars(number_format((float)$s['preco'], 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>") || 0;
    <?php endforeach; ?>

    // === Utilidades HH:MM replicadas do cadastro de serviços ===
    function apenasDigitos(str){ return (str || '').replace(/\D/g, ''); }
    function formatarLiveHhMm(digitos){
        const d = apenasDigitos(digitos).slice(0,4);
        if (d.length === 0) return '';
        if (d.length <= 2) return d; // H, HH
        if (d.length === 3) return d.slice(0,2) + ':' + d.slice(2,3);
        return d.slice(0,2) + ':' + d.slice(2,4);
    }
    function normalizarHhMmCompleto(digitos){
        // Para duração: limites 00:01 a 24:00
        const d = apenasDigitos(digitos);
        if (d.length === 0) return '';
        let h=0,m=0;
        if (d.length === 1){ h=parseInt(d[0],10); m=0; }
        else if (d.length === 2){ h=parseInt(d,10); m=0; }
        else if (d.length === 3){ h=parseInt(d[0],10); m=parseInt(d.slice(1,3),10); }
        else { h=parseInt(d.slice(0,2),10); m=parseInt(d.slice(2,4),10); }
        if (h>24) h=24; if (m>59) m=59; if (h===24 && m>0) m=0; if (h===0 && m===0) m=1;
        const HH=String(h).padStart(2,'0'); const MM=String(m).padStart(2,'0');
        return `${HH}:${MM}`;
    }
    function hhMmParaMinutos(hhmm){
        if (!hhmm || hhmm.indexOf(':')===-1) return 0;
        const [hStr,mStr]=hhmm.split(':');
        let h=parseInt(hStr,10)||0, m=parseInt(mStr,10)||0;
        let total=h*60+m; if (total<1) total=1; if (total>1440) total=1440; return total;
    }
    function minutosParaHhMm(total){
        let t=parseInt(total,10)||0; if (t<1) t=1; if (t>1440) t=1440;
        const h=Math.floor(t/60), m=t%60;
        return String(h).padStart(2,'0')+':'+String(m).padStart(2,'0');
    }

    // Variante para HORA INÍCIO: 00:00..23:59 (24:00 não permitido) e 00:00 é válido
    function normalizarHoraHhMm(digitos){
        const d = apenasDigitos(digitos);
        if (d.length === 0) return '';
        let h=0,m=0;
        if (d.length === 1){ h=parseInt(d[0],10); m=0; }
        else if (d.length === 2){ h=parseInt(d,10); m=0; }
        else if (d.length === 3){ h=parseInt(d[0],10); m=parseInt(d.slice(1,3),10); }
        else { h=parseInt(d.slice(0,2),10); m=parseInt(d.slice(2,4),10); }
        if (h>23) h=23; if (m>59) m=59; // 24:xx nunca
        const HH=String(h).padStart(2,'0'); const MM=String(m).padStart(2,'0');
        return `${HH}:${MM}`;
    }
    function validarHora(hhmm){
        if (!hhmm || hhmm.length!==5 || hhmm.indexOf(':')!==2) return false;
        const [hStr,mStr]=hhmm.split(':');
        const h=parseInt(hStr,10), m=parseInt(mStr,10);
        return !(isNaN(h)||isNaN(m)||h<0||h>23||m<0||m>59);
    }

    function ligarCampoHhMm(idHhMm, idMinutos){
        const inpHhMm=document.getElementById(idHhMm);
        const inpMin=document.getElementById(idMinutos);
        if (!inpHhMm || !inpMin) return;
        inpHhMm.addEventListener('input', function(){
            const dig=apenasDigitos(this.value); const live=formatarLiveHhMm(dig); this.value=live; this.setCustomValidity('');
            if (dig.length===0){ inpMin.value=''; return; }
            if (this.value.length===5){ const mins=hhMmParaMinutos(this.value); inpMin.value=String(mins); inpMin.setCustomValidity(mins<1||mins>1440?'Duração deve ser entre 00:01 e 24:00':''); }
        });
        inpHhMm.addEventListener('blur', function(){
            if (!this.value) return; const norm=normalizarHhMmCompleto(this.value); this.value=norm; const mins=hhMmParaMinutos(norm); inpMin.value=String(mins);
            if (mins<1||mins>1440){ inpMin.setCustomValidity('Duração deve ser entre 00:01 e 24:00'); } else { inpMin.setCustomValidity(''); }
        });
        inpHhMm.addEventListener('invalid', function(){ if (!this.value||this.value.trim()===''){ this.setCustomValidity('Preencha esse campo'); } });
    }

    function ligarCampoHora(idHora){
        const inp=document.getElementById(idHora); if (!inp) return;
        inp.addEventListener('input', function(){ const dig=apenasDigitos(this.value); const live=formatarLiveHhMm(dig); this.value=live; this.setCustomValidity(''); });
        inp.addEventListener('blur', function(){ if (!this.value) return; const norm=normalizarHoraHhMm(this.value); this.value=norm; if (!validarHora(norm)){ this.setCustomValidity('Hora deve estar entre 00:00 e 23:59'); } else { this.setCustomValidity(''); }});
        inp.addEventListener('invalid', function(){ if (!this.value||this.value.trim()===''){ this.setCustomValidity('Preencha esse campo'); } });
    }

    // Mostrar/ocultar sessão de pagamento conforme status
    function togglePagamento(selectId, sectionId){
        const sel = document.getElementById(selectId);
        const sec = document.getElementById(sectionId);
        if (!sel || !sec) return;
        const show = (sel.value === 'concluido');
        sec.classList.toggle('d-none', !show);
    }

    function wirePagamentoToggle(selectId, sectionId){
        const sel = document.getElementById(selectId);
        if (!sel) return;
        sel.addEventListener('change', ()=> togglePagamento(selectId, sectionId));
        // estado inicial
        togglePagamento(selectId, sectionId);
    }

    // Mostrar/ocultar campos de parcelado baseado no select de status_pagamento
    function toggleParcelado(selectPagId, fieldsId){
        const sel = document.getElementById(selectPagId);
        const box = document.getElementById(fieldsId);
        if (!sel || !box) return;
        const show = (sel.value === 'parcelado');
        box.classList.toggle('d-none', !show);
    }
    function wireParceladoToggle(selectPagId, fieldsId){
        const sel = document.getElementById(selectPagId);
        if (!sel) return;
        sel.addEventListener('change', ()=> toggleParcelado(selectPagId, fieldsId));
        toggleParcelado(selectPagId, fieldsId);
    }

    // Ligações
    ligarCampoHhMm('novo_duracao_hhmm','novo_duracao_real');
    ligarCampoHhMm('edit_duracao_hhmm','edit_duracao_real');
    ligarCampoHora('novo_hora_inicio');
    ligarCampoHora('edit_hora_inicio');
    wirePagamentoToggle('novo_status','novo_pagamento_section');
    wirePagamentoToggle('edit_status','edit_pagamento_section');
    wireParceladoToggle('novo_status_pagamento','novo_parcelado_fields');
    wireParceladoToggle('edit_status_pagamento','edit_parcelado_fields');

    // Atualizar Valor Serviço ao alternar status para exibir pagamento
    function setValorServicoFromSelect(selectId, valorViewId){
        const sel = document.getElementById(selectId);
        const out = document.getElementById(valorViewId);
        if (!sel || !out) return;
        const preco = mapPreco[String(sel.value)] || 0;
        out.value = formatCurrencyBRL(preco);
    }
    const novoStatusSel = document.getElementById('novo_status');
    if (novoStatusSel){
        novoStatusSel.addEventListener('change', ()=> setValorServicoFromSelect('novo_servico_id','novo_valor_servico_view'));
        // estado inicial
        setValorServicoFromSelect('novo_servico_id','novo_valor_servico_view');
    }
    const editStatusSel = document.getElementById('edit_status');
    if (editStatusSel){
        editStatusSel.addEventListener('change', ()=> setValorServicoFromSelect('edit_servico_id','edit_valor_servico_view'));
    }

    // Bloquear/Desbloquear campos não pagamento quando status = concluído
    function applyLockForConcluido(form, lock){
        if (!form) return;
        const allowed = new Set(['action','id','status','valor_pago','forma_pagamento','status_pagamento','numero_parcelas','valor_parcela','dia_vencimento']);
        const els = form.querySelectorAll('input, select, textarea');
        els.forEach(el=>{
            const name = el.name || '';
            if (!lock){
                if (el.dataset && el.dataset.locked === '1'){
                    // Preserve always-readonly fields like duracao_real
                    if (el.dataset.alwaysreadonly === '1') {
                        el.readOnly = true;
                    } else {
                        el.readOnly = false;
                    }
                    el.disabled = false;
                    el.classList.remove('bg-light');
                    delete el.dataset.locked;
                }
                return;
            }
            if (name === '' || allowed.has(name)) return;
            if (el.type === 'hidden') return;
            el.dataset.locked = '1';
            if (el.tagName === 'SELECT'){
                el.disabled = true;
            } else {
                el.readOnly = true;
            }
            // Evita cobrir o estilo global de readonly nos campos marcados como sempre-readonly
            if (el.dataset.alwaysreadonly !== '1') {
                el.classList.add('bg-light');
            }
        });
    }
    function unlockDisabledForSubmit(form){
        if (!form) return;
        form.querySelectorAll('[data-locked="1"]').forEach(el=>{
            if (el.disabled) el.disabled = false;
        });
    }

    // Ao trocar serviço, sugerir duração padrão (em hh:mm) e refletir minutos
    function formatCurrencyBRL(v){
        try { return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v||0); } catch(e){ return 'R$ '+(v||0).toFixed ? (v||0).toFixed(2).replace('.',',') : '0,00'; }
    }

    function conectarServicoParaDuracao(selectId, hhmmId, minId, valorServicoViewId){
        const sel=document.getElementById(selectId); const hh=document.getElementById(hhmmId); const mi=document.getElementById(minId);
        const vsView = valorServicoViewId ? document.getElementById(valorServicoViewId) : null;
        if (!sel || !hh || !mi) return;
        const setFromServico=()=>{
            // Novo comportamento: não preencher HH:MM automaticamente.
            // Se usuário já digitou HH:MM, apenas recalcula minutos.
            if (hh.value && hh.value.length === 5) {
                const mins=hhMmParaMinutos(hh.value);
                mi.value=String(mins);
            } else {
                // manter minutos em branco quando HH:MM está vazio
                mi.value='';
            }
            // Atualizar valor do serviço
            if (vsView) {
                const idSel = sel.value;
                const preco = mapPreco[String(idSel)] || 0;
                vsView.value = formatCurrencyBRL(preco);
            }
        };
        sel.addEventListener('change', setFromServico);
        // inicializa respeitando o estado atual (vazio não preenche nada)
        setFromServico();
    }
    conectarServicoParaDuracao('novo_servico_id','novo_duracao_hhmm','novo_duracao_real','novo_valor_servico_view');
    conectarServicoParaDuracao('edit_servico_id','edit_duracao_hhmm','edit_duracao_real','edit_valor_servico_view');

    // Preencher modal de edição a partir do botão
    const modalEditar = document.getElementById('modalEditarAgendamento');
    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function(e){
            const btn = e.relatedTarget;
            if (!btn) return;
            const json = btn.getAttribute('data-ag');
            if (!json) return;
            let ag;
            try { ag = JSON.parse(json); } catch(err) { return; }
            document.getElementById('edit_id').value = ag.id || '';
            document.getElementById('edit_data').value = ag.data_agendamento || '';
            // hora_inicio vem com HH:MM:SS
            const h = (ag.hora_inicio || '').slice(0,5);
            // normaliza e aplica no campo texto
            const hNorm = normalizarHoraHhMm(h);
            document.getElementById('edit_hora_inicio').value = hNorm;
            document.getElementById('edit_profissional_id').value = ag.profissional_id || '';
            document.getElementById('edit_servico_id').value = ag.servico_id || '';
            // Duração: preencher hh:mm a partir de duracao_real ou duração padrão do serviço
            const durReal = (ag.duracao_real != null) ? parseInt(ag.duracao_real,10) : (mapDuracao[String(ag.servico_id)]||0);
            const hhmm = minutosParaHhMm(durReal);
            const editHH = document.getElementById('edit_duracao_hhmm');
            const editMin = document.getElementById('edit_duracao_real');
            if (editHH) editHH.value = hhmm;
            if (editMin) editMin.value = String(durReal);
            document.getElementById('edit_status').value = ag.status || 'agendado';
            // Guardar status original e quantidade de parcelas para confirmação
            const hOrig = document.getElementById('edit_original_status');
            if (hOrig) hOrig.value = ag.status || '';
            const hParc = document.getElementById('edit_tem_parcelas');
            if (hParc) hParc.value = String(parseInt(ag.parcelas_qtd || 0, 10));
            const hConf = document.getElementById('edit_confirm_reset_pagamento');
            if (hConf) hConf.value = '0';
            const formEd = document.getElementById('formEditarAgendamento');
            if (formEd) delete formEd.dataset.confirmedReset;
            // Ajustar sessão de pagamento conforme status carregado
            togglePagamento('edit_status','edit_pagamento_section');
            // Trancar campos não pagamento se concluído
            applyLockForConcluido(document.getElementById('formEditarAgendamento'), (ag.status||'agendado') === 'concluido');
            // Pagamento: preencher campos quando disponível
            const vsView = document.getElementById('edit_valor_servico_view');
            if (vsView) {
                let vs = parseFloat(ag.valor_servico ?? 0) || 0;
                // fallback: caso não haja valor_servico salvo, usa tabela de serviços
                if (!vs && ag.servico_id) {
                    vs = mapPreco[String(ag.servico_id)] || 0;
                }
                vsView.value = formatCurrencyBRL(vs);
            }
            const inpVP = document.getElementById('edit_valor_pago');
            if (inpVP) { inpVP.value = (ag.valor_pago != null ? parseFloat(ag.valor_pago) : '').toString(); }
            const selForma = document.getElementById('edit_forma_pagamento');
            if (selForma) { selForma.value = ag.forma_pagamento || ''; }
            const selSP = document.getElementById('edit_status_pagamento');
            if (selSP) { selSP.value = ag.status_pagamento || ''; }
            // Exibir campos de parcelado se aplicável e preencher quantidade
            toggleParcelado('edit_status_pagamento','edit_parcelado_fields');
            const np = document.getElementById('edit_numero_parcelas');
            if (np) {
                const qtd1 = (ag.quantidade_parcelas != null ? parseInt(ag.quantidade_parcelas,10) : 0) || 0;
                const qtd2 = (ag.parcelas_qtd != null ? parseInt(ag.parcelas_qtd,10) : 0) || 0;
                np.value = String(qtd1 > 0 ? qtd1 : (qtd2 > 0 ? qtd2 : ''));
            }
            const vparc = document.getElementById('edit_valor_parcela');
            if (vparc) {
                if (ag.valor_parcela_medio != null) {
                    const vv = parseFloat(ag.valor_parcela_medio);
                    vparc.value = isNaN(vv) ? '' : vv.toString();
                } else { vparc.value = ''; }
            }
            const diaV = document.getElementById('edit_dia_vencimento');
            if (diaV) {
                if (ag.dia_vencimento != null) {
                    const dv = parseInt(ag.dia_vencimento,10);
                    diaV.value = isNaN(dv) ? '' : String(dv);
                } else { diaV.value=''; }
            }
            document.getElementById('edit_cliente_id').value = ag.cliente_id || '';
            document.getElementById('edit_nome_cliente').value = ag.nome_cliente || '';
            document.getElementById('edit_telefone_cliente').value = ag.telefone_cliente || '';
            document.getElementById('edit_observacoes').value = ag.observacoes || '';
        });

        modalEditar.addEventListener('hidden.bs.modal', function(){
            document.getElementById('formEditarAgendamento').reset();
        });
    }

    // Validação antes de enviar (novo e editar)
    function setFieldError(el, msg){
        if (!el) return; el.classList.add('is-invalid');
        let fb = el.nextElementSibling;
        if (!fb || !fb.classList || !fb.classList.contains('invalid-feedback')){
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            el.insertAdjacentElement('afterend', fb);
        }
        fb.textContent = msg || '';
    }
    function clearFieldError(el){
        if (!el) return;
        el.classList.remove('is-invalid');
        const fb = el.nextElementSibling;
        if (fb && fb.classList && fb.classList.contains('invalid-feedback')){ fb.textContent=''; }
    }
    function validarPagamentoAntesSubmit(form){
        if (!form) return true;
        const statusAg = form.querySelector('select[name="status"]');
        if (!statusAg) return true;
        if (statusAg.value !== 'concluido') return true;
        const sp = form.querySelector('select[name="status_pagamento"]');
        const fp = form.querySelector('select[name="forma_pagamento"]');
        const vp = form.querySelector('input[name="valor_pago"]');
        const np = form.querySelector('input[name="numero_parcelas"]');
        const vParc = form.querySelector('input[name="valor_parcela"]');
        const dia = form.querySelector('input[name="dia_vencimento"]');

        // limpar erros anteriores
        [sp, fp, vp, np, vParc, dia].forEach(clearFieldError);

        if (!sp || !sp.value || sp.value === 'pendente' || sp.value === 'cancelado'){
            setFieldError(sp, 'Selecione PAGO ou PARCELADO para concluir.');
            sp && sp.focus();
            return false;
        }
        if (sp.value === 'pago'){
            const v = parseFloat(vp && vp.value ? vp.value.replace(',','.') : '0') || 0;
            if (v <= 0){ setFieldError(vp,'Informe um valor maior que 0.'); vp && vp.focus(); return false; }
            if (!fp || !fp.value){ setFieldError(fp,'Selecione a forma de pagamento.'); fp && fp.focus(); return false; }
        }
        if (sp.value === 'parcelado'){
            const n = np ? parseInt(np.value,10) : 0;
            const vv = vParc ? parseFloat(vParc.value.replace(',','.')) : 0;
            const d = dia ? parseInt(dia.value,10) : 0;
            if (!n || n < 2){ setFieldError(np,'Informe ao menos 2 parcelas.'); np && np.focus(); return false; }
            if (!vv || vv <= 0){ setFieldError(vParc,'Informe o valor por parcela.'); vParc && vParc.focus(); return false; }
            if (!d || d < 1 || d > 31){ setFieldError(dia,'Dia de vencimento entre 1 e 31.'); dia && dia.focus(); return false; }
        }
        return true;
    }
    const formNovo = document.querySelector('#modalNovoAgendamento form');
    if (formNovo){
        formNovo.addEventListener('submit', function(e){
            if (!validarPagamentoAntesSubmit(formNovo)){
                e.preventDefault(); e.stopPropagation(); return;
            }
            unlockDisabledForSubmit(formNovo);
        });
        const novoStatus = document.getElementById('novo_status');
        if (novoStatus){
            novoStatus.addEventListener('change', ()=>{
                applyLockForConcluido(formNovo, novoStatus.value === 'concluido');
            });
            applyLockForConcluido(formNovo, novoStatus.value === 'concluido');
        }
    }
    const formEdit = document.getElementById('formEditarAgendamento');
    if (formEdit){
        formEdit.addEventListener('submit', function(e){
            // Confirmação: se status original era concluído e mudou para outro (avisar que pagamento será resetado e parcelas serão deletadas se existirem)
            const orig = document.getElementById('edit_original_status')?.value || '';
            const selStatus = document.getElementById('edit_status');
            const statusAtual = selStatus ? selStatus.value : '';
            if (orig === 'concluido' && statusAtual !== 'concluido' && formEdit.dataset.confirmedReset !== '1'){
                e.preventDefault(); e.stopPropagation();
                const modalEl = document.getElementById('confirmResetPagamentoModal');
                if (modalEl){
                    const yesBtn = modalEl.querySelector('[data-action="confirm-reset"]');
                    const noBtn = modalEl.querySelector('[data-bs-dismiss]');
                    // Limpa handlers anteriores
                    yesBtn.replaceWith(yesBtn.cloneNode(true));
                    const yesNew = modalEl.querySelector('[data-action="confirm-reset"]');
                    yesNew.addEventListener('click', function(){
                        formEdit.dataset.confirmedReset = '1';
                        const conf = document.getElementById('edit_confirm_reset_pagamento');
                        if (conf) conf.value = '1';
                        const bsModal = bootstrap.Modal.getInstance(modalEl);
                        if (bsModal) bsModal.hide();
                        setTimeout(()=> formEdit.requestSubmit(), 50);
                    });
                    const bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                }
                return;
            }
            if (!validarPagamentoAntesSubmit(formEdit)){
                e.preventDefault(); e.stopPropagation(); return;
            }
            unlockDisabledForSubmit(formEdit);
        });
        const editStatus = document.getElementById('edit_status');
        if (editStatus){
            editStatus.addEventListener('change', ()=>{
                applyLockForConcluido(formEdit, editStatus.value === 'concluido');
            });
            applyLockForConcluido(formEdit, editStatus.value === 'concluido');
        }
    }

    // Remover estado de erro ao editar os campos
    function attachClearInvalid(selector){
        document.querySelectorAll(selector).forEach(el=>{
            const evt = (el.tagName === 'SELECT') ? 'change' : 'input';
            el.addEventListener(evt, ()=>{
                clearFieldError(el);
            });
        });
    }
    attachClearInvalid('input[name="valor_pago"], select[name="forma_pagamento"], select[name="status_pagamento"], input[name="numero_parcelas"], input[name="valor_parcela"], input[name="dia_vencimento"]');
})();
</script>

<!-- Modal de confirmação para reset de pagamento/parcelas -->
<div class="modal fade" id="confirmResetPagamentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar alteração de status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Ao mudar o Status de <strong>Concluído</strong> para outro, os dados de pagamento serão resetados para o padrão e todos os registros de parcelamento vinculados a este agendamento serão <strong>deletados</strong>.</p>
                <p class="mb-0">Deseja continuar?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-action="confirm-reset">Sim, continuar</button>
            </div>
        </div>
    </div>
    </div>
