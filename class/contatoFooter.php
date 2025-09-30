<?php
// class/contatoFooter.php
?>

<!-- Seção Contato -->
<section class="secao-contato">
  <div class="container">
    <div class="row">
      
      <!-- Coluna Contato -->
      <div class="col-md-4">
        <h3>Contato</h3>
        <p>Entre em contato conosco para agendamentos.</p>
        <div class="social-icons">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-tiktok"></i></a>
          <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

      <!-- Coluna Siga-nos -->
      <div class="col-md-4">
        <h5>SIGA-NOS</h5>
        <p>(11) 98765-4321<br>
        contato@studiosalome.com</p>
      </div>

      <!-- Coluna Localização/Formulário -->
      <div class="col-md-4">
        <h5>LOCALIZAÇÃO</h5>
        <form>
          <div class="mb-3">
            <input type="text" class="form-control" placeholder="Digite seu nome completo">
          </div>
          <button type="submit" class="btn btn-dourado">Enviar solicitação</button>
        </form>
      </div>

    </div>
  </div>
</section>

<style>
.secao-contato {
  background: #211c19;
  color: #fff;
  padding: 60px 0;
}

.secao-contato h3, 
.secao-contato h5 {
  font-weight: bold;
  margin-bottom: 15px;
}

.secao-contato p {
  color: #ddd;
}

.secao-contato .social-icons a {
  margin: 0 10px;
  font-size: 1.6rem;
  color: #fff;
  transition: 0.3s;
}

.secao-contato .social-icons a:hover {
  color: #d4af37;
}

.btn-dourado {
  background: #bfa46b;
  color: #fff;
  border-radius: 30px;
  padding: 10px 25px;
  border: none;
  transition: 0.3s;
}

.btn-dourado:hover {
  background: #a67c00;
  color: #fff;
}
</style>
