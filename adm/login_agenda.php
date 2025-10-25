<?php
// Login para acesso direto à Agenda Profissional (tema verde)
session_start();
include("../conexao.php");

// Se já estiver logado, vai direto para a agenda
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id']) {
    header('Location: agenda_profissional.php');
    exit();
}

// Fallback sem JS: permite login via POST nesta própria página
$erro = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $senha = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($login !== '' && $senha !== '') {
        $stmt = $conn->prepare("SELECT id, username, password FROM backend_users WHERE login = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $login);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($senha, $user['password'])) {
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['usuario_nome'] = $user['username'];
                    header('Location: agenda_profissional.php');
                    exit();
                } else {
                    $erro = 'Senha incorreta!';
                }
            } else {
                $erro = 'Usuário não encontrado!';
            }
            $stmt->close();
        } else {
            $erro = 'Não foi possível validar o login no momento.';
        }
    } else {
        $erro = 'Informe usuário e senha.';
    }
}

// Variáveis usadas pelo componente de modal
$adminLogado = false;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Agenda Profissional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #122016 0%, #1f5a30 35%, #28a745 55%, #20c997 70%, #13331d 100%);
            background-attachment: fixed;
            position: relative;
        }
        body::before {
            content: "";
            position: absolute; inset: 0;
            background: radial-gradient(60% 60% at 70% 20%, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0) 60%),
                        radial-gradient(60% 60% at 20% 80%, rgba(0,0,0,0.16) 0%, rgba(0,0,0,0) 60%);
            pointer-events: none;
        }
        .login-modal { z-index: 10; }
        .no-scroll { overflow: hidden; }

        /* Marca Agenda Pro */
        .agenda-login-brand { position:absolute; top:22px; left:50%; transform:translateX(-50%); background:linear-gradient(135deg, #28a745, #20c997); color:#fff; border-radius:14px; padding:14px 22px; box-shadow:0 10px 30px rgba(0,0,0,0.25); display:flex; align-items:center; gap:14px; z-index:10002; border:3px solid rgba(255,255,255,0.9); cursor:pointer; }
        .agenda-login-brand .brand-icon { font-size:1.6rem; }
        .agenda-login-brand .brand-text h1 { font-size:1.05rem; font-weight:800; line-height:1; margin:0; }
        .agenda-login-brand .brand-text small { display:block; font-size:0.78rem; opacity:0.95; margin-top:2px; font-weight:600; }
        @media (max-width: 576px){ .agenda-login-brand{ padding:10px 14px; border-width:2px; gap:10px;} .agenda-login-brand .brand-icon{font-size:1.3rem;} .agenda-login-brand .brand-text h1{font-size:0.98rem;} .agenda-login-brand .brand-text small{font-size:0.72rem;} }

        /* Overrides de tema verde para a modal original */
        .login-welcome-panel { background: linear-gradient(150deg, rgba(64, 196, 128, 0.96), rgba(32, 201, 151, 0.96)) !important; }
        .login-submit-button { background: linear-gradient(135deg, #28a745, #20c997) !important; box-shadow: 0 16px 32px rgba(32, 201, 151, 0.32) !important; }
        .login-submit-button:hover { box-shadow: 0 20px 36px rgba(32, 201, 151, 0.42) !important; }
        .login-forgot-link { color: #20c997 !important; }
        .toggle-password:hover, .toggle-password.is-active { background: rgba(32, 201, 151, 0.12) !important; color: #1b6d57 !important; }
        .login-form-header h3 { color: #163c2a !important; }
    </style>
</head>
<body>
    <div class="agenda-login-brand" id="agendaLoginBrand" aria-label="Marca da Agenda Pro" title="Abrir login da Agenda Pro">
        <i class="bi bi-calendar-heart brand-icon" aria-hidden="true"></i>
        <div class="brand-text">
            <h1>Agenda Pro</h1>
            <small>Sistema Profissional</small>
        </div>
    </div>

    <?php include("../class/modais.php"); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var loginModal = document.getElementById('loginModal');
            var loginError = document.getElementById('loginError');
            var closeBtn = document.getElementById('closeLoginModal');
            var brand = document.getElementById('agendaLoginBrand');
            var form = document.getElementById('loginForm');

            if (form) {
                // Injeta o redirect para a Agenda no envio por AJAX
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'redirect';
                hidden.value = 'adm/agenda_profissional.php';
                form.appendChild(hidden);
            }

            if (loginModal) {
                loginModal.style.display = 'flex';
                document.body.classList.add('no-scroll');
                loginModal.addEventListener('click', function(e){
                    if (e.target === loginModal) {
                        document.body.classList.remove('no-scroll');
                    }
                });
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(){
                        document.body.classList.remove('no-scroll');
                    });
                }
            }
            if (brand) {
                brand.addEventListener('click', function(){
                    if (loginModal) {
                        loginModal.style.display = 'flex';
                        document.body.classList.add('no-scroll');
                    }
                });
            }

            // Exibir erro do fallback (POST direto)
            <?php if (!empty($erro)) { ?>
            if (loginError) {
                loginError.textContent = <?= json_encode($erro) ?>;
                loginError.style.display = 'block';
            }
            <?php } ?>
        });
    </script>
</body>
</html>
