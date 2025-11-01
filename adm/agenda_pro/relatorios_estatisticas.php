<?php
// Análises Gerais — Relatórios & Estatísticas por período (Mensal / Semanal / Diária)
if (!isset($conn)) {
    $connFile = __DIR__ . '/../../conexao.php';
    if (file_exists($connFile)) { require_once $connFile; }
}

// Timezone consistente
$tz = ini_get('date.timezone');
if (!$tz || !@date_default_timezone_set($tz)) {
    date_default_timezone_set('America/Sao_Paulo');
}

function brl($v){ return 'R$ ' . number_format((float)$v, 2, ',', '.'); }

$hoje = date('Y-m-d');
$view = isset($_GET['view']) ? strtolower(trim($_GET['view'])) : 'mensal';
if (!in_array($view, ['mensal','semanal','diaria'])) { $view = 'mensal'; }
$data = isset($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data']) ? $_GET['data'] : $hoje;

// Calcular intervalo de datas conforme view
switch ($view) {
    case 'semanal':
        $ini = new DateTime($data);
        $dow = (int)$ini->format('N'); // 1=Seg..7=Dom
        $ini->modify('-' . ($dow-1) . ' days');
        $fim = clone $ini; $fim->modify('+6 days');
        $titulo = 'Análises — Semana de ' . $ini->format('d/m') . ' a ' . $fim->format('d/m') . ' (' . $ini->format('Y') . ')';
        $rangeStart = $ini->format('Y-m-d');
        $rangeEnd = $fim->format('Y-m-d');
        $prevData = (clone $ini)->modify('-7 days')->format('Y-m-d');
        $nextData = (clone $ini)->modify('+7 days')->format('Y-m-d');
        break;
    case 'diaria':
        $ini = new DateTime($data);
        $fim = clone $ini;
        $titulo = 'Análises — Dia ' . $ini->format('d/m/Y');
        $rangeStart = $ini->format('Y-m-d');
        $rangeEnd = $fim->format('Y-m-d');
        $prevData = (clone $ini)->modify('-1 day')->format('Y-m-d');
        $nextData = (clone $ini)->modify('+1 day')->format('Y-m-d');
        break;
    default:
        // mensal
        $dt = new DateTime($data);
        $dt->modify('first day of this month');
        $dtEnd = clone $dt; $dtEnd->modify('last day of this month');
        $titulo = 'Análises — ' . $dt->format('m/Y');
        $rangeStart = $dt->format('Y-m-d');
        $rangeEnd = $dtEnd->format('Y-m-d');
        $prevData = (clone $dt)->modify('-1 month')->format('Y-m-d');
        $nextData = (clone $dt)->modify('+1 month')->format('Y-m-d');
}

// Consultas
$fat = 0.0; $qtdAg = 0; $qtdConcl = 0; $qtdCanc = 0; $avgSatisf = null; $topServicos = [];
if (isset($conn) && $conn instanceof mysqli) {
    // Faturamento concluído no período
    if ($st = $conn->prepare('SELECT SUM(s.preco) AS total FROM salao_agendamentos a INNER JOIN salao_servicos s ON s.id = a.servico_id WHERE a.data_agendamento BETWEEN ? AND ? AND a.status = "concluido"')) {
        $st->bind_param('ss', $rangeStart, $rangeEnd);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $fat=(float)($row['total']??0); }
        $st->close();
    }
    // Agendamentos (não cancelados) no período
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento BETWEEN ? AND ? AND status <> "cancelado"')) {
        $st->bind_param('ss', $rangeStart, $rangeEnd);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qtdAg=(int)($row['t']??0); }
        $st->close();
    }
    // Concluídos
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento BETWEEN ? AND ? AND status = "concluido"')) {
        $st->bind_param('ss', $rangeStart, $rangeEnd);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qtdConcl=(int)($row['t']??0); }
        $st->close();
    }
    // Cancelados
    if ($st = $conn->prepare('SELECT COUNT(*) AS t FROM salao_agendamentos WHERE data_agendamento BETWEEN ? AND ? AND status = "cancelado"')) {
        $st->bind_param('ss', $rangeStart, $rangeEnd);
        if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $qtdCanc=(int)($row['t']??0); }
        $st->close();
    }
    // Satisfação média: tenta filtrar por data_criacao se existir
    $temDataCriacao = false;
    if ($res = $conn->query("SHOW COLUMNS FROM salao_depoimentos LIKE 'data_criacao'")) {
        $temDataCriacao = $res->num_rows > 0; $res->free();
    }
    if ($temDataCriacao) {
        if ($st = $conn->prepare('SELECT AVG(estrelas) AS m FROM salao_depoimentos WHERE ativo = 1 AND data_criacao BETWEEN ? AND ?')) {
            $st->bind_param('ss', $rangeStart, $rangeEnd);
            if ($st->execute()) { $r = $st->get_result(); $row=$r->fetch_assoc(); $avgSatisf = $row && $row['m'] !== null ? (float)$row['m'] : null; }
            $st->close();
        }
    } else {
        if ($r = $conn->query('SELECT AVG(estrelas) AS m FROM salao_depoimentos WHERE ativo = 1')) {
            $row = $r->fetch_assoc(); $avgSatisf = $row && $row['m'] !== null ? (float)$row['m'] : null; $r->free();
        }
    }
    // Top serviços do período (por agendamentos)
    if ($st = $conn->prepare('SELECT s.nome, COUNT(*) AS q FROM salao_agendamentos a INNER JOIN salao_servicos s ON s.id = a.servico_id WHERE a.data_agendamento BETWEEN ? AND ? AND a.status <> "cancelado" GROUP BY s.id, s.nome ORDER BY q DESC, s.nome ASC LIMIT 7')) {
        $st->bind_param('ss', $rangeStart, $rangeEnd);
        if ($st->execute()) { $r = $st->get_result(); while ($row = $r->fetch_assoc()) { $topServicos[] = $row; } }
        $st->close();
    }
}
?>

<div class="card mb-3">
  <div class="card-body d-flex flex-wrap gap-2 align-items-center">
    <form method="get" class="d-flex align-items-center gap-2">
      <input type="hidden" name="modulo" value="relatorios_estatisticas" />
      <?php
        $clsMensal  = ($view==='mensal')  ? 'btn-outline-success' : 'btn-success';
        $clsSemanal = ($view==='semanal') ? 'btn-outline-success' : 'btn-success';
        $clsDiaria  = ($view==='diaria')  ? 'btn-outline-success' : 'btn-success';
      ?>
      <div class="btn-group btn-group-sm" role="group" aria-label="Escolha de período">
        <a class="btn <?php echo $clsMensal; ?>" href="?modulo=relatorios_estatisticas&view=mensal&data=<?php echo urlencode($data); ?>">Mensal</a>
        <a class="btn <?php echo $clsSemanal; ?>" href="?modulo=relatorios_estatisticas&view=semanal&data=<?php echo urlencode($data); ?>">Semanal</a>
        <a class="btn <?php echo $clsDiaria; ?>" href="?modulo=relatorios_estatisticas&view=diaria&data=<?php echo urlencode($data); ?>">Diária</a>
      </div>

      <div class="ms-auto d-flex align-items-center gap-2">
        <div class="input-group input-group-sm" style="width: 210px;">
            <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
            <input type="date" class="form-control" id="dataPicker" value="<?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?>" />
        </div>
        <div class="btn-group btn-group-sm">
            <a class="btn btn-outline-success" href="?modulo=relatorios_estatisticas&view=<?php echo $view; ?>&data=<?php echo urlencode($prevData); ?>">Anterior</a>
            <a class="btn btn-success" href="?modulo=relatorios_estatisticas&view=<?php echo $view; ?>&data=<?php echo urlencode($hoje); ?>">Hoje</a>
            <a class="btn btn-outline-success" href="?modulo=relatorios_estatisticas&view=<?php echo $view; ?>&data=<?php echo urlencode($nextData); ?>">Próximo</a>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="row mb-4">
  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-graph-up text-success me-2"></i>
          Performance do Período
        </h5>
        <small class="text-muted"><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></small>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <span>Faturamento</span>
            <span class="fw-semibold text-success"><?php echo brl($fat ?? 0); ?></span>
          </div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <span>Agendamentos</span>
            <span class="fw-semibold text-primary"><?php echo (int)($qtdAg ?? 0); ?></span>
          </div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <span>Concluídos</span>
            <span class="fw-semibold text-success"><?php echo (int)($qtdConcl ?? 0); ?></span>
          </div>
        </div>
        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <span>Cancelados</span>
            <span class="fw-semibold text-danger"><?php echo (int)($qtdCanc ?? 0); ?></span>
          </div>
        </div>
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

  <div class="col-xl-6">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">
          <i class="bi bi-star text-warning me-2"></i>
          Serviços Mais Solicitados (período)
        </h5>
      </div>
      <div class="card-body">
        <?php if (empty($topServicos)): ?>
          <div class="alert alert-light mb-0">Sem dados para o período selecionado.</div>
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
.badge { font-size: 0.75rem; }
.card { transition: all 0.3s ease; }
</style>

<script>
// Navegar ao trocar a data
document.getElementById('dataPicker')?.addEventListener('change', function(){
  const url = new URL(window.location.href);
  url.searchParams.set('modulo','relatorios_estatisticas');
  url.searchParams.set('view','<?php echo $view; ?>');
  url.searchParams.set('data', this.value);
  window.location.href = url.toString();
});
</script>
