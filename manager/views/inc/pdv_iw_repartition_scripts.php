<?php
/** Script AJAX premi aggiuntivi (Concorso step IW). */
if (empty($pdvIwPanelReady) || empty($extraPrizesPlanIw)) {
    return;
}
$ajaxBaseJs = json_encode(isset($pdvIwAjaxBase) ? (string) $pdvIwAjaxBase : 'res/inc/', JSON_UNESCAPED_SLASHES);
?>
<script>
jQuery(function ($) {
    var ajaxBase = <?php echo $ajaxBaseJs; ?>;
    function ajaxUrl(file) {
        return ajaxBase + file;
    }
    function updateExtraSum($form) {
        var total = parseInt($form.data('extra-total'), 10) || 0;
        var sum = 0;
        $form.find('.js-pdv-extra-slot').each(function () {
            sum += parseInt($(this).val(), 10) || 0;
        });
        var $strong = $form.find('.js-extra-sum');
        $strong.text(sum);
        $strong.toggleClass('text-success', sum === total).toggleClass('text-danger', sum !== total);
    }
    $('.form-pdv-premio-extra').each(function () {
        updateExtraSum($(this));
    });
    function applyPdvExtraFilter($form) {
        var $input = $form.find('.js-pdv-extra-filter');
        var raw = ($input.val() || '').toString().trim().toLowerCase();
        var q = raw.replace(/\s+/g, ' ');
        var nVisible = 0;
        $form.find('tbody tr.js-pdv-extra-row').each(function () {
            var hay = ($(this).attr('data-pdv-search') || '').toString().toLowerCase();
            var show = q === '' || hay.indexOf(q) !== -1;
            $(this).toggle(show);
            if (show) {
                nVisible += 1;
            }
        });
        var $empty = $form.find('.js-pdv-extra-no-match');
        if (!$empty.length) {
            $empty = $('<p class="small text-warning mb-0 js-pdv-extra-no-match" style="display:none">Nessun punto vendita corrisponde alla ricerca.</p>');
            $form.find('.table-responsive').after($empty);
        }
        $empty.toggle(nVisible === 0 && q !== '');
    }
    $(document).on('input', '.form-pdv-premio-extra .js-pdv-extra-filter', function () {
        applyPdvExtraFilter($(this).closest('.form-pdv-premio-extra'));
    });
    $('.form-pdv-premio-extra').each(function () {
        applyPdvExtraFilter($(this));
    });
    $(document).on('input change', '.form-pdv-premio-extra .js-pdv-extra-slot', function () {
        updateExtraSum($(this).closest('.form-pdv-premio-extra'));
    });
    $(document).on('click', '.js-pdv-extra-reset', function () {
        var $form = $(this).closest('.form-pdv-premio-extra');
        $form.find('.js-pdv-extra-slot').val(0);
        updateExtraSum($form);
    });
    $(document).on('click', '.js-pdv-extra-save', function () {
        var $form = $(this).closest('.form-pdv-premio-extra');
        var prizeId = $form.data('prize-id');
        var extraTotal = parseInt($form.data('extra-total'), 10) || 0;
        var sum = 0;
        $form.find('.js-pdv-extra-slot').each(function () {
            sum += parseInt($(this).val(), 10) || 0;
        });
        if (sum !== extraTotal) {
            var msg = 'La somma degli slot aggiuntivi deve essere ' + extraTotal + ' (attuale: ' + sum + ').';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Controllo', text: msg });
            } else {
                window.alert(msg);
            }
            return;
        }
        var run = function () {
            $.ajax({
                url: ajaxUrl('pdv_premio_extra_save.php'),
                method: 'POST',
                data: $form.find(':input').serialize() + '&prize_id=' + encodeURIComponent(prizeId),
                dataType: 'json'
            }).done(function (res) {
                if (res && res.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'OK', text: res.message || 'Salvato.' }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        window.alert(res.message || 'Salvato.');
                        window.location.reload();
                    }
                } else {
                    var m = (res && res.message) ? res.message : 'Salvataggio non riuscito.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Errore', text: m });
                    } else {
                        window.alert(m);
                    }
                }
            }).fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore di rete o server.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Errore', text: msg });
                } else {
                    window.alert(msg);
                }
            });
        };
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Salvare premi aggiuntivi?',
                text: 'Verrà rigenerata pdv_premio_budget per questo premio.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sì, salva',
                cancelButtonText: 'Annulla',
                reverseButtons: true
            }).then(function (r) {
                if (r.isConfirmed) {
                    run();
                }
            });
        } else if (window.confirm('Salvare premi aggiuntivi?')) {
            run();
        }
    });
});
</script>
