$(document).ready(function() {
    // Waiting Page - Mode Background
    $('#waiting_page_bg_mode').on('change', function() {
        if ($(this).val() === 'color') {
            $('.waiting-page-color-group').show();
            $('.waiting-page-image-group').hide();
        } else {

            $('.waiting-page-color-group').hide();
            $('.waiting-page-image-group').show();
        }
    }).trigger('change');
    
    // Waiting Page - Sincronizzazione colori
    $('#waiting_page_bg_color_picker').on('input', function() {
        $('#waiting_page_bg_color').val($(this).val());
    });
    $('#waiting_page_bg_color').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(val)) {
            $('#waiting_page_bg_color_picker').val(val);
        }
    });
    $('#waiting_page_text_color_picker').on('input', function() {
        $('#waiting_page_text_color').val($(this).val());
    });
    $('#waiting_page_text_color').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(val)) {
            $('#waiting_page_text_color_picker').val(val);
        }
    });
    
    // Waiting Page - Opacità
    $('#waiting_page_opacity_slider').on('input', function() {
        $('#waiting_page_opacity').val($(this).val());
    });
    $('#waiting_page_opacity').on('input', function() {
        var val = Math.max(0, Math.min(100, parseInt($(this).val()) || 100));
        $(this).val(val);
        $('#waiting_page_opacity_slider').val(val);
    });
    
    // Waiting Page Ended - Mode Background
    $('#waiting_page_ended_bg_mode').on('change', function() {
        if ($(this).val() === 'color') {
            $('.waiting-page-ended-color-group').show();
            $('.waiting-page-ended-image-group').hide();
        } else {

            $('.waiting-page-ended-color-group').hide();
            $('.waiting-page-ended-image-group').show();
        }
    }).trigger('change');
    
    // Waiting Page Ended - Sincronizzazione colori
    $('#waiting_page_ended_bg_color_picker').on('input', function() {
        $('#waiting_page_ended_bg_color').val($(this).val());
    });
    $('#waiting_page_ended_bg_color').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(val)) {
            $('#waiting_page_ended_bg_color_picker').val(val);
        }
    });
    $('#waiting_page_ended_text_color_picker').on('input', function() {
        $('#waiting_page_ended_text_color').val($(this).val());
    });
    $('#waiting_page_ended_text_color').on('input', function() {
        var val = $(this).val();
        if (/^#[0-9A-F]{6}$/i.test(val)) {
            $('#waiting_page_ended_text_color_picker').val(val);
        }
    });
    
    // Waiting Page Ended - Opacità 
    $('#waiting_page_ended_opacity_slider').on('input', function() {
        $('#waiting_page_ended_opacity').val($(this).val());
    });
    $('#waiting_page_ended_opacity').on('input', function() {
        var val = Math.max(0, Math.min(100, parseInt($(this).val()) || 100));
        $(this).val(val);
        $('#waiting_page_ended_opacity_slider').val(val);
    });

});



// Preview Pages Functions
function openPreview(type) {
    var previewUrl = '';
    var title = '';
    
    switch(type) {
        case 'waiting_page':
            previewUrl = '../public/attesa';
            title = 'Preview - Waiting Page';
            break;
        case 'waiting_page_ended':
            previewUrl = '../public/concorso-terminato';
            title = 'Preview - Waiting Page Ended';
            break;
        case 'thank_you_estrazione':
            previewUrl = 'anteprima-ringraziamento?type=estrazione';
            title = 'Preview - Thank You (Estrazione)';
            break;
        case 'thank_you_instantwin':
            previewUrl = 'anteprima-ringraziamento?type=instantwin';
            title = 'Preview - Thank You (Instant Win)';
            break;
        case 'email_estrazione':
            previewUrl = 'ajax/preview_email.php?type=estrazione';
            title = 'Preview - Email Estrazione';
            break;
        case 'email_instantwin_win':
        case 'email_instantwin_confirm':
            previewUrl = 'ajax/preview_email.php?type=instantwin_confirm';
            title = 'Preview - Email Instant Win (Vincita)';
            break;
        case 'email_instantwin_loss':
            previewUrl = 'ajax/preview_email.php?type=instantwin_loss';
            title = 'Preview - Email Instant Win (Non Vincita)';
            break;
        default:
            return;
    }
    
    document.getElementById('previewModalLabel').textContent = title;
    document.getElementById('previewIframe').src = previewUrl;
    $('#previewModal').modal('show');
}

function refreshPreview() {
    var iframe = document.getElementById('previewIframe');
    iframe.src = iframe.src;
}
