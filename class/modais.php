<?php
if (isset($menuBasePathUrl)) {
    $modaisBasePathUrl = $menuBasePathUrl;
} else {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = realpath(__DIR__ . '/..');

    if ($documentRoot && $projectRoot && strpos($projectRoot, $documentRoot) === 0) {
        $relativePath = substr($projectRoot, strlen($documentRoot));
        $relativePath = str_replace('\\', '/', $relativePath);
        $modaisBasePathUrl = $relativePath === '' ? '' : '/' . ltrim($relativePath, '/');
    } else {
        $modaisBasePathUrl = '';
    }

    $modaisBasePathUrl = rtrim($modaisBasePathUrl, '/');
    $modaisBasePathUrl = $modaisBasePathUrl === '' ? '' : $modaisBasePathUrl;
}
?>

<link rel="stylesheet" href="<?= $modaisBasePathUrl ?>/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= $modaisBasePathUrl ?>/css/styleProjet.css">
<link rel="stylesheet" href="<?= $modaisBasePathUrl ?>/css/estilo.css">

<div id="modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Contatos:</h2>
            <span class="close-btn" id="closeContactModal">&times;</span>
        </div>
        <div class="modal-body">
            <p><strong>WhatsApp:</strong>
                <img src="<?= $modaisBasePathUrl ?>/img/whats-logo.png" class="whats-logo" alt="WhatsApp">
                <span class="title-contact">(31) 99189-2974</span>
            </p>
            <p><strong>Instagram:</strong>
                <img src="<?= $modaisBasePathUrl ?>/img/instagram_logo.png" class="instagram-logo" alt="Instagram">
                <span class="title-contact">
                    <a href="https://www.instagram.com/_queniasalome?igsh=cTZmNjVjaTVnM25i" style="text-decoration: none;" target="_blank" rel="noopener noreferrer">
                        @_queniasalome
                    </a>
                </span>
            </p>
            <p><strong>Email:</strong>
                <img src="<?= $modaisBasePathUrl ?>/img/email-logo.png" class="email-logo" alt="Email">
                <span class="title-contact">exemplo@email.com</span>
            </p>
        </div>
    </div>
</div>

<div id="mapModal" class="mapModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Local:</h2>
            <span class="close-btn" id="closeMapModal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="carousel-container">
                <button class="prev">&#10094;</button>
                <div class="carousel">
                    <img src="<?= $modaisBasePathUrl ?>/img/salao/entrada.jpg" alt="Entrada do local">
                    <img src="<?= $modaisBasePathUrl ?>/img/salao/frente.jpg" alt="Frente do local">
                    <img src="<?= $modaisBasePathUrl ?>/img/salao/interior01.jpg" alt="Interior do local">
                    <img src="<?= $modaisBasePathUrl ?>/img/salao/interior02.jpg" alt="Interior do local">
                </div>
                <button class="next">&#10095;</button>
            </div>

            <p>
                <strong>Endereço:</strong>
                <a href="https://www.google.com/maps/search/?api=1&query=Av.+Professor+Lucas+Machado,+442,+Asteca,+Santa+Luzia+-+MG" target="_blank" rel="noopener noreferrer">
                    Av. Professor Lucas Machado - Nº 442 - Asteca, Santa Luzia - MG
                </a>
            </p>
        </div>
    </div>
</div>

<div id="loginModal" class="modal login-modal" style="display:none;">
    <div class="modal-content login-modal-content">
        <button type="button" class="close-btn login-modal-close" id="closeLoginModal" aria-label="Fechar modal">&times;</button>
        <div class="login-modal-card">
            <div class="login-welcome-panel">
                <h2>Painel administrativo!</h2>
                <p>Login com credenciais administrativas.</p>
            </div>
            <div class="login-form-panel">
                <header class="login-form-header">
                    <h3>Login</h3>
                    <p>Acesse sua conta do painel administrativo.</p>
                </header>

                <p id="loginError" class="login-error-message" style="display:none;"></p>

                <form id="loginForm">
                    <div class="login-input-group">
                        <label for="loginUsername">Usuario / Email</label>
                        <div class="login-input-wrapper">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4 20C4 16.6863 6.68629 14 10 14H14C17.3137 14 20 16.6863 20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input type="text" id="loginUsername" name="login" required autocomplete="username">
                        </div>
                    </div>

                    <div class="login-input-group">
                        <label for="loginPassword">Senha</label>
                        <div class="login-input-wrapper">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <input type="password" id="loginPassword" name="password" required autocomplete="current-password">
                            <button type="button" class="toggle-password" aria-label="Mostrar senha">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12C2.8 7 7 4 12 4C17 4 21.2 7 23 12C21.2 17 17 20 12 20C7 20 2.8 17 1 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="login-form-options">
                        <a href="#" class="login-forgot-link">Esqueceu a senha?</a>
                    </div>

                    <button type="submit" class="login-submit-button">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="logoutModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirmar Logout</h2>
            <span class="close-btn" id="closeLogoutModal">&times;</span>
        </div>
        <div class="modal-body">
            <p>Tem certeza que deseja sair?</p>
            <button id="confirmLogout">Sim</button>
            <button id="cancelLogout">Nao</button>
        </div>
    </div>
</div>

<script>
    const adminLogado = <?= $adminLogado ? 'true' : 'false' ?>;
</script>
<script src="<?= $modaisBasePathUrl ?>/js/script.js"></script>
<script src="<?= $modaisBasePathUrl ?>/bootstrap/js/bootstrap.bundle.min.js"></script>
