(() => {
    const body = document.body;
    const profileToggle = document.querySelector('[data-profile-menu-toggle]');
    const profileMenu = document.querySelector('[data-profile-menu]');

    function currentTheme() {
        return body.classList.contains('theme-dark') ? 'dark' : 'light';
    }

    function setTheme(theme, persist = true) {
        const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
        body.classList.toggle('theme-dark', normalizedTheme === 'dark');
        body.classList.toggle('theme-light', normalizedTheme === 'light');
        document.querySelectorAll('[name="tema"]').forEach((input) => {
            input.value = normalizedTheme;
        });
        document.querySelectorAll('[data-theme-toggle] i').forEach((icon) => {
            icon.className = normalizedTheme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        });

        if (persist) {
            localStorage.setItem('controle-treinamentos:tema', normalizedTheme);
        }
    }

    function setThemeColor(color, persist = true) {
        if (!/^#[0-9a-f]{6}$/i.test(color)) {
            return;
        }

        body.style.setProperty('--primary', color);
        document.querySelectorAll('[name="cor_tema"]').forEach((input) => {
            input.value = color;
        });

        if (persist) {
            localStorage.setItem('controle-treinamentos:cor', color);
        }
    }

    function closeProfileMenu() {
        if (!profileMenu || !profileToggle) {
            return;
        }

        profileMenu.hidden = true;
        profileToggle.setAttribute('aria-expanded', 'false');
        profileToggle.classList.remove('is-open');
    }

    setTheme(currentTheme(), false);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => setTheme(currentTheme() === 'dark' ? 'light' : 'dark'));
    });

    document.querySelectorAll('[name="tema"]').forEach((input) => {
        input.addEventListener('change', () => setTheme(input.value));
    });

    document.querySelectorAll('[name="cor_tema"]').forEach((input) => {
        input.addEventListener('input', () => setThemeColor(input.value));
    });

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
