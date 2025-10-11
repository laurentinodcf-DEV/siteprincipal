<?php
declare(strict_types=1);

$senha = '';
$hashGerado = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = (string)($_POST['password'] ?? '');
    if (trim($senha) === '') {
        $erro = 'Informe uma senha para gerar o hash.';
    } else {
        $hashGerado = password_hash($senha, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerador de hash de senha</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.15);
            padding: 32px 36px;
            width: min(420px, 92vw);
        }
        h1 {
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.6rem;
            color: #1f2937;
        }
        p.description {
            color: #4b5563;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        label {
            display: block;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 1rem;
        }
        button {
            width: 100%;
            margin-top: 16px;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }
        button:hover,
        button:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.2);
        }
        .erro {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 10px 14px;
            border-radius: 8px;
            margin-top: 16px;
        }
        .resultado {
            margin-top: 20px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            word-break: break-all;
            font-family: "Courier New", Courier, monospace;
            color: #111827;
        }
        .helpers {
            margin-top: 16px;
            font-size: 0.9rem;
            color: #6b7280;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerador de hash</h1>
        <p class="description">
            Use esta ferramenta para criar um hash seguro antes de salvar ou atualizar senhas na tabela <strong>backend_users</strong>.
        </p>
        <form method="post">
            <label for="password">Senha em texto puro</label>
            <input type="password" id="password" name="password" placeholder="Digite uma nova senha" required>
            <button type="submit">Gerar hash</button>
        </form>

        <?php if ($erro): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if ($hashGerado): ?>
            <div class="resultado">
                <?php echo htmlspecialchars($hashGerado); ?>
            </div>
            <div class="helpers">
                Copie o hash acima e atualize o registro desejado no banco.<br>
                Lembre-se de guardar a senha original em local seguro.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
