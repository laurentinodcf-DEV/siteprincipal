<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Serviços - Salome Beleza</title>
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/estilo.css">

  <style>
    .secao {
      padding: 80px 0;
      color: #fff;
    }
    .secao h2 {
      font-size: 2.5rem;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .secao video {
      max-width: 100%;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
    .secao p {
      font-size: 1.2rem;
    }
    .secao-alisamentos { background: linear-gradient(135deg, #d4af37, #c0a060); }
    .secao-escovas { background: linear-gradient(135deg, #e6c87f, #d4af37); }
    .secao-sobrancelhas { background: linear-gradient(135deg, #bfa46b, #d4af37); }

    .secao-profissional {
      background: #f9f9f9;
      text-align: center;
      padding: 60px 0;
      color: #333;
    }
    .social-icons a {
      margin: 0 10px;
      font-size: 1.8rem;
      color: #d4af37;
      transition: 0.3s;
    }
    .social-icons a:hover {
      color: #a67c00;
    }
  </style>
</head>
<body>
<?php
include '../class/menu.php';
?>

<!-- Seção Alisamentos -->
<section class="secao secao-alisamentos" id="alisamentos">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <video controls>
          <source src="../videos/alisamento.mp4" type="video/mp4">
        </video>
      </div>
      <div class="col-md-6">
        <h2>Alisamentos</h2>
        <p>Transforme seus cabelos com técnicas modernas de alisamento que preservam a saúde e o brilho dos fios.</p>
      </div>
    </div>
  </div>
</section>

<!-- Seção Escovas -->
<section class="secao secao-escovas" id="escovas">
  <div class="container">
    <div class="row align-items-center flex-row-reverse">
      <div class="col-md-6">
        <video controls>
          <source src="../videos/escova.mp4" type="video/mp4">
        </video>
      </div>
      <div class="col-md-6">
        <h2>Escovas</h2>
        <p>Escovas especiais para cada tipo de cabelo, garantindo movimento natural e um visual elegante.</p>
      </div>
    </div>
  </div>
</section>

<!-- Seção Sobrancelhas -->
<section class="secao secao-sobrancelhas" id="sobrancelhas">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <video controls>
          <source src="../videos/sobrancelha.mp4" type="video/mp4">
        </video>
      </div>
      <div class="col-md-6">
        <h2>Sobrancelhas</h2>
        <p>Realce sua beleza com design de sobrancelhas personalizado e técnicas de longa duração.</p>
      </div>
    </div>
  </div>
</section>


<!-- Seção Contato -->
<?php include '../class/contatoFooter.php'; ?>
