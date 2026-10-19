(() => {
    const form = document.getElementById('advent-form');
    const status = document.getElementById('advent-save-status');
    const stepInput = document.getElementById('advent-current-step');
    const tabs = [...document.querySelectorAll('[data-calendar-step]')];
    const panels = [...form.querySelectorAll('.config-step')];
    const previous = document.getElementById('advent-prev');
    const next = document.getElementById('advent-next');
    let step = Number(stepInput.value) || 1;
    function showStep(value) {
        step = Math.max(1, Math.min(tabs.length, value));
        stepInput.value = String(step);
        tabs.forEach((tab, index) => {
            tab.classList.toggle('active', index + 1 === step);
            tab.setAttribute('aria-selected', String(index + 1 === step));
            tab.tabIndex = index + 1 === step ? 0 : -1;
            panels[index].hidden = index + 1 !== step;
        });
        document.getElementById('advent-progress-text').textContent = `Passaggio ${step} di ${tabs.length}`;
        document.getElementById('advent-progress-bar').style.width = `${step / tabs.length * 100}%`;
        previous.disabled = step === 1;
        next.disabled = step === tabs.length;
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => showStep(index + 1));
        tab.addEventListener('keydown', event => {
            const target = { ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1 }[event.key];
            if (target === undefined) return;
            event.preventDefault();
            showStep(target + 1);
            tabs[target].focus();
        });
    });
    previous.addEventListener('click', () => { showStep(step - 1); tabs[step - 1].focus(); });
    next.addEventListener('click', () => { showStep(step + 1); tabs[step - 1].focus(); });
    form.addEventListener('invalid', event => {
        const panel = event.target.closest('.config-step');
        if (panel) showStep(panels.indexOf(panel) + 1);
        const details = event.target.closest('details');
        if (details) details.open = true;
    }, true);
    showStep(step);
    let dirty = false;
    form.addEventListener('input', event => { if (!event.target.name) return; dirty = true; status.textContent = 'Hai modifiche da salvare. L’anteprima non pubblica il calendario.'; });
    document.getElementById('cal-published').addEventListener('change', () => { dirty = true; status.textContent = 'Stato di pubblicazione modificato: salva la configurazione per applicarlo.'; });
    form.addEventListener('submit', event => {
        if (event.submitter?.value === 'preview') return;
        dirty = false;
        status.textContent = 'Salvataggio in corso…';
        form.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
})();
(() => {
    const presets = [...document.querySelectorAll('[data-grid-preset]')];
    const fields = {
        mobile: document.getElementById('cal-grid-columns-mobile'),
        tablet: document.getElementById('cal-grid-columns-tablet'),
        desktop: document.getElementById('cal-grid-columns-desktop'),
        gap: document.getElementById('cal-grid-gap'),
        ratio: document.getElementById('cal-grid-tile-ratio'),
        radius: document.getElementById('cal-grid-tile-radius'),
        fit: document.getElementById('cal-grid-image-fit')
    };
    const preview = document.getElementById('cal-grid-preview');
    const device = document.getElementById('cal-grid-preview-device');
    const message = document.getElementById('cal-grid-preset-status');
    const advanced = document.querySelector('.advent-grid-advanced');
    if (!preview || !device || Object.values(fields).some(field => !field)) return;
    const ratios = { square: '1', classic: '1.2', wide: '1.5' };
    let firstRender = true;
    function updatePreview() {
        const selectedDevice = device.value;
        const columns = Number(fields[selectedDevice].value) || 2;
        preview.style.maxWidth = { mobile: '320px', tablet: '480px', desktop: '620px' }[selectedDevice];
        preview.style.gridTemplateColumns = `repeat(${columns}, minmax(0, 1fr))`;
        preview.style.gap = `${Math.max(0, Math.min(40, Number(fields.gap.value) || 0))}px`;
        preview.style.setProperty('--preview-ratio', ratios[fields.ratio.value] || '1.2');
        preview.style.setProperty('--preview-radius', `${Math.max(0, Math.min(32, Number(fields.radius.value) || 0))}px`);
        preview.dataset.fit = fields.fit.value;
        const resolutions = { square: '1200 × 1200 px', classic: '1200 × 1000 px', wide: '1200 × 800 px' };
        document.querySelectorAll('[data-advent-cover-resolution]').forEach(element => { element.textContent = resolutions[fields.ratio.value] || resolutions.classic; });
        const fitNote = fields.fit.value === 'cover'
            ? 'Riempi la casella: una proporzione diversa viene ritagliata, senza deformare l’immagine.'
            : 'Mostra l’immagine intera: una proporzione diversa lascia margini nella casella.';
        document.querySelectorAll('[data-advent-fit-note]').forEach(element => { element.textContent = fitNote; });
        const matching = presets.find(preset => Object.entries(fields).every(([key, field]) => preset.dataset[key] === field.value));
        presets.forEach(preset => preset.setAttribute('aria-pressed', String(preset === matching)));
        message.textContent = matching ? `Modello ${matching.querySelector('strong').textContent} selezionato.` : 'Modello personalizzato.';
        if (firstRender && !matching && advanced) advanced.open = true;
        firstRender = false;
    }
    presets.forEach(preset => preset.addEventListener('click', () => {
        Object.entries(fields).forEach(([key, field]) => { field.value = preset.dataset[key]; });
        fields.mobile.dispatchEvent(new Event('input', { bubbles: true }));
        updatePreview();
    }));
    Object.values(fields).forEach(field => { field.addEventListener('input', updatePreview); field.addEventListener('change', updatePreview); });
    device.addEventListener('change', updatePreview);
    updatePreview();
})();
(() => {
    const preview = document.getElementById('cal-logo-size-preview');
    const device = document.getElementById('cal-logo-preview-device');
    const usage = document.getElementById('cal-logo-replaces-heading');
    const controls = document.querySelector('.advent-logo-sizes');
    if (!preview || !device || !usage || !controls) return;
    const sizes = Object.fromEntries(['mobile', 'tablet', 'desktop', 'wide'].map(name => [name, document.getElementById(`cal-logo-width-${name}`)]));
    const canvas = preview.closest('.advent-logo-preview-canvas');
    const empty = document.getElementById('cal-logo-preview-empty');
    function updateLogoPreview() {
        const selected = device.value;
        canvas.style.maxWidth = { mobile: '320px', tablet: '480px', desktop: '620px', wide: '800px' }[selected];
        preview.style.width = `${Math.max(60, Math.min(1000, Number(sizes[selected]?.value) || 700))}px`;
    }
    function updateUsage() { controls.hidden = usage.value !== '1'; }
    Object.values(sizes).forEach(input => input?.addEventListener('input', updateLogoPreview));
    device.addEventListener('change', updateLogoPreview);
    usage.addEventListener('change', updateUsage);
    const upload = document.querySelector('input[name="logo_upload"]');
    const savedSrc = preview.getAttribute('src');
    let temporaryUrl;
    upload?.addEventListener('change', () => {
        if (temporaryUrl) URL.revokeObjectURL(temporaryUrl);
        temporaryUrl = upload.files?.[0] ? URL.createObjectURL(upload.files[0]) : undefined;
        if (temporaryUrl) {
            preview.src = temporaryUrl;
            preview.hidden = false;
            if (empty) empty.hidden = true;
        } else if (savedSrc) {
            preview.src = savedSrc;
            preview.hidden = false;
            if (empty) empty.hidden = true;
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
            if (empty) empty.hidden = false;
        }
    });
    updateLogoPreview();
    updateUsage();
})();
(() => {
    document.querySelectorAll('.advent-image-field').forEach(field => {
        const fileInput = field.querySelector('input[type="file"]');
        const pathInput = field.querySelector('input.form-control');
        const preview = field.querySelector('.advent-image-preview');
        if (!fileInput || !pathInput || !preview) return;
        const savedSrc = preview.getAttribute('src') || '';
        let temporaryUrl = '';
        const show = src => {
            if (!src) {
                preview.removeAttribute('src');
                preview.hidden = true;
                return;
            }
            preview.hidden = false;
            if (preview.getAttribute('src') !== src) preview.src = src;
        };
        const syncLogoHeading = src => {
            if (fileInput.name !== 'logo_upload') return;
            const logoPreview = document.getElementById('cal-logo-size-preview');
            const empty = document.getElementById('cal-logo-preview-empty');
            if (!logoPreview) return;
            if (src) {
                logoPreview.hidden = false;
                if (logoPreview.getAttribute('src') !== src) logoPreview.src = src;
                if (empty) empty.hidden = true;
            } else {
                logoPreview.removeAttribute('src');
                logoPreview.hidden = true;
                if (empty) empty.hidden = false;
            }
        };
        fileInput.addEventListener('change', () => {
            if (temporaryUrl) URL.revokeObjectURL(temporaryUrl);
            const file = fileInput.files && fileInput.files[0];
            if (file && file.type.indexOf('image/') === 0) {
                temporaryUrl = URL.createObjectURL(file);
                show(temporaryUrl);
                syncLogoHeading(temporaryUrl);
                return;
            }
            temporaryUrl = '';
            const fallback = pathInput.value.trim() || savedSrc;
            show(fallback);
            syncLogoHeading(fallback);
        });
        pathInput.addEventListener('input', () => {
            if (temporaryUrl) return;
            const value = pathInput.value.trim();
            show(value);
            syncLogoHeading(value);
        });
    });
})();
const adventOverlayOpacity = document.getElementById('cal-modal-overlay-opacity');
if (adventOverlayOpacity) {
    const adventOverlayOutput = adventOverlayOpacity.parentElement.querySelector('output');
    const updateAdventOverlayOutput = () => { adventOverlayOutput.value = `${adventOverlayOpacity.value}%`; };
    adventOverlayOpacity.addEventListener('input', updateAdventOverlayOutput);
    updateAdventOverlayOutput();
}
const adventFooterOpacity = document.getElementById('cal-footer-opacity');
if (adventFooterOpacity) {
    const adventFooterOutput = adventFooterOpacity.parentElement.querySelector('output');
    const updateAdventFooterOutput = () => { adventFooterOutput.value = `${adventFooterOpacity.value}%`; };
    adventFooterOpacity.addEventListener('input', updateAdventFooterOutput);
    updateAdventFooterOutput();
}

