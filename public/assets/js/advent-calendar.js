(() => {
    const modal = document.getElementById('advent-prize-modal');
    if (!modal || typeof modal.showModal !== 'function') return;

    const loading = modal.querySelector('.advent-modal-loading');
    const error = modal.querySelector('.advent-modal-error');
    const content = modal.querySelector('.advent-modal-content');
    const closeButton = modal.querySelector('.advent-modal-close');
    const title = document.getElementById('advent-modal-title');
    const day = document.getElementById('advent-modal-day');
    const description = document.getElementById('advent-modal-description');
    const image = document.getElementById('advent-modal-image');
    const video = document.getElementById('advent-modal-video');
    const videoFrame = video ? video.querySelector('iframe') : null;
    const linkWrap = document.getElementById('advent-modal-link');
    const link = linkWrap ? linkWrap.querySelector('a') : null;
    const barcode = document.getElementById('advent-modal-barcode');
    const barcodeImage = barcode.querySelector('img');
    const barcodeCode = barcode.querySelector('strong');
    let opener = null;
    let request = null;

    function resetModal() {
        loading.hidden = false;
        error.hidden = true;
        error.textContent = '';
        content.hidden = true;
        image.hidden = true;
        image.removeAttribute('src');
        image.alt = '';
        if (video) {
            video.hidden = true;
            if (videoFrame) videoFrame.removeAttribute('src');
        }
        if (linkWrap) {
            linkWrap.hidden = true;
            if (link) {
                link.removeAttribute('href');
                link.textContent = '';
            }
        }
        barcode.hidden = true;
        barcodeImage.removeAttribute('src');
    }

    async function openPrize(tile) {
        opener = tile;
        resetModal();
        modal.showModal();
        closeButton.focus();
        request?.abort();
        request = new AbortController();
        try {
            const url = new URL(tile.href, window.location.href);
            url.searchParams.set('advent_json', '1');
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.message || 'Premio non disponibile.');
            const prize = payload.data;
            day.textContent = `CASELLA ${prize.day} · ${prize.date_label}`;
            title.textContent = prize.title;
            description.textContent = prize.description;
            if (prize.type !== 'video' && prize.image_url) {
                image.src = prize.image_url;
                image.alt = prize.title;
                image.hidden = false;
            }
            if (prize.youtube_id && video && videoFrame) {
                videoFrame.src = `https://www.youtube-nocookie.com/embed/${prize.youtube_id}`;
                videoFrame.title = prize.title;
                video.hidden = false;
            }
            if (prize.type === 'link' && prize.media_url && link && linkWrap) {
                link.href = prize.media_url;
                link.textContent = prize.link_label || 'Apri link';
                linkWrap.hidden = false;
            }
            if (prize.type !== 'video' && prize.type !== 'link' && prize.barcode_svg_data_uri) {
                barcodeImage.src = prize.barcode_svg_data_uri;
                barcodeImage.alt = `Barcode EAN-13 ${prize.barcode_ean13}`;
                barcodeCode.textContent = prize.code;
                barcode.hidden = false;
            }
            loading.hidden = true;
            content.hidden = false;
            title.focus({ preventScroll: true });
        } catch (exception) {
            if (exception.name === 'AbortError') return;
            loading.hidden = true;
            error.textContent = exception.message || 'Non è stato possibile aprire il premio. Riprova.';
            error.hidden = false;
        }
    }

    document.querySelectorAll('a.advent-tile.is-open').forEach(link => {
        link.addEventListener('click', event => {
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            openPrize(link);
        });
    });
    closeButton.addEventListener('click', () => modal.close());
    modal.addEventListener('click', event => {
        if (event.target === modal) modal.close();
    });
    modal.addEventListener('close', () => {
        request?.abort();
        opener?.focus();
    });
})();
