$(function () {
    const field = document.getElementById('cal-intro-field');
    const intro = document.getElementById('cal-intro');
    const step = document.getElementById('advent-step-2');
    if (!field || !intro || !step || typeof $.fn.summernote !== 'function') {
        return;
    }
    function visible() {
        return !field.hidden && !step.hidden;
    }
    function init() {
        if (!visible() || $(intro).data('summernote')) {
            return;
        }
        $(intro).summernote({
            height: 160,
            lang: 'it-IT',
            placeholder: 'Testo sotto il titolo',
            toolbar: [
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['paragraph']]
            ],
            disableDragAndDrop: true,
            callbacks: {
                onChange: function () {
                    intro.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });
        intro.dispatchEvent(new Event('input', { bubbles: true }));
    }
    new MutationObserver(init).observe(field, { attributes: true, attributeFilter: ['hidden'] });
    new MutationObserver(init).observe(step, { attributes: true, attributeFilter: ['hidden'] });
    init();
});
