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
        const surface = event.target.closest('[data-surface-pane]');
        if (surface) document.querySelector(`[data-surface="${surface.dataset.surfacePane}"]`)?.click();
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
        preview.classList.toggle('is-caption-hidden', document.getElementById('cal-grid-show-caption')?.value === '0');
        const expiredMessage = document.getElementById('cal-expired-message');
        const expiredGray = document.getElementById('cal-expired-grayscale');
        const expiredImage = document.getElementById('cal-expired-image');
        const expiredFitField = document.getElementById('cal-expired-image-fit-field');
        const expiredFit = document.getElementById('cal-expired-image-fit');
        const useBanner = expiredMessage?.value === 'banner';
        const usePromotion = (expiredImage?.value || 'promotion') !== 'case';
        if (expiredFitField) expiredFitField.hidden = !usePromotion;
        preview.classList.toggle('is-expired-banner', Boolean(useBanner));
        preview.classList.toggle('is-expired-gray', expiredGray?.value === '1');
        preview.classList.toggle('is-expired-promotion', usePromotion);
        preview.classList.toggle('is-expired-promo-cover', Boolean(usePromotion && expiredFit?.value === 'cover'));
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
    document.getElementById('cal-grid-show-caption')?.addEventListener('change', updatePreview);
    document.getElementById('cal-expired-message')?.addEventListener('change', updatePreview);
    document.getElementById('cal-expired-grayscale')?.addEventListener('change', updatePreview);
    document.getElementById('cal-expired-image')?.addEventListener('change', updatePreview);
    document.getElementById('cal-expired-image-fit')?.addEventListener('change', updatePreview);
    device.addEventListener('change', updatePreview);
    updatePreview();
})();
(() => {
    const frame = document.getElementById('cal-brand-frame');
    const usage = document.getElementById('cal-logo-replaces-heading');
    const controls = document.querySelector('.advent-logo-sizes');
    const logo = document.getElementById('cal-brand-logo');
    const row = document.getElementById('cal-brand-row');
    if (!frame || !usage || !controls || !logo || !row) return;
    const introField = document.getElementById('cal-intro-field');
    const intro = document.getElementById('cal-intro');
    const title = document.getElementById('cal-title');
    const titlePreview = document.getElementById('cal-brand-title');
    const introPreview = document.getElementById('cal-brand-intro');
    const empty = document.getElementById('cal-brand-logo-empty');
    const status = document.getElementById('cal-brand-preview-status');
    const tiles = Array.from(row.querySelectorAll('[data-brand-tile]'));
    const labels = { desktop: 'Computer', tablet: 'Tablet', mobile: 'Smartphone' };
    let device = 'desktop';
    function imageUrl(name) {
        const field = document.querySelector(`input[name="${name}"]`)?.closest('.advent-image-field');
        if (!field) return '';
        const img = field.querySelector('.advent-image-preview');
        const path = field.querySelector('input.form-control')?.value.trim() || '';
        if (img && !img.hidden && img.getAttribute('src')) return img.src;
        return /^(https?:\/\/|\/)/.test(path) ? path : '';
    }
    function introLine() {
        const html = (window.jQuery && intro && jQuery(intro).data('summernote')) ? jQuery(intro).summernote('code') : (intro?.value || '');
        const withBreaks = html.replace(/<br\s*\/?>/gi, '\n').replace(/<\/p>/gi, '\n').replace(/<[^>]+>/g, '');
        return withBreaks.split('\n').map(part => part.replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim()).find(Boolean) || '';
    }
    function update() {
        const fullHeader = usage.value === '1';
        if (introField) introField.hidden = fullHeader;
        frame.classList.toggle('is-heading', fullHeader);
        frame.classList.toggle('is-separate', !fullHeader);
        frame.classList.toggle('is-desktop', device === 'desktop');
        frame.classList.toggle('is-tablet', device === 'tablet');
        frame.classList.toggle('is-mobile', device === 'mobile');
        document.querySelectorAll('[data-brand-width]').forEach(field => { field.hidden = field.dataset.brandWidth !== device && field.dataset.brandWidth !== 'wide'; });
        document.querySelectorAll('[data-brand-overlap]').forEach(field => { field.hidden = field.dataset.brandOverlap !== device; });
        document.querySelectorAll('[data-brand-device]').forEach(button => button.setAttribute('aria-selected', String(button.dataset.brandDevice === device)));
        const background = imageUrl(device === 'mobile' ? 'background_mobile' : device === 'tablet' ? 'background_tablet' : 'background') || imageUrl('background');
        frame.style.backgroundImage = background ? `url(${JSON.stringify(background)})` : '';
        const src = imageUrl('logo');
        if (src) {
            logo.src = src;
            logo.hidden = false;
            if (empty) empty.hidden = true;
        } else {
            logo.removeAttribute('src');
            logo.hidden = true;
            if (empty) empty.hidden = false;
        }
        const width = Math.max(60, Math.min(1000, Number(document.getElementById(`cal-logo-width-${device}`)?.value) || 700));
        logo.style.width = `min(100%, ${width}px)`;
        logo.style.maxWidth = 'none';
        logo.style.maxHeight = 'none';
        if (titlePreview) titlePreview.textContent = title?.value.trim() || 'Titolo del calendario';
        if (introPreview) introPreview.textContent = introLine();
        const columns = Math.max(1, Number(document.getElementById(`cal-grid-columns-${device}`)?.value) || { desktop: 4, tablet: 3, mobile: 2 }[device]);
        const gap = Math.max(0, Math.min(40, Number(document.getElementById('cal-grid-gap')?.value) || 16));
        const radius = Math.max(0, Math.min(32, Number(document.getElementById('cal-grid-tile-radius')?.value) || 16));
        const ratio = { square: '1', classic: '1.2', wide: '1.5' }[document.getElementById('cal-grid-tile-ratio')?.value] || '1.2';
        const fit = document.getElementById('cal-grid-image-fit')?.value === 'cover' ? 'cover' : 'contain';
        row.style.gridTemplateColumns = `repeat(${columns}, minmax(0, 1fr))`;
        row.style.gap = `${gap}px`;
        tiles.forEach((tile, index) => {
            tile.hidden = index >= columns;
            tile.style.borderRadius = `${radius}px`;
            tile.style.aspectRatio = ratio;
            const image = tile.querySelector('img');
            if (image) image.style.objectFit = fit;
        });
        const overlap = document.getElementById(`cal-logo-overlap-${device}`);
        const overlapValue = Number(overlap?.value) || 0;
        row.style.marginTop = `${overlapValue}px`;
        if (overlap) {
            const output = overlap.parentElement?.querySelector('output');
            if (output) output.textContent = `${overlapValue} px`;
        }
        if (status) status.textContent = `${labels[device]} · sovrapposizione ${overlapValue} px`;
    }
    document.querySelectorAll('[data-brand-device]').forEach(button => button.addEventListener('click', () => { device = button.dataset.brandDevice; update(); }));
    ['logo', 'background', 'background_tablet', 'background_mobile'].forEach(name => {
        const field = document.querySelector(`input[name="${name}"]`)?.closest('.advent-image-field');
        field?.querySelector('input[type="file"]')?.addEventListener('change', () => setTimeout(update, 0));
        field?.querySelector('input.form-control')?.addEventListener('input', update);
    });
    usage.addEventListener('change', update);
    title?.addEventListener('input', update);
    intro?.addEventListener('input', update);
    document.querySelectorAll('[name^="logo_width_"], [name^="logo_overlap_"], [name^="grid_columns_"], #cal-grid-gap, #cal-grid-tile-radius, #cal-grid-tile-ratio, #cal-grid-image-fit').forEach(field => {
        field.addEventListener('input', update);
        field.addEventListener('change', update);
    });
    update();
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
(() => {
    const help = {
        prize: 'Immagine del premio, descrizione, codice e barcode.',
        video: 'Incolla un link YouTube. Il video si apre nella casella. L’immagine compare solo quando la casella è scaduta.',
        link: 'Immagine link e pulsante. Il pulsante apre il link, per esempio una playlist Spotify.'
    };
    function applyDayType(select) {
        const editor = select.closest('.advent-day-editor');
        if (!editor) return;
        editor.querySelectorAll('[data-day-panel]').forEach(panel => {
            const active = panel.dataset.dayPanel.split(/\s+/).includes(select.value);
            panel.hidden = !active;
            panel.querySelectorAll('input, textarea, select').forEach(field => {
                if (field.name && field.name.indexOf('[media_url]') !== -1) field.disabled = !active;
            });
        });
        const imagePanel = editor.querySelector('[data-day-image]');
        if (imagePanel) {
            const imageLabel = { link: 'Immagine link', video: 'Immagine della casella scaduta' }[select.value] || 'Immagine del premio';
            const label = imagePanel.querySelector('label');
            const file = imagePanel.querySelector('input[type="file"]');
            const preview = imagePanel.querySelector('.advent-image-preview');
            if (label) label.textContent = imageLabel;
            if (file) file.setAttribute('aria-label', `Carica ${imageLabel}`);
            if (preview) preview.alt = `Anteprima ${imageLabel}`;
        }
        const note = editor.querySelector('.js-day-type-help');
        if (note) note.textContent = help[select.value] || help.prize;
    }
    document.querySelectorAll('.js-day-type').forEach(select => {
        select.addEventListener('change', () => applyDayType(select));
        applyDayType(select);
    });
})();
const adventOverlayOpacity = document.getElementById('cal-modal-overlay-opacity');
if (adventOverlayOpacity) {
    const adventOverlayOutput = adventOverlayOpacity.parentElement.querySelector('output');
    const updateAdventOverlayOutput = () => { adventOverlayOutput.value = `${adventOverlayOpacity.value}%`; };
    adventOverlayOpacity.addEventListener('input', updateAdventOverlayOutput);
    updateAdventOverlayOutput();
}
(() => {
    const tabs = [...document.querySelectorAll('[data-container-tab]')];
    const panels = [...document.querySelectorAll('[data-container-type]')];
    if (!tabs.length || !panels.length) return;
    const accent = document.getElementById('cal-accent');
    const appearance = document.querySelector('.advent-container-appearance');
    function field(panel, key) {
        return panel?.querySelector(`[name="containers[${panel.dataset.containerType}][${key}]"]`) || null;
    }
    function appearanceField(key) {
        return appearance?.querySelector(`[name="containers[appearance][${key}]"]`) || null;
    }
    function numberFrom(input, fallback, min, max) {
        const value = Number(input?.value);
        if (!Number.isFinite(value)) return fallback;
        return Math.max(min, Math.min(max, value));
    }
    function imageSource(input) {
        const wrap = input ? input.closest('.advent-image-field') : null;
        const preview = wrap ? wrap.querySelector('.advent-image-preview') : null;
        if (preview && !preview.hidden && preview.getAttribute('src')) return preview.src;
        return input?.value.trim() || '';
    }
    function syncAppearanceFields() {
        if (!appearance) return;
        const backgroundMode = appearanceField('background_mode')?.value === 'image' ? 'image' : 'color';
        const closeKind = appearanceField('close_kind')?.value === 'image' ? 'image' : 'icon';
        appearance.querySelectorAll('[data-container-background]').forEach(item => {
            item.hidden = item.dataset.containerBackground !== backgroundMode;
        });
        appearance.querySelectorAll('[data-container-close-icon]').forEach(item => { item.hidden = closeKind === 'image'; });
        const closeImageField = appearance.querySelector('[data-container-close-image]');
        if (closeImageField) closeImageField.hidden = closeKind !== 'image';
    }
    function update(type) {
        const panel = document.querySelector(`[data-container-type="${type}"]`);
        const preview = document.querySelector(`[data-container-preview="${type}"]`);
        const dialog = preview?.querySelector('.advent-container-preview-dialog');
        const stage = preview?.querySelector('.advent-container-preview-stage');
        const device = preview?.querySelector('.js-container-device')?.value || 'desktop';
        if (!dialog || !stage) return;
        const width = numberFrom(appearanceField('width_' + device), 680, 280, 900);
        const padding = numberFrom(appearanceField('padding'), 28, 12, 64);
        const radius = numberFrom(appearanceField('radius'), 20, 0, 40);
        const media = numberFrom(field(panel, 'media_max_height'), 420, 120, 640);
        const closeSize = numberFrom(appearanceField('close_size'), 42, 28, 72);
        const closeKind = appearanceField('close_kind')?.value === 'image' ? 'image' : 'icon';
        const closeShape = appearanceField('close_shape')?.value === 'square' ? 'square' : 'circle';
        const backgroundMode = appearanceField('background_mode')?.value === 'image' ? 'image' : 'color';
        stage.style.width = { mobile: '390px', tablet: '768px', desktop: '100%' }[device] || '100%';
        stage.style.maxWidth = '100%';
        dialog.style.width = `min(${width}px, calc(100% - 16px))`;
        dialog.style.borderRadius = `${radius}px`;
        dialog.style.padding = `${padding + 14}px ${padding}px ${padding}px`;
        dialog.style.backgroundColor = appearanceField('background_color')?.value || '#fffdf9';
        const background = backgroundMode === 'image' ? imageSource(appearanceField('background_image')) : '';
        dialog.style.backgroundImage = background ? `url("${background.replace(/"/g, '')}")` : 'none';
        const close = preview.querySelector('.advent-container-preview-close');
        if (close) {
            close.hidden = false;
            close.style.width = `${closeSize}px`;
            close.style.height = `${closeSize}px`;
            close.style.fontSize = `${Math.round(closeSize * 0.67)}px`;
            close.style.borderRadius = closeShape === 'square' ? '8px' : '50%';
            close.style.color = appearanceField('close_color')?.value || '#243c34';
            close.style.backgroundColor = appearanceField('close_background')?.value || '#ffffff';
            close.style.borderColor = appearanceField('close_border')?.value || '#d8ddd6';
            const icon = close.querySelector('[data-preview-close-icon]');
            const image = close.querySelector('[data-preview-close-image]');
            const closeImage = closeKind === 'image' ? imageSource(appearanceField('close_image')) : '';
            if (icon) icon.hidden = Boolean(closeImage);
            if (image) {
                image.hidden = !closeImage;
                if (closeImage) image.src = closeImage;
                else image.removeAttribute('src');
            }
        }
        const photo = preview.querySelector('.advent-container-preview-media.is-photo');
        if (photo) photo.style.height = `${media}px`;
        const video = preview.querySelector('.advent-container-preview-media.is-video');
        if (video) {
            const stageMax = { mobile: 390, tablet: 768, desktop: 960 }[device] || 960;
            const dialogWidth = Math.min(width, stageMax - 40);
            video.style.width = '100%';
            video.style.aspectRatio = 'auto';
            video.style.height = `${Math.round(Math.min(media, dialogWidth * 9 / 16))}px`;
        }
        preview.querySelector('.advent-container-preview-kicker').hidden = field(panel, 'show_kicker')?.value === '0';
        preview.querySelector('.advent-container-preview-title').hidden = field(panel, 'show_title')?.value === '0';
        preview.querySelector('.advent-container-preview-description').hidden = field(panel, 'show_description')?.value === '0';
        const code = preview.querySelector('.advent-container-preview-code small');
        if (code) code.hidden = field(panel, 'show_code')?.value === '0';
        const action = preview.querySelector('.advent-container-preview-action');
        if (action && accent) action.style.background = accent.value || '#185844';
        fitPreview(preview);
    }
    function fitPreview(preview) {
        const stage = preview.querySelector('.advent-container-preview-stage');
        const dialog = preview.querySelector('.advent-container-preview-dialog');
        if (!preview || !stage || !dialog) return;
        dialog.style.transform = 'none';
        const wrap = preview.closest('.advent-container-previews');
        if (!wrap) return;
        if (preview.hidden || window.innerWidth < 1100) {
            wrap.style.position = '';
            return;
        }
        wrap.style.position = wrap.offsetHeight > window.innerHeight - 24 ? 'static' : 'sticky';
    }
    const types = () => panels.map(panel => panel.dataset.containerType);
    const selectedType = () => tabs.find(tab => tab.getAttribute('aria-selected') === 'true')?.dataset.containerTab || 'prize';
    function selectType(type) {
        tabs.forEach(tab => tab.setAttribute('aria-selected', String(tab.dataset.containerTab === type)));
        panels.forEach(panel => { panel.hidden = panel.dataset.containerType !== type; });
        document.querySelectorAll('[data-container-preview]').forEach(preview => { preview.hidden = preview.dataset.containerPreview !== type; });
        update(type);
    }
    const contenuto = document.querySelector('.advent-container-content');
    let switchingSection = false;
    function showAppearance() {
        switchingSection = true;
        if (appearance) appearance.open = true;
        if (contenuto) contenuto.open = false;
        switchingSection = false;
        update(selectedType());
    }
    function showContent() {
        switchingSection = true;
        if (contenuto) contenuto.open = true;
        if (appearance) appearance.open = false;
        switchingSection = false;
        update(selectedType());
    }
    appearance?.addEventListener('toggle', () => {
        if (switchingSection) return;
        if (appearance.open) showAppearance();
        else showContent();
    });
    contenuto?.addEventListener('toggle', () => {
        if (switchingSection) return;
        if (contenuto.open) showContent();
        else showAppearance();
    });
    tabs.forEach(tab => tab.addEventListener('click', () => selectType(tab.dataset.containerTab)));
    panels.forEach(panel => {
        panel.addEventListener('input', () => update(panel.dataset.containerType));
        panel.addEventListener('change', () => update(panel.dataset.containerType));
    });
    document.querySelectorAll('[data-container-preview]').forEach(preview => {
        preview.addEventListener('input', () => update(preview.dataset.containerPreview));
        preview.addEventListener('change', () => update(preview.dataset.containerPreview));
    });
    appearance?.addEventListener('input', () => { syncAppearanceFields(); types().forEach(update); });
    appearance?.addEventListener('change', () => { syncAppearanceFields(); types().forEach(update); });
    syncAppearanceFields();
    types().forEach(update);
    accent?.addEventListener('input', () => types().forEach(update));
    window.addEventListener('resize', () => {
        const visible = document.querySelector('[data-container-preview]:not([hidden])');
        if (visible) fitPreview(visible);
    });
})();
(() => {
    const buttons = [...document.querySelectorAll('[data-surface]')];
    const panes = [...document.querySelectorAll('[data-surface-pane]')];
    if (!buttons.length || !panes.length) return;
    function show(name) {
        buttons.forEach(button => button.setAttribute('aria-selected', String(button.dataset.surface === name)));
        panes.forEach(pane => { pane.hidden = pane.dataset.surfacePane !== name; });
        window.dispatchEvent(new Event('resize'));
    }
    buttons.forEach(button => button.addEventListener('click', () => show(button.dataset.surface)));
})();
const adventFooterOpacity = document.getElementById('cal-footer-opacity');
if (adventFooterOpacity) {
    const adventFooterOutput = adventFooterOpacity.parentElement.querySelector('output');
    const updateAdventFooterOutput = () => { adventFooterOutput.value = `${adventFooterOpacity.value}%`; };
    adventFooterOpacity.addEventListener('input', updateAdventFooterOutput);
    updateAdventFooterOutput();
}

