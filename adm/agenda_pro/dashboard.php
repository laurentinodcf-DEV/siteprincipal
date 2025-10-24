<?php
// Dashboard Principal - Painel de Controle da Agenda Profissional (dinâmico)
if (!isset($conn)) {
    // Tenta obter conexão global do container agenda_profissional
    $connFile = __DIR__ . '/../../conexao.php';
    if (file_exists($connFile)) { require_once $connFile; }
}

// Garantir timezone local consistente
$tz = ini_get('date.timezone');
if (!$tz || !@date_default_timezone_set($tz)) {
    date_default_timezone_set('America/Sao_Paulo');
}

// Helpers
function brl($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function dt($format, $ts=null){ return date($format, $ts ?? time()); }

$hoje = date('Y-m-d');
$agora = date('H:i:s');
$ontem = date('Y-m-d', strtotime('-1 day'));

$agHoje = 0; $agOntem = 0; $dif = 0;
if (isset($conn) && $conn instanceof mysqli) {
    // Agendamentos hoje
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento = ?')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $agHoje = (int)($row['t'] ?? 0); }
        $st->close();
    }
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento = ?')) {
        $st->bind_param('s', $ontem);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $agOntem = (int)($row['t'] ?? 0); }
        $st->close();
    }
    $dif = $agHoje - $agOntem;

    // Faturamento: previsto (status != cancelado) e realizado (concluído)
    $fatPrev = 0.0; $fatReal = 0.0;
    if ($st = $conn->prepare('SELECT SUM(s.preco) AS total FROM salao_agendamentos a INNER JOIN salao_servicos s ON s.id = a.servico_id WHERE a.data_agendamento = ? AND a.status <> "cancelado"')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $fatPrev = (float)($row['total'] ?? 0); }
        $st->close();
    }
    if ($st = $conn->prepare('SELECT SUM(s.preco) AS total FROM salao_agendamentos a INNER JOIN salao_servicos s ON s.id = a.servico_id WHERE a.data_agendamento = ? AND a.status = "concluido"')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $fatReal = (float)($row['total'] ?? 0); }
        $st->close();
    }

    // Clientes novos hoje (primeira vez na agenda)
    $clientesNovosHoje = 0;
    // Vinculados (cliente_id)
    if ($st = $conn->prepare('SELECT COUNT(DISTINCT a.cliente_id) AS q FROM salao_agendamentos a WHERE a.data_agendamento = ? AND a.cliente_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM salao_agendamentos ap WHERE ap.cliente_id = a.cliente_id AND ap.data_agendamento < ?)')) {
        $st->bind_param('ss', $hoje, $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $clientesNovosHoje += (int)($row['q'] ?? 0); }
        $st->close();
    }
    // Rápidos (sem cliente_id): considera par (nome, telefone)
    if ($st = $conn->prepare('SELECT COUNT(DISTINCT CONCAT(COALESCE(a.nome_cliente, ""), "|", COALESCE(a.telefone_cliente, ""))) AS q FROM salao_agendamentos a WHERE a.data_agendamento = ? AND a.cliente_id IS NULL AND NOT EXISTS (SELECT 1 FROM salao_agendamentos ap WHERE ap.cliente_id IS NULL AND ap.data_agendamento < ? AND COALESCE(ap.nome_cliente, "") = COALESCE(a.nome_cliente, "") AND COALESCE(ap.telefone_cliente, "") = COALESCE(a.telefone_cliente, ""))')) {
        $st->bind_param('ss', $hoje, $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $clientesNovosHoje += (int)($row['q'] ?? 0); }
        $st->close();
    }

    // Ocupação: minutos agendados hoje / (profissionais ativos * 8h)
    $minAgendadosHoje = 0; $profAtivos = 0; $taxaOcup = 0;
    if ($st = $conn->prepare('SELECT SUM(COALESCE(a.duracao_real, a.duracao_prevista)) AS mins FROM salao_agendamentos a WHERE a.data_agendamento = ? AND a.status <> "cancelado"')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row = $r->fetch_assoc(); $minAgendadosHoje = (int)($row['mins'] ?? 0); }
        $st->close();
    }
    if ($r = $conn->query('SELECT COUNT(*) AS c FROM salao_profissionais WHERE ativo = 1')) {
        $row = $r->fetch_assoc(); $profAtivos = (int)($row['c'] ?? 0); $r->free();
    }
    $capacidadeMinDia = $profAtivos * 8 * 60; // hipótese 8h por profissional
    if ($capacidadeMinDia > 0) { $taxaOcup = max(0, min(100, round(($minAgendadosHoje / $capacidadeMinDia) * 100))); }

    // Agenda de hoje (lista)
    $listaHoje = [];
    if ($st = $conn->prepare('SELECT a.*, p.nome AS profissional_nome, s.nome AS servico_nome, s.preco AS servico_preco, c.nome AS cliente_nome_cad FROM salao_agendamentos a INNER JOIN salao_profissionais p ON p.id = a.profissional_id INNER JOIN salao_servicos s ON s.id = a.servico_id LEFT JOIN salao_clientes c ON c.id = a.cliente_id WHERE a.data_agendamento = ? ORDER BY a.hora_inicio ASC')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); while ($row = $r->fetch_assoc()) { $listaHoje[] = $row; } }
        $st->close();
    }

    // Semana por profissional (top 3 por agendamentos)
    $iniSemana = (new DateTime('today')); $wkDow = (int)$iniSemana->format('N'); $iniSemana->modify('-' . ($wkDow-1) . ' days');
    $fimSemana = (clone $iniSemana); $fimSemana->modify('+6 days');
    $semIni = $iniSemana->format('Y-m-d'); $semFim = $fimSemana->format('Y-m-d');
    $profSemanal = [];
    $sql = 'SELECT p.id, p.nome, COUNT(a.id) AS total_ag, COALESCE(SUM(COALESCE(a.duracao_real, a.duracao_prevista)),0) AS min_total, COALESCE(SUM(s.preco),0) AS faturamento FROM salao_profissionais p LEFT JOIN salao_agendamentos a ON a.profissional_id = p.id AND a.data_agendamento BETWEEN ? AND ? AND a.status <> "cancelado" LEFT JOIN salao_servicos s ON s.id = a.servico_id WHERE p.ativo = 1 GROUP BY p.id, p.nome ORDER BY total_ag DESC, p.nome ASC LIMIT 3';
    if ($st = $conn->prepare($sql)) {
        $st->bind_param('ss', $semIni, $semFim);
        if ($st->execute()) { $r = $st->get_result(); while ($row = $r->fetch_assoc()) { $profSemanal[] = $row; } }
        $st->close();
    }

    // Alertas do dia (hoje)
    $qProximos1h = 0; $qAtrasados = 0; $qCanceladosHoje = 0; $qSemClienteHoje = 0;
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento = ? AND status = "agendado" AND hora_inicio BETWEEN ? AND ADDTIME(?, "01:00:00")')) {
        $st->bind_param('sss', $hoje, $agora, $agora);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qProximos1h=(int)($row['t']??0);} $st->close();
    }
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento = ? AND status = "agendado" AND hora_inicio < ?')) {
        $st->bind_param('ss', $hoje, $agora);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qAtrasados=(int)($row['t']??0);} $st->close();
    }
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento = ? AND status = "cancelado"')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qCanceladosHoje=(int)($row['t']??0);} $st->close();
    }
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento = ? AND (cliente_id IS NULL)')) {
        $st->bind_param('s', $hoje);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qSemClienteHoje=(int)($row['t']??0);} $st->close();
    }

    // Métricas do mês
    $iniMes = date('Y-m-01');
    $fimMes = date('Y-m-t');
    $fatMes = 0.0; $qtdAgMes = 0; $avgSatisf = null;
    if ($st = $conn->prepare('SELECT SUM(s.preco) AS total FROM salao_agendamentos a INNER JOIN salao_servicos s ON s.id = a.servico_id WHERE a.data_agendamento BETWEEN ? AND ? AND a.status = "concluido"')) {
        $st->bind_param('ss', $iniMes, $fimMes);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $fatMes=(float)($row['total']??0);} $st->close();
    }
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento BETWEEN ? AND ? AND status <> "cancelado"')) {
        $st->bind_param('ss', $iniMes, $fimMes);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qtdAgMes=(int)($row['t']??0);} $st->close();
    }
    // Satisfação do mês (média de estrelas de salao_depoimentos)
    $temDataCriacao = false;
    if ($res = $conn->query("SHOW COLUMNS FROM salao_depoimentos LIKE 'data_criacao'")) {
        $temDataCriacao = $res->num_rows > 0; $res->free();
    }
    if ($temDataCriacao) {
        if ($st = $conn->prepare('SELECT AVG(estrelas) AS m FROM salao_depoimentos WHERE ativo = 1 AND data_criacao BETWEEN ? AND ?')) {
            $st->bind_param('ss', $iniMes, $fimMes);
            if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $avgSatisf = $row && $row['m'] !== null ? (float)$row['m'] : null; }
            $st->close();
        }
    } else {
        if ($r = $conn->query('SELECT AVG(estrelas) AS m FROM salao_depoimentos WHERE ativo = 1')) {
            $row = $r->fetch_assoc(); $avgSatisf = $row && $row['m'] !== null ? (float)$row['m'] : null; $r->free();
        }
    }

    // Top serviços do mês (por agendamentos)
    $topServicos = [];
    if ($st = $conn->prepare('SELECT s.nome, COUNT(*) AS q FROM salao_agendamentos a INNER JOIN salao_servicos s ON s.id = a.servico_id WHERE a.data_agendamento BETWEEN ? AND ? AND a.status <> "cancelado" GROUP BY s.id, s.nome ORDER BY q DESC, s.nome ASC LIMIT 5')) {
        $st->bind_param('ss', $iniMes, $fimMes);
        if ($st->execute()) { $r = $st->get_result(); while ($row = $r->fetch_assoc()) { $topServicos[] = $row; } }
        $st->close();
    }
}
?>

<!-- Resumo Executivo -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Agendamentos Hoje</h6>
                        <h2 class="mb-0"><?php echo (int)($agHoje ?? 0); ?></h2>
                        <small class="opacity-75"><?php echo ($dif>=0?'+':'') . (int)$dif; ?> desde ontem</small>
                    </div>
                    <div class="align-self-center">
                        <!-- Ícone sem texto em inglês dentro -->
                        <i class="bi bi-calendar-check fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-bg-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Faturamento Hoje</h6>
                        <h2 class="mb-0"><?php echo brl($fatReal ?? 0); ?></h2>
                        <small class="opacity-75">Previsto: <?php echo brl($fatPrev ?? 0); ?></small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Clientes Novos</h6>
                        <h2 class="mb-0"><?php echo (int)($clientesNovosHoje ?? 0); ?></h2>
                        <small class="opacity-75">Hoje</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-plus fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-bg-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Taxa Ocupação</h6>
                        <h2 class="mb-0"><?php echo isset($taxaOcup) ? (int)$taxaOcup : 0; ?>%</h2>
                        <small class="opacity-75">Horários preenchidos</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-speedometer2 fs-1 opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agenda do Dia -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check text-primary me-2"></i>
                    Agenda de Hoje - <?php echo date('d/m/Y'); ?>
                </h5>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary" disabled>Hoje</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="timeline-container" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($listaHoje)): ?>
                        <div class="p-3 text-muted">Sem agendamentos para hoje.</div>
                    <?php else: ?>
                        <?php foreach ($listaHoje as $ag): ?>
                            <?php
                                $hora = htmlspecialchars(substr($ag['hora_inicio'],0,5), ENT_QUOTES, 'UTF-8');
                                $cliente = $ag['nome_cliente'] ?: ($ag['cliente_nome_cad'] ?? '—');
                                $serv = $ag['servico_nome'] ?? '';
                                $prof = $ag['profissional_nome'] ?? '';
                                $preco = isset($ag['servico_preco']) ? brl($ag['servico_preco']) : '—';
                                $st = $ag['status'] ?? 'agendado';
                                $map = ['agendado'=>'warning','em_andamento'=>'info','concluido'=>'success','cancelado'=>'secondary'];
                                $badge = $map[$st] ?? 'secondary';
                            ?>
                            <div class="timeline-item d-flex align-items-center p-3 border-bottom">
                                <div class="time-label text-center me-3" style="min-width: 60px;">
                                    <strong class="text-primary"><?php echo $hora; ?></strong>
                                </div>
                                <div class="appointment-info flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($serv . ' • ' . $prof, ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <div class="mt-1">
                                                <small class="text-success fw-bold"><?php echo $preco; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a class="btn btn-success" href="?modulo=agendamento_inteligente">
                        <i class="bi bi-plus-lg me-1"></i> Novo Agendamento
                    </a>
                    <a class="btn btn-outline-primary" href="?modulo=calendario">
                        <i class="bi bi-calendar3 me-1"></i> Ver Calendário
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Alertas e Notificações -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-bell text-warning me-2"></i>
                    Alertas e Notificações
                </h6>
            </div>
            <div class="card-body">
                <?php if (($qProximos1h ?? 0) > 0): ?>
                    <div class="alert alert-info alert-sm d-flex align-items-center mb-2">
                        <i class="bi bi-alarm me-2"></i>
                        <small><?php echo (int)$qProximos1h; ?> agendamento(s) nas próximas 1h</small>
                    </div>
                <?php endif; ?>
                <?php if (($qAtrasados ?? 0) > 0): ?>
                    <div class="alert alert-warning alert-sm d-flex align-items-center mb-2">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <small><?php echo (int)$qAtrasados; ?> agendamento(s) atrasado(s)</small>
                    </div>
                <?php endif; ?>
                <?php if (($qCanceladosHoje ?? 0) > 0): ?>
                    <div class="alert alert-danger alert-sm d-flex align-items-center mb-2">
                        <i class="bi bi-x-circle me-2"></i>
                        <small><?php echo (int)$qCanceladosHoje; ?> cancelamento(s) hoje</small>
                    </div>
                <?php endif; ?>
                <?php if (($qSemClienteHoje ?? 0) > 0): ?>
                    <div class="alert alert-secondary alert-sm d-flex align-items-center mb-0">
                        <i class="bi bi-person-dash me-2"></i>
                        <small><?php echo (int)$qSemClienteHoje; ?> agendamento(s) sem cliente vinculado</small>
                    </div>
                <?php endif; ?>
                <?php if (($qProximos1h ?? 0) === 0 && ($qAtrasados ?? 0) === 0 && ($qCanceladosHoje ?? 0) === 0 && ($qSemClienteHoje ?? 0) === 0): ?>
                    <div class="alert alert-light alert-sm mb-0 d-flex align-items-center">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Sem alertas no momento.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Próximas Ações -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-lightning text-primary me-2"></i>
                    Ações Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="?modulo=agendamento_inteligente">
                        <i class="bi bi-plus-circle me-2"></i> Novo Agendamento
                    </a>
                    <a class="btn btn-outline-success btn-sm" href="?modulo=agendamento_inteligente">
                        <i class="bi bi-calendar-check me-2"></i> Agendamentos de Hoje <span class="badge bg-success ms-1"><?php echo (int)($agHoje ?? 0); ?></span>
                    </a>
                    <a class="btn btn-outline-info btn-sm" href="?modulo=agendamento_inteligente&data=<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        <i class="bi bi-bell me-2"></i> Lembretes de Amanhã <span class="badge bg-info ms-1"></span>
                    </a>
                    <a class="btn btn-outline-warning btn-sm" href="?modulo=agendamento_inteligente">
                        <i class="bi bi-alarm me-2"></i> Próximos 60 minutos <span class="badge bg-warning text-dark ms-1"><?php echo (int)($qProximos1h ?? 0); ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Visão Semanal por Profissional -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-people text-success me-2"></i>
                    Visão Semanal por Profissional
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($profSemanal)): ?>
                        <div class="col-12">
                            <div class="alert alert-light mb-0">Sem dados de semana para mostrar.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($profSemanal as $i => $ps): ?>
                            <?php
                                $cor = ['primary','success','info'][$i % 3];
                                $ag = (int)($ps['total_ag'] ?? 0);
                                $fat = brl($ps['faturamento'] ?? 0);
                                $min = (int)($ps['min_total'] ?? 0);
                                // capacidade semanal estimada (8h x 7 dias)
                                $capSem = 8*60*7; $pct = $capSem>0 ? max(0, min(100, round(($min/$capSem)*100))) : 0;
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="card border-<?php echo $cor; ?>">
                                    <div class="card-header bg-<?php echo $cor; ?> text-white">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($ps['nome'] ?? 'Profissional', ENT_QUOTES, 'UTF-8'); ?></h6>
                                        <small>Profissional</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <strong class="text-<?php echo $cor; ?>"><?php echo $ag; ?></strong>
                                                <div class="small text-muted">Agendamentos</div>
                                            </div>
                                            <div class="col-6">
                                                <strong class="text-success"><?php echo $fat; ?></strong>
                                                <div class="small text-muted">Faturamento</div>
                                            </div>
                                        </div>
                                        <div class="progress mt-2">
                                            <div class="progress-bar bg-<?php echo $cor; ?>" style="width: <?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
                                        </div>
                                        <small class="text-muted">Ocupação da semana</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Indicadores de Performance -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-graph-up text-success me-2"></i>
                    Performance do Mês (<?php echo date('m/Y'); ?>)
                </h6>
            </div>
            <div class="card-body">
                <!-- Linha: Faturamento -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Faturamento</span>
                        <span class="fw-semibold text-success"><?php echo brl($fatMes ?? 0); ?></span>
                    </div>
                </div>

                <!-- Linha: Agendamentos -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Agendamentos</span>
                        <span class="fw-semibold text-primary"><?php echo (int)($qtdAgMes ?? 0); ?></span>
                    </div>
                </div>

                <!-- Linha: Satisfação -->
                <div class="mb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Satisfação do Cliente</span>
                        <span>
                            <?php if ($avgSatisf !== null): ?>
                                <?php $stars = (int)round($avgSatisf); ?>
                                <?php for ($i=1;$i<=5;$i++): ?>
                                    <?php if ($i <= $stars): ?><i class="bi bi-star-fill text-warning"></i><?php else: ?><i class="bi bi-star text-warning"></i><?php endif; ?>
                                <?php endfor; ?>
                                <span class="ms-1 small"><?php echo number_format((float)$avgSatisf, 1, ',', '.'); ?>/5</span>
                            <?php else: ?>
                                <span class="text-muted small">Sem avaliações</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-star text-warning me-2"></i>
                    Serviços Mais Solicitados (mês)
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($topServicos)): ?>
                    <div class="alert alert-light mb-0">Sem dados para o mês atual.</div>
                <?php else: ?>
                    <?php foreach ($topServicos as $ts): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($ts['nome'] ?? 'Serviço', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="badge bg-primary"><?php echo (int)($ts['q'] ?? 0); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.alert-sm {
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 8px;
}

.timeline-item:hover {
    background-color: rgba(0,123,255,0.05);
    transition: background-color 0.3s ease;
}

.card {
    transition: all 0.3s ease;
}

.progress {
    height: 8px;
}

.badge {
    font-size: 0.75rem;
}
</style>