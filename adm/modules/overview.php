<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

$usuario = $_SESSION['usuario_nome'] ?? 'Administrador';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Painel geral</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../../bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/styleProjet.css">
    <style>
        :root {
            color-scheme: light;
        }

        body {
            margin: 0;
            padding: 32px;
            font-family: "Segoe UI", "Roboto", sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: #0f172a;
        }

        .welcome-card {
            background: #fff;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(148, 163, 184, 0.25);
            margin-bottom: 32px;
        }

        .welcome-card h2 {
            margin: 0 0 12px;
            font-size: 1.75rem;
        }

        .welcome-card p {
            margin: 0;
            color: #475569;
            font-size: 1rem;
        }

        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .module-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(203, 213, 225, 0.8);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .module-card h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .module-card p {
            margin: 0;
            color: #64748b;
            font-size: 0.95rem;
            flex: 1;
        }

        .module-card a {
            align-self: flex-start;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .module-card a:hover {
            background: #1d4ed8;
        }

        @media (max-width: 640px) {
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <section class="welcome-card">
        <h2>Olá, <?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>!</h2>
        <p>Use o menu ao lado ou os atalhos abaixo para gerenciar o conteúdo do site com segurança.</p>
    </section>

    <section class="modules-grid">
        <article class="module-card">
            <h3>Vídeos</h3>
            <p>Cadastrar novos Vídeos e manter a vitrine atualizada.</p>
            <a href="../dashboard.php?module=videos" target="_top">Ir para Vídeos</a>
        </article>
        <article class="module-card">
            <h3>Serviços</h3>
            <p>Adicionar, editar ou remover Serviços exibidos aos clientes.</p>
            <a href="../dashboard.php?module=servicos" target="_top">Ver Serviços</a>
        </article>
        <article class="module-card">
            <h3>Horários</h3>
            <p>Atualizar períodos de atendimento e Horários especiais.</p>
            <a href="../dashboard.php?module=horarios" target="_top">Configurar Horários</a>
        </article>
        <article class="module-card">
            <h3>Produtos</h3>
            <p>Gerenciar catálogo de produtos e materiais disponibilizados.</p>
            <a href="../dashboard.php?module=produtos" target="_top">Ver Produtos</a>
        </article>
        <article class="module-card">
            <h3>Clientes</h3>
            <p>Cadastrar e gerenciar informações dos clientes do salão.</p>
            <a href="../dashboard.php?module=clientes" target="_top">Ver Clientes</a>
        </article>
        <article class="module-card">
            <h3>Depoimentos clientes</h3>
            <p>Administrar depoimentos dos clientes para exibição no site.</p>
            <a href="../dashboard.php?module=depoimentos" target="_top">Ver Depoimentos</a>
        </article>
    </section>
</body>
</html>
