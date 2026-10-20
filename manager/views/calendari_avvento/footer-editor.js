// Calendar transport only; editor interactions are shared with Concorso.
(() => {
    let request;
    let generation = 0;
    window.adventRefreshFooterPreview = async () => {
        const current = ++generation;
        request?.abort();
        request = new AbortController();
        try {
            const response = await fetch('ajax/preview_advent_footer.php', {
                method: 'POST', body: new FormData(document.getElementById('advent-form')),
                signal: request.signal
            });
            if (!response.ok) throw new Error('Anteprima non disponibile');
            const html = await response.text();
            if (current === generation) document.getElementById('footer-layout-preview-iframe').srcdoc = html;
        } catch (error) {
            if (error.name !== 'AbortError') document.getElementById('advent-save-status').textContent = 'Anteprima footer non aggiornata. Controlla i campi e riprova.';
        }
    };
    $(function () {
        const section = $('#section-footer');
        section.on('input', '.color-swatch[data-target]', function () {
            $('#' + this.dataset.target).val(this.value).trigger('input');
        });
        section.on('input', '.color-code-input', function () {
            if (/^#[0-9a-f]{6}$/i.test(this.value)) section.find('[data-target="' + this.id + '"]').val(this.value);
        });
        function colors() { $('#footer-text-colors-panel').toggle($('#style_customization_footer_text_colors').is(':checked')); }
        $('#style_customization_footer_text_colors').on('change', colors);
        colors();
        section.on('change', 'input[type=file]', function () {
            const file = this.files[0];
            if (!file) return;
            const image = document.getElementById(this.dataset.previewTarget);
            if (image) { image.src = URL.createObjectURL(file); image.classList.remove('d-none'); }
            $(this).next('.custom-file-label').text(file.name);
        });
        $('#advent-step-5').on('input change', 'input, select, textarea', function () {
            if (typeof window.scheduleFooterPreview === 'function') window.scheduleFooterPreview();
        });
    });
})();
