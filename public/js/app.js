(() => {
    const body = document.body;
    const profileToggle = document.querySelector('[data-profile-menu-toggle]');
    const profileMenu = document.querySelector('[data-profile-menu]');

    function currentTheme() {
        return body.classList.contains('theme-dark') ? 'dark' : 'light';
    }

    function setTheme(theme) {
        const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
        body.classList.toggle('theme-dark', normalizedTheme === 'dark');
        body.classList.toggle('theme-light', normalizedTheme === 'light');
        document.querySelectorAll('[name="tema"]').forEach((input) => {
            input.value = normalizedTheme;
        });
    }

    function setThemeColor(color) {
        if (!/^#[0-9a-f]{6}$/i.test(color)) {
            return;
        }

        body.style.setProperty('--primary', color);
        document.querySelectorAll('[name="cor_tema"]').forEach((input) => {
            input.value = color;
        });

    }

    function closeProfileMenu() {
        if (!profileMenu || !profileToggle) {
            return;
        }

        profileMenu.hidden = true;
        profileToggle.setAttribute('aria-expanded', 'false');
        profileToggle.classList.remove('is-open');
    }

    setTheme(currentTheme());

    document.querySelectorAll('[name="tema"]').forEach((input) => {
        input.addEventListener('change', () => setTheme(input.value));
    });

    document.querySelectorAll('[name="cor_tema"]').forEach((input) => {
        input.addEventListener('input', () => setThemeColor(input.value));
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const photoInput = document.querySelector('[data-profile-photo-input]');
    const photoPreview = document.querySelector('[data-profile-photo-preview]');
    const photoFeedback = document.querySelector('[data-profile-photo-feedback]');
    const photoTriggers = document.querySelectorAll('[data-profile-photo-trigger]');

    if (photoInput && photoTriggers.length > 0) {
        photoTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => photoInput.click());
        });
    }

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', () => {
            const file = photoInput.files?.[0];
            if (!file) {
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                photoInput.value = '';
                if (photoFeedback) {
                    photoFeedback.textContent = 'Escolha uma imagem JPG, PNG ou WEBP.';
                }
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                photoInput.value = '';
                if (photoFeedback) {
                    photoFeedback.textContent = 'A foto selecionada ultrapassa 2 MB.';
                }
                return;
            }

            if (photoPreview.tagName === 'IMG') {
                const previewUrl = URL.createObjectURL(file);
                photoPreview.addEventListener('load', () => URL.revokeObjectURL(previewUrl), { once: true });
                photoPreview.src = previewUrl;
            }

            if (photoFeedback) {
                photoFeedback.textContent = `Nova foto selecionada: ${file.name}. Clique em Salvar perfil para aplicar.`;
            }
        });
    }

    if (profileToggle && profileMenu) {
        profileToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = profileMenu.hidden;
            profileMenu.hidden = !willOpen;
            profileToggle.setAttribute('aria-expanded', String(willOpen));
            profileToggle.classList.toggle('is-open', willOpen);
        });

        document.addEventListener('click', (event) => {
            if (!profileMenu.contains(event.target) && !profileToggle.contains(event.target)) {
                closeProfileMenu();
            }
        });
    }

    const menuToggle = document.querySelector('[data-menu-toggle]');
    if (menuToggle) {
        menuToggle.addEventListener('click', () => body.classList.toggle('sidebar-open'));
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeProfileMenu();
            body.classList.remove('sidebar-open');
        }
    });
})();
