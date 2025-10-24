<?php
// Calendário de Agendamentos (Mensal / Semanal / Diária)
if (!isset($conn)) {
    $connFile = __DIR__ . '/../../conexao.php';
    if (file_exists($connFile)) { require_once $connFile; }
}

// Timezone consistente
$tz = ini_get('date.timezone');
if (!$tz || !@date_default_timezone_set($tz)) {
    date_default_timezone_set('America/Sao_Paulo');
}

$view = isset($_GET['view']) ? strtolower(trim($_GET['view'])) : 'mensal';
if (!in_array($view, ['mensal','semanal','diaria'])) { $view = 'mensal'; }

$hoje = date('Y-m-d');
$data = isset($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data']) ? $_GET['data'] : $hoje;

$profissionalId = isset($_GET['profissional_id']) && ctype_digit($_GET['profissional_id']) ? (int)$_GET['profissional_id'] : null;

// Carregar lista de profissionais
$profissionais = [];
if (isset($conn) && $conn instanceof mysqli) {
    if ($res = $conn->query('SELECT id, nome FROM salao_profissionais WHERE ativo = 1 ORDER BY nome')) {
        while ($row = $res->fetch_assoc()) { $profissionais[] = $row; }
        $res->free();
    }
}

// Intervalo de busca
switch ($view) {
    case 'semanal':
        $ini = new DateTime($data);
        $dow = (int)$ini->format('N'); // 1=Mon..7=Sun
        $ini->modify('-' . ($dow-1) . ' days');
        $fim = clone $ini; $fim->modify('+6 days');
        $titulo = 'Agenda — Semana de ' . $ini->format('d/m') . ' a ' . $fim->format('d/m') . ' (' . mesPtBr((int)$ini->format('m')) . ')';
        $rangeStart = $ini->format('Y-m-d');
        $rangeEnd = $fim->format('Y-m-d');
        $prevData = (clone $ini)->modify('-7 days')->format('Y-m-d');
        $nextData = (clone $ini)->modify('+7 days')->format('Y-m-d');
        break;
    case 'diaria':
        $ini = new DateTime($data);
        $fim = clone $ini;
        $titulo = 'Agenda — Dia ' . $ini->format('d/m/Y');
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
        $titulo = 'Agenda — ' . mesPtBr((int)$dt->format('m')) . ' / ' . $dt->format('Y');
        $rangeStart = $dt->format('Y-m-d');
        $rangeEnd = $dtEnd->format('Y-m-d');
        $prevData = (clone $dt)->modify('-1 month')->format('Y-m-d');
        $nextData = (clone $dt)->modify('+1 month')->format('Y-m-d');
}

// Buscar agendamentos no intervalo
$agendamentos = [];
if (isset($conn) && $conn instanceof mysqli) {
    $sql = 'SELECT a.*, p.nome AS profissional_nome, s.nome AS servico_nome, COALESCE(a.nome_cliente, c.nome) AS cliente_exib
            FROM salao_agendamentos a
            INNER JOIN salao_profissionais p ON p.id = a.profissional_id
            INNER JOIN salao_servicos s ON s.id = a.servico_id
            LEFT JOIN salao_clientes c ON c.id = a.cliente_id
            WHERE a.data_agendamento BETWEEN ? AND ?';
    $types = 'ss';
    $params = [$rangeStart, $rangeEnd];
    if ($profissionalId) { $sql .= ' AND a.profissional_id = ?'; $types .= 'i'; $params[] = $profissionalId; }
    $sql .= ' ORDER BY a.data_agendamento ASC, a.hora_inicio ASC';
    if ($st = $conn->prepare($sql)) {
        $st->bind_param($types, ...$params);
        if ($st->execute()) {
            $r = $st->get_result();
            while ($row = $r->fetch_assoc()) { $agendamentos[] = $row; }
        }
        $st->close();
    }
}

// Indexar por data para renderização rápida
$porDia = [];
foreach ($agendamentos as $a) {
    $d = $a['data_agendamento'];
    if (!isset($porDia[$d])) { $porDia[$d] = []; }
    $porDia[$d][] = $a;
}

function mesPtBr(int $m): string {
    $n = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    return $n[$m] ?? '';
}

function badgeByStatus(?string $s): string {
    switch ($s) {
        case 'concluido': return 'success';
        case 'em_andamento': return 'warning';
        case 'cancelado': return 'secondary';
        default: return 'primary'; // agendado
    }
}
?>

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <form method="get" class="d-flex align-items-center gap-2">
            <input type="hidden" name="modulo" value="calendario" />
            <select class="form-select form-select-sm" name="profissional_id" onchange="this.form.submit()" style="min-width: 230px;">
                <option value="">Todos os profissionais</option>
                <?php foreach ($profissionais as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" <?php echo ($profissionalId==(int)$p['id'])?'selected':''; ?>><?php echo htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <span class="ms-2 small">
                <span class="badge bg-success">concluído</span>
                <span class="badge bg-warning text-dark">em andamento</span>
                <span class="badge bg-primary">agendado</span>
                <span class="badge bg-secondary">cancelado</span>
            </span>

            <div class="ms-auto d-flex align-items-center gap-2">
                <a class="btn btn-outline-success btn-sm <?php echo $view==='mensal'?'active':''; ?>" href="?modulo=calendario&view=mensal&data=<?php echo urlencode($data); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">Mensal</a>
                <a class="btn btn-outline-success btn-sm <?php echo $view==='semanal'?'active':''; ?>" href="?modulo=calendario&view=semanal&data=<?php echo urlencode($data); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">Semanal</a>
                <a class="btn btn-success btn-sm <?php echo $view==='diaria'?'active':''; ?>" href="?modulo=calendario&view=diaria&data=<?php echo urlencode($data); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">Diária</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <h5 class="mb-0"><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 210px;">
                <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                <input type="date" class="form-control" id="dataPicker" value="<?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
            <div class="btn-group btn-group-sm">
                <a class="btn btn-outline-success" href="?modulo=calendario&view=<?php echo $view; ?>&data=<?php echo urlencode($prevData); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">Anterior</a>
                <a class="btn btn-success" href="?modulo=calendario&view=<?php echo $view; ?>&data=<?php echo urlencode($hoje); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">Hoje</a>
                <a class="btn btn-outline-success" href="?modulo=calendario&view=<?php echo $view; ?>&data=<?php echo urlencode($nextData); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">Próximo</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($view === 'mensal'): ?>
            <?php
                $first = new DateTime($rangeStart);
                $last  = new DateTime($rangeEnd);
                // grid começa no domingo anterior/igual ao primeiro dia
                $startGrid = clone $first;
                while ((int)$startGrid->format('w') !== 0) { $startGrid->modify('-1 day'); }
                // termina no sábado após/igual ao último dia
                $endGrid = clone $last;
                while ((int)$endGrid->format('w') !== 6) { $endGrid->modify('+1 day'); }

                $cursor = clone $startGrid;
            ?>
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <?php foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d): ?>
                                <th class="text-center"><?php echo $d; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($cursor <= $endGrid): ?>
                            <tr>
                                <?php for ($i=0;$i<7;$i++): ?>
                                    <?php $d = $cursor->format('Y-m-d'); $isCurMonth = ($cursor->format('m') === $first->format('m')); ?>
                                    <td class="p-2" style="vertical-align: top; background: <?php echo $isCurMonth?'#ffffff':'#f8f9fa'; ?>;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong class="small <?php echo $d===$hoje?'text-success':''; ?>"><?php echo $cursor->format('j'); ?></strong>
                                            <a class="badge bg-success text-decoration-none" href="?modulo=agendamento_inteligente&data=<?php echo urlencode($d); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">+ Agendar</a>
                                        </div>
                                        <?php if (!empty($porDia[$d])): ?>
                                            <?php foreach ($porDia[$d] as $ev): ?>
                                                <div class="mb-1 p-1 rounded border border-1">
                                                    <span class="badge bg-<?php echo badgeByStatus($ev['status']); ?> me-1">
                                                        <?php echo htmlspecialchars(substr($ev['hora_inicio'],0,5), ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                    <small>
                                                        <?php echo htmlspecialchars($ev['servico_nome'] . ' • ' . ($ev['profissional_nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <?php $cursor->modify('+1 day'); ?>
                                <?php endfor; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($view === 'semanal'): ?>
            <?php
                $dias = [];
                $d = new DateTime($rangeStart);
                for ($i=0;$i<7;$i++){ $dias[] = clone $d; $d->modify('+1 day'); }
                $horas = [];
                for ($h=8;$h<=19;$h++){ $horas[] = sprintf('%02d:00:00', $h); }
            ?>
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;"></th>
                            <?php foreach ($dias as $d): ?>
                                <th class="text-center"><?php echo $d->format('D d/m'); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas as $h): ?>
                            <tr>
                                <td class="text-end pe-2"><small><?php echo substr($h,0,5); ?></small></td>
                                <?php foreach ($dias as $d): ?>
                                    <?php $key = $d->format('Y-m-d'); ?>
                                    <td style="min-height:48px;">
                                        <?php if (!empty($porDia[$key])): ?>
                                            <?php foreach ($porDia[$key] as $ev): ?>
                                                <?php if (substr($ev['hora_inicio'],0,5) === substr($h,0,5)): ?>
                                                    <div class="mb-1 p-1 rounded border">
                                                        <span class="badge bg-<?php echo badgeByStatus($ev['status']); ?> me-1"><?php echo substr($ev['hora_inicio'],0,5); ?></span>
                                                        <small><?php echo htmlspecialchars($ev['servico_nome'] . ' • ' . ($ev['profissional_nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php
                $horas = [];
                for ($h=8;$h<=20;$h++){ $horas[] = sprintf('%02d:00:00', $h); }
            ?>
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start" colspan="2"><?php echo (new DateTime($data))->format('D d/m'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas as $h): ?>
                            <tr>
                                <td style="width: 80px;" class="text-end pe-2"><small><?php echo substr($h,0,5); ?></small></td>
                                <td>
                                    <?php if (!empty($porDia[$data])): ?>
                                        <?php foreach ($porDia[$data] as $ev): ?>
                                            <?php if (substr($ev['hora_inicio'],0,5) === substr($h,0,5)): ?>
                                                <div class="mb-1 p-1 rounded border">
                                                    <span class="badge bg-<?php echo badgeByStatus($ev['status']); ?> me-1"><?php echo substr($ev['hora_inicio'],0,5); ?></span>
                                                    <small><?php echo htmlspecialchars($ev['servico_nome'] . ' • ' . ($ev['profissional_nome'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
        <a class="btn btn-success" href="?modulo=agendamento_inteligente&data=<?php echo urlencode($data); ?><?php echo $profissionalId?('&profissional_id='.$profissionalId):''; ?>">
            <i class="bi bi-plus-lg me-1"></i> Novo Agendamento
        </a>
    </div>
</div>

<style>
/* Ajustes visuais usando a paleta do sistema (verde) */
.table thead th { font-weight: 600; }
.table-bordered > :not(caption) > * > * { border-color: #e2e8f0; }
.badge.bg-primary { background-color: #0d6efd !important; }
.badge.bg-success { background-color: #28a745 !important; }
.badge.bg-warning { background-color: #ffc107 !important; }
.badge.bg-secondary { background-color: #6c757d !important; }
</style>

<script>
// Navegar ao trocar a data
document.getElementById('dataPicker')?.addEventListener('change', function(){
    const url = new URL(window.location.href);
    url.searchParams.set('modulo','calendario');
    url.searchParams.set('view','<?php echo $view; ?>');
    url.searchParams.set('data', this.value);
    <?php if ($profissionalId): ?>
    url.searchParams.set('profissional_id','<?php echo (int)$profissionalId; ?>');
    <?php endif; ?>
    window.location.href = url.toString();
});
</script>
