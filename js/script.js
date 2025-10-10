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

const scriptReference =
    document.currentScript || document.querySelector('script[src*="js/script.js"]');
const scriptBaseUrl = (() => {
    if (!scriptReference || !scriptReference.src) {
        return window.location.href;
    }
    return scriptReference.src.replace(/js\/script\.js(?:\?.*)?$/i, "");
})();

const resolveAdminUrl = (relativePath) => {
    try {
        return new URL(relativePath, scriptBaseUrl).toString();
    } catch (error) {
        return relativePath;
    }
};

document.addEventListener("DOMContentLoaded", () => {
    const loginModal = document.getElementById("loginModal");
    const closeLoginBtn = document.getElementById("closeLoginModal");
    const loginError = document.getElementById("loginError");
    const loginForm = document.getElementById("loginForm");
    const adminAccessTrigger = document.getElementById("adminAccessTrigger");
    const legacyLoginTrigger = document.getElementById("openLoginModal");

    const openLoginModal = () => {
        if (typeof adminLogado !== "undefined" && adminLogado) {
            window.location.href = resolveAdminUrl("adm/dashboard.php");
            return;
        }

        if (!loginModal) {
            window.location.href = resolveAdminUrl("adm/login.php");
            return;
        }

        if (loginError) {
            loginError.style.display = "none";
        }
        loginModal.style.display = "flex";
    };

    if (adminAccessTrigger) {
        adminAccessTrigger.addEventListener("click", openLoginModal);
    }

    if (legacyLoginTrigger) {
        legacyLoginTrigger.addEventListener("click", openLoginModal);
    }

    if (closeLoginBtn && loginModal && loginError) {
        closeLoginBtn.addEventListener("click", () => {
            loginModal.style.display = "none";
            loginError.style.display = "none";
        });
    }

    if (loginForm && loginError) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch(resolveAdminUrl("adm/login_ajax.php"), {
                method: "POST",
                body: formData
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        loginModal.style.display = "none";
                        loginError.style.display = "none";
                        const redirectTarget = data.redirect
                            ? resolveAdminUrl(data.redirect)
                            : resolveAdminUrl("adm/dashboard.php");
                        window.location.href = redirectTarget;
                    } else {
                        loginError.textContent = data.message;
                        loginError.style.display = "block";
                    }
                })
                .catch(() => {
                    loginError.textContent = "Erro ao tentar fazer login. Tente novamente.";
                    loginError.style.display = "block";
                });
        });

        const senhaInput = loginForm.querySelector('input[name="password"]');
        const togglePasswordButton = loginForm.querySelector(".toggle-password");
        if (togglePasswordButton && senhaInput) {
            togglePasswordButton.addEventListener("click", () => {
                const isMasked = senhaInput.type === "password";
                senhaInput.type = isMasked ? "text" : "password";
                togglePasswordButton.classList.toggle("is-active", !isMasked);
                togglePasswordButton.setAttribute(
                    "aria-label",
                    isMasked ? "Ocultar senha" : "Mostrar senha"
                );
            });
        }
    }
});


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


// carrossel da sessão 02
  document.addEventListener("DOMContentLoaded", function() {
    const carousel = document.querySelector(".servicos-carousel-02");
    const prevBtn = document.querySelector(".carousel-btn-02.prev");
    const nextBtn = document.querySelector(".carousel-btn-02.next");

    if (!carousel || !prevBtn || !nextBtn) {
      return;
    }

    const getScrollAmount = () => {
      const firstCard = carousel.querySelector(".card-servico");
      if (!firstCard) {
        return 0;
      }

      const carouselStyles = window.getComputedStyle(carousel);
      const gapValue = parseFloat(carouselStyles.getPropertyValue("column-gap"));
      const gap = Number.isNaN(gapValue) ? 0 : gapValue;

      return firstCard.offsetWidth + gap;
    };

    const scrollByAmount = (direction) => {
      const amount = getScrollAmount();
      if (amount === 0) {
        return;
      }

      carousel.scrollBy({ left: direction * amount, behavior: "smooth" });
    };

    prevBtn.addEventListener("click", () => {
      scrollByAmount(-1);
    });

    nextBtn.addEventListener("click", () => {
      scrollByAmount(1);
    });
  });



// carrossel da sessão 04
  document.addEventListener("DOMContentLoaded", function() {
    const carousel = document.querySelector(".servicos-carousel");
    const prevBtn = document.querySelector(".carousel-btn.prev");
    const nextBtn = document.querySelector(".carousel-btn.next");

    if (!carousel || !prevBtn || !nextBtn) {
      return;
    }

    const getScrollAmount = () => {
      const firstCard = carousel.querySelector(".card-servico-04");
      if (!firstCard) {
        return 0;
      }

      const carouselStyles = window.getComputedStyle(carousel);
      const gapValue = parseFloat(carouselStyles.getPropertyValue("column-gap"));
      const gap = Number.isNaN(gapValue) ? 0 : gapValue;

      return firstCard.offsetWidth + gap;
    };

    const scrollByAmount = (direction) => {
      const amount = getScrollAmount();
      if (amount === 0) {
        return;
      }

      carousel.scrollBy({ left: direction * amount * 4, behavior: "smooth" });
    };

    prevBtn.addEventListener("click", () => {
      scrollByAmount(-1);
    });

    nextBtn.addEventListener("click", () => {
      scrollByAmount(1);
    });
  });


// Carrossel de depoimentos (seção 03)
document.addEventListener("DOMContentLoaded", function() {
  const carousel = document.querySelector(".depoimentos-carousel");
  const prevBtn = document.querySelector(".depoimentos-btn.prev");
  const nextBtn = document.querySelector(".depoimentos-btn.next");
  const indicators = document.querySelectorAll(".depoimentos-indicadores .indicador");
  
  if (!carousel || !prevBtn || !nextBtn) {
    return;
  }
  
  let currentSlide = 0;
  const totalSlides = Math.ceil(carousel.children.length / 2); // Mostra 2 por vez
  
  // Função para atualizar o carrossel
  function updateCarousel() {
    // Calcula a posição de deslocamento
    const offset = currentSlide * -100;
    carousel.style.transform = `translateX(${offset}%)`;
    
    // Atualiza os indicadores
    indicators.forEach((indicator, index) => {
      indicator.classList.toggle("active", index === currentSlide);
    });
  }
  
  // Botão próximo
  nextBtn.addEventListener("click", function() {
    if (currentSlide < totalSlides - 1) {
      currentSlide++;
      updateCarousel();
    }
  });
  
  // Botão anterior
  prevBtn.addEventListener("click", function() {
    if (currentSlide > 0) {
      currentSlide--;
      updateCarousel();
    }
  });
  
  // Clique nos indicadores
  indicators.forEach((indicator, index) => {
    indicator.addEventListener("click", function() {
      currentSlide = index;
      updateCarousel();
    });
  });
  
  // Inicializa o carrossel
  updateCarousel();
});






