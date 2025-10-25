<?php
// Página de login do administrador usando a modal original do sistema
session_start();
include("../conexao.php");

// Se já estiver logado, vai direto para o painel
if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id']) {
    header('Location: dashboard.php');
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
                    header('Location: dashboard.php');
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

// Informa ao componente de modal que o usuário não está logado
$adminLogado = false;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login do Administrador</title>
    <style>
        /* Fundo dourado profissional */
        html, body {
            height: 100%;
        }
        body {
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1f1a12 0%, #7f6a31 35%, #d4af37 50%, #c49c2f 65%, #3a2f1a 100%);
            background-attachment: fixed;
            position: relative;
        }
        /* Filme sutil para deixar o ouro mais elegante */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(60% 60% at 70% 20%, rgba(255,255,255,0.09) 0%, rgba(255,255,255,0) 60%),
                        radial-gradient(60% 60% at 20% 80%, rgba(0,0,0,0.18) 0%, rgba(0,0,0,0) 60%);
            pointer-events: none;
        }
        /* Garante que a modal fique acima do fundo */
        .login-modal { z-index: 10; }
        /* Remove qualquer rolagem inesperada quando a modal abrir */
        .no-scroll { overflow: hidden; }
    </style>
</head>
<body>
    <?php
        // Inclui a modal de login original do sistema
        // Ela já importa Bootstrap e o estilo principal e registra o script de controle
        include("../class/modais.php");
    ?>

    <script>
        // Abre a modal automaticamente ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            var loginModal = document.getElementById('loginModal');
            var loginError = document.getElementById('loginError');
            var closeBtn = document.getElementById('closeLoginModal');
            if (loginModal) {
                loginModal.style.display = 'flex';
                // Evita rolagem de fundo quando a modal estiver aberta
                document.body.classList.add('no-scroll');
                loginModal.addEventListener('click', function(e) {
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

            // Caso o login tenha falhado via fallback (POST direto), mostra o erro na área da modal
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

