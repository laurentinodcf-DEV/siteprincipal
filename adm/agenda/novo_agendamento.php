<?php
// Módulo de novo agendamento
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Criar Novo Agendamento</h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Buscar cliente...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Horário</label>
                            <select class="form-select">
                                <option>08:00</option>
                                <option>08:30</option>
                                <option>09:00</option>
                                <option>09:30</option>
                                <option>10:00</option>
                                <option>10:30</option>
                                <option>11:00</option>
                                <option>11:30</option>
                                <option>14:00</option>
                                <option>14:30</option>
                                <option>15:00</option>
                                <option>15:30</option>
                                <option>16:00</option>
                                <option>16:30</option>
                                <option>17:00</option>
                                <option>17:30</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Profissional</label>
                            <select class="form-select">
                                <option>Selecione o profissional...</option>
                                <option>Maria José</option>
                                <option>Ana Paula</option>
                                <option>Carla Silva</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Serviços</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="servico1">
                                        <label class="form-check-label" for="servico1">
                                            Corte de Cabelo
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="servico2">
                                        <label class="form-check-label" for="servico2">
                                            Escova
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="servico3">
                                        <label class="form-check-label" for="servico3">
                                            Manicure
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" rows="3" placeholder="Observações sobre o agendamento..."></textarea>
                        </div>
                    </div>
                    
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-outline-secondary me-2">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Agendamento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Resumo</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Selecione um cliente e serviços para ver o resumo do agendamento.
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Horários Disponíveis</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-1">
                    <button type="button" class="btn btn-outline-success btn-sm">08:00</button>
                    <button type="button" class="btn btn-outline-success btn-sm">08:30</button>
                    <button type="button" class="btn btn-outline-success btn-sm">09:00</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" disabled>09:30 - Ocupado</button>
                    <button type="button" class="btn btn-outline-success btn-sm">10:00</button>
                </div>
            </div>
        </div>
    </div>
</div>