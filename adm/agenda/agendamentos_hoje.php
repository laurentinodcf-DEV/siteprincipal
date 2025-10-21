<?php
// Módulo de agendamentos de hoje
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Agendamentos de Hoje - <?php echo date('d/m/Y'); ?></h4>
            <button class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Novo Agendamento
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Profissional</th>
                                <th>Serviços</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-primary">08:00</span></td>
                                <td>Maria da Silva</td>
                                <td>Ana Paula</td>
                                <td>Corte, Escova</td>
                                <td><span class="badge bg-warning">Agendado</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Confirmar">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Cancelar">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">09:30</span></td>
                                <td>João Santos</td>
                                <td>Maria José</td>
                                <td>Corte Masculino</td>
                                <td><span class="badge bg-success">Confirmado</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Iniciar">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Cancelar">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">11:00</span></td>
                                <td>Carla Oliveira</td>
                                <td>Ana Paula</td>
                                <td>Manicure, Pedicure</td>
                                <td><span class="badge bg-info">Em Andamento</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Finalizar">
                                            <i class="bi bi-check2-all"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">14:30</span></td>
                                <td>Patricia Costa</td>
                                <td>Carla Silva</td>
                                <td>Química, Escova</td>
                                <td><span class="badge bg-warning">Agendado</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Confirmar">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Cancelar">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-primary">16:00</span></td>
                                <td>Fernanda Lima</td>
                                <td>Maria José</td>
                                <td>Corte, Hidratação</td>
                                <td><span class="badge bg-success">Confirmado</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Iniciar">
                                            <i class="bi bi-play-fill"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" title="Cancelar">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Confirmados</h6>
                        <h3 class="mb-0">2</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Agendados</h6>
                        <h3 class="mb-0">2</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Em Andamento</h6>
                        <h3 class="mb-0">1</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-play-circle fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Hoje</h6>
                        <h3 class="mb-0">5</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-day fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>