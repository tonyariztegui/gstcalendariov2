// Toggle footer mode: mostra colore o immagine in base alla select
$(function() {
    function toggleFooterMode() {
        var mode = $('#footer_style_mode').val();
        if (mode === 'color') {
            $('#footer_color_options').show();
            $('#footer_image_options').hide();
        } else {
            $('#footer_color_options').hide();
            $('#footer_image_options').show();
        }
    }
    $('#footer_style_mode').on('change', toggleFooterMode);
    toggleFooterMode();
    
    // Toggle dimensione personalizzata per immagine footer
    function toggleFooterImageSizeCustom() {
        var size = $('#footer_style_image_size').val();
        if (size === 'custom') {
            $('#footer_image_size_custom_wrapper').show();
        } else {
            $('#footer_image_size_custom_wrapper').hide();
        }
    }
    $('#footer_style_image_size').on('change', toggleFooterImageSizeCustom);
    toggleFooterImageSizeCustom();
});

// Sync opacity slider with input number
$(function() {
    function clampOpacity(value) {
        return Math.max(0, Math.min(100, parseInt(value, 10) || 0));
    }

    $('.form-container-panel').each(function() {
        var $panel = $(this);
        var $slider = $panel.find('.opacity-slider').first();
        var $input = $panel.find('.opacity-input').first();
        if (!$slider.length || !$input.length) {
            return;
        }

        $slider.on('input', function() {
            $input.val($slider.val());
        });

        $input.on('input', function() {
            $slider.val(clampOpacity($input.val()));
        });

        $input.on('blur', function() {
            var val = clampOpacity($input.val());
            $input.val(val);
            $slider.val(val);
        });
    });
});

// Sync footer opacity slider with input number
$(function() {
    var footerSlider = $('#footer_style_opacity_slider');
    var footerInput = $('#footer_style_opacity');

    footerSlider.on('input', function() {
        footerInput.val(footerSlider.val());
    });

    footerInput.on('input', function() {
        var val = Math.max(0, Math.min(100, parseInt(footerInput.val()) || 0));
        footerSlider.val(val);
    });

    footerInput.on('blur', function() {
        var val = Math.max(0, Math.min(100, parseInt(footerInput.val()) || 0));
        footerInput.val(val);
        footerSlider.val(val);
    });
});

// Toggle footer border options
$(function() {
    function toggleFooterBorder() {
        var enabled = $('#footer_border_enabled').is(':checked');
        if (enabled) {
            $('#footer_border_options').show();
            var width = parseInt($('#footer_style_border_width').val(), 10) || 0;
            if (width <= 0) {
                $('#footer_style_border_width').val(1);
            }
            if (!$('#footer_border_top, #footer_border_right, #footer_border_bottom, #footer_border_left').is(':checked')) {
                $('#footer_border_top').prop('checked', true);
            }
        } else {
            $('#footer_border_options').hide();
        }
    }
    $('#footer_border_enabled').on('change', toggleFooterBorder);
    toggleFooterBorder();
});

function syncFooterCookiePolicyBlockFromBanner() {
    var on = typeof window.adventRefreshFooterPreview === 'function' || $('#cookie_consent_enabled').is(':checked');
    var $item = $('.footer-layout-block-item[data-block-key="links_cookie_policy"]');
    var $cb = $('#footer_layout_block_links_cookie_policy');
    var $labelWrap = $('#footer-layout-cookie-policy-label-wrap');
    if (!$item.length || !$cb.length) {
        return;
    }
    if (on) {
        $item.show();
        $cb.prop('disabled', false);
        $labelWrap.show();
    } else {
        $item.hide();
        $cb.prop('checked', false).prop('disabled', true);
        $labelWrap.hide();
    }
    if (typeof scheduleFooterPreview === 'function') {
        scheduleFooterPreview();
    } else if (typeof window.scheduleFooterPreview === 'function') {
        window.scheduleFooterPreview();
    }
}
// Footer layout: template, blocchi legali, summernote
$(function() {
    var legalBlockKeys = ['legal_block_1', 'legal_block_2'];

    function footerLayoutTemplate() {
        return $('#footer_layout_template').val() || 'three_columns';
    }

    function isLegalBlockEnabled(blockKey) {
        return $('#footer_layout_block_' + blockKey).is(':checked');
    }

    function syncFooterLegalEditorVisibility() {
        var centered = footerLayoutTemplate() === 'centered_compact';
        var anyVisible = false;
        legalBlockKeys.forEach(function(key, index) {
            var show = centered && isLegalBlockEnabled(key);
            $('#footer-legal-editor-wrap-' + (index + 1)).toggle(show);
            if (show) {
                anyVisible = true;
            }
        });
        $('#footer-layout-legal-editors').toggle(anyVisible);
    }

    function toggleFooterLayoutUi() {
        var tpl = footerLayoutTemplate();
        var centered = tpl === 'centered_compact';
        $('#footer-layout-centered-only').toggle(centered);
        $('.footer-layout-block-item').each(function() {
            var key = $(this).data('block-key');
            if (legalBlockKeys.indexOf(key) !== -1) {
                $(this).toggle(centered);
            }
        });
        syncFooterLegalEditorVisibility();
    }

    function initFooterLegalEditors() {
        if (typeof $.fn.summernote !== 'function') {
            return;
        }
        $('.footer-legal-editor').each(function() {
            var $editor = $(this);
            if ($editor.data('summernote')) {
                return;
            }
            $editor.summernote($.extend({
                height: 140,
                lang: 'it-IT',
                toolbar: [
                    ['font', ['fontname', 'bold', 'italic', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ],
                disableDragAndDrop: true,
                callbacks: {
                    onInit: function() {
                        if (typeof window.contestSummernoteSanitizeEditor === 'function') {
                            window.contestSummernoteSanitizeEditor($editor);
                        }
                    },
                    onChange: function() {
                        if ($editor.data('isSanitizingFonts')) {
                            return;
                        }
                        if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                            var raw = ($editor.summernote('code') || '').toString();
                            var fixed = window.contestSanitizeSummernoteHtml(raw);
                            if (fixed !== raw) {
                                $editor.data('isSanitizingFonts', true);
                                $editor.summernote('code', fixed);
                                $editor.data('isSanitizingFonts', false);
                            }
                        }
                        scheduleFooterPreview();
                    }
                }
            }, typeof window.contestSummernoteFontOpts === 'function' ? window.contestSummernoteFontOpts() : {}));
        });
        syncFooterLegalEditorCanvas();
    }

    function syncFooterLegalEditorCanvas() {
        if (typeof window.applyNoteEditableCanvas !== 'function') {
            return;
        }
        var mode = ($('#footer_style_mode').val() || 'color').toString().toLowerCase().trim();
        var style = {
            backgroundColor: '',
            backgroundImage: '',
            backgroundSize: '',
            backgroundPosition: '',
            isTransparent: false,
            borderRadius: '6px',
            padding: '12px'
        };
        if (mode === 'color') {
            var color = ($('#footer_style_background_text').val() || '#ffffff').trim();
            var rgba = typeof window.noteEditableHexToRgba === 'function'
                ? window.noteEditableHexToRgba(color, 100)
                : '';
            style.backgroundColor = rgba || color || '#f8f9fb';
        } else if (mode === 'image') {
            var $img = $('#preview_footer_image');
            var imageUrl = '';
            if ($img.length && !$img.hasClass('d-none')) {
                imageUrl = ($img.attr('src') || '').trim();
            }
            if (!imageUrl) {
                imageUrl = ($('input[name="footer_style_image"]').val() || '').trim();
            }
            var $file = $('#upload_footer_image');
            if ($file.length && $file[0].files && $file[0].files[0]) {
                imageUrl = URL.createObjectURL($file[0].files[0]);
            }
            if (imageUrl) {
                style.backgroundImage = "url('" + imageUrl.replace(/'/g, "\\'") + "')";
                style.backgroundSize = 'cover';
                style.backgroundPosition = 'center';
            } else {
                style.isTransparent = true;
            }
        } else {
            style.isTransparent = true;
        }
        $('.footer-legal-editor').each(function() {
            var $note = $(this).next('.note-editor').find('.note-editable');
            window.applyNoteEditableCanvas($note, style);
        });
    }

    $('#footer_layout_template').on('change', toggleFooterLayoutUi);
    $(document).on('change', '#footer_layout_block_legal_block_1, #footer_layout_block_legal_block_2', function() {
        syncFooterLegalEditorVisibility();
        scheduleFooterPreview();
    });
    toggleFooterLayoutUi();
    initFooterLegalEditors();

    $('#footer-layout-perso-body').on('shown.bs.collapse', function() {
        syncFooterLegalEditorVisibility();
        syncFooterLegalEditorCanvas();
    });
    $('#footer-layout-perso-body').on('show.bs.collapse hide.bs.collapse', function(e) {
        var expanded = e.type === 'show';
        $('.footer-layout-perso-toggle').attr('aria-expanded', expanded ? 'true' : 'false');
    });

    $('#config-form, #advent-form').on('submit.footerLegalEditors', function() {
        $('.footer-legal-editor').each(function() {
            var $editor = $(this);
            if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
                var html = $editor.summernote('code') || '';
                if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                    html = window.contestSanitizeSummernoteHtml(html);
                }
                $editor.val(html);
            }
        });
    });

    var footerPreviewTimer = null;

    function syncFooterLegalEditorsToTextarea() {
        $('.footer-legal-editor').each(function() {
            var $editor = $(this);
            if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
                var html = $editor.summernote('code') || '';
                if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                    html = window.contestSanitizeSummernoteHtml(html);
                }
                $editor.val(html);
            }
        });
    }

    function collectFooterPreviewFormData() {
        syncFooterLegalEditorsToTextarea();
        var fd = new FormData();
        var $section = $('#section-footer');
        if (!$section.length) {
            return fd;
        }
        $section.find('input, select, textarea').each(function() {
            var el = this;
            var name = el.getAttribute('name');
            if (!name || el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox') {
                if (el.checked) {
                    fd.append(name, el.value || '1');
                }
                return;
            }
            fd.append(name, el.value);
        });
        var cookiePolicy = $('#cookie_policy_link').val() || $('input[name="cookie_policy_link"]').val() || '';
        fd.append('cookie_policy_link', cookiePolicy);
        if ($('#cookie_consent_enabled').is(':checked')) {
            fd.append('cookie_consent_enabled', '1');
        }
        if ($('#style_customization_footer_text_colors').is(':checked')) {
            fd.append('style_customization_footer_text_colors', '1');
        }
        ['primary', 'secondary', 'tertiary', 'text'].forEach(function(key) {
            var $el = $('#colors_' + key + '_text');
            if (!$el.length) {
                $el = $('input[name="colors_' + key + '"]');
            }
            if ($el.length) {
                fd.append('colors_' + key, String($el.val() || '').trim());
            }
        });
        return fd;
    }

    function refreshFooterPreviewIframe() {
        var $iframe = $('#footer-layout-preview-iframe');
        if (!$iframe.length) {
            return;
        }
        if (typeof window.adventRefreshFooterPreview === 'function') {
            syncFooterLegalEditorsToTextarea();
            window.adventRefreshFooterPreview();
            return;
        }
        fetch('ajax/set_footer_preview_draft.php', { method: 'POST', body: collectFooterPreviewFormData() })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.ok) {
                    $iframe.attr('src', 'ajax/preview_footer.php?live=1&_=' + Date.now());
                }
            })
            .catch(function() { /* ignore */ });
    }

    function scheduleFooterPreview() {
        clearTimeout(footerPreviewTimer);
        footerPreviewTimer = setTimeout(function() {
            syncFooterLegalEditorCanvas();
            refreshFooterPreviewIframe();
        }, 450);
    }
    window.scheduleFooterPreview = scheduleFooterPreview;

    var $footerSection = $('#section-footer');
    if ($footerSection.length) {
        $footerSection.on('change input', 'input, select, textarea', scheduleFooterPreview);
        if (($('#footer_layout_logo_image').val() || '').trim() !== '') {
            $('#footer_layout_show_logo').prop('checked', true);
        }
        $('#upload_footer_logo_image').on('change', function() {
            if (this.files && this.files.length) {
                $('#footer_layout_show_logo').prop('checked', true);
                scheduleFooterPreview();
            }
        });
        $(document).on('change input', '#cookie_consent_enabled, #cookie_policy_link', scheduleFooterPreview);
        $(document).on('change input', '#colors_primary_text, #colors_secondary_text, #colors_tertiary_text, #colors_text_text, input[name^="colors_"]', scheduleFooterPreview);
        syncFooterCookiePolicyBlockFromBanner();
        scheduleFooterPreview();
    }
});

// Sync footer border opacity slider with input number
$(function() {
    var borderSlider = $('#footer_style_border_opacity_slider');
    var borderInput = $('#footer_style_border_opacity');

    borderSlider.on('input', function() {
        borderInput.val(borderSlider.val());
    });

    borderInput.on('input', function() {
        var val = Math.max(0, Math.min(100, parseInt(borderInput.val()) || 0));
        borderSlider.val(val);
    });

    borderInput.on('blur', function() {
        var val = Math.max(0, Math.min(100, parseInt(borderInput.val()) || 0));
        borderInput.val(val);
        borderSlider.val(val);
    });
});

