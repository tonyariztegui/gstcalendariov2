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
    form.addEventListener('input', () => { dirty = true; status.textContent = 'Hai modifiche da salvare. L’anteprima non pubblica il calendario.'; });
    document.getElementById('cal-published').addEventListener('change', () => { dirty = true; status.textContent = 'Stato di pubblicazione modificato: salva la configurazione per applicarlo.'; });
    form.addEventListener('submit', event => {
        if (event.submitter?.value === 'preview') return;
        dirty = false;
        status.textContent = 'Salvataggio in corso…';
        form.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('beforeunload', event => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });
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

