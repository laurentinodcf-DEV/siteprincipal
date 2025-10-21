<?php
// Módulo de horários disponíveis
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Configuração de Horários</h4>
            <button class="btn btn-success">
                <i class="bi bi-plus-lg"></i> Novo Horário
            </button>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Profissional</label>
                    <select class="form-select">
                        <option>Todos os profissionais</option>
                        <option>Maria José</option>
                        <option>Ana Paula</option>
                        <option>Carla Silva</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Dia da Semana</label>
                    <select class="form-select">
                        <option>Todos os dias</option>
                        <option>Segunda-feira</option>
                        <option>Terça-feira</option>
                        <option>Quarta-feira</option>
                        <option>Quinta-feira</option>
                        <option>Sexta-feira</option>
                        <option>Sábado</option>
                        <option>Domingo</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select">
                        <option>Todos</option>
                        <option>Disponível</option>
                        <option>Ocupado</option>
                        <option>Bloqueado</option>
                    </select>
                </div>
                
                <button class="btn btn-primary w-100">Aplicar Filtros</button>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Grade de Horários - Semana de 08/01 a 14/01/2024</h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <button class="btn btn-outline-primary">Hoje</button>
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr class="table-light">
                                <th width="100">Horário</th>
                                <th class="text-center">Seg<br><small>08/01</small></th>
                                <th class="text-center">Ter<br><small>09/01</small></th>
                                <th class="text-center">Qua<br><small>10/01</small></th>
                                <th class="text-center">Qui<br><small>11/01</small></th>
                                <th class="text-center">Sex<br><small>12/01</small></th>
                                <th class="text-center">Sáb<br><small>13/01</small></th>
                                <th class="text-center">Dom<br><small>14/01</small></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">08:00</td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Maria - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Maria - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center bg-light">
                                    <span class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">08:30</td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning cursor-pointer" title="Ana - Bloqueado">!</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Ana - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center bg-light">
                                    <span class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">09:00</td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Carla - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Carla - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Carla - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Carla - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Carla - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Carla - Disponível">✓</span>
                                </td>
                                <td class="text-center bg-light">
                                    <span class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">09:30</td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Maria - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Maria - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning cursor-pointer" title="Maria - Bloqueado">!</span>
                                </td>
                                <td class="text-center bg-light">
                                    <span class="text-muted">-</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold">10:00</td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger cursor-pointer" title="Ana - Ocupado">✗</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success cursor-pointer" title="Ana - Disponível">✓</span>
                                </td>
                                <td class="text-center bg-light">
                                    <span class="text-muted">-</span>
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
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Configurações de Funcionamento</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Horários Padrão</h6>
                        <div class="mb-3">
                            <label class="form-label">Horário de Abertura</label>
                            <input type="time" class="form-control" value="08:00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Horário de Fechamento</label>
                            <input type="time" class="form-control" value="18:00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Intervalo entre Agendamentos</label>
                            <select class="form-select">
                                <option>15 minutos</option>
                                <option selected>30 minutos</option>
                                <option>45 minutos</option>
                                <option>60 minutos</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Dias de Funcionamento</h6>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="segunda" checked>
                            <label class="form-check-label" for="segunda">Segunda-feira</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="terca" checked>
                            <label class="form-check-label" for="terca">Terça-feira</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="quarta" checked>
                            <label class="form-check-label" for="quarta">Quarta-feira</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="quinta" checked>
                            <label class="form-check-label" for="quinta">Quinta-feira</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="sexta" checked>
                            <label class="form-check-label" for="sexta">Sexta-feira</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="sabado" checked>
                            <label class="form-check-label" for="sabado">Sábado</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="domingo">
                            <label class="form-check-label" for="domingo">Domingo</label>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-3">
                    <button class="btn btn-outline-secondary me-2">Cancelar</button>
                    <button class="btn btn-success">Salvar Configurações</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Legenda</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <span class="badge bg-success me-2">✓</span> Horário disponível
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-danger me-2">✗</span> Horário ocupado
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-warning me-2">!</span> Horário bloqueado
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted me-2">-</span> Não funcionamos
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cursor-pointer {
    cursor: pointer;
}

.badge:hover {
    transform: scale(1.1);
    transition: transform 0.2s;
}
</style>