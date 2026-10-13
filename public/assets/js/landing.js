(() => {
    const navToggle = document.querySelector('.nav-toggle');
    const navContent = document.querySelector('.nav-content');

    if (navToggle && navContent) {
        navToggle.addEventListener('click', () => {
            const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!isOpen));
            navToggle.setAttribute('aria-label', isOpen ? 'Abrir menú' : 'Cerrar menú');
            navContent.classList.toggle('is-open', !isOpen);
        });

        navContent.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', 'Abrir menú');
                navContent.classList.remove('is-open');
            });
        });
    }

    const videoModal = document.querySelector('[data-video-modal]');
    const videoFrame = videoModal?.querySelector('[data-video-frame]');
    const videoDialog = videoModal?.querySelector('.video-modal-dialog');
    const videoOpen = document.querySelector('[data-video-open]');
    let previousFocus = null;

    const closeVideo = () => {
        if (!videoModal?.classList.contains('is-open')) return;
        videoModal.classList.remove('is-open');
        videoModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('video-modal-open');
        videoFrame?.replaceChildren();
        previousFocus?.focus();
    };

    videoOpen?.addEventListener('click', () => {
        if (!videoModal || !videoFrame || !videoDialog) return;
        const videoUrl = new URL(videoModal.dataset.videoUrl);
        const videoId = videoUrl.hostname === 'youtu.be'
            ? videoUrl.pathname.slice(1)
            : videoUrl.searchParams.get('v');
        if (!videoId) return;

        const iframe = document.createElement('iframe');
        iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?autoplay=1&rel=0`;
        iframe.title = 'Video: cómo funciona Horalia';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;
        videoFrame.replaceChildren(iframe);
        previousFocus = document.activeElement;
        videoModal.classList.add('is-open');
        videoModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('video-modal-open');
        videoDialog.focus();
    });

    videoModal?.querySelectorAll('[data-video-close]').forEach((element) => {
        element.addEventListener('click', closeVideo);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeVideo();
    });

    const legalModal = document.querySelector('[data-legal-modal]');
    const legalDialog = legalModal?.querySelector('.legal-modal-dialog');
    const legalOpenButtons = document.querySelectorAll('[data-legal-open]');
    let legalPreviousFocus = null;

    const closeLegalModal = () => {
        if (!legalModal?.classList.contains('is-open')) return;
        legalModal.classList.remove('is-open');
        legalModal.setAttribute('aria-hidden', 'true');
        legalModal.inert = true;
        document.body.classList.remove('legal-modal-open');
        legalPreviousFocus?.focus();
    };

    legalOpenButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!legalModal || !legalDialog) return;
            legalPreviousFocus = document.activeElement;
            legalModal.inert = false;
            legalModal.classList.add('is-open');
            legalModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('legal-modal-open');
            legalDialog.focus();
        });
    });

    legalModal?.querySelectorAll('[data-legal-close]').forEach((element) => {
        element.addEventListener('click', closeLegalModal);
    });

    legalModal?.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab' || !legalDialog) return;
        const focusable = [...legalDialog.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')];
        if (focusable.length === 0) {
            event.preventDefault();
            legalDialog.focus();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === legalDialog)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeLegalModal();
    });
})();
