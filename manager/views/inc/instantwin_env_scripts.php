<?php
/** Script AJAX salvataggio .env — doit être inclus après jQuery (footer). */
if (empty($iwEnvSaveUrl) && !empty($iwEnvAjaxBase)) {
    $iwEnvSaveUrl = $iwEnvAjaxBase . 'instantwin_env_save.php';
}
$iwEnvSaveUrlJs = json_encode($iwEnvSaveUrl ?? 'res/inc/instantwin_env_save.php', JSON_UNESCAPED_SLASHES);
?>
<script>
jQuery(function ($) {
    var saveUrlDefault = <?php echo $iwEnvSaveUrlJs; ?>;

    function isChecked(name) {
        var $el = $('#formInstantwinEnv').find('[name="' + name + '"]');
        if ($el.is(':checkbox')) {
            return $el.is(':checked');
        }
        return false;
    }

    function syncIwEnvDeps() {
        $('.js-iw-env-dep').each(function () {
            var key = $(this).data('show-if');
            $(this).toggle(isChecked(key));
        });
        var qd = ($('#iw-env-INSTANTWIN_QUOTA_DISTRIBUTION').val() || '').toLowerCase();
        $('.js-iw-env-quota-prog').toggle(qd === 'progressive');
    }

    $(document).on('change', '#formInstantwinEnv .js-iw-env-bool, #iw-env-INSTANTWIN_QUOTA_DISTRIBUTION', syncIwEnvDeps);
    syncIwEnvDeps();

    $(document).on('click', '#btnSaveInstantwinEnv', function () {
        var $btn = $(this);
        var $wrap = $('#formInstantwinEnv');
        if (!$wrap.length || $btn.prop('disabled')) {
            return;
        }
        var saveUrl = $wrap.data('save-url') || saveUrlDefault;
        $btn.prop('disabled', true);
        $.ajax({
            url: saveUrl,
            method: 'POST',
            data: $wrap.find(':input').serialize(),
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
                $btn.prop('disabled', false);
            }
        }).fail(function (xhr) {
            var msg = 'Erreur de sauvegarde';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                msg = 'Endpoint introuvable (404). URL : ' + saveUrl;
            } else if (xhr.status === 401) {
                msg = 'Session expirée — reconnectez-vous au manager.';
            } else if (xhr.responseText && xhr.responseText.indexOf('{') === 0) {
                try {
                    var j = JSON.parse(xhr.responseText);
                    if (j.message) {
                        msg = j.message;
                    }
                } catch (e) { /* ignore */ }
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Erreur', text: msg });
            } else {
                window.alert(msg);
            }
            $btn.prop('disabled', false);
        });
    });
});
</script>
