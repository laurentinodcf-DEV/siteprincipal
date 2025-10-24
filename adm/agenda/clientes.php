<?php
// Módulo de clientes da agenda
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Clientes</h4>
            <button class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Novo Cliente
            </button>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Buscar cliente...">
            <button class="btn btn-outline-secondary" type="button">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>
    <div class="col-md-3">
        <select class="form-select">
            <option>Todos os status</option>
            <option>Ativo</option>
            <option>Inativo</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select">
            <option>Ordenar por nome</option>
            <option>Último agendamento</option>
            <option>Data de cadastro</option>
        </select>
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
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th>Último Agendamento</th>
                                <th>Total de Agendamentos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-3">MS</div>
                                        <div>
                                            <div class="fw-bold">Maria da Silva</div>
                                            <small class="text-muted">Cliente desde 15/03/2023</small>
                                        </div>
                                    </div>
                                </td>
                                <td>(11) 99999-1234</td>
                                <td>maria@email.com</td>
                                <td>
                                    <div>10/01/2024</div>
                                    <small class="text-muted">Corte, Escova</small>
                                </td>
                                <td><span class="badge bg-info">15</span></td>
                                <td><span class="badge bg-success">Ativo</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Novo Agendamento">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Histórico">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-success text-white me-3">JS</div>
                                        <div>
                                            <div class="fw-bold">João Santos</div>
                                            <small class="text-muted">Cliente desde 28/06/2023</small>
                                        </div>
                                    </div>
                                </td>
                                <td>(11) 88888-5678</td>
                                <td>joao@email.com</td>
                                <td>
                                    <div>09/01/2024</div>
                                    <small class="text-muted">Corte Masculino</small>
                                </td>
                                <td><span class="badge bg-info">8</span></td>
                                <td><span class="badge bg-success">Ativo</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Novo Agendamento">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Histórico">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-warning text-white me-3">CO</div>
                                        <div>
                                            <div class="fw-bold">Carla Oliveira</div>
                                            <small class="text-muted">Cliente desde 12/09/2023</small>
                                        </div>
                                    </div>
                                </td>
                                <td>(11) 77777-9101</td>
                                <td>carla@email.com</td>
                                <td>
                                    <div>08/01/2024</div>
                                    <small class="text-muted">Manicure, Pedicure</small>
                                </td>
                                <td><span class="badge bg-info">12</span></td>
                                <td><span class="badge bg-success">Ativo</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Novo Agendamento">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Histórico">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-danger text-white me-3">PC</div>
                                        <div>
                                            <div class="fw-bold">Patricia Costa</div>
                                            <small class="text-muted">Cliente desde 05/11/2023</small>
                                        </div>
                                    </div>
                                </td>
                                <td>(11) 66666-1213</td>
                                <td>patricia@email.com</td>
                                <td>
                                    <div>05/01/2024</div>
                                    <small class="text-muted">Química, Escova</small>
                                </td>
                                <td><span class="badge bg-info">5</span></td>
                                <td><span class="badge bg-success">Ativo</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Novo Agendamento">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Histórico">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-info text-white me-3">FL</div>
                                        <div>
                                            <div class="fw-bold">Fernanda Lima</div>
                                            <small class="text-muted">Cliente desde 18/12/2023</small>
                                        </div>
                                    </div>
                                </td>
                                <td>(11) 55555-1415</td>
                                <td>fernanda@email.com</td>
                                <td>
                                    <div>03/01/2024</div>
                                    <small class="text-muted">Corte, Hidratação</small>
                                </td>
                                <td><span class="badge bg-info">3</span></td>
                                <td><span class="badge bg-success">Ativo</span></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-success" title="Novo Agendamento">
                                            <i class="bi bi-calendar-plus"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-info" title="Histórico">
                                            <i class="bi bi-clock-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <nav aria-label="Navegação da página">
                    <ul class="pagination justify-content-center mt-3">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1">Anterior</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Próximo</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total de Clientes</h6>
                        <h3 class="mb-0">127</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Clientes Ativos</h6>
                        <h3 class="mb-0">115</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-check fs-2"></i>
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
                        <h6 class="card-title">Novos Este Mês</h6>
                        <h3 class="mb-0">8</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-plus fs-2"></i>
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
                        <h6 class="card-title">Sem Agendamento</h6>
                        <h3 class="mb-0">12</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-person-dash fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>