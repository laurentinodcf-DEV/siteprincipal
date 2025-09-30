console.log("JavaScript carregado");


//------------------ MODAL DE CONTATO ----------------------------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal');
    const openModalButton = document.getElementById('openModal');
    const closeModalButton = document.getElementById('closeContactModal');

    // Garante que o modal esteja escondido ao carregar a página
    modal.style.display = 'none';

    openModalButton.addEventListener('click', function () {
        modal.style.display = 'flex'; // Exibe o modal como flex para centralizar
    });

    closeModalButton.addEventListener('click', function () {
        modal.style.display = 'none'; // Oculta o modal
    });

    // Fecha o modal ao clicar fora do conteúdo do modal
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

//------------------ MODAL DE ENDEREÇO ----------------------------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const mapModal = document.getElementById('mapModal');
    const openModalButton = document.getElementById('openMapModal');
    const closeModalButton = document.getElementById('closeMapModal');

    // Garante que o modal esteja escondido ao carregar a página
    mapModal.style.display = 'none';

    openModalButton.addEventListener('click', function () {
        mapModal.style.display = 'flex'; // Exibe o modal como flex para centralizar
    });

    closeModalButton.addEventListener('click', function () {
        mapModal.style.display = 'none'; // Oculta o modal
    });

    // Fecha o modal ao clicar fora do conteúdo do modal
    window.addEventListener('click', (event) => {
        if (event.target === mapModal) {
            mapModal.style.display = 'none';
        }
    });
});


//------------------ CARROSSEL DE IMAGENS ----------------------------------------------------------------------------------------------
let currentIndex = 0;
const carousel = document.querySelector('.carousel');
const images = document.querySelectorAll('.carousel img');
const totalImages = images.length;

document.querySelector('.next').addEventListener('click', () => {
  currentIndex = (currentIndex + 1) % totalImages;
  carousel.style.transform = `translateX(${-currentIndex * 100}%)`;
});

document.querySelector('.prev').addEventListener('click', () => {
  currentIndex = (currentIndex - 1 + totalImages) % totalImages;
  carousel.style.transform = `translateX(${-currentIndex * 100}%)`;
});


//----------------------------------------- controle paninel de login -----------------------------------------------
// Abrir e fechar modal
const loginModal = document.getElementById("loginModal");
const openLoginBtn = document.getElementById("openLoginModal");
const closeLoginBtn = document.getElementById("closeLoginModal");
const loginError = document.getElementById("loginError");

openLoginBtn.addEventListener("click", () => {
    loginModal.style.display = "flex";
});

closeLoginBtn.addEventListener("click", () => {
    loginModal.style.display = "none";
    loginError.style.display = "none";
});

// Enviar formulário via AJAX
document.getElementById("loginForm").addEventListener("submit", function(e){
    e.preventDefault();

    const formData = new FormData(this);

    fetch("adm/login_ajax.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            loginModal.style.display = "none";
            loginError.style.display = "none";
            location.reload(); // recarrega a página
        } else {
            loginError.textContent = data.message;
            loginError.style.display = "block";
        }
    });
});

// Mostrar/ocultar senha
const senhaInput = document.querySelector('input[name="password"]');
const showPasswordCheckbox = document.getElementById('showPassword');

showPasswordCheckbox.addEventListener('change', () => {
    if(showPasswordCheckbox.checked){
        senhaInput.type = 'text';
    } else {
        senhaInput.type = 'password';
    }
});

// Logout Modal
const logoutMenu = document.getElementById('logoutMenu');
const logoutModal = document.getElementById('logoutModal');
const closeLogoutModal = document.getElementById('closeLogoutModal');
const confirmLogout = document.getElementById('confirmLogout');
const cancelLogout = document.getElementById('cancelLogout');

logoutMenu.addEventListener('click', () => {
    logoutModal.style.display = 'flex';
});

closeLogoutModal.addEventListener('click', () => logoutModal.style.display = 'none');
cancelLogout.addEventListener('click', () => logoutModal.style.display = 'none');

confirmLogout.addEventListener('click', () => {
    fetch('adm/logout.php')
    .then(() => location.reload());
});


if(adminLogado){
    document.getElementById('logoutMenu').style.display = 'block';
    document.getElementById('menuInserir').style.display = 'flex';
}

//----------------------------------------- Inserir Vídeo (Link) -----------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const formLink = document.getElementById('formLink');
    if(formLink){
        formLink.addEventListener('submit', function(e){
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('tipo', 'link'); // força o tipo para link

            fetch('video_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if(data.success){
                    // fecha modal (se for bootstrap)
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalLink'));
                    if(modal){ modal.hide(); }

                    // recarrega lista de vídeos
                    location.reload();
                }
            })
            .catch(err => console.error('Erro:', err));
        });
    }
});
