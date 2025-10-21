<?php
// Visão geral do sistema de agenda
require '../../conexao.php';

// Buscar dados para o dashboard
$agendamentosHoje = 0;
$agendamentosAmanha = 0;
$agendamentosSemana = 0;
$clientesAtivos = 0;

// Aqui futuramente faremos as consultas ao banco de dados
// Por enquanto, valores de exemplo
$agendamentosHoje = 8;
$agendamentosAmanha = 12;
$agendamentosSemana = 45;
$clientesAtivos = 127;

?>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="status-card">
            <h3><?php echo $agendamentosHoje; ?></h3>
            <p>Agendamentos Hoje</p>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="status-card" style="border-left-color: #17a2b8;">
            <h3 style="color: #17a2b8;"><?php echo $agendamentosAmanha; ?></h3>
            <p>Agendamentos Amanhã</p>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="status-card" style="border-left-color: #ffc107;">
            <h3 style="color: #ffc107;"><?php echo $agendamentosSemana; ?></h3>
            <p>Esta Semana</p>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="status-card" style="border-left-color: #6f42c1;">
            <h3 style="color: #6f42c1;"><?php echo $clientesAtivos; ?></h3>
            <p>Clientes Ativos</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Próximos Agendamentos</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Maria Silva</h6>
                            <p class="text-muted mb-0">Corte + Escova • 09:00</p>
                        </div>
                        <span class="badge bg-success rounded-pill">Hoje</span>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Ana Santos</h6>
                            <p class="text-muted mb-0">Manicure • 10:30</p>
                        </div>
                        <span class="badge bg-success rounded-pill">Hoje</span>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Carla Ferreira</h6>
                            <p class="text-muted mb-0">Coloração • 14:00</p>
                        </div>
                        <span class="badge bg-info rounded-pill">Amanhã</span>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Juliana Costa</h6>
                            <p class="text-muted mb-0">Tratamento • 15:30</p>
                        </div>
                        <span class="badge bg-info rounded-pill">Amanhã</span>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="?module=calendario" class="btn btn-outline-primary">Ver Calendário Completo</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?module=novo_agendamento" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Novo Agendamento
                    </a>
                    <a href="?module=agendamentos_hoje" class="btn btn-primary">
                        <i class="bi bi-calendar-day"></i> Ver Hoje
                    </a>
                    <a href="?module=clientes_agenda" class="btn btn-info">
                        <i class="bi bi-people"></i> Buscar Cliente
                    </a>
                    <a href="?module=relatorios" class="btn btn-warning">
                        <i class="bi bi-graph-up"></i> Relatórios
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Status do Sistema</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Profissionais Ativos</span>
                    <span class="badge bg-success">3</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Serviços Disponíveis</span>
                    <span class="badge bg-info">15</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Horários Configurados</span>
                    <span class="badge bg-warning">8h às 18h</span>
                </div>
            </div>
        </div>
    </div>
</div>