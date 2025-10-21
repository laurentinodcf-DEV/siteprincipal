<?php
// Módulo de calendário
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Calendário de Agendamentos</h4>
            <div>
                <button class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Anterior
                </button>
                <button class="btn btn-success me-2">
                    <i class="bi bi-plus-lg"></i> Novo Agendamento
                </button>
                <button class="btn btn-outline-secondary">
                    Próximo <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Janeiro 2024</h5>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="btnradio" id="btnradio1" checked>
                        <label class="btn btn-outline-primary" for="btnradio1">Mês</label>

                        <input type="radio" class="btn-check" name="btnradio" id="btnradio2">
                        <label class="btn btn-outline-primary" for="btnradio2">Semana</label>

                        <input type="radio" class="btn-check" name="btnradio" id="btnradio3">
                        <label class="btn btn-outline-primary" for="btnradio3">Dia</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered calendar-table">
                        <thead>
                            <tr class="table-light">
                                <th class="text-center">Dom</th>
                                <th class="text-center">Seg</th>
                                <th class="text-center">Ter</th>
                                <th class="text-center">Qua</th>
                                <th class="text-center">Qui</th>
                                <th class="text-center">Sex</th>
                                <th class="text-center">Sáb</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-muted text-center p-3" style="height: 120px;">31</td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">1</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">3 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">2</div>
                                    <div class="small">
                                        <span class="badge bg-warning mb-1">1 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">3</div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">4</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">2 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">5</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">4 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">6</div>
                                    <div class="small">
                                        <span class="badge bg-info mb-1">1 agend.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">7</div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">8</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">3 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">9</div>
                                    <div class="small">
                                        <span class="badge bg-warning mb-1">2 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">10</div>
                                </td>
                                <td class="text-center p-2 bg-light" style="height: 120px;">
                                    <div class="fw-bold text-primary">11</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">5 agend.</span>
                                        <div class="text-muted">HOJE</div>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">12</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">3 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">13</div>
                                    <div class="small">
                                        <span class="badge bg-info mb-1">1 agend.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">14</div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">15</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">2 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">16</div>
                                    <div class="small">
                                        <span class="badge bg-warning mb-1">1 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">17</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">4 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">18</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">3 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">19</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">6 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">20</div>
                                    <div class="small">
                                        <span class="badge bg-info mb-1">2 agend.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">21</div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">22</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">4 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">23</div>
                                    <div class="small">
                                        <span class="badge bg-warning mb-1">2 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">24</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">1 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">25</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">5 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">26</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">7 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">27</div>
                                    <div class="small">
                                        <span class="badge bg-info mb-1">3 agend.</span>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">28</div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">29</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">2 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">30</div>
                                    <div class="small">
                                        <span class="badge bg-warning mb-1">1 agend.</span>
                                    </div>
                                </td>
                                <td class="text-center p-2" style="height: 120px;">
                                    <div class="fw-bold">31</div>
                                    <div class="small">
                                        <span class="badge bg-success mb-1">3 agend.</span>
                                    </div>
                                </td>
                                <td class="text-muted text-center p-3" style="height: 120px;">1</td>
                                <td class="text-muted text-center p-3" style="height: 120px;">2</td>
                                <td class="text-muted text-center p-3" style="height: 120px;">3</td>
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
                <h5 class="mb-0">Legenda</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <span class="badge bg-success me-2">Verde</span> Dias com agendamentos confirmados
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-warning me-2">Amarelo</span> Dias com agendamentos pendentes
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-info me-2">Azul</span> Fins de semana com agendamentos
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-light text-dark me-2">Cinza</span> Dia atual
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-table td {
    cursor: pointer;
    transition: background-color 0.2s;
}

.calendar-table td:hover {
    background-color: #f8f9fa;
}

.calendar-table .bg-light {
    border: 2px solid #007bff !important;
}
</style>