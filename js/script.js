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