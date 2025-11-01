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
if ($r = $conn->query("SELECT id, nome, duracao FROM salao_servicos WHERE ativo = 1 ORDER BY nome")) {
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
                    $stmt = $conn->prepare('INSERT INTO salao_agendamentos (cliente_id, nome_cliente, telefone_cliente, profissional_id, servico_id, data_agendamento, hora_inicio, hora_fim, duracao_prevista, duracao_real, observacoes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $types = 'issiisssiisss'; // i s s i i s s s i i s s
                        bindParamsSafe($stmt, $types,
                            $cliente_id,
                            $nome_cliente,
                            $telefone_cliente,
                            $profissional_id,
                            $servico_id,
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
                        $stmt = $conn->prepare('UPDATE salao_agendamentos SET cliente_id = ?, nome_cliente = ?, telefone_cliente = ?, profissional_id = ?, servico_id = ?, data_agendamento = ?, hora_inicio = ?, hora_fim = ?, duracao_prevista = ?, duracao_real = ?, observacoes = ?, status = ? WHERE id = ?');
                        if ($stmt) {
                            $types = 'issiisssiisssi'; // + id no final
                            bindParamsSafe($stmt, $types,
                                $cliente_id,
                                $nome_cliente,
                                $telefone_cliente,
                                $profissional_id,
                                $servico_id,
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
$sql = "SELECT a.*, c.nome AS cliente_nome_cad, p.nome AS profissional_nome, s.nome AS servico_nome
        FROM salao_agendamentos a
        LEFT JOIN salao_clientes c ON c.id = a.cliente_id
        INNER JOIN salao_profissionais p ON p.id = a.profissional_id
        INNER JOIN salao_servicos s ON s.id = a.servico_id
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
                                    <option value="<?php echo (int)$s['id']; ?>" data-duracao="<?php echo (int)$s['duracao']; ?>"><?php echo htmlspecialchars($s['nome'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$s['duracao']; ?> min)</option>
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
                            <input type="number" name="duracao_real" id="novo_duracao_real" class="form-control" min="1" readonly>
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
                                    <div class="text-muted small">Sessão de pagamento será exibida quando o status for "Concluído". (Campos serão adicionados posteriormente.)</div>
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
                                    <option value="<?php echo (int)$s['id']; ?>" data-duracao="<?php echo (int)$s['duracao']; ?>"><?php echo htmlspecialchars($s['nome'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int)$s['duracao']; ?> min)</option>
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
                            <input type="number" name="duracao_real" id="edit_duracao_real" class="form-control" min="1" readonly>
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
                                    <div class="text-muted small">Sessão de pagamento será exibida quando o status for "Concluído". (Campos serão adicionados posteriormente.)</div>
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

    // Ligações
    ligarCampoHhMm('novo_duracao_hhmm','novo_duracao_real');
    ligarCampoHhMm('edit_duracao_hhmm','edit_duracao_real');
    ligarCampoHora('novo_hora_inicio');
    ligarCampoHora('edit_hora_inicio');
    wirePagamentoToggle('novo_status','novo_pagamento_section');
    wirePagamentoToggle('edit_status','edit_pagamento_section');

    // Ao trocar serviço, sugerir duração padrão (em hh:mm) e refletir minutos
    function conectarServicoParaDuracao(selectId, hhmmId, minId){
        const sel=document.getElementById(selectId); const hh=document.getElementById(hhmmId); const mi=document.getElementById(minId);
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
        };
        sel.addEventListener('change', setFromServico);
        // inicializa respeitando o estado atual (vazio não preenche nada)
        setFromServico();
    }
    conectarServicoParaDuracao('novo_servico_id','novo_duracao_hhmm','novo_duracao_real');
    conectarServicoParaDuracao('edit_servico_id','edit_duracao_hhmm','edit_duracao_real');

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
            // Ajustar sessão de pagamento conforme status carregado
            togglePagamento('edit_status','edit_pagamento_section');
            document.getElementById('edit_cliente_id').value = ag.cliente_id || '';
            document.getElementById('edit_nome_cliente').value = ag.nome_cliente || '';
            document.getElementById('edit_telefone_cliente').value = ag.telefone_cliente || '';
            document.getElementById('edit_observacoes').value = ag.observacoes || '';
        });

        modalEditar.addEventListener('hidden.bs.modal', function(){
            document.getElementById('formEditarAgendamento').reset();
        });
    }
})();
</script>
