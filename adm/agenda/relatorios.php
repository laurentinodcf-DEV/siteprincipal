<?php
// Módulo de relatórios da agenda
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Relatórios e Estatísticas</h4>
            <div>
                <button class="btn btn-outline-success me-2">
                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                </button>
                <button class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Filtros de Relatório</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Período</label>
                        <select class="form-select">
                            <option>Últimos 7 dias</option>
                            <option>Últimos 30 dias</option>
                            <option>Este mês</option>
                            <option>Mês anterior</option>
                            <option>Personalizado</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Inicial</label>
                        <input type="date" class="form-control" value="2024-01-01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Final</label>
                        <input type="date" class="form-control" value="2024-01-31">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Profissional</label>
                        <select class="form-select">
                            <option>Todos</option>
                            <option>Maria José</option>
                            <option>Ana Paula</option>
                            <option>Carla Silva</option>
                        </select>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button class="btn btn-primary">Gerar Relatório</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total de Agendamentos</h6>
                        <h3 class="mb-0">245</h3>
                        <small>Este mês</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-calendar-check fs-2"></i>
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
                        <h6 class="card-title">Confirmados</h6>
                        <h3 class="mb-0">198</h3>
                        <small>80.8% do total</small>
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
                        <h6 class="card-title">Cancelamentos</h6>
                        <h3 class="mb-0">32</h3>
                        <small>13.1% do total</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-x-circle fs-2"></i>
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
                        <h6 class="card-title">Receita Total</h6>
                        <h3 class="mb-0">R$ 12.450</h3>
                        <small>Este mês</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-currency-dollar fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Agendamentos por Profissional</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Profissional</th>
                                <th class="text-center">Agendamentos</th>
                                <th class="text-center">Receita</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Maria José</td>
                                <td class="text-center">89</td>
                                <td class="text-center">R$ 4.890</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: 36%">36%</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Ana Paula</td>
                                <td class="text-center">82</td>
                                <td class="text-center">R$ 4.320</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" style="width: 33%">33%</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Carla Silva</td>
                                <td class="text-center">74</td>
                                <td class="text-center">R$ 3.240</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" style="width: 30%">30%</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Serviços Mais Solicitados</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th class="text-center">Quantidade</th>
                                <th class="text-center">Receita</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Corte de Cabelo</td>
                                <td class="text-center">95</td>
                                <td class="text-center">R$ 3.800</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-primary" style="width: 39%">39%</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Escova</td>
                                <td class="text-center">67</td>
                                <td class="text-center">R$ 2.010</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: 27%">27%</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Manicure</td>
                                <td class="text-center">54</td>
                                <td class="text-center">R$ 1.350</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" style="width: 22%">22%</div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Pedicure</td>
                                <td class="text-center">43</td>
                                <td class="text-center">R$ 1.290</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" style="width: 18%">18%</div>
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

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Relatório Detalhado de Agendamentos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr class="table-light">
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Profissional</th>
                                <th>Serviços</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Forma Pagto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>11/01/2024</td>
                                <td>08:00</td>
                                <td>Maria da Silva</td>
                                <td>Ana Paula</td>
                                <td>Corte, Escova</td>
                                <td>R$ 70,00</td>
                                <td><span class="badge bg-success">Concluído</span></td>
                                <td>Cartão</td>
                            </tr>
                            <tr>
                                <td>11/01/2024</td>
                                <td>09:30</td>
                                <td>João Santos</td>
                                <td>Maria José</td>
                                <td>Corte Masculino</td>
                                <td>R$ 35,00</td>
                                <td><span class="badge bg-success">Concluído</span></td>
                                <td>Dinheiro</td>
                            </tr>
                            <tr>
                                <td>11/01/2024</td>
                                <td>11:00</td>
                                <td>Carla Oliveira</td>
                                <td>Ana Paula</td>
                                <td>Manicure, Pedicure</td>
                                <td>R$ 50,00</td>
                                <td><span class="badge bg-warning">Agendado</span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>11/01/2024</td>
                                <td>14:30</td>
                                <td>Patricia Costa</td>
                                <td>Carla Silva</td>
                                <td>Química, Escova</td>
                                <td>R$ 120,00</td>
                                <td><span class="badge bg-warning">Agendado</span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>11/01/2024</td>
                                <td>16:00</td>
                                <td>Fernanda Lima</td>
                                <td>Maria José</td>
                                <td>Corte, Hidratação</td>
                                <td>R$ 85,00</td>
                                <td><span class="badge bg-warning">Agendado</span></td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <nav aria-label="Navegação da página">
                    <ul class="pagination justify-content-center mt-3 mb-0">
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

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Horários Mais Procurados</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>09:00 - 10:00</span>
                        <span>45 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-primary" style="width: 90%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>14:00 - 15:00</span>
                        <span>38 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 76%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>10:00 - 11:00</span>
                        <span>32 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-info" style="width: 64%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>15:00 - 16:00</span>
                        <span>28 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: 56%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Dias da Semana Mais Movimentados</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Sexta-feira</span>
                        <span>52 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Sábado</span>
                        <span>48 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" style="width: 92%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Quinta-feira</span>
                        <span>41 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-info" style="width: 79%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <span>Terça-feira</span>
                        <span>35 agendamentos</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-warning" style="width: 67%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>