<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Produtos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 32px;
            font-family: "Segoe UI", "Roboto", sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        .placeholder {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            padding: 36px;
            border: 1px solid rgba(203, 213, 225, 0.9);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
            text-align: center;
        }

        .placeholder h2 {
            margin: 0 0 16px;
            font-size: 1.8rem;
        }

        .placeholder p {
            margin: 0 0 20px;
            color: #475569;
            font-size: 1rem;
        }

        .placeholder ul {
            list-style: none;
            padding: 0;
            margin: 0 0 24px;
            display: grid;
            gap: 10px;
        }

        .placeholder li {
            background: #eff6ff;
            border-radius: 12px;
            padding: 12px 16px;
            color: #1d4ed8;
            font-weight: 500;
        }

        .placeholder a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .placeholder a:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="placeholder">
        <h2>Gestão de produtos</h2>
        <p>Este módulo ainda não possui cadastros. Defina como deseja divulgar os produtos para liberarmos o painel.</p>
        <ul>
            <li>Listagem com fotos, preços e descrição detalhada.</li>
            <li>Organização por categorias ou destaque promocional.</li>
            <li>Integração com estoque ou orçamentos (opcional).</li>
        </ul>
        <a href="../dashboard.php?module=overview" target="_top">Voltar ao painel</a>
    </div>
</body>
</html>
