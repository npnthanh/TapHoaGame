document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    const key = document.getElementById(button.dataset.copy);
    if (!key) return;
    try {
        await navigator.clipboard.writeText(key.textContent.trim());
        const originalText = button.textContent;
        button.textContent = '✓ Đã sao chép';
        button.style.borderColor = 'var(--green)';
        button.style.color = 'var(--green)';
        setTimeout(() => {
            button.textContent = originalText;
            button.style.borderColor = '';
            button.style.color = '';
        }, 2200);
    } catch {
        const selection = window.getSelection();
        const range = document.createRange();
        range.selectNodeContents(key);
        selection.removeAllRanges();
        selection.addRange(range);
        button.textContent = 'Nhấn Ctrl+C';
    }
});

document.addEventListener('submit', (event) => {
    if (event.target.method.toLowerCase() !== 'post') return;
    const button = event.submitter;
    if (button && !button.hasAttribute('data-no-busy')) {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.style.opacity = '0.7';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
        const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
        const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
        const previews = [...carousel.querySelectorAll('[data-carousel-preview]')];
        let selected = 0;
        function show(index) {
            if (!slides.length) return;
            selected = (index + slides.length) % slides.length;
            slides.forEach((slide, i) => { slide.hidden = i !== selected; });
            previews.forEach((preview) => {
                const isPrevious = preview.dataset.carouselPreview === 'previous';
                const neighbor = slides[(selected + (isPrevious ? -1 : 1) + slides.length) % slides.length];
                const title = neighbor.dataset.bannerTitle;
                const imageUrl = neighbor.querySelector('.slide-main-visual img')?.getAttribute('src');
                const image = preview.querySelector('[data-preview-image]');
                if (imageUrl) image.src = imageUrl;
                else image.removeAttribute('src');
                image.hidden = !imageUrl;
                preview.querySelector('[data-preview-title]').textContent = title;
                preview.setAttribute('aria-label', `${isPrevious ? 'Xem banner trước' : 'Xem banner tiếp theo'}: ${title}`);
            });
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === selected);
                dot.setAttribute('aria-pressed', String(i === selected));
            });
        }
        dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
        carousel.querySelector('.prev')?.addEventListener('click', () => show(selected - 1));
        carousel.querySelector('.next')?.addEventListener('click', () => show(selected + 1));
        previews.forEach((preview) => preview.addEventListener('click', () => show(selected + (preview.dataset.carouselPreview === 'previous' ? -1 : 1))));
        show(0);
    });
});
