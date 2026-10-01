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
})();
