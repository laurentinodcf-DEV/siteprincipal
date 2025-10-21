<?php
// Dashboard Principal - Painel de Controle da Agenda Profissional
?>

<!-- Resumo Executivo -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card text-bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Agendamentos Hoje</h6>
                        <h2 class="mb-0">12</h2>
                        <small class="opacity-75">+3 desde ontem</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-day fs-1 opacity-75"></i>
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
                        <h2 class="mb-0">R$ 890</h2>
                        <small class="opacity-75">Meta: R$ 1.200</small>
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
                        <h2 class="mb-0">3</h2>
                        <small class="opacity-75">Este mês: 28</small>
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
                        <h2 class="mb-0">85%</h2>
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
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <button class="btn btn-primary">Hoje</button>
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="timeline-container" style="max-height: 500px; overflow-y: auto;">
                    <div class="timeline-item d-flex align-items-center p-3 border-bottom">
                        <div class="time-label text-center me-3" style="min-width: 60px;">
                            <strong class="text-primary">08:00</strong>
                        </div>
                        <div class="appointment-info flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Maria da Silva</h6>
                                    <small class="text-muted">Corte + Escova • Ana Paula</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">Confirmado</span>
                                    <div class="mt-1">
                                        <small class="text-success fw-bold">R$ 85,00</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="timeline-item d-flex align-items-center p-3 border-bottom">
                        <div class="time-label text-center me-3" style="min-width: 60px;">
                            <strong class="text-warning">09:30</strong>
                        </div>
                        <div class="appointment-info flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">João Santos</h6>
                                    <small class="text-muted">Corte Masculino • Maria José</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning">Agendado</span>
                                    <div class="mt-1">
                                        <small class="text-success fw-bold">R$ 45,00</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="timeline-item d-flex align-items-center p-3 border-bottom">
                        <div class="time-label text-center me-3" style="min-width: 60px;">
                            <strong class="text-info">11:00</strong>
                        </div>
                        <div class="appointment-info flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Carla Oliveira</h6>
                                    <small class="text-muted">Manicure + Pedicure • Ana Paula</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info">Em Andamento</span>
                                    <div class="mt-1">
                                        <small class="text-success fw-bold">R$ 60,00</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="timeline-item d-flex align-items-center p-3 border-bottom">
                        <div class="time-label text-center me-3" style="min-width: 60px;">
                            <strong class="text-primary">14:30</strong>
                        </div>
                        <div class="appointment-info flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Patricia Costa</h6>
                                    <small class="text-muted">Química + Escova • Carla Silva</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">Confirmado</span>
                                    <div class="mt-1">
                                        <small class="text-success fw-bold">R$ 150,00</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="timeline-item d-flex align-items-center p-3">
                        <div class="time-label text-center me-3" style="min-width: 60px;">
                            <strong class="text-primary">16:00</strong>
                        </div>
                        <div class="appointment-info flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">Fernanda Lima</h6>
                                    <small class="text-muted">Corte + Hidratação • Maria José</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success">Confirmado</span>
                                    <div class="mt-1">
                                        <small class="text-success fw-bold">R$ 95,00</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button class="btn btn-success">
                        <i class="bi bi-plus-lg me-1"></i> Novo Agendamento
                    </button>
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-calendar3 me-1"></i> Ver Calendário
                    </button>
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
                <div class="alert alert-warning alert-sm d-flex align-items-center mb-2">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <small>3 horários vagos hoje após 17h</small>
                </div>
                <div class="alert alert-info alert-sm d-flex align-items-center mb-2">
                    <i class="bi bi-person-check me-2"></i>
                    <small>2 clientes VIP agendados amanhã</small>
                </div>
                <div class="alert alert-success alert-sm d-flex align-items-center mb-0">
                    <i class="bi bi-graph-up me-2"></i>
                    <small>Meta mensal 73% atingida</small>
                </div>
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
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-circle me-2"></i> Novo Agendamento
                    </button>
                    <button class="btn btn-outline-success btn-sm">
                        <i class="bi bi-whatsapp me-2"></i> Enviar Lembretes
                    </button>
                    <button class="btn btn-outline-info btn-sm">
                        <i class="bi bi-people me-2"></i> Cadastrar Cliente
                    </button>
                    <button class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-clock me-2"></i> Bloquear Horário
                    </button>
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
                    <div class="col-md-4 mb-3">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">Ana Paula</h6>
                                <small>Cabeleireira</small>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <strong class="text-primary">15</strong>
                                        <div class="small text-muted">Agendamentos</div>
                                    </div>
                                    <div class="col-6">
                                        <strong class="text-success">R$ 1.280</strong>
                                        <div class="small text-muted">Faturamento</div>
                                    </div>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-primary" style="width: 75%">75%</div>
                                </div>
                                <small class="text-muted">Ocupação da semana</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">Maria José</h6>
                                <small>Cabeleireira</small>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <strong class="text-primary">12</strong>
                                        <div class="small text-muted">Agendamentos</div>
                                    </div>
                                    <div class="col-6">
                                        <strong class="text-success">R$ 950</strong>
                                        <div class="small text-muted">Faturamento</div>
                                    </div>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" style="width: 60%">60%</div>
                                </div>
                                <small class="text-muted">Ocupação da semana</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">Carla Silva</h6>
                                <small>Manicure</small>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <strong class="text-primary">18</strong>
                                        <div class="small text-muted">Agendamentos</div>
                                    </div>
                                    <div class="col-6">
                                        <strong class="text-success">R$ 720</strong>
                                        <div class="small text-muted">Faturamento</div>
                                    </div>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" style="width: 90%">90%</div>
                                </div>
                                <small class="text-muted">Ocupação da semana</small>
                            </div>
                        </div>
                    </div>
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
                    Performance do Mês
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Meta de Faturamento</span>
                        <span>R$ 18.500 / R$ 25.000</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 74%">74%</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Meta de Agendamentos</span>
                        <span>185 / 250</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-primary" style="width: 74%">74%</div>
                    </div>
                </div>
                
                <div class="mb-0">
                    <div class="d-flex justify-content-between">
                        <span>Satisfação do Cliente</span>
                        <span>4.8 / 5.0</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: 96%">96%</div>
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
                    Serviços Mais Solicitados
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Corte de Cabelo</span>
                        <span class="badge bg-primary">45</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Manicure</span>
                        <span class="badge bg-success">38</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Escova</span>
                        <span class="badge bg-info">32</span>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Pedicure</span>
                        <span class="badge bg-warning">28</span>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between">
                        <span>Hidratação</span>
                        <span class="badge bg-secondary">22</span>
                    </div>
                </div>
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