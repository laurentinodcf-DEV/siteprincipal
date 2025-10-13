console.log("JavaScript carregado");


//------------------ MODAL DE CONTATO ----------------------------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal');
    const openModalButton = document.getElementById('openModal');
    const closeModalButton = document.getElementById('closeContactModal');

    if (modal) {
        // Garante que o modal esteja escondido ao carregar a página
        modal.style.display = 'none';

        if (openModalButton) {
            openModalButton.addEventListener('click', function () {
                modal.style.display = 'flex';
            });
        }

        if (closeModalButton) {
            closeModalButton.addEventListener('click', function () {
                modal.style.display = 'none';
            });
        }

        // Fecha o modal ao clicar fora do conteúdo do modal
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});

//------------------ MODAL DE ENDEREÇO ----------------------------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    const mapModal = document.getElementById('mapModal');
    const openModalButton = document.getElementById('openMapModal');
    const closeModalButton = document.getElementById('closeMapModal');

    if (mapModal) {
        // Garante que o modal esteja escondido ao carregar a página
        mapModal.style.display = 'none';

        if (openModalButton) {
            openModalButton.addEventListener('click', function () {
                mapModal.style.display = 'flex';
            });
        }

        if (closeModalButton) {
            closeModalButton.addEventListener('click', function () {
                mapModal.style.display = 'none';
            });
        }

        // Fecha o modal ao clicar fora do conteúdo do modal
        window.addEventListener('click', (event) => {
            if (event.target === mapModal) {
                mapModal.style.display = 'none';
            }
        });
    }
});


//------------------ CARROSSEL DE IMAGENS ----------------------------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
  const carousel = document.querySelector('.carousel');
  const images = document.querySelectorAll('.carousel img');
  const nextBtn = document.querySelector('.next');
  const prevBtn = document.querySelector('.prev');

  if (!carousel || images.length === 0 || (!nextBtn && !prevBtn)) {
    return;
  }

  let currentIndex = 0;
  const totalImages = images.length;

  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      currentIndex = (currentIndex + 1) % totalImages;
      carousel.style.transform = `translateX(${-currentIndex * 100}%)`;
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      currentIndex = (currentIndex - 1 + totalImages) % totalImages;
      carousel.style.transform = `translateX(${-currentIndex * 100}%)`;
    });
  }
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

        loginModal.addEventListener("click", (event) => {
            if (event.target === loginModal) {
                loginModal.style.display = "none";
                loginError.style.display = "none";
            }
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

    const forgotPasswordLink = document.querySelector(".login-forgot-link");
    const forgotPasswordModal = document.getElementById("forgotPasswordModal");
    const forgotPasswordForm = document.getElementById("forgotPasswordForm");
    const forgotPasswordFeedback = document.getElementById("forgotPasswordFeedback");
    const closeForgotPasswordModal = document.getElementById("closeForgotPasswordModal");
    const resetIdentifierInput = document.getElementById("resetIdentifier");

    const verifyCodeModal = document.getElementById("verifyCodeModal");
    const verifyCodeForm = document.getElementById("verifyCodeForm");
    const verifyCodeFeedback = document.getElementById("verifyCodeFeedback");
    const closeVerifyCodeModal = document.getElementById("closeVerifyCodeModal");
    const codeTimerElement = document.getElementById("codeTimer");
    const resetCodeInput = document.getElementById("resetCode");
    const resetCodeGrid = document.querySelector(".reset-code-grid");
    const codeDigitPreviews = document.querySelectorAll(".code-digit-preview");

    const forgotSubmitButton = forgotPasswordForm
        ? forgotPasswordForm.querySelector(".btn-reset-primary")
        : null;
    const verifySubmitButton = verifyCodeForm
        ? verifyCodeForm.querySelector(".btn-reset-primary")
        : null;

    let lastIdentifier = "";
    let codeTimerInterval = null;

    const toggleModalVisibility = (modal, show) => {
        if (!modal) {
            return;
        }
        modal.style.display = show ? "flex" : "none";
    };

    const resetFeedbackState = (element) => {
        if (!element) {
            return;
        }
        element.textContent = "";
        element.style.display = "none";
        element.classList.remove("is-error", "is-success");
    };

    const showFeedback = (element, message, type) => {
        if (!element) {
            return;
        }
        element.textContent = message;
        element.style.display = "block";
        element.classList.remove("is-error", "is-success");
        element.classList.add(type === "success" ? "is-success" : "is-error");
    };

    const sanitizeCodeValue = (value) => {
        return (value || "").replace(/\D/g, "").slice(0, 6);
    };

    const updateCodePreview = (value) => {
        if (!codeDigitPreviews || codeDigitPreviews.length === 0) {
            return;
        }
        const digits = value.split("");
        codeDigitPreviews.forEach((preview, index) => {
            preview.value = digits[index] || "";
            preview.classList.toggle("is-filled", Boolean(digits[index]));
        });
    };

    const clearCodeInput = () => {
        if (resetCodeInput) {
            resetCodeInput.value = "";
        }
        updateCodePreview("");
    };

    const stopCodeTimer = () => {
        if (codeTimerInterval) {
            clearInterval(codeTimerInterval);
            codeTimerInterval = null;
        }
        if (codeTimerElement) {
            codeTimerElement.textContent = "";
        }
    };

    const startCodeTimer = (expiresAt) => {
        if (!codeTimerElement) {
            return;
        }

        stopCodeTimer();

        let expiration = null;
        const limitDurationMs = 2 * 60 * 1000;
        const now = Date.now();

        if (expiresAt) {
            const numericExpires = Number(expiresAt);
            if (!Number.isNaN(numericExpires) && numericExpires > 0) {
                expiration = new Date(now + numericExpires * 1000);
            } else {
                const parsed = new Date(String(expiresAt).replace(" ", "T"));
                expiration = Number.isNaN(parsed.getTime())
                    ? null
                    : parsed;
            }
        }

        if (!expiration) {
            expiration = new Date(now + limitDurationMs);
        }

        const maxExpiration = now + limitDurationMs;
        if (expiration.getTime() > maxExpiration) {
            expiration = new Date(maxExpiration);
        }

        const updateTimer = () => {
            const diff = expiration.getTime() - Date.now();
            if (diff <= 0) {
                codeTimerElement.textContent = "Código expirado";
                stopCodeTimer();
                showFeedback(
                    verifyCodeFeedback,
                    "O código expirou. Solicite um novo para continuar.",
                    "error"
                );
                return;
            }

            const totalSeconds = Math.floor(diff / 1000);
            const displayMinutes = Math.floor(totalSeconds / 60);
            const displaySeconds = totalSeconds % 60;
            codeTimerElement.textContent = `${String(displayMinutes).padStart(2, "0")}:${String(displaySeconds).padStart(2, "0")}`;
        };

        updateTimer();
        codeTimerInterval = setInterval(updateTimer, 1000);
    };

    const closeForgotModal = () => {
        toggleModalVisibility(forgotPasswordModal, false);
        resetFeedbackState(forgotPasswordFeedback);
        if (forgotPasswordForm) {
            forgotPasswordForm.reset();
        }
    };

    const closeVerifyModal = () => {
        toggleModalVisibility(verifyCodeModal, false);
        resetFeedbackState(verifyCodeFeedback);
        stopCodeTimer();
        if (verifyCodeForm) {
            verifyCodeForm.reset();
        }
        clearCodeInput();
    };

    if (forgotPasswordLink && forgotPasswordModal) {
        forgotPasswordLink.addEventListener("click", (event) => {
            event.preventDefault();
            toggleModalVisibility(loginModal, false);
            resetFeedbackState(forgotPasswordFeedback);
            if (forgotPasswordForm) {
                forgotPasswordForm.reset();
            }
            toggleModalVisibility(forgotPasswordModal, true);
            if (resetIdentifierInput) {
                resetIdentifierInput.focus();
            }
        });
    }

    if (closeForgotPasswordModal) {
        closeForgotPasswordModal.addEventListener("click", closeForgotModal);
    }

    if (forgotPasswordModal) {
        forgotPasswordModal.addEventListener("click", (event) => {
            if (event.target === forgotPasswordModal) {
                closeForgotModal();
            }
        });
    }

    if (verifyCodeModal) {
        verifyCodeModal.addEventListener("click", (event) => {
            if (event.target === verifyCodeModal) {
                closeVerifyModal();
            }
        });
    }

    if (closeVerifyCodeModal) {
        closeVerifyCodeModal.addEventListener("click", closeVerifyModal);
    }

    if (resetCodeGrid) {
        resetCodeGrid.addEventListener("click", () => {
            if (resetCodeInput) {
                resetCodeInput.focus();
            }
        });
    }

    if (verifyCodeForm) {
        verifyCodeForm.addEventListener("click", (event) => {
            if (!resetCodeInput) {
                return;
            }
            const target = event.target;
            if (target && target.classList && target.classList.contains("btn-reset-primary")) {
                return;
            }
            resetCodeInput.focus();
        });
    }

    if (resetCodeInput) {
        resetCodeInput.addEventListener("input", () => {
            const sanitized = sanitizeCodeValue(resetCodeInput.value);
            if (sanitized !== resetCodeInput.value) {
                resetCodeInput.value = sanitized;
            }
            updateCodePreview(sanitized);
        });

        resetCodeInput.addEventListener("focus", () => {
            updateCodePreview(sanitizeCodeValue(resetCodeInput.value));
        });
    }

    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener("submit", (event) => {
            event.preventDefault();
            resetFeedbackState(forgotPasswordFeedback);

            const identifier = resetIdentifierInput
                ? resetIdentifierInput.value.trim()
                : "";

            if (identifier === "") {
                showFeedback(forgotPasswordFeedback, "Informe seu usuário ou e-mail cadastrado.", "error");
                return;
            }

            if (forgotSubmitButton) {
                forgotSubmitButton.disabled = true;
                forgotSubmitButton.textContent = "Enviando...";
            }

            const body = new URLSearchParams({ identifier });

            fetch(resolveAdminUrl("adm/forgot_password_request.php"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: body.toString(),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (forgotSubmitButton) {
                        forgotSubmitButton.disabled = false;
                        forgotSubmitButton.textContent = "Enviar código";
                    }

                    if (data.success) {
                        showFeedback(
                            forgotPasswordFeedback,
                            data.message || "Enviamos um código para o e-mail informado.",
                            "success"
                        );
                        lastIdentifier = identifier;
                        toggleModalVisibility(forgotPasswordModal, false);
                        resetFeedbackState(forgotPasswordFeedback);
                        toggleModalVisibility(verifyCodeModal, true);
                        resetFeedbackState(verifyCodeFeedback);
                        clearCodeInput();
                        if (resetCodeInput) {
                            resetCodeInput.focus();
                        }
                        startCodeTimer(data.expiresAt || null);
                        if (data.codePreview) {
                            console.info("Código de teste:", data.codePreview);
                        }
                    } else {
                        showFeedback(
                            forgotPasswordFeedback,
                            data.message || "Não foi possível enviar o código. Tente novamente.",
                            "error"
                        );
                        if (data.codePreview) {
                            console.info("Código de teste:", data.codePreview);
                        }
                    }
                })
                .catch(() => {
                    if (forgotSubmitButton) {
                        forgotSubmitButton.disabled = false;
                        forgotSubmitButton.textContent = "Enviar código";
                    }
                    showFeedback(
                        forgotPasswordFeedback,
                        "Não foi possível solicitar o código agora. Tente novamente em instantes.",
                        "error"
                    );
                });
        });
    }

    if (verifyCodeForm) {
        verifyCodeForm.addEventListener("submit", (event) => {
            event.preventDefault();
            resetFeedbackState(verifyCodeFeedback);

            if (!lastIdentifier) {
                showFeedback(
                    verifyCodeFeedback,
                    "Solicite um novo código antes de prosseguir.",
                    "error"
                );
                return;
            }

            const code = resetCodeInput
                ? sanitizeCodeValue(resetCodeInput.value)
                : "";
            if (resetCodeInput) {
                resetCodeInput.value = code;
            }
            updateCodePreview(code);

            if (!/^[0-9]{6}$/.test(code)) {
                showFeedback(
                    verifyCodeFeedback,
                    "Digite um código válido de 6 dígitos.",
                    "error"
                );
                if (resetCodeInput) {
                    resetCodeInput.focus();
                }
                return;
            }

            if (verifySubmitButton) {
                verifySubmitButton.disabled = true;
                verifySubmitButton.textContent = "Validando...";
            }

            const body = new URLSearchParams({ identifier: lastIdentifier, code });

            fetch(resolveAdminUrl("adm/forgot_password_verify.php"), {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: body.toString(),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (verifySubmitButton) {
                        verifySubmitButton.disabled = false;
                        verifySubmitButton.textContent = "Ok";
                    }

                    if (data.success) {
                        showFeedback(
                            verifyCodeFeedback,
                            data.message || "Código validado com sucesso.",
                            "success"
                        );
                        stopCodeTimer();
                        const redirectTarget = data.redirect
                            ? resolveAdminUrl(data.redirect)
                            : resolveAdminUrl("adm/reset_password.php");
                        setTimeout(() => {
                            window.location.href = redirectTarget;
                        }, 600);
                    } else {
                        showFeedback(
                            verifyCodeFeedback,
                            data.message || "Não foi possível validar o código informado.",
                            "error"
                        );
                    }
                })
                .catch(() => {
                    if (verifySubmitButton) {
                        verifySubmitButton.disabled = false;
                        verifySubmitButton.textContent = "Ok";
                    }
                    showFeedback(
                        verifyCodeFeedback,
                        "Erro ao validar o código. Tente novamente.",
                        "error"
                    );
                });
        });
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






