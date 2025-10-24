<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../conexao.php';

$mensagemSucesso = '';
$mensagemErro = '';

// Util: verifica se tabela existe
function tabelaExiste(mysqli $conn, string $nome): bool {
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($nome) . "'");
    return $res && $res->num_rows > 0;
}

// Util: obter profissionais, servicos e clientes
function obterProfissionais(mysqli $conn): array {
    $lista = [];
    $res = $conn->query("SELECT id, nome FROM salao_profissionais ORDER BY nome");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $lista[] = $row; }
        $res->free();
    }
    return $lista;
}

function obterServicos(mysqli $conn): array {
    $lista = [];
    $res = $conn->query("SELECT id, nome, duracao, preco FROM salao_servicos WHERE ativo = 1 ORDER BY nome");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $lista[] = $row; }
        $res->free();
    }
    return $lista;
}

function obterClientes(mysqli $conn): array {
    $lista = [];
    if ($conn->query("SHOW TABLES LIKE 'salao_clientes'")) {
        $res = $conn->query("SELECT id, nome, telefone FROM salao_clientes ORDER BY nome LIMIT 500");
        if ($res) {
            while ($row = $res->fetch_assoc()) { $lista[] = $row; }
            $res->free();
        }
    }
    return $lista;
}

$temTabelaAgendamento = tabelaExiste($conn, 'salao_agendamentos');

// Cria/agenda/edita/exclui (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $temTabelaAgendamento) {
    $acao = $_POST['action'] ?? '';
    if ($acao === 'create' || $acao === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $cliente_id = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== '' ? (int)$_POST['cliente_id'] : null;
        $nome_cliente = trim((string)($_POST['nome_cliente'] ?? ''));
        $telefone_cliente = trim((string)($_POST['telefone_cliente'] ?? ''));
        $profissional_id = (int)($_POST['profissional_id'] ?? 0);
        $servico_id = (int)($_POST['servico_id'] ?? 0);
    $data_agendamento = trim((string)($_POST['data_agendamento'] ?? ''));
    // Normaliza caso o navegador/locale envie no formato dd/mm/aaaa
    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $data_agendamento)) {
      [$d,$m,$y] = explode('/', $data_agendamento);
      $data_agendamento = sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
    }
        $hora_inicio = trim((string)($_POST['hora_inicio'] ?? ''));
        $duracao_prevista = (int)($_POST['duracao_prevista'] ?? 0);
        $duracao_real = isset($_POST['duracao_real']) && $_POST['duracao_real'] !== '' ? (int)$_POST['duracao_real'] : null;
        $observacoes = trim((string)($_POST['observacoes'] ?? ''));
        $status = in_array(($_POST['status'] ?? 'agendado'), ['agendado','em_andamento','concluido','cancelado'], true)
            ? $_POST['status'] : 'agendado';

        // Validações básicas
        if ($profissional_id <= 0 || $servico_id <= 0 || $data_agendamento === '' || $hora_inicio === '' || $duracao_prevista <= 0) {
            $mensagemErro = 'Preencha profissional, serviço, data, hora e duração.';
        } else {
            // Calcula hora fim a partir de duração_real (se fornecida) senao prevista
            $dur = $duracao_real ?? $duracao_prevista;
            $inicioDateTime = DateTime::createFromFormat('Y-m-d H:i', $data_agendamento . ' ' . $hora_inicio);
            if (!$inicioDateTime) {
                $mensagemErro = 'Data ou hora inválida.';
            } else {
                $fimDateTime = clone $inicioDateTime;
                $fimDateTime->modify("+{$dur} minutes");
                $hora_fim = $fimDateTime->format('H:i:s');
                $hora_inicio_fmt = $inicioDateTime->format('H:i:s');

                // Checa conflito para o mesmo profissional
                $sqlConf = "SELECT COUNT(*) AS qtd FROM salao_agendamentos 
                            WHERE profissional_id = ? AND data_agendamento = ? 
                              AND id <> ?
                              AND NOT (hora_fim <= ? OR hora_inicio >= ?)";
                $stmt = $conn->prepare($sqlConf);
                $idComp = $acao === 'update' ? $id : 0;
                $stmt->bind_param('isiss', $profissional_id, $data_agendamento, $idComp, $hora_inicio_fmt, $hora_fim);
                if ($stmt->execute()) {
                    $q = $stmt->get_result()->fetch_assoc()['qtd'] ?? 0;
                    $stmt->close();
                    if ((int)$q > 0) {
                        $mensagemErro = 'Há conflito de horário para este profissional.';
                    } else {
                        if ($acao === 'create') {
              $stmt2 = $conn->prepare("INSERT INTO salao_agendamentos 
                (cliente_id, nome_cliente, telefone_cliente, profissional_id, servico_id, data_agendamento, hora_inicio, hora_fim, duracao_prevista, duracao_real, observacoes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
              $stmt2->bind_param(
                'issiisssiiss',
                                $cliente_id,
                                $nome_cliente,
                                $telefone_cliente,
                                $profissional_id,
                                $servico_id,
                                $data_agendamento,
                                $hora_inicio_fmt,
                                $hora_fim,
                                $duracao_prevista,
                                $duracao_real,
                                $observacoes,
                                $status
                            );
                            if ($stmt2->execute()) {
                                $mensagemSucesso = 'Agendamento criado.';
                            } else {
                                $mensagemErro = 'Erro ao criar agendamento.';
                            }
                            $stmt2->close();
                        } else { // update
                            if ($id <= 0) {
                                $mensagemErro = 'Agendamento inválido para edição.';
                            } else {
                $stmt2 = $conn->prepare("UPDATE salao_agendamentos SET
                                    cliente_id = ?, nome_cliente = ?, telefone_cliente = ?, profissional_id = ?, servico_id = ?,
                                    data_agendamento = ?, hora_inicio = ?, hora_fim = ?, duracao_prevista = ?, duracao_real = ?, observacoes = ?, status = ?
                                    WHERE id = ?");
                $stmt2->bind_param(
                  'issiisssiissi',
                                    $cliente_id,
                                    $nome_cliente,
                                    $telefone_cliente,
                                    $profissional_id,
                                    $servico_id,
                                    $data_agendamento,
                                    $hora_inicio_fmt,
                                    $hora_fim,
                                    $duracao_prevista,
                                    $duracao_real,
                                    $observacoes,
                                    $status,
                                    $id
                                );
                                if ($stmt2->execute()) {
                                    $mensagemSucesso = 'Agendamento atualizado.';
                                } else {
                                    $mensagemErro = 'Erro ao atualizar agendamento.';
                                }
                                $stmt2->close();
                            }
                        }
                    }
                } else {
                    $mensagemErro = 'Erro ao validar conflito de horário.';
                }
            }
        }
    } elseif ($acao === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM salao_agendamentos WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $mensagemSucesso = 'Agendamento removido.';
            } else {
                $mensagemErro = 'Erro ao remover agendamento.';
            }
            $stmt->close();
        }
    }
}

// Filtros e período (mensal por padrão)
$profissionalFiltro = isset($_GET['profissional']) ? (int)$_GET['profissional'] : 0; // 0 = todos
$view = isset($_GET['view']) && in_array($_GET['view'], ['month','week','day'], true) ? $_GET['view'] : 'month';
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
if ($mes < 1 || $mes > 12) { $mes = (int)date('n'); }
// Base para semana/dia
$diaParam = isset($_GET['dia']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dia']) ? $_GET['dia'] : date('Y-m-d');
$baseDate = DateTime::createFromFormat('Y-m-d', $diaParam) ?: new DateTime();

$primeiroDia = new DateTime("$ano-$mes-01");
$inicioMes = clone $primeiroDia;
$inicioMes->modify('first day of this month');
$fimMes = clone $inicioMes;
$fimMes->modify('last day of this month');

$profissionais = obterProfissionais($conn);
$servicos = obterServicos($conn);
$clientes = obterClientes($conn);

// Determina janela de busca conforme a view
if ($view === 'week') {
  // Semana iniciando no Domingo para combinar com cabeçalho DOM..SÁB
  $inicioSemana = clone $baseDate; // base
  // move to Sunday
  $w = (int)$inicioSemana->format('w'); // 0=Sun
  $inicioSemana->modify('-' . $w . ' day');
  $fimSemana = clone $inicioSemana; $fimSemana->modify('+6 day');
  $rangeInicio = $inicioSemana->format('Y-m-d');
  $rangeFim = $fimSemana->format('Y-m-d');
} elseif ($view === 'day') {
  $rangeInicio = $baseDate->format('Y-m-d');
  $rangeFim = $baseDate->format('Y-m-d');
} else { // month
  $rangeInicio = $inicioMes->format('Y-m-d');
  $rangeFim = $fimMes->format('Y-m-d');
}

// Busca agendamentos para a janela selecionada
$agendamentosPorDia = [];
$agendamentosInvalidos = [];
$debugInfo = "Buscando de $rangeInicio até $rangeFim (view: $view)";
if ($temTabelaAgendamento) {
  $sql = "SELECT a.*, s.nome AS servico_nome, s.duracao AS servico_duracao, p.nome AS profissional_nome
      FROM salao_agendamentos a
      LEFT JOIN salao_servicos s ON s.id = a.servico_id
      LEFT JOIN salao_profissionais p ON p.id = a.profissional_id
      WHERE (a.data_agendamento BETWEEN ? AND ? OR a.data_agendamento = '0000-00-00' OR a.data_agendamento IS NULL)" .
      ($profissionalFiltro > 0 ? " AND a.profissional_id = ?" : '') .
      " ORDER BY a.data_agendamento, a.hora_inicio";
  if ($profissionalFiltro > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $rangeInicio, $rangeFim, $profissionalFiltro);
  } else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $rangeInicio, $rangeFim);
  }
  if ($stmt && $stmt->execute()) {
    $res = $stmt->get_result();
    $contador = 0;
    while ($row = $res->fetch_assoc()) {
      $dia = $row['data_agendamento'];
      // Separa agendamentos com data inválida
      if ($dia === '0000-00-00' || $dia === null || $dia === '') {
        $agendamentosInvalidos[] = $row;
      } else {
        if (!isset($agendamentosPorDia[$dia])) $agendamentosPorDia[$dia] = [];
        $agendamentosPorDia[$dia][] = $row;
      }
      $contador++;
    }
    // Coletas extras de diagnóstico
    $dbgTotal = 0; $dbgRange = 0; $dbgProf = (int)$profissionalFiltro;
    if ($r = $conn->query("SELECT COUNT(*) AS c FROM salao_agendamentos")) { $dbgTotal = (int)($r->fetch_assoc()['c'] ?? 0); $r->free(); }
    $stmtC = $conn->prepare("SELECT COUNT(*) AS c FROM salao_agendamentos WHERE data_agendamento BETWEEN ? AND ?");
    if ($stmtC) { $stmtC->bind_param('ss', $rangeInicio, $rangeFim); $stmtC->execute(); $resC=$stmtC->get_result(); $dbgRange=(int)($resC->fetch_assoc()['c']??0); $stmtC->close(); }
    $debugInfo .= " | Encontrados: $contador agendamentos (".count($agendamentosInvalidos)." com data inválida) | total_tab=".$dbgTotal." | no_range=".$dbgRange." | filtro_prof=".$dbgProf;
    $stmt->close();
  } else {
    $debugInfo .= " | Erro ao executar query";
  }
} else {
  $debugInfo .= " | Tabela não encontrada";
}

function nomeMesPt(int $m): string {
    $nomes = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    return $nomes[$m] ?? (string)$m;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Sistema de Agenda</title>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <style>
        :root{
            --cor-prim:#6366f1; /* indigo */
            --cor-sec:#a855f7; /* purple */
            --cor-bg:#0f172a;  /* slate-900 */
            --cor-card:#111827; /* gray-900 */
            --cor-borda:#1f2937; /* gray-800 */
            --cor-texto:#e5e7eb; /* gray-200 */
            --cor-suave:#9ca3af; /* gray-400 */
            --cor-ok:#10b981; --cor-warn:#f59e0b; --cor-err:#ef4444;
        }
        body{background:#0b1020;color:var(--cor-texto);} 
        .agenda-wrapper{max-width:1200px;margin:0 auto;padding:1rem 1.25rem;}
        .agenda-top{display:flex;gap:.75rem;align-items:center;justify-content:space-between;margin-bottom:1rem;}
        .titulo{font-size:1.25rem;font-weight:600;margin:0;}
        .controls{display:flex;gap:.5rem;flex-wrap:wrap;}
        .select, .input{background:#0f1a36;border:1px solid #193159;color:var(--cor-texto);border-radius:8px;padding:.5rem .75rem;}
        .btn-prim{background:linear-gradient(135deg,var(--cor-prim),var(--cor-sec));border:none;color:white;border-radius:8px;padding:.5rem .9rem;font-weight:600}
        .btn-ghost{background:transparent;border:1px solid #27406e;color:#e5e7eb;border-radius:8px;padding:.5rem .9rem}
    .calendar{background:#0f1a36;border:1px solid #193159;border-radius:12px;overflow:hidden}
        .calendar-header{display:grid;grid-template-columns:repeat(7,1fr);background:#0c1430;color:#a5b4fc;border-bottom:1px solid #193159}
        .calendar-header div{padding:.6rem .75rem;font-weight:600;text-align:center}
        .calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);}
        .day{min-height:120px;border-right:1px solid #193159;border-bottom:1px solid #193159;padding:.5rem;position:relative}
        .day:nth-child(7n){border-right:none}
        .day-num{position:absolute;top:.4rem;right:.5rem;font-size:.85rem;color:#9aa7d9}
        .day .novo-btn{position:absolute;bottom:.5rem;left:.5rem;font-size:.75rem;padding:.2rem .45rem}
        .event{background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.35);color:#dbeafe;border-radius:6px;padding:.25rem .4rem;margin:.2rem 0;font-size:.8rem;cursor:pointer}
        .legenda{display:flex;gap:.5rem;align-items:center;color:#9aa7d9}
        .pill{height:10px;width:10px;border-radius:50%}
        .pill-ok{background:var(--cor-ok)}
        .pill-warn{background:var(--cor-warn)}
        .pill-err{background:var(--cor-err)}
        .alerta{margin:.75rem 0}
    /* Views */
    .view-tabs{display:flex;gap:.5rem}
    .view-tabs a{padding:.4rem .7rem;border-radius:8px;border:1px solid #27406e;color:#dbeafe;text-decoration:none}
    .view-tabs a.active{background:linear-gradient(135deg,var(--cor-prim),var(--cor-sec));border-color:transparent}
    /* Weekly grid */
    .week-grid{display:grid;grid-template-columns:80px repeat(7,1fr);border-top:1px solid #193159;border-left:1px solid #193159;border-radius:12px;overflow:hidden}
    .week-header{display:grid;grid-template-columns:80px repeat(7,1fr);background:#0c1430;color:#a5b4fc;border:1px solid #193159;border-bottom:none;border-radius:12px 12px 0 0}
    .week-header div{padding:.5rem .5rem;text-align:center;font-weight:600;border-right:1px solid #193159}
    .week-header div:last-child{border-right:none}
    .slot{min-height:32px;border-right:1px solid #193159;border-bottom:1px solid #193159;position:relative}
    .slot.time{background:#0c1430;color:#9aa7d9;text-align:right;padding-right:.4rem;font-size:.8rem}
    .slot .event{position:absolute;left:.25rem;right:.25rem;top:.25rem}
    .slot.drop-target{outline:1px dashed rgba(99,102,241,.4)}
    </style>
</head>
<body>
<div class="agenda-wrapper">
    <div class="agenda-top">
        <?php 
        if($view==='week'){ 
            $t = clone $baseDate; 
            $w=(int)$t->format('w'); 
            $t->modify('-'.$w.' day'); 
            $t2=clone $t; 
            $t2->modify('+6 day'); 
        }
        if($view==='day'){ 
            $t = clone $baseDate; 
        }
        ?>
        <h1 class="titulo">
            Agenda — 
            <?php 
            if($view==='week'){ 
                echo 'Semana de '.$t->format('d/m').' a '.$t2->format('d/m').' ('.nomeMesPt((int)$t->format('n')).')'; 
            } elseif($view==='day'){ 
                echo 'Dia '.$t->format('d/m/Y'); 
            } else { 
                echo nomeMesPt($mes) . ' / ' . $ano; 
            } 
            ?>
        </h1>
        <div class="controls">
            <form method="get" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="mes" value="<?php echo (int)$mes; ?>">
                <input type="hidden" name="ano" value="<?php echo (int)$ano; ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view,ENT_QUOTES,'UTF-8'); ?>">
                <input type="hidden" name="dia" value="<?php echo htmlspecialchars($baseDate->format('Y-m-d'),ENT_QUOTES,'UTF-8'); ?>">
                <select class="select" name="profissional" onchange="this.form.submit()">
                    <option value="0" <?php echo $profissionalFiltro===0?'selected':''; ?>>Todos os profissionais</option>
                    <?php foreach($profissionais as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo $profissionalFiltro===(int)$p['id']?'selected':''; ?>><?php echo htmlspecialchars($p['nome'],ENT_QUOTES,'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="legenda">
                <span class="pill pill-ok"></span> confirmado
                <span class="pill pill-warn"></span> em andamento
                <span class="pill pill-err"></span> cancelado
            </div>
            <div class="d-flex gap-2">
                <?php if($view==='month'){ 
                    $prev=(clone $inicioMes)->modify('-1 month'); 
                    $next=(clone $inicioMes)->modify('+1 month'); 
                ?>
                    <a class="btn-ghost" href="?view=month&mes=<?php echo (int)$prev->format('n'); ?>&ano=<?php echo (int)$prev->format('Y'); ?>&profissional=<?php echo (int)$profissionalFiltro; ?>">◀ Anterior</a>
                    <a class="btn-ghost" href="?view=month&mes=<?php echo (int)$next->format('n'); ?>&ano=<?php echo (int)$next->format('Y'); ?>&profissional=<?php echo (int)$profissionalFiltro; ?>">Próximo ▶</a>
                <?php } elseif($view==='week'){ 
                    $w=(clone $baseDate); 
                    $prevW=(clone $w)->modify('-7 day'); 
                    $nextW=(clone $w)->modify('+7 day'); 
                ?>
                    <a class="btn-ghost" href="?view=week&profissional=<?php echo (int)$profissionalFiltro; ?>&dia=<?php echo $prevW->format('Y-m-d'); ?>">◀ Semana anterior</a>
                    <a class="btn-ghost" href="?view=week&profissional=<?php echo (int)$profissionalFiltro; ?>&dia=<?php echo $nextW->format('Y-m-d'); ?>">Próxima semana ▶</a>
                <?php } elseif($view==='day'){ 
                    $d=(clone $baseDate); 
                    $prevD=(clone $d)->modify('-1 day'); 
                    $nextD=(clone $d)->modify('+1 day'); 
                ?>
                    <a class="btn-ghost" href="?view=day&profissional=<?php echo (int)$profissionalFiltro; ?>&dia=<?php echo $prevD->format('Y-m-d'); ?>">◀ Dia anterior</a>
                    <a class="btn-ghost" href="?view=day&profissional=<?php echo (int)$profissionalFiltro; ?>&dia=<?php echo $nextD->format('Y-m-d'); ?>">Próximo dia ▶</a>
                <?php } ?>
            </div>
            <div class="view-tabs">
                <a class="<?php echo $view==='month'?'active':''; ?>" href="?view=month&mes=<?php echo (int)$mes; ?>&ano=<?php echo (int)$ano; ?>&profissional=<?php echo (int)$profissionalFiltro; ?>">Mensal</a>
                <a class="<?php echo $view==='week'?'active':''; ?>" href="?view=week&profissional=<?php echo (int)$profissionalFiltro; ?>&dia=<?php echo htmlspecialchars($baseDate->format('Y-m-d'),ENT_QUOTES,'UTF-8'); ?>">Semanal</a>
                <a class="<?php echo $view==='day'?'active':''; ?>" href="?view=day&profissional=<?php echo (int)$profissionalFiltro; ?>&dia=<?php echo htmlspecialchars($baseDate->format('Y-m-d'),ENT_QUOTES,'UTF-8'); ?>">Diária</a>
            </div>
        </div>
    </div>

    <?php if (!$temTabelaAgendamento): ?>
        <div class="alert alert-warning alerta">
            A tabela salao_agendamentos não foi encontrada. Crie a tabela no seu banco com o SQL abaixo:
            <pre class="mt-2 mb-0 small">CREATE TABLE `salao_agendamentos` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cliente_id` INT(11) DEFAULT NULL,
  `nome_cliente` VARCHAR(150) DEFAULT NULL,
  `telefone_cliente` VARCHAR(20) DEFAULT NULL,
  `profissional_id` INT(10) UNSIGNED NOT NULL,
  `servico_id` INT(10) UNSIGNED NOT NULL,
  `data_agendamento` DATE NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fim` TIME NOT NULL,
  `duracao_prevista` INT(11) NOT NULL,
  `duracao_real` INT(11) DEFAULT NULL,
  `observacoes` TEXT DEFAULT NULL,
  `status` ENUM('agendado','em_andamento','concluido','cancelado') DEFAULT 'agendado',
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
        </div>
    <?php endif; ?>

    

    <?php if ($mensagemSucesso): ?><div class="alert alert-success alerta"><?php echo htmlspecialchars($mensagemSucesso,ENT_QUOTES,'UTF-8'); ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger alerta"><?php echo htmlspecialchars($mensagemErro,ENT_QUOTES,'UTF-8'); ?></div><?php endif; ?>

  <?php if($view==='month'): ?>
    <div class="calendar">
      <div class="calendar-header">
        <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
      </div>
      <div class="calendar-grid">
        <?php
        // Monta grade mensal
        $startWeekday = (int)$inicioMes->format('w'); // 0=Dom
        $daysInMonth = (int)$fimMes->format('j');
        for ($i=0; $i<$startWeekday; $i++) { echo '<div class="day"></div>'; }
        for ($d=1; $d<=$daysInMonth; $d++) {
          $dataStr = sprintf('%04d-%02d-%02d', $ano, $mes, $d);
          $itens = $agendamentosPorDia[$dataStr] ?? [];
          echo '<div class="day">';
          echo '<div class="day-num">'.$d.'</div>';
          foreach ($itens as $ag) {
            $cls = 'event';
            if (($ag['status'] ?? '') === 'em_andamento') { $cls .= ' status-warn'; }
            if (($ag['status'] ?? '') === 'cancelado') { $cls .= ' status-err'; }
            $label = htmlspecialchars(substr($ag['hora_inicio'],0,5).' '.($ag['profissional_nome']??'').' — '.($ag['servico_nome']??''),ENT_QUOTES,'UTF-8');
            echo '<div class="'.$cls.'" data-ag="'.htmlspecialchars(json_encode($ag),ENT_QUOTES,'UTF-8').'">'.$label.'</div>';
          }
          echo '<button class="btn-prim novo-btn" data-dia="'.$dataStr.'">+ Agendar</button>';
          echo '</div>';
        }
        $totalCells = $startWeekday + $daysInMonth;
        $resto = $totalCells % 7;
        if ($resto !== 0) { for ($i=0; $i<7-$resto; $i++) echo '<div class="day"></div>'; }
        ?>
      </div>
    </div>
  <?php elseif($view==='week'): ?>
    <?php $ini = clone $baseDate; $w=(int)$ini->format('w'); $ini->modify('-'.$w.' day'); $dias=[]; for($i=0;$i<7;$i++){ $d=(clone $ini)->modify("+{$i} day"); $dias[]=$d; } ?>
    <div class="week-header">
      <div></div>
      <?php foreach($dias as $d): ?><div><?php echo $d->format('D d/m'); ?></div><?php endforeach; ?>
    </div>
    <div class="week-grid" id="weekGrid">
      <?php
      $startHour=8; $endHour=20; $step=30; // min
      for($h=$startHour;$h<$endHour;$h++){
        for($m=0;$m<60;$m+=$step){
          $horaLabel = sprintf('%02d:%02d',$h,$m);
          echo '<div class="slot time">'.($m===0?$horaLabel:'').'</div>';
          foreach($dias as $d){
            $dataStr=$d->format('Y-m-d');
            echo '<div class="slot drop" data-dia="'.$dataStr.'" data-hora="'.$horaLabel.'">';
            $itens=$agendamentosPorDia[$dataStr]??[];
            foreach($itens as $ag){
              if(substr($ag['hora_inicio'],0,5)===$horaLabel){
                $label=htmlspecialchars(substr($ag['hora_inicio'],0,5).' — '.($ag['servico_nome']??''),ENT_QUOTES,'UTF-8');
                echo '<div class="event" draggable="true" data-ag="'.htmlspecialchars(json_encode($ag),ENT_QUOTES,'UTF-8').'">'.$label.'</div>';
              }
            }
            echo '</div>';
          }
        }
      }
      ?>
    </div>
    <div class="mt-2 text-muted" style="font-size:.9rem">Dica: arraste um agendamento para outro horário/dia para reagendar rapidamente (será aberto para confirmação).</div>
  <?php else: ?>
    <?php $d = clone $baseDate; $dataStr=$d->format('Y-m-d'); ?>
    <div class="week-header"><div></div><div><?php echo $d->format('D d/m'); ?></div></div>
    <div class="week-grid" id="dayGrid">
      <?php $startHour=8; $endHour=20; $step=30; for($h=$startHour;$h<$endHour;$h++){ for($m=0;$m<60;$m+=$step){ $horaLabel=sprintf('%02d:%02d',$h,$m); echo '<div class="slot time">'.($m===0?$horaLabel:'').'</div>'; echo '<div class="slot drop" data-dia="'.$dataStr.'" data-hora="'.$horaLabel.'">'; $itens=$agendamentosPorDia[$dataStr]??[]; foreach($itens as $ag){ if(substr($ag['hora_inicio'],0,5)===$horaLabel){ $label=htmlspecialchars(substr($ag['hora_inicio'],0,5).' — '.($ag['servico_nome']??''),ENT_QUOTES,'UTF-8'); echo '<div class="event" draggable="true" data-ag="'.htmlspecialchars(json_encode($ag),ENT_QUOTES,'UTF-8').'">'.$label.'</div>'; } } echo '</div>'; } } ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal Agendamento -->
<div class="modal fade" id="modalAgendamento" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" id="ag_action" value="create">
        <input type="hidden" name="id" id="ag_id" value="">
        <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#a855f7);color:white">
          <h5 class="modal-title" id="modalTitulo">Novo agendamento</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Data</label>
              <input type="date" class="form-control" name="data_agendamento" id="ag_data" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Hora início</label>
              <input type="time" class="form-control" name="hora_inicio" id="ag_hora_inicio" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-select" name="status" id="ag_status">
                <option value="agendado">Agendado</option>
                <option value="em_andamento">Em andamento</option>
                <option value="concluido">Concluído</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Profissional</label>
              <select class="form-select" name="profissional_id" id="ag_profissional" required>
                <option value="">Selecione</option>
                <?php foreach($profissionais as $p): ?>
                  <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['nome'],ENT_QUOTES,'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Serviço</label>
              <select class="form-select" name="servico_id" id="ag_servico" required>
                <option value="">Selecione</option>
                <?php foreach($servicos as $s): ?>
                  <option value="<?php echo (int)$s['id']; ?>" data-duracao="<?php echo (int)$s['duracao']; ?>">
                    <?php echo htmlspecialchars($s['nome'].' ('.$s['duracao'].'min)',ENT_QUOTES,'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Duração prevista (min)</label>
              <input type="number" class="form-control" name="duracao_prevista" id="ag_dur_prev" min="1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Duração real (min)</label>
              <input type="number" class="form-control" name="duracao_real" id="ag_dur_real" min="1" placeholder="opcional">
            </div>
            <div class="col-md-4">
              <label class="form-label">Telefone</label>
              <input type="text" class="form-control" name="telefone_cliente" id="ag_tel" placeholder="(xx) xxxxx-xxxx">
            </div>
            <div class="col-md-6">
              <label class="form-label">Cliente (cadastrado)</label>
              <select class="form-select" name="cliente_id" id="ag_cliente_id">
                <option value="">—</option>
                <?php foreach($clientes as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nome'].' '.($c['telefone']?('('.$c['telefone'].')'):''),ENT_QUOTES,'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nome (agendamento rápido)</label>
              <input type="text" class="form-control" name="nome_cliente" id="ag_nome_cli" placeholder="se não usar cliente cadastrado">
            </div>

            <div class="col-12">
              <label class="form-label">Observações</label>
              <textarea class="form-control" name="observacoes" id="ag_obs" rows="3"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form method="post" id="formExcluir" class="d-none">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id" id="excluirId">
</form>

<script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const modalEl = document.getElementById('modalAgendamento');
  const bsModal = new bootstrap.Modal(modalEl);
  const novoBtns = document.querySelectorAll('.novo-btn');
  const form = modalEl.querySelector('form');

  // Auto-preenche duração a partir do serviço
  const selectServico = document.getElementById('ag_servico');
  const durPrev = document.getElementById('ag_dur_prev');
  const durReal = document.getElementById('ag_dur_real');
  selectServico.addEventListener('change', () => {
    const opt = selectServico.selectedOptions[0];
    const d = opt ? parseInt(opt.getAttribute('data-duracao')||'0',10) : 0;
    if (d>0) durPrev.value = d;
  });

  // Novo agendamento ao clicar no dia
  novoBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      form.reset();
      document.getElementById('ag_action').value = 'create';
      document.getElementById('ag_id').value = '';
      document.getElementById('modalTitulo').textContent = 'Novo agendamento';
      document.getElementById('ag_data').value = btn.getAttribute('data-dia');
      bsModal.show();
    });
  });

  // Abrir para edição ao clicar no evento
  document.querySelectorAll('.event').forEach(ev => {
    ev.addEventListener('click', () => {
      const dados = JSON.parse(ev.getAttribute('data-ag'));
      form.reset();
      document.getElementById('ag_action').value = 'update';
      document.getElementById('ag_id').value = dados.id;
      document.getElementById('modalTitulo').textContent = 'Editar agendamento';
      document.getElementById('ag_data').value = dados.data_agendamento;
      document.getElementById('ag_hora_inicio').value = dados.hora_inicio.substring(0,5);
      document.getElementById('ag_status').value = dados.status;
      document.getElementById('ag_profissional').value = dados.profissional_id;
      document.getElementById('ag_servico').value = dados.servico_id;
      document.getElementById('ag_dur_prev').value = dados.duracao_prevista;
      document.getElementById('ag_dur_real').value = dados.duracao_real || '';
      document.getElementById('ag_tel').value = dados.telefone_cliente || '';
      document.getElementById('ag_cliente_id').value = dados.cliente_id || '';
      document.getElementById('ag_nome_cli').value = dados.nome_cliente || '';
      document.getElementById('ag_obs').value = dados.observacoes || '';
      bsModal.show();
    });
    // Suporte a drag
    ev.addEventListener('dragstart', (e) => {
      e.dataTransfer.setData('text/plain', ev.getAttribute('data-ag'));
    });
  });

  // Botão para corrigir agendamentos inválidos
  document.querySelectorAll('.editar-invalido').forEach(btn => {
    btn.addEventListener('click', () => {
      const dados = JSON.parse(btn.getAttribute('data-ag'));
      form.reset();
      document.getElementById('ag_action').value = 'update';
      document.getElementById('ag_id').value = dados.id;
      document.getElementById('modalTitulo').textContent = 'Corrigir agendamento';
      document.getElementById('ag_data').value = '<?php echo date('Y-m-d'); ?>'; // data de hoje como padrão
      document.getElementById('ag_hora_inicio').value = dados.hora_inicio ? dados.hora_inicio.substring(0,5) : '09:00';
      document.getElementById('ag_status').value = dados.status || 'agendado';
      document.getElementById('ag_profissional').value = dados.profissional_id;
      document.getElementById('ag_servico').value = dados.servico_id;
      document.getElementById('ag_dur_prev').value = dados.duracao_prevista || 30;
      document.getElementById('ag_dur_real').value = dados.duracao_real || '';
      document.getElementById('ag_tel').value = dados.telefone_cliente || '';
      document.getElementById('ag_cliente_id').value = dados.cliente_id || '';
      document.getElementById('ag_nome_cli').value = dados.nome_cliente || '';
      document.getElementById('ag_obs').value = dados.observacoes || '';
      bsModal.show();
    });
  });

  // Drop para reagendar rapidamente (sem salvar automático - abre modal preenchido)
  document.querySelectorAll('.slot.drop').forEach(slot => {
    slot.addEventListener('dragover', (e) => { e.preventDefault(); slot.classList.add('drop-target'); });
    slot.addEventListener('dragleave', () => { slot.classList.remove('drop-target'); });
    slot.addEventListener('drop', (e) => {
      e.preventDefault();
      slot.classList.remove('drop-target');
      const dadosStr = e.dataTransfer.getData('text/plain');
      if (!dadosStr) return;
      const dados = JSON.parse(dadosStr);
      form.reset();
      document.getElementById('ag_action').value = 'update';
      document.getElementById('ag_id').value = dados.id;
      document.getElementById('modalTitulo').textContent = 'Reagendar agendamento';
      document.getElementById('ag_data').value = slot.getAttribute('data-dia');
      document.getElementById('ag_hora_inicio').value = slot.getAttribute('data-hora');
      document.getElementById('ag_status').value = dados.status;
      document.getElementById('ag_profissional').value = dados.profissional_id;
      document.getElementById('ag_servico').value = dados.servico_id;
      document.getElementById('ag_dur_prev').value = dados.duracao_prevista;
      document.getElementById('ag_dur_real').value = dados.duracao_real || '';
      document.getElementById('ag_tel').value = dados.telefone_cliente || '';
      document.getElementById('ag_cliente_id').value = dados.cliente_id || '';
      document.getElementById('ag_nome_cli').value = dados.nome_cliente || '';
      document.getElementById('ag_obs').value = dados.observacoes || '';
      bsModal.show();
    });
    // clique em slot vazio cria novo agendamento direto naquele horário
    slot.addEventListener('dblclick', () => {
      form.reset();
      document.getElementById('ag_action').value = 'create';
      document.getElementById('ag_id').value = '';
      document.getElementById('modalTitulo').textContent = 'Novo agendamento';
      document.getElementById('ag_data').value = slot.getAttribute('data-dia');
      document.getElementById('ag_hora_inicio').value = slot.getAttribute('data-hora');
      bsModal.show();
    });
  });
})();
</script>
</body>
</html>
