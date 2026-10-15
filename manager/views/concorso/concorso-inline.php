(function() {
    // Sync color inputs with their paired text inputs (hex codes)
    <?php
        $summernoteBaseFonts = ['Segoe UI', 'Montserrat', 'Arial', 'Georgia', 'Times New Roman', 'Courier New', 'Verdana', 'Tahoma', 'Trebuchet MS'];
        $summernoteCustomFonts = contest_font_family_names(is_array($content_current ?? null) ? $content_current : null);
        $summernoteAllFonts = array_values(array_unique(array_merge($summernoteBaseFonts, $summernoteCustomFonts)));
    ?>
    window.contestSummernoteFontNames = <?php echo json_encode($summernoteAllFonts, JSON_UNESCAPED_UNICODE); ?>;

    /** Fix font-family: "Nome Con Spazi" che rompe l'attributo style HTML di Summernote. */
    window.contestSanitizeSummernoteHtml = function(html) {
        html = (html == null ? '' : html).toString();
        html = html.replace(/font-family\s*:\s*"([^"]+)"/gi, "font-family: '$1'");
        html = html.replace(/font-family\s*:\s*&quot;([^&]+)&quot;/gi, "font-family: '$1'");
        return html;
    };

    window.contestSummernoteSanitizeEditor = function($editor) {
        if (!$editor || !$editor.length || typeof $editor.summernote !== 'function' || !$editor.data('summernote')) {
            return;
        }
        var html = ($editor.summernote('code') || '').toString();
        var fixed = window.contestSanitizeSummernoteHtml(html);
        if (fixed !== html) {
            $editor.summernote('code', fixed);
        }
    };

    window.contestSummernoteFontOpts = function() {
        return {
            styleWithCSS: true,
            fontNames: window.contestSummernoteFontNames || [],
            fontNamesIgnoreCheck: window.contestSummernoteFontNames || []
        };
    };

    function isHex(val) {
        return /^#?[0-9a-fA-F]{6}$/.test(val.trim());
    }

    function ensureVisiblePageContainerColor(el) {
        var panel = el.closest ? el.closest('.public-page-container-panel') : null;
        if (!panel) return;
        var input = panel.querySelector('.public-page-container-opacity');
        var slider = panel.querySelector('.public-page-container-opacity-slider');
        if (!input || parseInt(input.value, 10) !== 0) return;
        input.value = '100';
        if (slider) {
            slider.value = '100';
        }
    }

    document.querySelectorAll('.color-input').forEach(function(picker) {
        var targetId = picker.getAttribute('data-target');
        var target = document.getElementById(targetId);
        if (!target) return;

        function syncPickerDisabledState() {
            picker.disabled = target.value.trim().toLowerCase() === 'transparent';
        }

        syncPickerDisabledState();

        // From picker to text (+ evento per anteprime live che ascoltano il campo testo)
        picker.addEventListener('input', function() {
            target.value = picker.value;
            picker.disabled = false;
            ensureVisiblePageContainerColor(picker);
            target.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // From text to picker (only if valid hex)
        target.addEventListener('input', function() {
            var val = target.value.trim();
            if (val.toLowerCase() === 'transparent') {
                syncPickerDisabledState();
                return;
            }
            picker.disabled = false;
            if (isHex(val)) {
                if (val.charAt(0) !== '#') {
                    val = '#' + val;
                }
                picker.value = val;
                ensureVisiblePageContainerColor(target);
            }
        });
    });
})();

// Bottoni per pagina (waiting/thank-you): modifica testo centralizzata
(function() {
    function getEditorHtml($editor) {
        if (!$editor || !$editor.length) {
            return '';
        }
        if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
            return ($editor.summernote('code') || '').toString();
        }
        return ($editor.val() || '').toString();
    }

    function setEditorHtml($editor, html) {
        if (!$editor || !$editor.length) {
            return;
        }
        if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
            $editor.summernote('code', html);
        } else {
            $editor.val(html);
        }
        $editor.trigger('input').trigger('change');
    }

    function parseButtonsFromHtml(html) {
        var raw = (html || '').toString();
        // Some stored/editor values keep anchors encoded as &lt;a ...&gt;.
        // Decode entities first so button nodes are detectable from editable source.
        if (raw.indexOf('&lt;') !== -1 && raw.indexOf('&gt;') !== -1) {
            var decoder = document.createElement('textarea');
            decoder.innerHTML = raw;
            raw = decoder.value || raw;
        }
        var wrapper = document.createElement('div');
        wrapper.innerHTML = raw;
        var nodes = wrapper.querySelectorAll('a.btn, button.btn');
        var buttons = [];
        nodes.forEach(function(node, idx) {
            var text = (node.textContent || '').replace(/\s+/g, ' ').trim();
            var classes = (node.getAttribute('class') || '').trim();
            buttons.push({
                index: idx,
                text: text,
                classes: classes
            });
        });
        return buttons;
    }

    function runWhenReady(fn) {
        if (typeof window.deferInit === 'function') {
            window.deferInit(fn);
            return;
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
            return;
        }
        setTimeout(fn, 0);
    }

    runWhenReady(function() {
        var pageDefs = [
            { key: 'waiting_page', label: 'Waiting page', listSelector: '#waiting-containers-list', previewSelector: '#waiting-page-preview', isUsed: function() { return true; } },
            { key: 'participation_page', label: 'Pagina partecipazione', listSelector: '#participation-page-containers-list', previewSelector: '#participation-page-preview', isUsed: function() { return $('#participation_page_enabled').is(':checked'); } },
            { key: 'waiting_page_ended', label: 'Waiting page ended', listSelector: '#waiting-ended-containers-list', previewSelector: '#waiting-page-ended-preview', isUsed: function() { return true; } },
            {
                key: 'thankyou_instantwin_win_page',
                label: 'Thank-you win',
                listSelector: '#thankyou-win-containers-list',
                previewSelector: '#thankyou-win-preview',
                isUsed: function() {
                    var mode = (($('select[name="contest_mode"]').val() || '').toString().toLowerCase());
                    return mode === 'instantwin';
                }
            },
            {
                key: 'thankyou_instantwin_loss_page',
                label: 'Thank-you lose',
                listSelector: '#thankyou-lose-containers-list',
                previewSelector: '#thankyou-lose-preview',
                isUsed: function() {
                    var mode = (($('select[name="contest_mode"]').val() || '').toString().toLowerCase());
                    return mode === 'instantwin';
                }
            },
            {
                key: 'thankyou_instantwin_quiz_failed_page',
                label: 'Thank-you quiz failed',
                listSelector: '#thankyou-quiz-failed-containers-list',
                previewSelector: '#thankyou-quiz-failed-preview',
                isUsed: function() {
                    var mode = (($('select[name="contest_mode"]').val() || '').toString().toLowerCase());
                    return mode === 'instantwin' && $('#config-quiz-enabled-checkbox').is(':checked') && $('#quiz_require_all_correct').is(':checked');
                }
            },
            {
                key: 'thankyou_estrazione_page',
                label: 'Thank-you estrazione',
                listSelector: '#thankyou-partecipazione-containers-list',
                previewSelector: '#thankyou-partecipazione-preview',
                isUsed: function() {
                    var mode = (($('select[name="contest_mode"]').val() || '').toString().toLowerCase());
                    return mode !== 'instantwin';
                }
            },
            {
                key: 'thankyou_estrazione_quiz_failed_page',
                label: 'Thank-you quiz failed (estrazione)',
                listSelector: '#thankyou-estrazione-qfail-containers-list',
                previewSelector: '#thankyou-estrazione-qfail-preview',
                isUsed: function() {
                    var mode = (($('select[name="contest_mode"]').val() || '').toString().toLowerCase());
                    return mode !== 'instantwin' && $('#config-quiz-enabled-checkbox').is(':checked') && $('#quiz_require_all_correct').is(':checked');
                }
            }
        ];
        var syntheticDefs = [
            {
                pageKey: 'registration',
                pageLabel: 'Registration',
                entryId: 'registration::submit',
                entryLabel: 'PARTECIPA',
                classes: 'btn btn-success',
                inputSelector: 'input[name="buttons_primary_text"]',
                isUsed: function() { return true; }
            },
            {
                pageKey: 'help',
                pageLabel: 'Help',
                entryId: 'help::submit',
                entryLabel: 'INVIA',
                classes: 'btn btn-success',
                inputSelector: '#buttons_help_text',
                isUsed: function() { return true; }
            },
            {
                pageKey: 'quiz',
                pageLabel: 'Quiz',
                entryId: 'quiz::submit',
                entryLabel: 'INVIA RISPOSTE',
                classes: 'btn btn-success',
                inputSelector: '#buttons_quiz_submit_text',
                isUsed: function() { return $('#config-quiz-enabled-checkbox').is(':checked'); }
            }
        ];

        var pagesList = document.getElementById('page-buttons-pages-list');
        var itemsList = document.getElementById('page-buttons-items-list');
        var editorEmpty = document.getElementById('page-buttons-editor-empty');
        var editorForm = document.getElementById('page-buttons-editor-form');
        var textInput = document.getElementById('page-button-text-input');
        var metaEl = document.getElementById('page-button-meta');
        var refreshBtn = document.getElementById('refresh-page-buttons');
        var footerWrap = document.getElementById('page-button-footer-wrap') || document.getElementById('page-button-footer-registration-only');
        var footerInput = document.getElementById('page-button-footer-input');
        var footerColorText = document.getElementById('page-button-footer-color-text');
        var footerFontSelect = document.getElementById('page-button-footer-font');
        var footerContextLabel = document.getElementById('page-button-footer-context-label');
        var footerStores = {
            registration: {
                text: document.getElementById('store_form_page_button_footer_text'),
                color: document.getElementById('store_form_page_button_footer_text_color'),
                font: document.getElementById('store_form_page_button_footer_text_font'),
                label: '(opzionale, Registration — sotto PARTECIPA)'
            },
            participation_page: {
                text: document.getElementById('store_participation_page_button_footer_text'),
                color: document.getElementById('store_participation_page_button_footer_text_color'),
                font: document.getElementById('store_participation_page_button_footer_text_font'),
                label: '(opzionale, Pagina partecipazione — sotto i bottoni)'
            }
        };
        if (!pagesList || !itemsList || !editorEmpty || !editorForm || !textInput || !metaEl || !refreshBtn) {
            return;
        }

        function getFooterStore(pageKey) {
            var store = footerStores[pageKey];
            return store && store.text ? store : null;
        }
        function syncRegistrationFooterToStore() {
            var store = getFooterStore(state.activePageKey);
            if (!footerInput || !store) {
                return;
            }
            store.text.value = footerInput.value;
            if (footerColorText && store.color) {
                var colorVal = (footerColorText.value || '').trim();
                store.color.value = /^#[0-9A-Fa-f]{6}$/.test(colorVal) ? colorVal.toLowerCase() : '';
            }
            if (footerFontSelect && store.font) {
                store.font.value = (footerFontSelect.value || '').toString();
            }
        }
        function loadRegistrationFooterFromStore() {
            if (!footerWrap || !footerInput) {
                return;
            }
            var store = getFooterStore(state.activePageKey);
            if (!store) {
                footerWrap.classList.add('d-none');
                footerInput.value = '';
                return;
            }
            footerWrap.classList.remove('d-none');
            footerInput.value = store.text.value;
            if (footerContextLabel) {
                footerContextLabel.textContent = store.label || '(opzionale — sotto i bottoni della pagina)';
            }
            if (footerColorText && store.color) {
                footerColorText.value = store.color.value || '';
                var swatch = document.getElementById('page-button-footer-color-swatch');
                if (swatch) {
                    var hex = (footerColorText.value || '').trim();
                    if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                        swatch.value = hex;
                    }
                }
            }
            if (footerFontSelect && store.font) {
                footerFontSelect.value = store.font.value || '';
            }
        }

        var state = {
            pages: [],
            activePageKey: '',
            activeItemId: ''
        };

        function collectState() {
            var pages = [];
            pageDefs.forEach(function(def) {
                if (typeof def.isUsed === 'function' && !def.isUsed()) {
                    return;
                }
                var list = document.querySelector(def.listSelector);
                if (!list) {
                    return;
                }
                var entries = [];
                list.querySelectorAll('.waiting-container-item').forEach(function(item, containerIndex) {
                    var $editor = $(item).find('.waiting-container-editor').first();
                    var html = getEditorHtml($editor);
                    var buttons = parseButtonsFromHtml(html);
                    buttons.forEach(function(btn) {
                        entries.push({
                            id: def.key + '::' + containerIndex + '::' + btn.index,
                            pageKey: def.key,
                            containerIndex: containerIndex,
                            buttonIndex: btn.index,
                            text: btn.text,
                            classes: btn.classes
                        });
                    });
                });
                if (entries.length === 0 && def.previewSelector) {
                    var previewRoot = document.querySelector(def.previewSelector);
                    if (previewRoot) {
                        previewRoot.querySelectorAll('a.btn, button.btn').forEach(function(node, idx) {
                            var pText = (node.textContent || '').replace(/\s+/g, ' ').trim();
                            var pClasses = (node.getAttribute('class') || '').trim();
                            entries.push({
                                id: def.key + '::preview::' + idx,
                                pageKey: def.key,
                                containerIndex: -1,
                                buttonIndex: idx,
                                text: pText,
                                classes: pClasses,
                                source: 'preview'
                            });
                        });
                    }
                }
                if (entries.length > 0) {
                    pages.push({
                        key: def.key,
                        label: def.label,
                        listSelector: def.listSelector,
                        previewSelector: def.previewSelector,
                        entries: entries
                    });
                }
            });
            syntheticDefs.forEach(function(def) {
                if (typeof def.isUsed === 'function' && !def.isUsed()) {
                    return;
                }
                var input = document.querySelector(def.inputSelector);
                if (!input) {
                    return;
                }
                var entry = {
                    id: def.entryId,
                    pageKey: def.pageKey,
                    containerIndex: -1,
                    buttonIndex: 0,
                    text: (input.value || def.entryLabel || '').toString(),
                    classes: def.classes || 'btn',
                    source: 'input',
                    inputSelector: def.inputSelector
                };
                var page = pages.find(function(p) { return p.key === def.pageKey; });
                if (!page) {
                    page = { key: def.pageKey, label: def.pageLabel, entries: [] };
                    pages.push(page);
                }
                page.entries.push(entry);
            });
            state.pages = pages;
            if (!pages.some(function(p) { return p.key === state.activePageKey; })) {
                state.activePageKey = pages.length ? pages[0].key : '';
            }
            var activePage = pages.find(function(p) { return p.key === state.activePageKey; });
            if (!activePage || !activePage.entries.some(function(e) { return e.id === state.activeItemId; })) {
                state.activeItemId = activePage && activePage.entries.length ? activePage.entries[0].id : '';
            }
        }

        function renderPages() {
            pagesList.innerHTML = '';
            if (!state.pages.length) {
                pagesList.innerHTML = '<div class="page-buttons-empty small text-muted">Nessuna pagina con bottoni trovata.</div>';
                return;
            }
            state.pages.forEach(function(page) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-buttons-list-item' + (page.key === state.activePageKey ? ' active' : '');
                btn.setAttribute('data-page-key', page.key);
                btn.innerHTML = '<p class="page-buttons-list-title">' + page.label + '</p><p class="page-buttons-list-sub">' + page.entries.length + ' bottone/i</p>';
                btn.addEventListener('click', function() {
                    syncRegistrationFooterToStore();
                    state.activePageKey = page.key;
                    state.activeItemId = page.entries.length ? page.entries[0].id : '';
                    renderAll();
                });
                pagesList.appendChild(btn);
            });
        }

        function renderItems() {
            itemsList.innerHTML = '';
            var page = state.pages.find(function(p) { return p.key === state.activePageKey; });
            if (!page || !page.entries.length) {
                itemsList.innerHTML = '<div class="page-buttons-empty small text-muted">Nessun bottone in questa pagina.</div>';
                return;
            }
            page.entries.forEach(function(entry, idx) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-buttons-list-item' + (entry.id === state.activeItemId ? ' active' : '');
                btn.setAttribute('data-item-id', entry.id);
                var title = entry.text !== '' ? entry.text : '(testo vuoto)';
                btn.innerHTML = '<p class="page-buttons-list-title">' + title + '</p><p class="page-buttons-list-sub">Bottone ' + (idx + 1) + '</p>';
                btn.addEventListener('click', function() {
                    state.activeItemId = entry.id;
                    renderAll();
                });
                itemsList.appendChild(btn);
            });
        }

        function renderEditor() {
            var page = state.pages.find(function(p) { return p.key === state.activePageKey; });
            var entry = page ? page.entries.find(function(e) { return e.id === state.activeItemId; }) : null;
            if (!entry) {
                editorEmpty.classList.remove('d-none');
                editorForm.classList.add('d-none');
                textInput.value = '';
                textInput.disabled = false;
                textInput.title = '';
                metaEl.textContent = '';
                return;
            }
            editorEmpty.classList.add('d-none');
            editorForm.classList.remove('d-none');
            textInput.value = entry.text || '';
            metaEl.textContent = entry.classes ? ('Classi: ' + entry.classes) : 'Classi: btn';
            if (entry.source === 'preview') {
                textInput.disabled = true;
                textInput.title = 'Bottone rilevato dal preview: apri/modifica il relativo container per renderlo editabile qui.';
                metaEl.textContent += ' | Solo lettura (preview)';
            } else {
                textInput.disabled = false;
                textInput.title = '';
            }
        }

        function renderAll() {
            renderPages();
            renderItems();
            renderEditor();
            loadRegistrationFooterFromStore();
        }

        function updateSelectedButtonText(newText) {
            var page = state.pages.find(function(p) { return p.key === state.activePageKey; });
            if (!page) {
                return;
            }
            var entry = page.entries.find(function(e) { return e.id === state.activeItemId; });
            if (!entry) {
                return;
            }
            if (entry.source === 'preview') {
                return;
            }
            if (entry.source === 'input' && entry.inputSelector) {
                var mappedInput = document.querySelector(entry.inputSelector);
                if (!mappedInput) {
                    return;
                }
                mappedInput.value = newText;
                $(mappedInput).trigger('input').trigger('change');
                collectState();
                renderAll();
                return;
            }
            var list = document.querySelector(page.listSelector);
            if (!list) {
                return;
            }
            var item = list.querySelectorAll('.waiting-container-item')[entry.containerIndex];
            if (!item) {
                return;
            }
            var $editor = $(item).find('.waiting-container-editor').first();
            var html = getEditorHtml($editor);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html || '';
            var nodes = wrapper.querySelectorAll('a.btn, button.btn');
            var target = nodes[entry.buttonIndex];
            if (!target) {
                return;
            }
            target.textContent = newText;
            setEditorHtml($editor, wrapper.innerHTML);
            var targetContainerIndex = entry.containerIndex;
            var targetButtonIndex = entry.buttonIndex;
            collectState();
            state.activePageKey = page.key;
            var refreshedPage = state.pages.find(function(p) { return p.key === page.key; });
            if (refreshedPage) {
                var refreshedEntry = refreshedPage.entries.find(function(e) {
                    return e.containerIndex === targetContainerIndex && e.buttonIndex === targetButtonIndex;
                });
                state.activeItemId = refreshedEntry ? refreshedEntry.id : (refreshedPage.entries[0] ? refreshedPage.entries[0].id : '');
            }
            renderAll();
        }

        var refreshTimer = null;
        function scheduleRefresh() {
            if (refreshTimer) {
                clearTimeout(refreshTimer);
            }
            refreshTimer = setTimeout(function() {
                collectState();
                renderAll();
            }, 250);
        }

        refreshBtn.addEventListener('click', function() {
            collectState();
            renderAll();
        });
        textInput.addEventListener('input', function() {
            updateSelectedButtonText(textInput.value);
        });
        if (footerInput) {
            footerInput.addEventListener('input', function() {
                syncRegistrationFooterToStore();
            });
        }
        if (footerColorText) {
            footerColorText.addEventListener('input', function() {
                syncRegistrationFooterToStore();
            });
        }
        if (footerFontSelect) {
            footerFontSelect.addEventListener('change', function() {
                syncRegistrationFooterToStore();
            });
        }
        var footerColorSwatchEl = document.getElementById('page-button-footer-color-swatch');
        if (footerColorSwatchEl && footerColorText) {
            footerColorSwatchEl.addEventListener('input', function() {
                footerColorText.value = footerColorSwatchEl.value;
                syncRegistrationFooterToStore();
            });
        }
        $(document).on('submit', 'form', function() {
            syncRegistrationFooterToStore();
        });

        // Keep the list synced when containers/editors change in waiting/thank-you sections.
        $(document).on('input change', '.waiting-container-editor, .waiting-container-item input, .waiting-container-item select, .waiting-container-item textarea', function() {
            scheduleRefresh();
        });
        $(document).on('click', '.add-waiting-container', function() {
            scheduleRefresh();
        });

        collectState();
        renderAll();
        // Summernote/container init can happen right after load; refresh once more.
        setTimeout(function() {
            collectState();
            renderAll();
        }, 1200);
    });
})();

// Live preview per la sezione Bottoni (Stili)
(function() {
    function isHex(val) {
        return /^#[0-9a-fA-F]{6}$/.test((val || '').trim());
    }
    function isButtonColor(val) {
        var v = (val || '').trim();
        return /^#[0-9a-fA-F]{6}$/.test(v) || v.toLowerCase() === 'transparent';
    }

    function filePreviewUrl(input) {
        if (!input || !input.files || !input.files[0]) {
            return '';
        }
        if (input._buttonPreviewUrl) {
            URL.revokeObjectURL(input._buttonPreviewUrl);
        }
        input._buttonPreviewUrl = URL.createObjectURL(input.files[0]);
        return input._buttonPreviewUrl;
    }

    function currentImageUrl(panel, type) {
        var key = panel.getAttribute('data-button-key');
        var input = panel.querySelector('.button-image-input[data-button-image-type="' + type + '"]');
        var fromFile = filePreviewUrl(input);
        if (fromFile) {
            return fromFile;
        }
        var img = document.getElementById('preview_button_' + key + '_' + (type === 'hover' ? 'hover_image' : 'image'));
        if (!img || img.classList.contains('d-none')) {
            return '';
        }
        return img.getAttribute('src') || img.getAttribute('data-current-src') || '';
    }

    function updateImageCss(panel, preferHover) {
        var preview = panel.querySelector('.button-style-preview');
        if (!preview) return;
        var mode = (panel.querySelector('.button-mode-select') || {}).value || 'color';
        var useGradient = isButtonGradientEnabled(panel);
        var url = mode === 'image' ? currentImageUrl(panel, preferHover ? 'hover' : 'normal') : '';
        if (!url && preferHover) {
            url = currentImageUrl(panel, 'normal');
        }
        preview.style.setProperty('--button-preview-image', url ? "url('" + url.replace(/'/g, "\\'") + "')" : 'none');
        if (mode === 'color' && useGradient) {
            updateGradientCss(panel, preferHover);
            preview.setAttribute('data-mode', 'gradient');
        } else {
            preview.style.setProperty('--button-preview-gradient', 'none');
            preview.setAttribute('data-mode', mode === 'image' ? 'image' : 'color');
        }
    }

    function isButtonGradientEnabled(panel) {
        var mode = (panel.querySelector('.button-mode-select') || {}).value || 'color';
        var toggle = panel.querySelector('.button-use-gradient-input');
        return mode === 'color' && !!(toggle && toggle.checked);
    }

    function readButtonColorValue(panel, fieldKey, fallback) {
        var key = panel.getAttribute('data-button-key') || '';
        var input = document.getElementById('buttons_' + key + '_' + fieldKey + '_text');
        var value = input ? (input.value || '').trim() : '';
        if (isButtonColor(value)) {
            return value;
        }
        return fallback || '#ffffff';
    }

    function updateGradientCss(panel, preferHover) {
        var preview = panel.querySelector('.button-style-preview');
        if (!preview) return;
        if (!isButtonGradientEnabled(panel)) {
            preview.style.setProperty('--button-preview-gradient', 'none');
            return;
        }
        var direction = (panel.querySelector('.button-gradient-direction') || {}).value || 'horizontal';
        var style = (panel.querySelector('.button-gradient-style') || {}).value || 'center';
        var angle = direction === 'horizontal' ? '90deg' : '180deg';
        var from = readButtonColorValue(panel, preferHover ? 'gradient_hover_from' : 'gradient_from', readButtonColorValue(panel, 'gradient_from', '#e55a1a'));
        var to = readButtonColorValue(panel, preferHover ? 'gradient_hover_to' : 'gradient_to', readButtonColorValue(panel, 'gradient_to', '#ff9a4a'));
        if (preferHover) {
            var hoverFrom = readButtonColorValue(panel, 'gradient_hover_from', '');
            var hoverTo = readButtonColorValue(panel, 'gradient_hover_to', '');
            if (!isButtonColor(hoverFrom)) {
                from = readButtonColorValue(panel, 'gradient_from', from);
            }
            if (!isButtonColor(hoverTo)) {
                to = readButtonColorValue(panel, 'gradient_to', to);
            }
        }
        var gradientValue = style === 'center'
            ? 'linear-gradient(' + angle + ', ' + from + ' 0%, ' + to + ' 50%, ' + from + ' 100%)'
            : 'linear-gradient(' + angle + ', ' + from + ' 0%, ' + to + ' 100%)';
        preview.style.setProperty('--button-preview-gradient', gradientValue);
        preview.style.setProperty('--button-preview-bg', from);
    }

    function updateImageOptions(panel) {
        var sizeSelect = panel.querySelector('.button-image-size-select');
        var customInput = panel.querySelector('.button-image-size-custom');
        var customWrapper = panel.querySelector('.button-image-size-custom-wrapper');
        var size = sizeSelect ? sizeSelect.value : 'cover';
        if (customWrapper) {
            customWrapper.style.display = size === 'custom' ? '' : 'none';
        }
        var finalSize = size === 'custom' && customInput && customInput.value.trim() !== '' ? customInput.value.trim() : size;
        var posV = (panel.querySelector('[name$="_image_position_vertical"]') || {}).value || 'center';
        var posH = (panel.querySelector('[name$="_image_position_horizontal"]') || {}).value || 'center';
        var preview = panel.querySelector('.button-style-preview');
        if (!preview) return;
        preview.style.setProperty('--button-preview-image-size', finalSize);
        preview.style.setProperty('--button-preview-image-position', posH + ' ' + posV);
        updateImageCss(panel, false);
    }

    function applyButtonColorPanelsVisibility(panel, customOn) {
        var mode = (panel.querySelector('.button-mode-select') || {}).value || 'color';
        var textColorOptions = panel.querySelector('.button-text-color-options');
        var colorOptions = panel.querySelector('.button-color-options');
        var gradientOptions = panel.querySelector('.button-gradient-options');
        var useGradient = isButtonGradientEnabled(panel);
        if (textColorOptions) {
            textColorOptions.style.display = customOn ? '' : 'none';
        }
        if (colorOptions) {
            colorOptions.style.display = (customOn && mode === 'color') ? '' : 'none';
        }
        if (gradientOptions) {
            gradientOptions.style.display = (customOn && mode === 'color' && useGradient) ? '' : 'none';
        }
    }
    window.applyButtonColorPanelsVisibility = applyButtonColorPanelsVisibility;

    function setButtonImageModeTransparentColors(panel) {
        var key = panel.getAttribute('data-button-key') || '';
        ['bg', 'border', 'hover_bg', 'hover_border'].forEach(function(fieldKey) {
            var inputId = 'buttons_' + key + '_' + fieldKey + '_text';
            var textInput = document.getElementById(inputId);
            if (!textInput) return;
            textInput.value = 'transparent';
            textInput.dispatchEvent(new Event('input', { bubbles: true }));
            textInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
        var preview = panel.querySelector('.button-style-preview');
        if (preview) {
            preview.style.setProperty('--button-preview-bg', 'transparent');
            preview.style.setProperty('--button-preview-border', 'transparent');
            preview.style.setProperty('--button-preview-gradient', 'none');
        }
    }

    var buttonPanels = Array.prototype.slice.call(document.querySelectorAll('.button-style-panel'));
    buttonPanels.forEach(function(panel) {
        var preview = panel.querySelector('.button-style-preview');
        if (!preview) return;

        function applyMode() {
            var mode = (panel.querySelector('.button-mode-select') || {}).value || 'color';
            var imageOptions = panel.querySelector('.button-image-options');
            var customOn = document.getElementById('style_customization_buttons_colors') && document.getElementById('style_customization_buttons_colors').checked;
            applyButtonColorPanelsVisibility(panel, customOn);
            if (mode === 'image') {
                preview.style.setProperty('--button-preview-bg', 'transparent');
                preview.style.setProperty('--button-preview-border', 'transparent');
                preview.style.setProperty('--button-preview-gradient', 'none');
            }
            if (imageOptions) imageOptions.style.display = mode === 'image' ? '' : 'none';
            updateImageCss(panel, false);
        }

        var textInput = panel.querySelector('.button-preview-text-input');
        if (textInput) {
            textInput.addEventListener('input', function() {
                var text = panel.querySelector('.button-style-preview-text');
                if (text) text.textContent = textInput.value || 'BOTTONE';
            });
        }

        var radiusInput = panel.querySelector('.button-radius-input');
        if (radiusInput) {
            radiusInput.addEventListener('input', function() {
                var radius = parseInt(radiusInput.value || '0', 10);
                if (isNaN(radius)) radius = 0;
                radius = Math.max(0, Math.min(80, radius));
                preview.style.setProperty('--button-preview-radius', radius + 'px');
            });
        }

        function applyButtonTypographyPreview() {
            var fontSelect = panel.querySelector('.button-font-select');
            var sizeSelect = panel.querySelector('.button-font-size-select');
            var font = fontSelect ? (fontSelect.value || '').trim() : '';
            var size = sizeSelect ? (sizeSelect.value || '').trim() : '';
            // Predefinito = stack neutro (mai inherit: altrimenti Gotham da Summernote/genitori)
            preview.style.setProperty(
                '--button-preview-font',
                font ? ("'" + font.replace(/'/g, '') + "', sans-serif") : 'Arial, Helvetica, sans-serif'
            );
            if (size) {
                preview.style.setProperty('--button-preview-font-size', size);
            } else {
                preview.style.setProperty('--button-preview-font-size', '0.9rem');
            }
            if (typeof window.updateWaitingButtonCssVars === 'function') {
                window.updateWaitingButtonCssVars();
            }
        }
        var fontSelect = panel.querySelector('.button-font-select');
        if (fontSelect) {
            fontSelect.addEventListener('change', applyButtonTypographyPreview);
        }
        var fontSizeSelect = panel.querySelector('.button-font-size-select');
        if (fontSizeSelect) {
            fontSizeSelect.addEventListener('change', applyButtonTypographyPreview);
        }
        applyButtonTypographyPreview();

        var hideText = panel.querySelector('.button-hide-text-input');
        function applyHideText() {
            preview.setAttribute('data-hide-text', hideText && hideText.checked ? '1' : '0');
        }
        if (hideText) {
            hideText.addEventListener('change', applyHideText);
        }
        applyHideText();

        panel.querySelectorAll('.button-preview-color, .button-preview-color-text').forEach(function(input) {
            input.addEventListener('input', function() {
                var cssVar = input.getAttribute('data-preview-var') || '';
                var value = input.value || '';
                if (cssVar && isButtonColor(value)) {
                    preview.style.setProperty(cssVar, value);
                }
                if (isButtonGradientEnabled(panel)) {
                    updateGradientCss(panel, false);
                }
            });
        });
        panel.querySelectorAll('.button-set-transparent').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = btn.getAttribute('data-target') || '';
                if (!targetId) return;
                var textInput = document.getElementById(targetId);
                if (!textInput) return;
                textInput.value = 'transparent';
                var swatch = document.querySelector('.color-input[data-target="' + targetId + '"]');
                if (swatch) {
                    swatch.disabled = true;
                }
                textInput.dispatchEvent(new Event('input', { bubbles: true }));
                textInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        var modeSelect = panel.querySelector('.button-mode-select');
        if (modeSelect) {
            modeSelect.addEventListener('change', function() {
                if (modeSelect.value === 'image') {
                    setButtonImageModeTransparentColors(panel);
                }
                applyMode();
            });
        }
        var useGradientToggle = panel.querySelector('.button-use-gradient-input');
        if (useGradientToggle) {
            useGradientToggle.addEventListener('change', function() {
                applyMode();
            });
        }
        var gradientDirection = panel.querySelector('.button-gradient-direction');
        if (gradientDirection) {
            gradientDirection.addEventListener('change', function() {
                updateGradientCss(panel, false);
            });
        }
        var gradientStyle = panel.querySelector('.button-gradient-style');
        if (gradientStyle) {
            gradientStyle.addEventListener('change', function() {
                updateGradientCss(panel, false);
            });
        }
        panel.querySelectorAll('.button-image-input').forEach(function(input) {
            input.addEventListener('change', function() {
                updateImageCss(panel, false);
            });
        });
        panel.querySelectorAll('.button-image-size-select, .button-image-size-custom, .button-image-position').forEach(function(input) {
            input.addEventListener('input', function() { updateImageOptions(panel); });
            input.addEventListener('change', function() { updateImageOptions(panel); });
        });
        preview.addEventListener('mouseenter', function() { updateImageCss(panel, true); });
        preview.addEventListener('mouseleave', function() { updateImageCss(panel, false); });
        applyMode();
        updateImageOptions(panel);
    });

    // Left menu selector: one button style panel at a time
    var buttonMenu = document.getElementById('button-config-menu');
    if (buttonMenu && buttonPanels.length > 0) {
        buttonMenu.innerHTML = '';
        var activeKey = '';
        buttonPanels.forEach(function(panel, index) {
            var key = panel.getAttribute('data-button-key') || ('button-' + index);
            var titleEl = panel.querySelector('h6');
            var descEl = panel.querySelector('.small.text-muted');
            var title = titleEl ? titleEl.textContent.trim() : key;
            var desc = descEl ? descEl.textContent.trim() : '';

            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'button-config-menu-item';
            item.setAttribute('data-target-key', key);
            item.innerHTML = '<p class="button-config-menu-title">' + title + '</p>' + (desc ? '<p class="button-config-menu-sub">' + desc + '</p>' : '');
            buttonMenu.appendChild(item);

            if (index === 0) {
                activeKey = key;
            }
            item.addEventListener('click', function() {
                activeKey = key;
                buttonPanels.forEach(function(p) {
                    p.classList.toggle('is-active', p.getAttribute('data-button-key') === activeKey);
                });
                buttonMenu.querySelectorAll('.button-config-menu-item').forEach(function(menuBtn) {
                    menuBtn.classList.toggle('active', menuBtn.getAttribute('data-target-key') === activeKey);
                });
            });
        });

        if (activeKey !== '') {
            buttonPanels.forEach(function(p) {
                p.classList.toggle('is-active', p.getAttribute('data-button-key') === activeKey);
            });
            buttonMenu.querySelectorAll('.button-config-menu-item').forEach(function(menuBtn) {
                menuBtn.classList.toggle('active', menuBtn.getAttribute('data-target-key') === activeKey);
            });
        }
    }
})();

// Live preview for uploads
(function() {
    document.querySelectorAll('.preview-input').forEach(function(input) {
        input.addEventListener('change', function(ev) {
            var targetId = input.getAttribute('data-preview-target');
            if (!targetId) return;
            var img = document.getElementById(targetId);
            if (!img) return;

            if (input.files && input.files[0]) {
                var url = URL.createObjectURL(input.files[0]);
                img.src = url;
                img.classList.remove('d-none');
            } else {
                // restore existing preview if no file selected
                var current = img.getAttribute('data-current-src') || '';
                if (current) {
                    img.src = current;
                    img.classList.remove('d-none');
                } else {
                    img.classList.add('d-none');
                    img.removeAttribute('src');
                }
            }
        });
    });
})();

// Aggiorna etichette di tutti i file upload con il nome selezionato
(function() {
    function bindCustomFileLabel(input) {
        if (!input || input.dataset.fileLabelBound === '1') {
            return;
        }
        var label = input.nextElementSibling;
        if (!label) {
            return;
        }
        var defaultText = (label.textContent || '').trim() || 'Scegli file...';
        input.dataset.fileLabelBound = '1';
        input.addEventListener('change', function() {
            if (input.files && input.files.length > 1) {
                label.textContent = input.files.length + ' file selezionati';
            } else if (input.files && input.files[0]) {
                label.textContent = input.files[0].name;
            } else {
                label.textContent = defaultText;
            }
        });
    }

    document.querySelectorAll('.custom-file-input').forEach(bindCustomFileLabel);

    // Font personalizzati: aggiungi / rimuovi righe upload
    (function() {
        var rowsWrap = document.getElementById('custom-fonts-upload-rows');
        var addBtn = document.getElementById('custom-fonts-add-row');
        var tpl = document.getElementById('custom-font-upload-row-template');
        if (!rowsWrap || !addBtn || !tpl) {
            return;
        }
        var rowIndex = rowsWrap.querySelectorAll('.custom-font-upload-row').length;

        function refreshRemoveButtons() {
            var rows = rowsWrap.querySelectorAll('.custom-font-upload-row');
            rows.forEach(function(row, idx) {
                var wrap = row.querySelector('.custom-font-row-remove-wrap');
                if (!wrap) {
                    return;
                }
                if (rows.length <= 1) {
                    wrap.classList.add('d-none');
                } else {
                    wrap.classList.remove('d-none');
                }
                // Prima riga: col-md-7 file se senza remove; altrimenti col-md-6
                var fileCol = row.querySelector('.form-group.col-md-6, .form-group.col-md-7');
                if (fileCol && fileCol.querySelector('.custom-file')) {
                    fileCol.classList.remove('col-md-6', 'col-md-7');
                    fileCol.classList.add(rows.length <= 1 && idx === 0 ? 'col-md-7' : 'col-md-6');
                }
            });
        }

        addBtn.addEventListener('click', function() {
            var node = tpl.content.cloneNode(true);
            var row = node.querySelector('.custom-font-upload-row');
            if (!row) {
                return;
            }
            row.setAttribute('data-font-row', String(rowIndex));
            var familyInput = row.querySelector('input[name="custom_font_family[]"]');
            var fileInput = row.querySelector('input[name="upload_custom_font[]"]');
            var fileLabel = row.querySelector('.custom-file-label');
            var familyLabel = row.querySelector('label');
            var fileGroupLabel = row.querySelectorAll('label')[1];
            var idSuffix = String(rowIndex);
            if (familyInput) {
                familyInput.id = 'custom_font_family_' + idSuffix;
                if (familyLabel) {
                    familyLabel.setAttribute('for', familyInput.id);
                }
            }
            if (fileInput) {
                fileInput.id = 'upload_custom_font_' + idSuffix;
                if (fileLabel) {
                    fileLabel.setAttribute('for', fileInput.id);
                }
                if (fileGroupLabel && fileGroupLabel !== fileLabel) {
                    fileGroupLabel.setAttribute('for', fileInput.id);
                }
                bindCustomFileLabel(fileInput);
            }
            rowsWrap.appendChild(node);
            rowIndex += 1;
            refreshRemoveButtons();
        });

        rowsWrap.addEventListener('click', function(ev) {
            var btn = ev.target && ev.target.closest ? ev.target.closest('.custom-font-row-remove') : null;
            if (!btn) {
                return;
            }
            ev.preventDefault();
            var row = btn.closest('.custom-font-upload-row');
            if (!row) {
                return;
            }
            var rows = rowsWrap.querySelectorAll('.custom-font-upload-row');
            if (rows.length <= 1) {
                return;
            }
            row.remove();
            refreshRemoveButtons();
        });

        refreshRemoveButtons();
    })();
})();

// Rimuovi file caricati direttamente dal configuratore (immagini e PDF)
(function() {
    function isRemovableInput(input) {
        if (!input) {
            return false;
        }
        var accept = (input.getAttribute('accept') || '').toLowerCase();
        if (accept.indexOf('image/') !== -1 || accept === 'image/*') {
            return true;
        }
        if (accept.indexOf('application/pdf') !== -1 || accept.indexOf('.pdf') !== -1 || accept.indexOf('pdf') !== -1) {
            return true;
        }
        if (input.classList.contains('preview-input')) {
            return true;
        }
        if (input.classList.contains('waiting-bg-file-input') || input.classList.contains('waiting-image-file-input')) {
            return true;
        }
        return false;
    }

    function getBrandingHiddenInput(input) {
        if (!input) {
            return null;
        }
        // Alcuni blocchi (es. sfondi) non usano .form-group: fallback al contenitore più vicino.
        var group = input.closest('.form-group');
        if (!group) {
            var customFile = input.closest('.custom-file');
            group = (customFile && customFile.parentElement) ? customFile.parentElement : null;
        }
        if (!group) {
            return null;
        }
        // Branding/sfondo: hidden con data-fallback vicino al file input
        return group.querySelector('input[type="hidden"][data-fallback]');
    }

    function isDefaultAsset(input) {
        var hidden = getBrandingHiddenInput(input);
        if (!hidden) {
            return false;
        }
        var current = (hidden.value || '').trim();
        var fallback = (hidden.getAttribute('data-fallback') || '').trim();
        var fallbackDisplay = (hidden.getAttribute('data-fallback-display') || '').trim();
        var currentNorm = current.replace(/^\//, '');
        // Se il valore punta a un asset "shipped" (default_img), non è cancellabile.
        if (currentNorm.indexOf('assets/img/default_img/') === 0) {
            return true;
        }
        // Alcuni campi (es. sfondi) usano data-fallback-display per mostrare il default effettivo.
        if (fallbackDisplay && current === fallbackDisplay) {
            return true;
        }
        // If fallback is empty, we interpret it as "inherit" (default state)
        if (fallback === '') {
            return current === '';
        }
        return current === fallback;
    }

    function resetPreview(input) {
        var targetId = input.getAttribute('data-preview-target');
        if (!targetId) {
            return;
        }
        var img = document.getElementById(targetId);
        if (!img) {
            return;
        }
        img.classList.add('d-none');
        img.removeAttribute('src');
        img.setAttribute('data-current-src', '');
    }

    function clearHiddenValues(container) {
        if (!container) {
            return;
        }
        container.querySelectorAll('input[type="hidden"]').forEach(function(hidden) {
            hidden.value = '';
            if (hidden.hasAttribute('data-preview-src')) {
                hidden.setAttribute('data-preview-src', '');
            }
        });
        container.querySelectorAll('span.waiting-bg-current, span.waiting-image-current, span[id^="current_"]').forEach(function(span) {
            span.textContent = '';
        });
    }

    function updateRemoveButtonVisibility(input) {
        if (!input) {
            return;
        }
        var container = input.closest('.image-upload-row') || input.closest('.custom-file') || input.parentElement;
        if (!container) {
            return;
        }
        var btn = container.querySelector('.image-remove-btn');
        if (!btn) {
            return;
        }
        var hasSelectedFile = !!(input.files && input.files.length);
        if (hasSelectedFile) {
            btn.style.display = '';
            return;
        }
        // Hide trash if current is already the fallback/default
        if (isDefaultAsset(input)) {
            btn.style.display = 'none';
            return;
        }
        // Per i container (waiting/thankyou/form): mostra la poubelle solo se esiste già un'immagine salvata.
        if (input.classList.contains('waiting-bg-file-input') || input.classList.contains('waiting-image-file-input')) {
            var group = input.closest('.form-group') || input.closest('.waiting-container-item') || input.parentElement;
            var hidden = null;
            if (group) {
                hidden = input.classList.contains('waiting-bg-file-input')
                    ? group.querySelector('.waiting-bg-image-value')
                    : group.querySelector('.waiting-image-src-value');
            }
            var current = hidden ? ((hidden.value || '').trim()) : '';
            var previewSrc = hidden ? (((hidden.getAttribute('data-preview-src') || '')).trim()) : '';
            if (!current && !previewSrc) {
                btn.style.display = 'none';
                return;
            }
        }

        // Per upload "semplici" (es. footer image): mostra la poubelle solo se esiste già un file salvato.
        if (input.classList.contains('preview-input')) {
            var groupSimple = input.closest('.form-group') || input.parentElement;
            var hasExisting = false;
            if (groupSimple) {
                groupSimple.querySelectorAll('input[type="hidden"]').forEach(function(h) {
                    if (hasExisting) return;
                    var v = (h.value || '').trim();
                    if (v) {
                        hasExisting = true;
                    }
                });
                if (!hasExisting) {
                    // fallback: se la preview ha già un src, consideriamolo "esistente"
                    var targetId = input.getAttribute('data-preview-target');
                    if (targetId) {
                        var img = document.getElementById(targetId);
                        var currentSrc = img ? ((img.getAttribute('data-current-src') || img.getAttribute('src') || '').trim()) : '';
                        // Se è un default_img (shipped), non mostriamo la poubelle.
                        if (currentSrc && currentSrc.indexOf('assets/img/default_img/') === -1) {
                            hasExisting = true;
                        }
                    }
                }
            }
            if (!hasExisting) {
                btn.style.display = 'none';
                return;
            }
        }
        btn.style.display = '';
    }

    function resetBrandingToFallback(input) {
        if (!input) {
            return;
        }
        var hidden = getBrandingHiddenInput(input);
        if (!hidden) {
            return;
        }
        var fallback = (hidden.getAttribute('data-fallback') || '');
        var fallbackDisplay = (hidden.getAttribute('data-fallback-display') || fallback);
        fallback = (fallback || '').trim();
        fallbackDisplay = (fallbackDisplay || '').trim();

        // Clear file selection (if any)
        input.value = '';

        // Set hidden to fallback (B behavior)
        hidden.value = fallback;

        // Update "Attuale" text
        var group = input.closest('.form-group');
        if (group) {
            group.querySelectorAll('span[id^="current_"]').forEach(function(span) {
                span.textContent = fallbackDisplay;
            });
        }

        // Update preview image to fallback preview URL (computed server-side)
        var targetId = input.getAttribute('data-preview-target');
        if (targetId) {
            var img = document.getElementById(targetId);
            if (img) {
                var fallbackSrc = (img.getAttribute('data-fallback-src') || '').trim();
                var nextSrc = fallbackSrc || fallback;
                if (nextSrc) {
                    img.src = nextSrc;
                    img.setAttribute('data-current-src', nextSrc);
                    img.classList.remove('d-none');
                } else {
                    img.classList.add('d-none');
                    img.removeAttribute('src');
                    img.setAttribute('data-current-src', '');
                }
            }
        }

        // Bubble change so other listeners (labels, etc.) update
        var changeEvent = new Event('change', { bubbles: true });
        input.dispatchEvent(changeEvent);
        updateRemoveButtonVisibility(input);
    }

    function clearImageInput(input) {
        if (!input) {
            return;
        }
        // If this is a branding asset (has fallback), revert to fallback immediately (B)
        var hidden = getBrandingHiddenInput(input);
        if (hidden && hidden.hasAttribute('data-fallback')) {
            resetBrandingToFallback(input);
            return;
        }

        if (input.id && input.id.indexOf('upload_prize_email_template_') === 0) {
            var prizeRow = input.closest('.prize-email-template-row');
            if (prizeRow) {
                var removeFlag = prizeRow.querySelector('input.prize-template-remove-flag');
                if (removeFlag) {
                    removeFlag.value = '1';
                }
                var previewWrap = prizeRow.querySelector('.prize-template-preview-wrap');
                if (previewWrap) {
                    previewWrap.innerHTML = '<div class="prize-template-preview-empty border rounded text-muted small p-3 text-center" style="min-height:80px;">Immagine rimossa — salva per applicare.</div>';
                }
            }
        }

        input.value = '';
        resetPreview(input);
        clearHiddenValues(input.closest('.form-group'));
        var changeEvent = new Event('change', { bubbles: true });
        input.dispatchEvent(changeEvent);
    }

    function attachRemoveButton(input) {
        if (!isRemovableInput(input)) {
            return;
        }
        var container = input.closest('.custom-file');
        if (!container) {
            return;
        }
        var parent = container.parentElement;
        if (parent && parent.querySelector('.image-remove-btn')) {
            updateRemoveButtonVisibility(input);
            return;
        }
        if (parent && !parent.classList.contains('image-upload-row')) {
            var wrapper = document.createElement('div');
            wrapper.className = 'image-upload-row d-flex align-items-center';
            parent.insertBefore(wrapper, container);
            wrapper.appendChild(container);
            parent = wrapper;
        }
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-danger ml-2 image-remove-btn';
        button.innerHTML = '<i class="fa fa-trash"></i>';
        button.addEventListener('click', function() {
            clearImageInput(input);
        });
        parent.appendChild(button);

        // Hide/show based on default state or pending file selection
        input.addEventListener('change', function() {
            updateRemoveButtonVisibility(input);
        });
        updateRemoveButtonVisibility(input);
    }

    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        attachRemoveButton(input);
    });

    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType !== 1) {
                    return;
                }
                if (node.matches && node.matches('.custom-file-input')) {
                    attachRemoveButton(node);
                    return;
                }
                var inputs = node.querySelectorAll ? node.querySelectorAll('.custom-file-input') : [];
                inputs.forEach(function(input) {
                    attachRemoveButton(input);
                });
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();

// Toggle gruppi background: mostra solo colore o immagini in base alla select
$(function() {
    function toggleBgMode() {
        var mode = $('#background_mode').val();
        if (mode === 'color') {
            $('.bg-color-mode').show();
            $('.bg-image-mode').hide();
        } else {
            $('.bg-color-mode').hide();
            $('.bg-image-mode').show();
        }
    }
    $('#background_mode').on('change', toggleBgMode);
    toggleBgMode();
    
    // Toggle dimensione personalizzata globale per background
    function toggleBackgroundSizeCustom() {
        var size = $('#background_style_size').val();
        if (size === 'custom') {
            $('#background_size_custom_wrapper').show();
        } else {
            $('#background_size_custom_wrapper').hide();
        }
    }
    $('#background_style_size').on('change', toggleBackgroundSizeCustom);
    toggleBackgroundSizeCustom();
    

    function toggleCustomDateFormat() {
        var format = $('#date_format').val();
        if (format === 'custom') {
            $('#date_format_custom_wrapper').show();
        } else {
            $('#date_format_custom_wrapper').hide();
        }
    }
    $('#date_format').on('change', toggleCustomDateFormat);
    toggleCustomDateFormat();

    function toggleBackgroundAdvanced() {
        if ($('#background_customize_per_size').is(':checked')) {
            $('#background_advanced_options').show();
        } else {
            $('#background_advanced_options').hide();
        }
    }
    $('#background_customize_per_size').on('change', toggleBackgroundAdvanced);
    toggleBackgroundAdvanced();
    

    $('.background-size-select').on('change', function() {
        var sizeKey = $(this).data('size-key');
        var size = $(this).val();
        if (size === 'custom') {
            $('.background-size-custom-wrapper-' + sizeKey).show();
        } else {
            $('.background-size-custom-wrapper-' + sizeKey).hide();
        }
    });
    $('.background-size-select').each(function() {
        var sizeKey = $(this).data('size-key');
        var size = $(this).val();
        if (size === 'custom') {
            $('.background-size-custom-wrapper-' + sizeKey).show();
        } else {
            $('.background-size-custom-wrapper-' + sizeKey).hide();
        }
    });

    // Parse numbers in Italian (1.000,50) or US (100.00) format. Dot as thousands separator only when comma is present.
    function parseItalianNumber(value) {
        if (!value) return NaN;
        var s = String(value).trim();
        if (s === '') return NaN;
        var hasComma = s.indexOf(',') >= 0;
        var normalized;
        if (hasComma) {
            // Italian: dot=thousands, comma=decimal (e.g. 1.000,50)
            normalized = s.replace(/\./g, '').replace(',', '.');
        } else {
            // No comma: dot is decimal separator (e.g. 100.00 from DB). Do NOT remove dots.
            normalized = s.replace(/\s/g, '');
        }
        return normalized === '' ? NaN : Number(normalized);
    }

    function formatItalianNumber(value) {
        if (!isFinite(value)) return '';
        return new Intl.NumberFormat('it-IT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    function applyItalianNumberFormatting($input) {
        var raw = $input.val();
        if (!raw) return;
        var parsed = parseItalianNumber(raw);
        if (!isFinite(parsed)) return;
        $input.val(formatItalianNumber(parsed));
    }

    $(document).on('blur', '.js-format-number-it', function() {
        applyItalianNumberFormatting($(this));
    });

    $('.js-format-number-it').each(function() {
        applyItalianNumberFormatting($(this));
    });
});

// Toggle container form: mostra colore o immagine in base alla select
$(function() {
    function toggleContainerMode($select) {
        var $panel = $select.closest('.form-container-panel');
        var mode = $select.val();
        if (!$panel.length) {
            return;
        }
        if (mode === 'color') {
            $panel.find('.form-container-color').show();
            $panel.find('.form-container-image').hide();
        } else {
            $panel.find('.form-container-color').hide();
            $panel.find('.form-container-image').show();
        }
    }
    $('#form_container_mode, .public-page-container-mode').on('change', function() {
        toggleContainerMode($(this));
    });
    $('#form_container_mode, .public-page-container-mode').each(function() {
        toggleContainerMode($(this));
    });

    $(document).on('change', '.public-page-container-image-input', function() {
        if (!(this.files && this.files.length)) {
            return;
        }
        var $panel = $(this).closest('.public-page-container-panel');
        var $mode = $panel.find('.public-page-container-mode').first();
        if (!$mode.length) {
            return;
        }
        $mode.val('image');
        toggleContainerMode($mode);
    });
});

// Toggle anteprima "Stile dei campi" (label sopra vs placeholder dentro)
$(function() {
    var $select = $('#form_field_style');

    function normalizeFormFieldHex(raw) {
        var val = String(raw || '').trim();
        if (val === '') {
            return '';
        }
        if (val.charAt(0) !== '#') {
            val = '#' + val;
        }
        return /^#[0-9A-Fa-f]{6}$/.test(val) ? val : '';
    }

    function clampOpacity(raw) {
        var n = parseInt(raw, 10);
        if (isNaN(n)) {
            return 100;
        }
        return Math.max(0, Math.min(100, n));
    }

    function hexToRgba(hex, opacity) {
        var op = clampOpacity(opacity);
        if (op === 0) {
            return 'transparent';
        }
        var normalized = normalizeFormFieldHex(hex);
        if (!normalized) {
            return 'transparent';
        }
        if (op >= 100) {
            return normalized;
        }
        var r = parseInt(normalized.substr(1, 2), 16);
        var g = parseInt(normalized.substr(3, 2), 16);
        var b = parseInt(normalized.substr(5, 2), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + (op / 100).toFixed(2) + ')';
    }

    function getOpacityVal(id) {
        var el = document.getElementById(id);
        return el ? clampOpacity(el.value) : 100;
    }

    function hexColorMix(hex1, hex2, weight1) {
        var a = normalizeFormFieldHex(hex1) || '#000000';
        var b = normalizeFormFieldHex(hex2) || '#ffffff';
        var w = Math.max(0, Math.min(100, weight1)) / 100;
        var r1 = parseInt(a.substr(1, 2), 16);
        var g1 = parseInt(a.substr(3, 2), 16);
        var b1 = parseInt(a.substr(5, 2), 16);
        var r2 = parseInt(b.substr(1, 2), 16);
        var g2 = parseInt(b.substr(3, 2), 16);
        var b2 = parseInt(b.substr(5, 2), 16);
        var r = Math.round(r1 * w + r2 * (1 - w));
        var g = Math.round(g1 * w + g2 * (1 - w));
        var bl = Math.round(b1 * w + b2 * (1 - w));
        return '#' + [r, g, bl].map(function(v) {
            var s = v.toString(16);
            return s.length === 1 ? '0' + s : s;
        }).join('');
    }

    function hexColorIsLight(hex) {
        var normalized = normalizeFormFieldHex(hex);
        if (!normalized) {
            return false;
        }
        var r = parseInt(normalized.substr(1, 2), 16) / 255;
        var g = parseInt(normalized.substr(3, 2), 16) / 255;
        var b = parseInt(normalized.substr(5, 2), 16) / 255;
        var luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;
        return luminance > 0.62;
    }

    function readableTextOnBackground(backgroundHex) {
        return hexColorIsLight(backgroundHex) ? '#212529' : '#ffffff';
    }

    function getFormFieldInputVal(id) {
        var el = document.getElementById(id);
        return el ? String(el.value || '').trim() : '';
    }

    function isFormFieldStylePreviewInputId(id) {
        if (!id || typeof id !== 'string') {
            return false;
        }
        return /^form_field_.*_color_text$/.test(id)
            || id === 'form_field_select2_highlight_bg_text'
            || id === 'form_field_select2_highlight_text_text'
            || id === 'form_field_calendar_popup_bg_text'
            || id === 'form_field_calendar_selected_color_text'
            || id === 'form_field_calendar_hover_bg_text'
            || id === 'form_field_calendar_hover_text_text';
    }

    function updateFormFieldStylePreview() {
        var previews = document.querySelectorAll('.form-field-style-preview');
        if (!previews.length) {
            return;
        }
        var root = previews[0];
        var defaultText = root.getAttribute('data-default-text') || '#212529';
        var defaultBorder = root.getAttribute('data-default-border') || '#dee2e6';
        var defaultBg = root.getAttribute('data-default-bg') || '#ffffff';
        var defaultFocus = root.getAttribute('data-default-focus') || '#0d6efd';
        var defaultCheckbox = root.getAttribute('data-default-checkbox') || '#6c757d';
        var defaultConsentLink = root.getAttribute('data-default-consent-link') || defaultCheckbox;
        var defaultConsentLinkHover = root.getAttribute('data-default-consent-link-hover') || '#0a58ca';
        var customOn = document.getElementById('style_customization_form_field_colors') && document.getElementById('style_customization_form_field_colors').checked;

        var text;
        var border;
        var bg;
        var focus;
        var select2HighlightBg;
        var select2HighlightText;
        var calendarPopupBg;
        var calendarSelected;
        var calendarHoverBg;
        var calendarHoverText;
        var checkbox;
        var consentText;
        var consentLink;
        var consentLinkHover;
        var placeholder;
        if (!customOn) {
            text = defaultText;
            border = defaultBorder;
            bg = defaultBg;
            focus = defaultFocus;
            select2HighlightBg = 'color-mix(in srgb, ' + defaultFocus + ' 80%, ' + defaultBg + ')';
            select2HighlightText = readableTextOnBackground(hexColorMix(defaultFocus, defaultBg, 80));
            calendarPopupBg = defaultBg;
            calendarSelected = defaultFocus;
            calendarHoverBg = select2HighlightBg;
            calendarHoverText = select2HighlightText;
            checkbox = defaultCheckbox;
            consentText = defaultText;
            consentLink = defaultConsentLink;
            consentLinkHover = defaultConsentLinkHover;
            placeholder = 'color-mix(in srgb, ' + defaultText + ' 50%, #ffffff)';
        } else {
            text = normalizeFormFieldHex(getFormFieldInputVal('form_field_text_color_text')) || '#000000';
            border = normalizeFormFieldHex(getFormFieldInputVal('form_field_border_color_text')) || '#dee2e6';
            bg = normalizeFormFieldHex(getFormFieldInputVal('form_field_bg_color_text')) || '#ffffff';
            focus = normalizeFormFieldHex(getFormFieldInputVal('form_field_focus_color_text')) || defaultFocus;
            checkbox = normalizeFormFieldHex(getFormFieldInputVal('form_field_checkbox_color_text')) || defaultCheckbox;
            consentText = normalizeFormFieldHex(getFormFieldInputVal('form_field_consent_text_color_text')) || '#000000';
            consentLink = normalizeFormFieldHex(getFormFieldInputVal('form_field_consent_link_color_text')) || defaultConsentLink;
            consentLinkHover = normalizeFormFieldHex(getFormFieldInputVal('form_field_consent_link_hover_color_text')) || defaultConsentLinkHover;
            placeholder = normalizeFormFieldHex(getFormFieldInputVal('form_field_placeholder_color_text')) || '';
            if (!placeholder) {
                placeholder = 'color-mix(in srgb, ' + text + ' 50%, #ffffff)';
            }
        }

        var highlightBgStored = customOn ? normalizeFormFieldHex(getFormFieldInputVal('form_field_select2_highlight_bg_text')) : '';
        var highlightTextStored = customOn ? normalizeFormFieldHex(getFormFieldInputVal('form_field_select2_highlight_text_text')) : '';
        var focusBaseForHighlight = customOn
            ? (normalizeFormFieldHex(getFormFieldInputVal('form_field_focus_color_text')) || defaultFocus)
            : defaultFocus;
        var bgBaseForHighlight = customOn
            ? (normalizeFormFieldHex(getFormFieldInputVal('form_field_bg_color_text')) || '#ffffff')
            : defaultBg;

        var bgOpacity = customOn ? getOpacityVal('form_field_bg_opacity') : 100;
        var focusOpacity = customOn ? getOpacityVal('form_field_focus_opacity') : 100;
        var hoverOpacity = customOn ? getOpacityVal('form_field_consent_link_hover_opacity') : 100;
        bg = hexToRgba(bg, bgOpacity);
        focus = hexToRgba(focus, focusOpacity);
        consentLinkHover = hexToRgba(consentLinkHover, hoverOpacity);

        if (highlightBgStored) {
            select2HighlightBg = highlightBgStored;
        } else {
            select2HighlightBg = 'color-mix(in srgb, ' + focus + ' 80%, ' + bg + ')';
        }
        if (highlightTextStored) {
            select2HighlightText = highlightTextStored;
        } else {
            var effectiveHighlightBgHex = highlightBgStored || hexColorMix(focusBaseForHighlight, bgBaseForHighlight, 80);
            select2HighlightText = readableTextOnBackground(effectiveHighlightBgHex);
        }

        var calendarPopupStored = customOn ? normalizeFormFieldHex(getFormFieldInputVal('form_field_calendar_popup_bg_text')) : '';
        var calendarSelectedStored = customOn ? normalizeFormFieldHex(getFormFieldInputVal('form_field_calendar_selected_color_text')) : '';
        var calendarHoverBgStored = customOn ? normalizeFormFieldHex(getFormFieldInputVal('form_field_calendar_hover_bg_text')) : '';
        var calendarHoverTextStored = customOn ? normalizeFormFieldHex(getFormFieldInputVal('form_field_calendar_hover_text_text')) : '';

        calendarPopupBg = calendarPopupStored || bg;
        calendarSelected = calendarSelectedStored || focus;
        if (calendarHoverBgStored) {
            calendarHoverBg = calendarHoverBgStored;
        } else {
            calendarHoverBg = select2HighlightBg;
        }
        if (calendarHoverTextStored) {
            calendarHoverText = calendarHoverTextStored;
        } else if (calendarHoverBgStored) {
            calendarHoverText = readableTextOnBackground(calendarHoverBgStored);
        } else {
            calendarHoverText = select2HighlightText;
        }

        var errorModeEl = document.getElementById('form_field_error_mode');
        var errorMode = errorModeEl ? errorModeEl.value : 'classic';
        if (errorMode !== 'on_color_background') {
            errorMode = 'classic';
        }

        previews.forEach(function(el) {
            el.style.setProperty('--ffsp-text-color', text);
            el.style.setProperty('--ffsp-placeholder-color', placeholder);
            el.style.setProperty('--ffsp-border-color', border);
            el.style.setProperty('--ffsp-bg-color', bg);
            el.style.setProperty('--ffsp-focus-color', focus);
            el.style.setProperty('--ffsp-select2-highlight-bg', select2HighlightBg);
            el.style.setProperty('--ffsp-select2-highlight-text', select2HighlightText);
            el.style.setProperty('--ffsp-calendar-popup-bg', calendarPopupBg);
            el.style.setProperty('--ffsp-calendar-selected', calendarSelected);
            el.style.setProperty('--ffsp-calendar-hover-bg', calendarHoverBg);
            el.style.setProperty('--ffsp-calendar-hover-text', calendarHoverText);
            el.style.setProperty('--ffsp-checkbox-color', checkbox);
            el.style.setProperty('--ffsp-consent-text-color', consentText);
            el.style.setProperty('--ffsp-consent-link-color', consentLink);
            el.style.setProperty('--ffsp-consent-link-hover-color', consentLinkHover);
            var fontFamilyEl = document.getElementById('form_field_font');
            var fontSizeEl = document.getElementById('form_field_font_size');
            var fontFamily = fontFamilyEl ? String(fontFamilyEl.value || '').trim() : '';
            var fontSize = fontSizeEl ? String(fontSizeEl.value || '').trim() : '';
            if (fontFamily) {
                el.style.setProperty('--ffsp-font-family', "'" + fontFamily.replace(/'/g, '') + "', sans-serif");
            } else {
                el.style.removeProperty('--ffsp-font-family');
            }
            if (fontSize) {
                el.style.setProperty('--ffsp-font-size', fontSize);
            } else {
                el.style.removeProperty('--ffsp-font-size');
            }
            var radiusEl = document.getElementById('form_field_border_radius');
            var radiusRaw = radiusEl ? parseInt(radiusEl.value, 10) : 6;
            if (isNaN(radiusRaw)) {
                radiusRaw = 6;
            }
            radiusRaw = Math.max(0, Math.min(40, radiusRaw));
            el.style.setProperty('--ffsp-radius', radiusRaw + 'px');
            el.setAttribute('data-error-mode', errorMode);
        });

        if (customOn && !highlightTextStored) {
            var autoTextSwatch = document.querySelector('.color-input[data-target="form_field_select2_highlight_text_text"]');
            if (autoTextSwatch && /^#[0-9A-Fa-f]{6}$/.test(select2HighlightText)) {
                autoTextSwatch.value = select2HighlightText;
            }
        }
        if (customOn && !calendarHoverTextStored) {
            var calendarHoverTextSwatch = document.querySelector('.color-input[data-target="form_field_calendar_hover_text_text"]');
            if (calendarHoverTextSwatch && /^#[0-9A-Fa-f]{6}$/.test(calendarHoverText)) {
                calendarHoverTextSwatch.value = calendarHoverText;
            }
        }

        if (typeof window.updateHelpPageStylePreview === 'function') {
            window.updateHelpPageStylePreview();
        }
    }

    function syncFormFieldColorsPanel() {
        var customOn = document.getElementById('style_customization_form_field_colors') && document.getElementById('style_customization_form_field_colors').checked;
        var panel = document.getElementById('form-field-colors-panel');
        var idle = document.getElementById('form-field-colors-idle');
        var previewFull = document.getElementById('form-field-preview-top-bar-full');
        var previewTop = document.getElementById('form-field-preview-top-bar-top');
        if (panel) {
            panel.style.display = customOn ? '' : 'none';
        }
        if (idle) {
            idle.style.display = customOn ? 'none' : '';
        }
        if (previewFull) {
            previewFull.style.display = customOn ? 'none' : '';
        }
        if (previewTop) {
            previewTop.style.display = customOn ? '' : 'none';
        }
    }

    function readFieldsEnabledForStyle() {
        var fieldsEnabled = {
            provincia: true,
            citta: true,
            prov_pdv: true,
            citta_pdv: true,
            indirizzo_pdv: true,
            data_scontrino: true,
            scontrino: true,
            privacy: true,
            marketing: true,
            profilazione: true
        };

        $('.field-checkbox:not(.prefisso-cellulare-group)').each(function() {
            var $checkbox = $(this);
            var fieldName = $checkbox.attr('name');
            if (fieldName && fieldName.indexOf('fields_enabled[') === 0) {
                var key = fieldName.replace('fields_enabled[', '').replace(']', '');
                if (Object.prototype.hasOwnProperty.call(fieldsEnabled, key)) {
                    fieldsEnabled[key] = $checkbox.is(':checked');
                }
            }
        });

        var $pdvGroupCheckbox = $('#field_pdv_group');
        if ($pdvGroupCheckbox.length) {
            var pdvEnabled = $pdvGroupCheckbox.is(':checked');
            fieldsEnabled.prov_pdv = pdvEnabled;
            fieldsEnabled.citta_pdv = pdvEnabled;
            fieldsEnabled.indirizzo_pdv = pdvEnabled;
        }

        return fieldsEnabled;
    }

    function syncFormFieldStyleSectionsVisibility() {
        var panel = document.querySelector('.form-field-style-panel');
        if (!panel) {
            return;
        }

        var fieldsEnabled = readFieldsEnabledForStyle();
        var showSelect2 = !!(fieldsEnabled.provincia || fieldsEnabled.citta || fieldsEnabled.prov_pdv || fieldsEnabled.citta_pdv || fieldsEnabled.indirizzo_pdv);
        var showDate = !!fieldsEnabled.data_scontrino;
        var showFile = !!fieldsEnabled.scontrino;
        var showConsents = !!(fieldsEnabled.privacy || fieldsEnabled.marketing || fieldsEnabled.profilazione);

        panel.classList.toggle('form-field-style-panel--no-select2', !showSelect2);
        panel.classList.toggle('form-field-style-panel--no-date', !showDate);
        panel.classList.toggle('form-field-style-panel--no-file', !showFile);
        panel.classList.toggle('form-field-style-panel--no-consents', !showConsents);

        document.querySelectorAll('[data-ffs-section="select2"]').forEach(function(el) {
            el.classList.toggle('d-none', !showSelect2);
        });
        document.querySelectorAll('[data-ffs-section="date"]').forEach(function(el) {
            el.classList.toggle('d-none', !showDate);
        });
        document.querySelectorAll('[data-ffs-section="consents"]').forEach(function(el) {
            el.classList.toggle('d-none', !showConsents);
        });

        var fileNote = document.getElementById('form-field-file-color-note');
        if (fileNote) {
            fileNote.classList.toggle('d-none', !showFile);
        }
    }

    function isFormFieldColorTarget(el) {
        if (!el) {
            return false;
        }
        if (el.id && isFormFieldStylePreviewInputId(el.id)) {
            return true;
        }
        if (el.id && /^form_field_.*_opacity$/.test(el.id)) {
            return true;
        }
        if (el.classList && el.classList.contains('form-field-opacity-slider')) {
            return true;
        }
        if (el.id === 'form_field_error_mode') {
            return true;
        }
        if (el.id === 'form_field_font' || el.id === 'form_field_font_size') {
            return true;
        }
        if (el.id === 'form_field_border_radius') {
            return true;
        }
        if (el.classList && el.classList.contains('color-input')) {
            var targetId = el.getAttribute('data-target') || '';
            return isFormFieldStylePreviewInputId(targetId);
        }
        return false;
    }

    function syncFormFieldOpacityControls() {
        document.querySelectorAll('.form-field-opacity-slider').forEach(function(slider) {
            var targetId = slider.getAttribute('data-target');
            var input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                return;
            }
            slider.addEventListener('input', function() {
                input.value = slider.value;
                updateFormFieldStylePreview();
            });
            input.addEventListener('input', function() {
                slider.value = clampOpacity(input.value);
                updateFormFieldStylePreview();
            });
        });
        document.querySelectorAll('.form-field-set-transparent').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var targetId = btn.getAttribute('data-target');
                var input = targetId ? document.getElementById(targetId) : null;
                var slider = targetId ? document.querySelector('.form-field-opacity-slider[data-target="' + targetId + '"]') : null;
                if (input) {
                    input.value = '0';
                }
                if (slider) {
                    slider.value = '0';
                }
                updateFormFieldStylePreview();
            });
        });
    }

    var sectionForm = document.getElementById('section-form');
    if (sectionForm) {
        sectionForm.addEventListener('input', function(ev) {
            if (isFormFieldColorTarget(ev.target)) {
                updateFormFieldStylePreview();
            }
        });
        sectionForm.addEventListener('change', function(ev) {
            if (isFormFieldColorTarget(ev.target)) {
                updateFormFieldStylePreview();
            }
        });
    }

    if ($select.length) {
        function applyFieldStylePreview() {
            var mode = $select.val() === 'placeholder' ? 'placeholder' : 'label_top';
            $('.form-field-style-preview').attr('data-style', mode);
        }
        $select.on('change', applyFieldStylePreview);
        applyFieldStylePreview();
    }

    var formFieldColorsToggle = document.getElementById('style_customization_form_field_colors');
    if (formFieldColorsToggle) {
        formFieldColorsToggle.addEventListener('change', function() {
            syncFormFieldColorsPanel();
            updateFormFieldStylePreview();
        });
    }
    syncFormFieldColorsPanel();
    syncFormFieldOpacityControls();
    updateFormFieldStylePreview();
    syncFormFieldStyleSectionsVisibility();

    $(document).on('change', '.field-checkbox, #field_pdv_group', function() {
        syncFormFieldStyleSectionsVisibility();
    });
});

// Anteprima live stile pagina Assistenza / FAQ (step 7)
(function() {
    function bootHelpPageStylePreview() {
        var previewNodes = Array.prototype.slice.call(document.querySelectorAll('.help-page-style-preview'));
        if (!previewNodes.length) {
            return;
        }
        var previewEl = previewNodes[0];

        function normalizeHex(val, fallback) {
            var hex = (val || '').trim();
            if (hex === '') {
                return fallback || '';
            }
            if (hex.charAt(0) !== '#') {
                hex = '#' + hex;
            }
            if (!/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                return fallback || '';
            }
            return hex;
        }

        function hexIsLight(hex) {
            var h = normalizeHex(hex, '');
            if (!h) {
                return false;
            }
            var r = parseInt(h.substr(1, 2), 16);
            var g = parseInt(h.substr(3, 2), 16);
            var b = parseInt(h.substr(5, 2), 16);
            var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.6;
        }

        function pickOrDefault(raw, fallback) {
            var normalized = normalizeHex(raw, '');
            return normalized || fallback;
        }

        function pickBgOrDefault(raw, fallback) {
            if (String(raw || '').trim().toLowerCase() === 'transparent') {
                return 'transparent';
            }
            return pickOrDefault(raw, fallback);
        }

        function getInputVal(id) {
            var el = document.getElementById(id);
            return el ? String(el.value || '').trim() : '';
        }

        function setPreviewVar(name, value) {
            previewNodes.forEach(function(node) {
                if (value === null || value === undefined || value === '') {
                    node.style.removeProperty(name);
                } else {
                    node.style.setProperty(name, value);
                }
            });
        }

        function setPreviewAttr(name, value) {
            previewNodes.forEach(function(node) {
                node.setAttribute(name, value);
            });
        }

        function updateHelpPageStylePreview() {
            previewNodes = Array.prototype.slice.call(document.querySelectorAll('.help-page-style-preview'));
            if (!previewNodes.length) {
                return;
            }
            previewEl = previewNodes[0];

            var primary = normalizeHex(getInputVal('colors_primary_text'), previewEl.getAttribute('data-palette-primary') || '#198754') || '#198754';
            var secondary = normalizeHex(getInputVal('colors_secondary_text'), '#6c757d') || '#6c757d';
            var tertiary = normalizeHex(getInputVal('colors_tertiary_text'), '#0a58ca') || '#0a58ca';
            var paletteText = normalizeHex(getInputVal('colors_text_text'), previewEl.getAttribute('data-palette-text') || '#000000') || '#000000';
            var formDefault = normalizeHex(getInputVal('form_field_text_color_text'), previewEl.getAttribute('data-default-form-text') || '#000000') || '#000000';
            var formFieldBgDefault = normalizeHex(getInputVal('form_field_bg_color_text'), '#ffffff') || '#ffffff';
            var formFieldBorderDefault = normalizeHex(getInputVal('form_field_border_color_text'), '#dee2e6') || '#dee2e6';
            var formCheckboxDefault = normalizeHex(getInputVal('form_field_checkbox_color_text'), secondary) || secondary;
            var formConsentTextDefault = normalizeHex(getInputVal('form_field_consent_text_color_text'), '#000000') || '#000000';
            var formConsentLinkDefault = normalizeHex(getInputVal('form_field_consent_link_color_text'), secondary) || secondary;
            var formConsentLinkHoverDefault = normalizeHex(getInputVal('form_field_consent_link_hover_color_text'), tertiary) || tertiary;

            var readableOnWhite = hexIsLight(paletteText) ? primary : paletteText;
            var bodyTextDefault = hexIsLight(paletteText) ? '#212529' : paletteText;

            var accHeaderBg = pickBgOrDefault(getInputVal('help_accordion_header_bg_text'), '#ffffff');
            var accHeaderText = pickOrDefault(getInputVal('help_accordion_header_text_text'), readableOnWhite);
            var openBgRaw = getInputVal('help_accordion_header_bg_open_text');
            var openBgTransparent = openBgRaw.toLowerCase() === 'transparent';
            var openBgMix = !openBgTransparent && (openBgRaw === '' || !/^#[0-9A-Fa-f]{6}$/.test(openBgRaw));
            var accHeaderBgOpen = openBgTransparent ? 'transparent' : (openBgMix ? '' : normalizeHex(openBgRaw, ''));
            var accHeaderTextOpen = pickOrDefault(getInputVal('help_accordion_header_text_open_text'), accHeaderText);
            var accBodyBg = pickBgOrDefault(getInputVal('help_accordion_body_bg_text'), '#ffffff');
            var accBodyText = pickOrDefault(getInputVal('help_accordion_body_text_text'), bodyTextDefault);
            var accTitleText = pickOrDefault(getInputVal('help_accordion_title_text_text'), readableOnWhite);
            var accBorderColor = pickOrDefault(getInputVal('help_accordion_border_color_text'), primary);

            var formHeading = pickOrDefault(getInputVal('help_form_heading_text_text'), readableOnWhite);
            var formSubtitle = pickOrDefault(getInputVal('help_form_subtitle_text_text'), bodyTextDefault);
            var formText = pickOrDefault(getInputVal('help_form_field_text_text'), formDefault);
            var placeholderRaw = getInputVal('help_form_placeholder_text_text');
            var placeholderMix = placeholderRaw === '' || !/^#[0-9A-Fa-f]{6}$/.test(placeholderRaw);
            var formPlaceholder = placeholderMix ? '' : normalizeHex(placeholderRaw, '');
            var formBg = pickBgOrDefault(getInputVal('help_form_field_bg_text'), formFieldBgDefault);
            var formBorder = pickOrDefault(getInputVal('help_form_field_border_text'), formFieldBorderDefault);
            var checkboxAccent = pickOrDefault(getInputVal('help_form_checkbox_text'), formCheckboxDefault);
            var consentText = pickOrDefault(getInputVal('help_form_consent_text_text'), formConsentTextDefault);
            var consentLink = pickOrDefault(getInputVal('help_form_consent_link_text'), formConsentLinkDefault);
            var consentLinkHover = pickOrDefault(getInputVal('help_form_consent_link_hover_text'), formConsentLinkHoverDefault);

            var cardBg = '#ffffff';
            var helpPanel = document.querySelector('.public-page-container-panel[data-public-page-container="help"]');
            var helpMode = helpPanel ? String((helpPanel.querySelector('.public-page-container-mode') || {}).value || 'color').toLowerCase() : 'color';
            var helpColor = getInputVal('public_page_container_help_color_text') || getInputVal('form_container_color_text') || '#ffffff';
            var helpOpacityEl = helpPanel ? helpPanel.querySelector('.public-page-container-opacity') : document.getElementById('form_container_opacity');
            var helpOpacity = helpOpacityEl ? Math.max(0, Math.min(100, parseInt(helpOpacityEl.value, 10) || 100)) : 100;
            if (helpMode === 'color') {
                if (String(helpColor).toLowerCase() === 'transparent' || helpOpacity <= 0) {
                    cardBg = 'transparent';
                } else {
                    var cardHex = normalizeHex(helpColor, '#ffffff') || '#ffffff';
                    var cr = parseInt(cardHex.substr(1, 2), 16);
                    var cg = parseInt(cardHex.substr(3, 2), 16);
                    var cb = parseInt(cardHex.substr(5, 2), 16);
                    cardBg = 'rgba(' + cr + ', ' + cg + ', ' + cb + ', ' + (helpOpacity / 100) + ')';
                }
            }

            var helpFontFamily = getInputVal('help_page_font_family');
            var helpFontSize = getInputVal('help_page_font_size');

            setPreviewVar('--hpp-acc-header-bg', accHeaderBg);
            setPreviewVar('--hpp-acc-header-text', accHeaderText);
            setPreviewVar('--hpp-acc-header-bg-open', accHeaderBgOpen === '' ? '#ffffff' : accHeaderBgOpen);
            setPreviewVar('--hpp-acc-header-text-open', accHeaderTextOpen);
            setPreviewVar('--hpp-acc-body-bg', accBodyBg);
            setPreviewVar('--hpp-acc-body-text', accBodyText);
            setPreviewVar('--hpp-acc-title-text', accTitleText);
            setPreviewVar('--hpp-acc-border-color', accBorderColor);
            setPreviewVar('--hpp-form-heading', formHeading);
            setPreviewVar('--hpp-form-subtitle', formSubtitle);
            setPreviewVar('--hpp-form-text', formText);
            setPreviewVar('--hpp-form-placeholder', formPlaceholder || formText);
            setPreviewVar('--hpp-form-bg', formBg);
            setPreviewVar('--hpp-form-border', formBorder);
            setPreviewVar('--hpp-checkbox-accent', checkboxAccent);
            setPreviewVar('--hpp-consent-text', consentText);
            setPreviewVar('--hpp-consent-link', consentLink);
            setPreviewVar('--hpp-consent-link-hover', consentLinkHover);
            setPreviewVar('--hpp-card-bg', cardBg);
            setPreviewVar('--hpp-palette-primary', primary);
            setPreviewVar('--hpp-palette-text', paletteText);
            setPreviewVar(
                '--hpp-font-family',
                helpFontFamily ? ("'" + helpFontFamily.replace(/'/g, '') + "', sans-serif") : ''
            );
            setPreviewVar('--hpp-font-size', helpFontSize);

            var faqTitleSize = getInputVal('help_accordion_title_font_size');
            var faqTitleMarginRaw = parseInt(getInputVal('help_accordion_title_margin_top'), 10);
            if (isNaN(faqTitleMarginRaw)) {
                faqTitleMarginRaw = 0;
            }
            faqTitleMarginRaw = Math.max(0, Math.min(120, faqTitleMarginRaw));
            setPreviewVar('--hpp-faq-title-size', faqTitleSize || '');
            setPreviewVar('--hpp-faq-title-margin-top', faqTitleMarginRaw + 'px');

            setPreviewAttr('data-open-bg-mix', openBgMix ? '1' : '0');
            setPreviewAttr('data-placeholder-mix', placeholderMix ? '1' : '0');
            var accordionTransparent = accHeaderBg === 'transparent' || accBodyBg === 'transparent' || accHeaderBgOpen === 'transparent';
            setPreviewAttr('data-acc-transparent', accordionTransparent ? '1' : '0');
        }

        $(document).on('change', '.help-style-bg-transparent-toggle', function() {
            var targetId = $(this).attr('data-target') || '';
            var $input = $('#' + targetId);
            var $card = $(this).closest('.faq-style-bg-color-card');
            var $swatch = $card.find('.help-style-bg-color-swatch');
            if (!$input.length) {
                return;
            }
            if ($(this).is(':checked')) {
                if ($input.val().toLowerCase() !== 'transparent') {
                    $input.data('prev-help-bg', $input.val());
                }
                $input.val('transparent').prop('readonly', true);
                $swatch.prop('disabled', true);
            } else {
                $input.val($input.data('prev-help-bg') || '').prop('readonly', false);
                $swatch.prop('disabled', false);
            }
            updateHelpPageStylePreview();
        });

        var sectionFaq = document.getElementById('section-faq');
        if (sectionFaq) {
            sectionFaq.addEventListener('input', function(ev) {
                var target = ev.target;
                if (!target) {
                    return;
                }
                if (target.classList.contains('help-style-color-input') ||
                    (target.classList.contains('color-input') && target.getAttribute('data-target')) ||
                    target.id === 'help_accordion_title_margin_top') {
                    updateHelpPageStylePreview();
                }
            });
            sectionFaq.addEventListener('change', function(ev) {
                var target = ev.target;
                if (!target) {
                    return;
                }
                if (target.classList.contains('help-style-color-input') ||
                    target.classList.contains('color-input') ||
                    target.id === 'help_page_font_family' ||
                    target.id === 'help_page_font_size' ||
                    target.id === 'help_accordion_title_font_size' ||
                    target.id === 'help_accordion_title_margin_top') {
                    updateHelpPageStylePreview();
                }
            });
        }

        var fallbackColorIds = [
            'colors_primary_text',
            'colors_secondary_text',
            'colors_tertiary_text',
            'colors_text_text',
            'form_field_text_color_text',
            'form_field_bg_color_text',
            'form_field_border_color_text',
            'form_field_checkbox_color_text',
            'form_field_consent_text_color_text',
            'form_field_consent_link_color_text',
            'form_field_consent_link_hover_color_text',
            'form_container_color_text',
            'public_page_container_help_color_text'
        ];
        fallbackColorIds.forEach(function(inputId) {
            var textEl = document.getElementById(inputId);
            if (textEl) {
                textEl.addEventListener('input', updateHelpPageStylePreview);
                textEl.addEventListener('change', updateHelpPageStylePreview);
            }
            var swatchEl = document.querySelector('input.color-input[data-target="' + inputId + '"]');
            if (swatchEl) {
                swatchEl.addEventListener('input', updateHelpPageStylePreview);
                swatchEl.addEventListener('change', updateHelpPageStylePreview);
            }
        });

        document.addEventListener('input', function(ev) {
            var target = ev.target;
            if (!target) {
                return;
            }
            if (target.id === 'form_container_opacity' ||
                target.id === 'form_container_opacity_slider' ||
                target.classList.contains('public-page-container-opacity') ||
                target.classList.contains('public-page-container-opacity-slider') ||
                target.classList.contains('public-page-container-mode')) {
                var panel = target.closest ? target.closest('.public-page-container-panel') : null;
                if (!panel || panel.getAttribute('data-public-page-container') === 'help' ||
                    target.id === 'form_container_opacity' || target.id === 'form_container_opacity_slider') {
                    updateHelpPageStylePreview();
                }
            }
        });
        document.addEventListener('change', function(ev) {
            var target = ev.target;
            if (!target) {
                return;
            }
            if (target.classList.contains('public-page-container-mode') ||
                target.classList.contains('public-page-container-opacity') ||
                target.classList.contains('public-page-container-opacity-slider') ||
                target.id === 'form_container_opacity' ||
                target.id === 'form_container_opacity_slider') {
                updateHelpPageStylePreview();
            }
        });

        window.updateHelpPageStylePreview = updateHelpPageStylePreview;
        updateHelpPageStylePreview();
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(bootHelpPageStylePreview);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootHelpPageStylePreview);
    } else {
        bootHelpPageStylePreview();
    }
})();

<?php readfile(__DIR__ . '/footer-editor.js'); ?>// Toggle gruppi campi
$(function() {
    function updateGroupToggle($group) {
        var $fields = $group.find('.field-checkbox:not(.prefisso-cellulare-group)');
        var $prefissoCellulareGroup = $group.find('.prefisso-cellulare-group');
        var total = $fields.length;
        var checked = $fields.filter(':checked').length;
        var $toggle = $group.find('.group-toggle');
        
        // Compter Prefisso/Cellulare comme un seul champ
        if ($prefissoCellulareGroup.length) {
            total += 1;
            var $prefissoHidden = $('#field_prefisso_hidden');
            var $cellulareHidden = $('#field_cellulare_hidden');
            if ($prefissoHidden.length && $cellulareHidden.length) {
                var prefissoChecked = $prefissoHidden.val() === '1';
                var cellulareChecked = $cellulareHidden.val() === '1';
                if (prefissoChecked && cellulareChecked) {
                    checked += 1;
                    $prefissoCellulareGroup.prop('checked', true);
                } else {
                    $prefissoCellulareGroup.prop('checked', false);
                }
            }
        }

        if (!total) {
            $toggle.prop('checked', false).prop('indeterminate', false);
            return;
        }
        if (checked === 0) {
            $toggle.prop('checked', false).prop('indeterminate', false);
        } else if (checked === total) {
            $toggle.prop('checked', true).prop('indeterminate', false);
        } else {
            $toggle.prop('checked', false).prop('indeterminate', true);
        }
    }

    function syncRequiredControls($scope) {
        var $root = $scope && $scope.length ? $scope : $('#section-form');
        $root.find('.field-checkbox').not('.prefisso-cellulare-group').each(function() {
            var $field = $(this);
            var fieldName = $field.attr('name') || '';
            if (fieldName.indexOf('fields_enabled[') !== 0) return;
            var key = fieldName.replace('fields_enabled[', '').replace(']', '');
            var $required = $('#field_' + key + '_required');
            if ($required.length) {
                $required.prop('disabled', !$field.is(':checked'));
            }
        });

        var prefissoCellulareOn = $('#field_prefisso_cellulare').is(':checked');
        $('#field_prefisso_cellulare_required').prop('disabled', !prefissoCellulareOn);
        var prefissoCellulareRequired = prefissoCellulareOn && $('#field_prefisso_cellulare_required').is(':checked');
        $('#field_prefisso_required_hidden').val(prefissoCellulareRequired ? '1' : '');
        $('#field_cellulare_required_hidden').val(prefissoCellulareRequired ? '1' : '');

        var pdvOn = $('#field_pdv_group').is(':checked');
        $('#field_pdv_group_required').prop('disabled', !pdvOn);
    }

    function updateFormCampiSummary() {
        var $badge = $('#form-campi-enabled-count');
        if (!$badge.length) return;
        var total = $('#section-form .field-group').length;
        var active = 0;
        $('#section-form .field-group').each(function() {
            var $group = $(this);
            var $toggle = $group.find('.group-toggle').first();
            if ($toggle.length) {
                if ($toggle.is(':checked') || $toggle.prop('indeterminate')) {
                    active++;
                }
            } else if ($group.find('.field-checkbox:checked').length > 0) {
                active++;
            }
        });
        $badge.text(active + ' / ' + total + ' attivi');
    }

    function setGroupChecked($group, checked) {
        // Cercare tutti i checkbox del gruppo (escluso prefisso-cellulare-group che viene gestito separatamente)
        var $checkboxes = $group.find('.field-checkbox:not(.prefisso-cellulare-group)');
        
        // Verificare se questo è il gruppo Scontrino
        var groupId = $group.data('group-id') || '';
        var isScontrinoGroup = groupId.indexOf('scontrino') !== -1 || $group.find('#field_scontrino').length > 0;
        
        // Impostare lo stato di tutti i checkbox
        $checkboxes.each(function() {
            var $checkbox = $(this);
            var wasChecked = $checkbox.is(':checked');
            $checkbox.prop('checked', checked);
            
            // Se lo stato è cambiato, triggerare l'evento change per aggiornare le dipendenze
            if (wasChecked !== checked) {
                $checkbox.trigger('change');
            }
        });
        
        // Gestire il caso speciale prefisso-cellulare-group
        var $prefissoCellulareGroup = $group.find('.prefisso-cellulare-group');
        if ($prefissoCellulareGroup.length) {
            $prefissoCellulareGroup.prop('checked', checked);
            var $prefissoHidden = $('#field_prefisso_hidden');
            var $cellulareHidden = $('#field_cellulare_hidden');
            if ($prefissoHidden.length && $cellulareHidden.length) {
                if (checked) {
                    $prefissoHidden.val('1');
                    $cellulareHidden.val('1');
                } else {
                    $prefissoHidden.val('');
                    $cellulareHidden.val('');
                }
            }
        }
        
        // Se è il gruppo Scontrino e viene attivato, assicurarsi che i tipi di file siano selezionati
        if (isScontrinoGroup && checked) {
            // Selezionare tutti i tipi di file se nessuno è selezionato
            var $receiptTypeCheckboxes = $('#receipt_types_panel input[type="checkbox"][name="receipt_allowed_extensions[]"]');
            if ($receiptTypeCheckboxes.length > 0 && $receiptTypeCheckboxes.filter(':checked').length === 0) {
                $receiptTypeCheckboxes.prop('checked', true);
            }
        }
        
        updateGroupToggle($group);
        syncRequiredControls($group);
    }

    $('.field-group').each(function() {
        updateGroupToggle($(this));
    });
    syncRequiredControls($('#section-form'));

    $(document).on('change', '.prefisso-cellulare-group', function() {
        var isChecked = $(this).is(':checked');
        var $prefissoHidden = $('#field_prefisso_hidden');
        var $cellulareHidden = $('#field_cellulare_hidden');
        
        if ($prefissoHidden.length && $cellulareHidden.length) {
            if (isChecked) {
                $prefissoHidden.val('1');
                $cellulareHidden.val('1');
            } else {
                $prefissoHidden.val('');
                $cellulareHidden.val('');
            }
        }
        syncRequiredControls($(this).closest('.field-group'));
        
        
        var $group = $(this).closest('.field-group');
        updateGroupToggle($group);
    });
    
    $(document).on('change', '.field-group .field-checkbox', function() {
        updateGroupToggle($(this).closest('.field-group'));
        syncRequiredControls($(this).closest('.field-group'));
    });

    $(document).on('change', '.field-required-checkbox', function() {
        syncRequiredControls($(this).closest('.field-group'));
    });
    
    $(document).on('change', '.field-group .group-toggle', function() {
        var $group = $(this).closest('.field-group');
        var isChecked = $(this).is(':checked');
        setGroupChecked($group, isChecked);
        
        // Se il gruppo è "Scontrino", aggiornare anche la visibilità dei campi correlati
        var groupId = $group.data('group-id') || '';
        var isScontrinoGroup = groupId.indexOf('scontrino') !== -1 || $group.find('#field_scontrino').length > 0;
        
        if (isScontrinoGroup) {
            // Forzare l'aggiornamento della visibilità dei campi correlati
            setTimeout(function() {
                updateReceiptTypesVisibility();
                updateAutoPrizeVisibility();
            }, 10);
        }
    });

    // Provincia XOR CAP (mutually exclusive)
    $(document).on('change', '.field-cap-provincia-xor', function() {
        var $cb = $(this);
        if (!$cb.is(':checked')) {
            return;
        }
        var key = String($cb.data('field-key') || '');
        var otherKey = key === 'cap' ? 'provincia' : (key === 'provincia' ? 'cap' : '');
        if (!otherKey) {
            return;
        }
        var $other = $('#field_' + otherKey);
        var $otherReq = $('#field_' + otherKey + '_required');
        if ($other.length && $other.is(':checked')) {
            $other.prop('checked', false);
            $otherReq.prop('checked', false).prop('disabled', true);
            updateGroupToggle($other.closest('.field-group'));
        }
    });

    // Ordine campi standard (SortableJS) — consensi esclusi; solo campi abilitati
    (function initStandardFieldsOrderUi() {
        var $list = $('#standard-fields-order-list');
        var $consensi = $('#standard-fields-order-consensi');
        if (!$list.length) {
            return;
        }

        var labels = {};
        try {
            labels = JSON.parse($('#standard-fields-order-labels-json').text() || '{}') || {};
        } catch (e) {
            labels = {};
        }
        var consensiKeys = [];
        try {
            consensiKeys = JSON.parse($('#standard-fields-order-consensi-json').text() || '[]') || [];
        } catch (e2) {
            consensiKeys = ['privacy', 'marketing', 'profilazione'];
        }

        var fullOrder = [];
        try {
            fullOrder = JSON.parse($list.attr('data-full-order') || '[]') || [];
        } catch (e3) {
            fullOrder = [];
        }
        if (!fullOrder.length) {
            $list.find('.standard-fields-order-item').each(function() {
                fullOrder.push(String($(this).data('order-key') || ''));
            });
        }

        function fieldCheckboxChecked(key) {
            var $cb = $('#field_' + key);
            if ($cb.length) {
                return $cb.is(':checked');
            }
            if (key === 'prefisso' || key === 'cellulare') {
                var $group = $('.prefisso-cellulare-group');
                if ($group.length) {
                    return $group.is(':checked');
                }
                return $('#field_prefisso_hidden').val() === '1' || $('#field_cellulare_hidden').val() === '1';
            }
            return false;
        }

        function isOrderKeyEnabled(orderKey) {
            if (orderKey === 'prefisso_cellulare') {
                return fieldCheckboxChecked('prefisso') || fieldCheckboxChecked('cellulare');
            }
            if (orderKey === 'cap_provincia') {
                return fieldCheckboxChecked('cap') || fieldCheckboxChecked('provincia');
            }
            if (orderKey === 'indirizzo_pdv') {
                return fieldCheckboxChecked('indirizzo_pdv')
                    || fieldCheckboxChecked('prov_pdv')
                    || fieldCheckboxChecked('citta_pdv')
                    || $('#field_pdv_group').is(':checked');
            }
            return fieldCheckboxChecked(orderKey);
        }

        function buildOrderItem(orderKey) {
            var label = labels[orderKey] || orderKey;
            return $(
                '<li class="list-group-item d-flex align-items-center standard-fields-order-item" data-order-key="' + orderKey + '">' +
                    '<span class="mr-2 text-muted" aria-hidden="true"><i class="fa fa-bars"></i></span>' +
                    '<span class="standard-fields-order-label"></span>' +
                    '<input type="hidden" name="standard_fields_order[]" value="">' +
                '</li>'
            ).each(function() {
                $(this).find('.standard-fields-order-label').text(label);
                $(this).find('input').val(orderKey);
            });
        }

        function currentVisibleOrder() {
            var keys = [];
            $list.find('.standard-fields-order-item').each(function() {
                var k = String($(this).data('order-key') || '');
                if (k) {
                    keys.push(k);
                }
            });
            return keys;
        }

        function refreshOrderListFromEnabled() {
            var visible = currentVisibleOrder();
            var next = [];
            // Preserve current visible order first
            visible.forEach(function(k) {
                if (isOrderKeyEnabled(k) && next.indexOf(k) === -1) {
                    next.push(k);
                }
            });
            // Append newly enabled keys in fullOrder position
            fullOrder.forEach(function(k) {
                if (isOrderKeyEnabled(k) && next.indexOf(k) === -1) {
                    next.push(k);
                }
            });

            $list.empty();
            next.forEach(function(k) {
                $list.append(buildOrderItem(k));
            });

            // Keep fullOrder in sync with visible + previously known disabled
            var merged = next.slice();
            fullOrder.forEach(function(k) {
                if (merged.indexOf(k) === -1) {
                    merged.push(k);
                }
            });
            fullOrder = merged;
            $list.attr('data-full-order', JSON.stringify(fullOrder));

            $consensi.empty();
            consensiKeys.forEach(function(k) {
                if (!isOrderKeyEnabled(k)) {
                    return;
                }
                var label = labels[k] || k;
                $consensi.append(
                    $('<div class="list-group-item d-flex align-items-center bg-light text-muted standard-fields-order-consenso"></div>')
                        .attr('data-order-key', k)
                        .append('<span class="mr-2" aria-hidden="true"><i class="fa fa-lock"></i></span>')
                        .append($('<span></span>').text(label))
                );
            });
        }

        if (typeof Sortable !== 'undefined') {
            Sortable.create($list[0], {
                animation: 150,
                handle: '.standard-fields-order-item',
                draggable: '.standard-fields-order-item',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen'
            });
        }

        $(document).on('change', '.field-checkbox, .prefisso-cellulare-group, #field_pdv_group', function() {
            refreshOrderListFromEnabled();
        });

        $('#standard-fields-order-collapse').on('shown.bs.collapse', function() {
            $('#standard-fields-order-toggle').attr('aria-expanded', 'true');
            $('.standard-fields-order-chevron').addClass('is-open');
        }).on('hidden.bs.collapse', function() {
            $('#standard-fields-order-toggle').attr('aria-expanded', 'false');
            $('.standard-fields-order-chevron').removeClass('is-open');
        });
    })();

    $(document).on('click', '.field-group-header', function(e) {
        if ($(e.target).closest('.field-group-toggle').length) {
            return;
        }
        var $group = $(this).closest('.field-group');
        var $toggle = $group.find('.group-toggle');
        var makeChecked = !$toggle.prop('checked') || $toggle.prop('indeterminate');
        $toggle.prop('checked', makeChecked).prop('indeterminate', false).trigger('change');
    });

    $(document).on('keydown', '.field-group-header', function(e) {
        if ($(e.target).closest('.field-group-toggle').length) {
            return;
        }
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    function buildFormCampiMenu() {
        var $menu = $('#form-campi-menu-list');
        if (!$menu.length) return;
        $menu.empty();
        var groupDescriptions = {
            'dati personali': 'Anagrafica, cellulare, CAP o provincia, indirizzo.',
            'fidelity': 'Codice fidelity.',
            'punto vendita': 'PDV combinato o cascata.',
            'scontrino': 'Importo, data, upload.',
            'codice partecipazione': 'Label, lunghezza, modalita.',
            'consensi': 'Privacy, marketing, profilazione.'
        };
        $('#section-form .field-group').each(function(index) {
            var $group = $(this);
            var groupId = String($group.data('group-id') || ('group-' + index));
            var title = $.trim($group.data('group-title') || $group.find('.section-subtitle').first().text() || 'Gruppo');
            var sub = $.trim($group.find('.field-group-description').first().text() || '');
            if (!sub) {
                sub = groupDescriptions[String(title).toLowerCase()] || '';
            }
            var switchId = 'menu_switch_' + groupId.replace(/[^a-z0-9_-]/gi, '_');
            var itemHtml = '' +
                '<div class="form-campi-menu-item" data-group-id="' + $('<div>').text(groupId).html() + '">' +
                    '<div>' +
                        '<p class="form-campi-menu-title">' + $('<div>').text(title).html() + '</p>' +
                        '<p class="form-campi-menu-sub">' + $('<div>').text(sub).html() + '</p>' +
                    '</div>' +
                    '<div class="custom-control custom-switch m-0">' +
                        '<input class="custom-control-input form-campi-menu-switch" id="' + $('<div>').text(switchId).html() + '" type="checkbox" data-group-id="' + $('<div>').text(groupId).html() + '">' +
                        '<label class="custom-control-label" for="' + $('<div>').text(switchId).html() + '"></label>' +
                    '</div>' +
                '</div>';
            $menu.append(itemHtml);
            var $item = $menu.children('.form-campi-menu-item').last();
            $item.attr('data-order', String(index));
            var $wrap = $group.closest('.form-campi-panel-wrap');
            $wrap.attr('data-group-id', groupId);
        });
    }

    function reorderFormCampiMenu() {
        var $menu = $('#form-campi-menu-list');
        if (!$menu.length) return;
        var $items = $menu.children('.form-campi-menu-item').get();
        $items.sort(function(a, b) {
            var aOn = $(a).find('.form-campi-menu-switch').is(':checked') ? 1 : 0;
            var bOn = $(b).find('.form-campi-menu-switch').is(':checked') ? 1 : 0;
            if (bOn !== aOn) {
                return bOn - aOn;
            }
            var aOrder = parseInt($(a).attr('data-order') || '0', 10);
            var bOrder = parseInt($(b).attr('data-order') || '0', 10);
            return aOrder - bOrder;
        });
        $.each($items, function(_, item) { $menu.append(item); });
    }

    function setActiveFormCampiPanel(groupId) {
        if (!groupId) return;
        $('#section-form .form-campi-menu-item').removeClass('active');
        $('#section-form .form-campi-menu-item[data-group-id="' + groupId + '"]').addClass('active');
        $('#section-form .form-campi-panel-wrap').removeClass('is-active');
        $('#section-form .form-campi-panel-wrap[data-group-id="' + groupId + '"]').addClass('is-active');
    }

    function refreshFormCampiMenuState() {
        $('#section-form .field-group').each(function() {
            var $group = $(this);
            var groupId = String($group.data('group-id') || '');
            if (!groupId) return;
            var $toggle = $group.find('.group-toggle').first();
            var enabled = false;
            if ($toggle.length) {
                enabled = $toggle.is(':checked') || $toggle.prop('indeterminate');
            } else {
                enabled = $group.find('.field-checkbox:checked').length > 0;
            }
            $('#section-form .form-campi-menu-switch[data-group-id="' + groupId + '"]').prop('checked', enabled);
        });
        reorderFormCampiMenu();
        updateFormCampiSummary();
    }

    $(document).on('click', '#section-form .form-campi-menu-item', function(e) {
        if ($(e.target).closest('.form-campi-menu-switch').length) return;
        var groupId = $(this).data('group-id');
        setActiveFormCampiPanel(groupId);
    });

    $(document).on('change', '#section-form .form-campi-menu-switch', function() {
        var groupId = String($(this).data('group-id') || '');
        var checked = $(this).is(':checked');
        if (!groupId) return;
        var $group = $('#section-form .field-group[data-group-id="' + groupId + '"]');
        if (!$group.length) return;
        var $toggle = $group.find('.group-toggle').first();
        if ($toggle.length) {
            $toggle.prop('checked', checked).prop('indeterminate', false).trigger('change');
        } else {
            var $primaryField = $group.find('.field-checkbox').first();
            if ($primaryField.length) {
                $primaryField.prop('checked', checked).trigger('change');
            }
        }
        refreshFormCampiMenuState();
    });

    buildFormCampiMenu();
    refreshFormCampiMenuState();
    var firstGroupId = $('#section-form .form-campi-menu-item').first().data('group-id');
    if (firstGroupId) {
        setActiveFormCampiPanel(String(firstGroupId));
    }
});

// Custom fields UI
$(function() {
    var list = $('#custom-fields-list');
    var editor = $('#custom-fields-editor');
    var addBtn = $('#add-custom-field');
    var template = document.getElementById('custom-field-template');
    var tabTemplate = document.getElementById('custom-field-tab-template');
    var emptyState = $('#custom-fields-empty');
    var customFieldsBody = $('.custom-fields-body');

    if (!list.length || !editor.length || !addBtn.length || !template || !tabTemplate) {
        return;
    }

    function updateEmptyState() {
        var hasItems = list.find('.custom-field-tab-item').length > 0;
        if (emptyState.length) {
            emptyState.toggle(!hasItems);
        }
        if (customFieldsBody.length) {
            customFieldsBody.toggle(hasItems);
        }
    }

    function buildCustomId() {
        return 'custom_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    }

    function labelToId(label) {
        if (!label || label.trim() === '') {
            return buildCustomId();
        }
        var id = label.toLowerCase();
        id = id.replace(/[^a-z0-9\\s_-]/g, '');
        id = id.replace(/\\s+/g, '_');
        id = id.replace(/[_-]+/g, '_');
        id = id.replace(/^[_-]+|[_-]+$/g, '');
        if (id === '') {
            return buildCustomId();
        }
        return id;
    }

    function getNextIndex() {
        var maxIndex = -1;
        editor.find('.custom-field-editor').each(function() {
            var value = $(this).data('field-index');
            var index = parseInt(value, 10);
            if (!Number.isNaN(index) && index > maxIndex) {
                maxIndex = index;
            }
        });
        return maxIndex + 1;
    }

    function setActiveField(fieldId) {
        var hasActive = false;
        list.find('.custom-field-tab').each(function() {
            var $tab = $(this);
            var isActive = $tab.data('field-id') === fieldId;
            $tab.toggleClass('active', isActive);
            if (isActive) {
                hasActive = true;
            }
        });
        editor.find('.custom-field-editor').each(function() {
            var $panel = $(this);
            var isActive = $panel.data('field-id') === fieldId;
            $panel.toggleClass('active', isActive);
        });
        if (!hasActive) {
            var firstTab = list.find('.custom-field-tab').first();
            if (firstTab.length) {
                setActiveField(firstTab.data('field-id'));
            }
        }
    }

    function updateTabMeta($panel) {
        var fieldId = $panel.data('field-id');
        var $tab = list.find('.custom-field-tab[data-field-id=\"' + fieldId + '\"]');
        if (!$tab.length) {
            return;
        }
        var label = $panel.find('.custom-field-label').val() || 'Nuovo campo';
        var type = $panel.find('.custom-field-type').val() || 'varchar';
        var typeLabel = type === 'boolean' ? 'CHECKBOX' : type.toUpperCase();
        $tab.find('.custom-field-tab-title').text(label);
        var $meta = $tab.find('.custom-field-tab-meta');
        $meta.find('.badge.badge-light').text(typeLabel);
        $meta.find('.badge.badge-warning').remove();
        var isRequired = $panel.find('input[name*=\"[required]\"]').is(':checked');
        if (isRequired) {
            $meta.append('<span class=\"badge badge-warning\">Obbligatorio</span>');
        }
    }

    function normalizePositionValue(value) {
        if (!value) {
            return '';
        }
        var map = {
            '1': 'before_s_nome',
            '2': 'after_s_nome',
            '3': 'after_s_cognome',
            '4': 'after_s_email',
            '5': 'after_s_prefisso_cellulare',
            '6': 'after_s_cap',
            '7': 'after_s_citta',
            '8': 'after_s_indirizzo',
            '9': 'after_s_fidelity',
            '10': 'after_s_indirizzo_pdv',
            '11': 'after_s_importo',
            '12': 'after_s_data_scontrino',
            '13': 'after_s_scontrino',
            '14': 'after_s_privacy',
            '15': 'after_s_marketing',
            '16': 'after_s_profilazione',
            'nome': 'after_s_nome',
            'cognome': 'after_s_cognome',
            'email': 'after_s_email',
            'cellulare': 'after_s_prefisso_cellulare',
            'cap': 'after_s_cap',
            'provincia': 'after_s_provincia',
            'citta': 'after_s_citta',
            'indirizzo': 'after_s_indirizzo',
            'fidelity': 'after_s_fidelity',
            'indirizzo_pdv': 'after_s_indirizzo_pdv',
            'importo': 'after_s_importo',
            'data_scontrino': 'after_s_data_scontrino',
            'scontrino': 'after_s_scontrino',
            'privacy': 'after_s_privacy',
            'marketing': 'after_s_marketing',
            'profilazione': 'after_s_profilazione'
        };
        if (map[value]) {
            return map[value];
        }
        return value;
    }

    function getFieldsEnabled() {
        var fieldsEnabled = {
            'nome': true,
            'cognome': true,
            'email': true,
            'prefisso_cellulare': true,
            'fidelity': true,
            'cap': false,
            'provincia': true,
            'citta': true,
            'indirizzo': true,
            'prov_pdv': true,
            'citta_pdv': true,
            'indirizzo_pdv': true,
            'importo': true,
            'data_scontrino': true,
            'scontrino': true,
            'codice_partecipazione': true,
            'privacy': true,
            'marketing': true,
            'profilazione': true
        };

        $('.field-checkbox:not(.prefisso-cellulare-group)').each(function() {
            var $checkbox = $(this);
            var fieldName = $checkbox.attr('name');
            if (fieldName && fieldName.indexOf('fields_enabled[') === 0) {
                var key = fieldName.replace('fields_enabled[', '').replace(']', '');
                if (fieldsEnabled.hasOwnProperty(key)) {
                    fieldsEnabled[key] = $checkbox.is(':checked');
                }
            }
        });

        var $prefissoCellulareCheckbox = $('#field_prefisso_cellulare');
        if ($prefissoCellulareCheckbox.length) {
            fieldsEnabled['prefisso_cellulare'] = $prefissoCellulareCheckbox.is(':checked');
        }

        var $pdvGroupCheckbox = $('#field_pdv_group');
        if ($pdvGroupCheckbox.length) {
            var pdvEnabled = $pdvGroupCheckbox.is(':checked');
            fieldsEnabled['prov_pdv'] = pdvEnabled;
            fieldsEnabled['citta_pdv'] = pdvEnabled;
            fieldsEnabled['indirizzo_pdv'] = pdvEnabled;
        }

        return fieldsEnabled;
    }

    function filterOptions(options, fieldsEnabled) {
        var customIds = getCustomFieldIds();
        var hasCustomIds = Object.keys(customIds).length > 0;
        var filtered = [];
        (options || []).forEach(function(opt) {
            var match = opt.value.match(/^(before|after)_(s|c)_(.+)$/);
            if (!match) {
                return;
            }
            if (match[2] === 'c') {
                var customId = match[3];
                if (customId && hasCustomIds && customIds[customId]) {
                    filtered.push(opt);
                }
                return;
            }
            var fieldName = match[3];
            if (fieldName === 'prefisso' || fieldName === 'cellulare') {
                fieldName = 'prefisso_cellulare';
            }
            if (fieldName === 'prov_pdv' || fieldName === 'citta_pdv') {
                fieldName = 'indirizzo_pdv';
            }
            if (!fieldsEnabled.hasOwnProperty(fieldName) || fieldsEnabled[fieldName] === true) {
                filtered.push(opt);
            }
        });
        return filtered;
    }

    function getCustomFieldIds() {
        var ids = {};
        if (!list || !list.length) {
            return ids;
        }
        list.find('.custom-field-tab-item').each(function() {
            var id = $(this).data('field-id');
            if (id) {
                ids[id] = true;
            }
        });
        return ids;
    }

    function stripCustomOptions(options) {
        return (options || []).filter(function(opt) {
            return !(opt.value && /^(before|after)_c_/.test(opt.value));
        });
    }

    function resolveSectionForValue(value, standardOptions) {
        if (!value) {
            return 'section1';
        }
        var sections = ['section1', 'section2', 'section3', 'section4', 'section5', 'section6'];
        for (var i = 0; i < sections.length; i++) {
            var key = sections[i];
            var list = standardOptions[key] || [];
            for (var j = 0; j < list.length; j++) {
                if (list[j].value === value) {
                    return key;
                }
            }
        }
        return 'section1';
    }

    function findDesiredPosition(value, optionsSource) {
        if (!value) {
            return null;
        }
        var numeric = parseInt(value, 10);
        if (!Number.isNaN(numeric)) {
            return numeric;
        }
        var allSections = []
            .concat(optionsSource.section1 || [])
            .concat(optionsSource.section2 || [])
            .concat(optionsSource.section3 || [])
            .concat(optionsSource.section4 || [])
            .concat(optionsSource.section5 || [])
            .concat(optionsSource.section6 || []);
        for (var i = 0; i < allSections.length; i++) {
            if (allSections[i].value === value) {
                return allSections[i].position || null;
            }
        }
        return null;
    }

    function getDynamicPositionOptions() {
        var base = (typeof DYNAMIC_POSITION_OPTIONS !== 'undefined' && DYNAMIC_POSITION_OPTIONS)
            ? DYNAMIC_POSITION_OPTIONS
            : { section1: [], section2: [], section3: [], section4: [], section5: [], section6: [] };
        var standard = {
            section1: stripCustomOptions(base.section1 || []),
            section2: stripCustomOptions(base.section2 || []),
            section3: stripCustomOptions(base.section3 || []),
            section4: stripCustomOptions(base.section4 || []),
            section5: stripCustomOptions(base.section5 || []),
            section6: stripCustomOptions(base.section6 || [])
        };
        var dynamic = {
            section1: standard.section1.slice(),
            section2: standard.section2.slice(),
            section3: standard.section3.slice(),
            section4: standard.section4.slice(),
            section5: standard.section5.slice(),
            section6: standard.section6.slice()
        };

        editor.find('.custom-field-editor').each(function() {
            var $panel = $(this);
            var fieldId = $panel.data('field-id');
            if (!fieldId) {
                return;
            }
            var rawLabel = ($panel.find('.custom-field-label').val() || '').trim();
            if (!rawLabel) {
                return;
            }
            var label = rawLabel;
            var currentValue = $panel.find('.custom-field-position').val() || $panel.data('position-value') || '';
            var normalizedValue = normalizePositionValue(currentValue);
            var desiredPosition = findDesiredPosition(normalizedValue, standard);
            if (desiredPosition === null) {
                desiredPosition = 5;
            }
            var sectionKey = resolveSectionForValue(normalizedValue, standard);
            var beforeValue = 'before_c_' + fieldId;
            var afterValue = 'after_c_' + fieldId;
            var typeIndicator = ' (custom)';
            var beforeOpt = {
                value: beforeValue,
                label: 'Prima di ' + label + typeIndicator,
                position: desiredPosition
            };
            var afterOpt = {
                value: afterValue,
                label: 'Dopo ' + label + typeIndicator,
                position: desiredPosition + 1
            };
            var targetList = dynamic[sectionKey] || dynamic.section1;
            if (!targetList.some(function(opt) { return opt.value === beforeValue; })) {
                targetList.push(beforeOpt);
            }
            if (!targetList.some(function(opt) { return opt.value === afterValue; })) {
                targetList.push(afterOpt);
            }
        });

        ['section1', 'section2', 'section3', 'section4', 'section5', 'section6'].forEach(function(key) {
            if (dynamic[key]) {
                dynamic[key].sort(function(a, b) {
                    return (a.position || 0) - (b.position || 0);
                });
            }
        });

        return dynamic;
    }

    function fillPositionSelect($select, fieldType, currentValue) {
        var dynamicOptions = getDynamicPositionOptions();
        var fieldsEnabled = getFieldsEnabled();
        var section1 = filterOptions(dynamicOptions.section1, fieldsEnabled);
        var section2 = filterOptions(dynamicOptions.section2, fieldsEnabled);
        var section3 = filterOptions(dynamicOptions.section3, fieldsEnabled);
        var section4 = filterOptions(dynamicOptions.section4, fieldsEnabled);
        var section5 = filterOptions(dynamicOptions.section5, fieldsEnabled);
        var section6 = filterOptions(dynamicOptions.section6, fieldsEnabled);
        $select.empty();

        var currentFieldId = $select.closest('.custom-field-editor').data('field-id');
        if (currentFieldId) {
            var selfPattern = new RegExp('^(before|after)_c_' + currentFieldId + '$');
            section1 = section1.filter(function(opt) { return !selfPattern.test(opt.value); });
            section2 = section2.filter(function(opt) { return !selfPattern.test(opt.value); });
            section3 = section3.filter(function(opt) { return !selfPattern.test(opt.value); });
            section4 = section4.filter(function(opt) { return !selfPattern.test(opt.value); });
            section5 = section5.filter(function(opt) { return !selfPattern.test(opt.value); });
            section6 = section6.filter(function(opt) { return !selfPattern.test(opt.value); });
        }

        var allHaveOptions = section1.length || section2.length || section3.length || section4.length || section5.length || section6.length;
        if (!allHaveOptions) {
            $select.append($('<option></option>').attr('value', '').text('Inserisci nel form'));
            $select.val('');
            $select.closest('.custom-field-editor').data('position-value', '');
            return;
        }

        if (section1.length) {
            var $optgroup1 = $('<optgroup label="1. Dati personali"></optgroup>');
            section1.forEach(function(opt) {
                $optgroup1.append($('<option></option>').attr('value', opt.value).text(opt.label));
            });
            $select.append($optgroup1);
        }

        if (section2.length) {
            var $optgroup2 = $('<optgroup label="2. Fidelity"></optgroup>');
            section2.forEach(function(opt) {
                $optgroup2.append($('<option></option>').attr('value', opt.value).text(opt.label));
            });
            $select.append($optgroup2);
        }

        if (section3.length) {
            var $optgroup3 = $('<optgroup label="3. Punto vendita"></optgroup>');
            section3.forEach(function(opt) {
                $optgroup3.append($('<option></option>').attr('value', opt.value).text(opt.label));
            });
            $select.append($optgroup3);
        }

        if (section4.length) {
            var $optgroup4 = $('<optgroup label="4. Scontrino"></optgroup>');
            section4.forEach(function(opt) {
                $optgroup4.append($('<option></option>').attr('value', opt.value).text(opt.label));
            });
            $select.append($optgroup4);
        }

        if (section5.length) {
            var $optgroup5 = $('<optgroup label="5. Codice partecipazione"></optgroup>');
            section5.forEach(function(opt) {
                $optgroup5.append($('<option></option>').attr('value', opt.value).text(opt.label));
            });
            $select.append($optgroup5);
        }

        if (section6.length) {
            var $optgroup6 = $('<optgroup label="6. Consensi"></optgroup>');
            section6.forEach(function(opt) {
                $optgroup6.append($('<option></option>').attr('value', opt.value).text(opt.label));
            });
            $select.append($optgroup6);
        }

        function pickClosestOption(options, desiredPosition) {
            if (!options.length) {
                return null;
            }
            if (desiredPosition === null) {
                return options[0].value;
            }
            var best = null;
            var bestPos = -Infinity;
            for (var i = 0; i < options.length; i++) {
                var pos = options[i].position;
                if (pos === undefined || pos === null) {
                    continue;
                }
                if (pos <= desiredPosition && pos > bestPos) {
                    bestPos = pos;
                    best = options[i].value;
                }
            }
            if (best !== null) {
                return best;
            }
            return options[0].value;
        }

        var normalizedValue = normalizePositionValue(currentValue);
        var desiredPosition = findDesiredPosition(normalizedValue, dynamicOptions);
        var allFiltered = []
            .concat(section1)
            .concat(section2)
            .concat(section3)
            .concat(section4)
            .concat(section5)
            .concat(section6);
        if (normalizedValue && $select.find('option[value="' + normalizedValue + '"]').length) {
            $select.val(normalizedValue);
            $select.closest('.custom-field-editor').data('position-value', normalizedValue);
        } else {
            var fallbackValue = pickClosestOption(allFiltered, desiredPosition);
            if (fallbackValue) {
                $select.val(fallbackValue);
                $select.closest('.custom-field-editor').data('position-value', fallbackValue);
            } else {
                $select.val('');
                $select.closest('.custom-field-editor').data('position-value', '');
            }
        }
    }

    function updateAllPositionDropdowns() {
        editor.find('.custom-field-editor').each(function() {
            var $panel = $(this);
            var fieldId = $panel.data('field-id');
            var $gridContainer = $panel.find('.position-grid-desktop');
            var $hiddenInput = $panel.find('input.custom-field-position');
            var currentValue = normalizePositionValue(($hiddenInput.val() || $panel.data('position-value') || '').trim());
            if ($gridContainer.length) {
                buildPositionGrid($gridContainer, fieldId, currentValue, $hiddenInput);
            }
        });
    }

    function findInsertIndexForPosition(items, posVal, isAfter) {
        if (!posVal) return 0;
        var m = posVal.match(/after_(?:s|c)_(.+)/);
        var place = 'after';
        var ref = null;
        if (m) { ref = m[1]; } else {
            m = posVal.match(/before_(?:s|c)_(.+)/);
            if (m) { ref = m[1]; place = 'before'; }
        }
        if (!ref) return 0;
        var idx = -1;
        for (var i = items.length - 1; i >= 0; i--) {
            if (items[i].ref === ref) { idx = i; break; }
        }
        if (idx < 0) return 0;
        return place === 'after' ? idx + 1 : idx;
    }

    function buildPositionGrid($container, currentFieldId, currentValue, $hiddenInput) {
        var pdvMode = ($('#pdv_config_mode').val() || 'combinato').toLowerCase();
        var baseItems = (pdvMode === 'cascata' && typeof POSITION_GRID_ITEMS_CASCATA !== 'undefined' && Array.isArray(POSITION_GRID_ITEMS_CASCATA))
            ? POSITION_GRID_ITEMS_CASCATA.slice()
            : ((typeof POSITION_GRID_ITEMS !== 'undefined' && Array.isArray(POSITION_GRID_ITEMS)) ? POSITION_GRID_ITEMS.slice() : []);
        var fieldsEnabled = getFieldsEnabled();
        var standardAndSeparators = baseItems.filter(function(it) {
            if (it.type === 'separator') return true;
            if (it.type === 'custom') return false;
            var ref = it.ref || '';
            return fieldsEnabled.hasOwnProperty(ref) && fieldsEnabled[ref] !== false;
        });
        var customIds = getCustomFieldIds();
        var tabOrder = [];
        list.find('.custom-field-tab-item').each(function() {
            var id = $(this).data('field-id');
            if (id) tabOrder.push(id);
        });
        var customFieldsData = [];
        tabOrder.forEach(function(id) {
            if (!id) return;
            var $p = editor.find('.custom-field-editor[data-field-id=\"' + id + '\"]');
            var posVal = (id === currentFieldId) ? (currentValue || $p.find('input.custom-field-position').val() || '') : ($p.find('input.custom-field-position').val() || $p.data('position-value') || '').trim();
            posVal = normalizePositionValue ? normalizePositionValue(posVal) : posVal;
            var label = ($p.find('.custom-field-label').val() || '').trim() || 'Nuovo campo';
            var widthVal = ($p.find('select.custom-field-width, select[name*="[field_width]"]').val() || 'small').toLowerCase();
            var isLarge = widthVal === 'large';
            var baseIdx = findInsertIndexForPosition(standardAndSeparators, posVal, true);
            customFieldsData.push({ id: id, posVal: posVal, label: label, baseIdx: baseIdx, is_large: isLarge });
        });
        customFieldsData.sort(function(a, b) { return a.baseIdx - b.baseIdx || tabOrder.indexOf(a.id) - tabOrder.indexOf(b.id); });
        var items = standardAndSeparators.slice();
        var lastInsertForRef = {};
        customFieldsData.forEach(function(cf) {
            var posVal = cf.posVal || '';
            var m = posVal.match(/after_(?:s|c)_(.+)/);
            var ref = m ? m[1] : null;
            if (!m) { m = posVal.match(/before_(?:s|c)_(.+)/); ref = m ? m[1] : null; }
            var insertAt = 0;
            if (ref) {
                var prev = lastInsertForRef[ref];
                if (prev !== undefined) {
                    insertAt = prev + 1;
                } else {
                    for (var i = items.length - 1; i >= 0; i--) {
                        if (items[i].ref === ref) {
                            insertAt = posVal.indexOf('before_') === 0 ? i : i + 1;
                            break;
                        }
                    }
                }
                lastInsertForRef[ref] = insertAt;
            } else {
                insertAt = 2;
            }
            var customItem = { type: 'custom', ref: cf.id, label: cf.label, before_value: 'before_c_' + cf.id, after_value: 'after_c_' + cf.id, is_large: !!cf.is_large };
            items.splice(Math.min(insertAt, items.length), 0, customItem);
            if (ref) lastInsertForRef[ref] = insertAt;
        });
        $container.empty();
        var sortableInstance = $container.data('sortable-instance');
        if (sortableInstance && sortableInstance.destroy) sortableInstance.destroy();
        $container.removeData('sortable-instance');
        var currentIdx = -1;
        var prevWasSeparator = false;
        items.forEach(function(it, i) {
            if (it.type === 'separator') {
                var hasContentAfter = false;
                for (var j = i + 1; j < items.length; j++) {
                    if (items[j].type !== 'separator') { hasContentAfter = true; break; }
                }
                if (hasContentAfter && !prevWasSeparator) {
                    var $el = $('<div class="position-grid-item position-grid-separator position-grid-full position-grid-static" data-ref="" data-before="" data-after="">&nbsp;</div>');
                    $container.append($el);
                    prevWasSeparator = true;
                }
                return;
            }
            prevWasSeparator = false;
            var isCurrent = (it.type === 'custom' && it.ref === currentFieldId);
            if (isCurrent) currentIdx = i;
            var $el = $('<div class="position-grid-item position-grid-' + it.type + (it.is_large ? ' position-grid-full' : '') + (isCurrent ? ' position-grid-draggable' : ' position-grid-static') + '" data-ref="' + (it.ref || '') + '" data-before="' + (it.before_value || '') + '" data-after="' + (it.after_value || '') + '">' + (it.label || '') + '</div>');
            $container.append($el);
        });
        if (!currentValue && currentIdx > 0 && currentFieldId) {
            var prevItem = items[currentIdx - 1];
            var defVal = prevItem ? (prevItem.after_value || 'after_s_email') : 'after_s_email';
            $hiddenInput.val(defVal);
            $container.closest('.custom-field-editor').data('position-value', defVal);
        }
        if (typeof Sortable !== 'undefined') {
            var sortable = new Sortable($container[0], {
                animation: 150,
                filter: '.position-grid-static',
                preventOnFilter: false,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function(evt) {
                    if (!evt.item || !evt.item.classList.contains('position-grid-draggable')) return;
                    var idx = Array.prototype.indexOf.call($container.children(), evt.item);
                    var newVal = '';
                    if (idx === 0) {
                        var next = $container.children().eq(1);
                        if (next.length) newVal = next.data('before') || next.attr('data-before') || '';
                    }
                    if (!newVal && idx > 0) {
                        var prev = $container.children().eq(idx - 1);
                        if (prev.length) newVal = prev.data('after') || prev.attr('data-after') || '';
                    }
                    if (!newVal && idx >= 0) {
                        var next = $container.children().eq(idx + 1);
                        if (next.length) newVal = next.data('before') || next.attr('data-before') || '';
                    }
                    if (!newVal) newVal = 'after_s_nome';
                    $hiddenInput.val(newVal);
                    $container.closest('.custom-field-editor').data('position-value', newVal);
                    schedulePositionUpdate();
                }
            });
            $container.data('sortable-instance', sortable);
        }
    }
    var positionUpdateScheduled = false;
    function schedulePositionUpdate() {
        if (positionUpdateScheduled) {
            return;
        }
        positionUpdateScheduled = true;
        var runner = function() {
            positionUpdateScheduled = false;
            updateAllPositionDropdowns();
        };
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(runner);
        } else {
            setTimeout(runner, 0);
        }
    }

    function toggleFieldControls($panel) {
        var fieldType = $panel.find('.custom-field-type').val();
        var $lengthControls = $panel.find('.custom-field-length-controls');
        var $dateControls = $panel.find('.custom-field-date-controls');
        var $intControls = $panel.find('.custom-field-int-controls');

        if (fieldType === 'date' || fieldType === 'int' || fieldType === 'boolean') {
            $lengthControls.hide();
            $lengthControls.find('input').val('');
        } else {
            $lengthControls.show();
        }

        if (fieldType === 'date') {
            $dateControls.show();
        } else {
            $dateControls.hide();
            $dateControls.find('input[type=\"date\"]').val('');
            $dateControls.find('input[type=\"checkbox\"]').prop('checked', false);
        }

        if (fieldType === 'int') {
            $intControls.show();
            syncIntExactDigitsMutex($panel);
        } else {
            $intControls.hide();
            $intControls.find('input').val('');
            $intControls.find('input').prop('disabled', false);
        }
    }

    function syncIntExactDigitsMutex($panel) {
        var $exact = $panel.find('.custom-field-int-exact-digits');
        var $min = $panel.find('.custom-field-int-min');
        var $max = $panel.find('.custom-field-int-max');
        if (!$exact.length) {
            return;
        }
        var hasExact = String($exact.val() || '').trim() !== '';
        if (hasExact) {
            $min.val('').prop('disabled', true);
            $max.val('').prop('disabled', true);
        } else {
            $min.prop('disabled', false);
            $max.prop('disabled', false);
        }
    }

    function wireIntExactDigitsMutex($panel) {
        var $exact = $panel.find('.custom-field-int-exact-digits');
        $exact.off('input.intExact change.intExact').on('input.intExact change.intExact', function() {
            syncIntExactDigitsMutex($panel);
        });
        syncIntExactDigitsMutex($panel);
    }

    function wireDateMax($panel) {
        var $dateMaxToday = $panel.find('input[name*=\"[date_max_today]\"]');
        var $dateMax = $panel.find('input[name*=\"[date_max]\"]');
        $dateMaxToday.off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $dateMax.prop('disabled', true).val('');
            } else {
                $dateMax.prop('disabled', false);
            }
        });
        if ($dateMaxToday.is(':checked')) {
            $dateMax.prop('disabled', true);
        }
    }

    function removeField(fieldId, index) {
        if (fieldId) {
            list.find('.custom-field-tab-item[data-field-id=\"' + fieldId + '\"]').remove();
            editor.find('.custom-field-editor[data-field-id=\"' + fieldId + '\"]').remove();
        } else if (index !== null && index !== undefined) {
            list.find('.custom-field-tab-item[data-field-index=\"' + index + '\"]').remove();
            editor.find('.custom-field-editor[data-field-index=\"' + index + '\"]').remove();
        }
        schedulePositionUpdate();
        updateEmptyState();
        var firstTab = list.find('.custom-field-tab').first();
        if (firstTab.length) {
            setActiveField(firstTab.data('field-id'));
        }
    }

    function initPanel($panel) {
        toggleFieldControls($panel);
        wireDateMax($panel);
        wireIntExactDigitsMutex($panel);
        updateTabMeta($panel);
    }

    addBtn.on('click', function() {
        var newId = buildCustomId();
        var newIndex = getNextIndex();
        var html = template.innerHTML.replace(/__INDEX__/g, newIndex).replace(/__ID__/g, newId);
        var tabHtml = tabTemplate.innerHTML.replace(/__INDEX__/g, newIndex).replace(/__ID__/g, newId);
        var $panel = $(html);
        var $tab = $(tabHtml);
        editor.append($panel);
        list.append($tab);
        initPanel($panel);
        schedulePositionUpdate();
        updateEmptyState();
        setActiveField(newId);
        if (window.applyConfigTooltips) {
            window.applyConfigTooltips($panel[0]);
        }
    });

    list.on('click', '.custom-field-tab', function() {
        var fieldId = $(this).data('field-id');
        setActiveField(fieldId);
        schedulePositionUpdate();
    });

    list.on('click', '.custom-field-remove', function(e) {
        e.stopPropagation();
        var $item = $(this).closest('.custom-field-tab-item');
        removeField($item.data('field-id'), $item.data('field-index'));
    });

    editor.on('click', '.custom-field-remove', function() {
        var $panel = $(this).closest('.custom-field-editor');
        removeField($panel.data('field-id'), $panel.data('field-index'));
    });

    editor.on('input', '.custom-field-label', function() {
        var $panel = $(this).closest('.custom-field-editor');
        var $idInput = $panel.find('input[name*=\"[id]\"]');
        var currentId = $idInput.val();
        if (!currentId || /^custom_\\d+_\\d+$/.test(currentId)) {
            var newId = labelToId($(this).val());
            $idInput.val(newId);
            $panel.attr('data-field-id', newId);
            list.find('.custom-field-tab-item[data-field-id=\"' + currentId + '\"]')
                .attr('data-field-id', newId)
                .find('.custom-field-tab').attr('data-field-id', newId);
        }
        updateTabMeta($panel);
        schedulePositionUpdate();
    });

    editor.on('change', '.custom-field-type, input[name*=\"[required]\"], .custom-field-width', function() {
        var $panel = $(this).closest('.custom-field-editor');
        toggleFieldControls($panel);
        schedulePositionUpdate();
        updateTabMeta($panel);
    });

    editor.on('change', '.custom-field-position', function() {
        var $panel = $(this).closest('.custom-field-editor');
        $panel.data('position-value', $(this).val());
    });

    $(document).on('change', '.field-checkbox, .group-toggle, .prefisso-cellulare-group, #field_pdv_group', function() {
        schedulePositionUpdate();
    });
    $(document).on('positionGridRefresh', function() {
        schedulePositionUpdate();
    });

    editor.find('.custom-field-editor').each(function() {
        initPanel($(this));
    });
    schedulePositionUpdate();
    updateEmptyState();

    var firstTab = list.find('.custom-field-tab').first();
    if (firstTab.length) {
        setActiveField(firstTab.data('field-id'));
    }
});

// Fond Summernote (.note-editable.card-block): sync con il canvas reale (container / footer / quiz).
// Email templates restano su fondo bianco (regole CSS dedicate).
(function() {
    function normalizeHex(val) {
        var hex = (val || '').trim();
        if (hex === '') {
            return '';
        }
        if (hex.charAt(0) !== '#') {
            hex = '#' + hex;
        }
        if (!/^#[0-9a-fA-F]{6}$/.test(hex)) {
            return '';
        }
        return hex;
    }

    function hexToRgba(hex, opacity) {
        var clean = normalizeHex(hex);
        if (!clean) {
            return '';
        }
        var r = parseInt(clean.substr(1, 2), 16);
        var g = parseInt(clean.substr(3, 2), 16);
        var b = parseInt(clean.substr(5, 2), 16);
        var alpha = Math.max(0, Math.min(100, parseInt(opacity, 10) || 0)) / 100;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    /**
     * @param {JQuery} $noteEditable
     * @param {{backgroundColor?:string,backgroundImage?:string,backgroundSize?:string,backgroundPosition?:string,backgroundRepeat?:string,borderRadius?:string,padding?:string,isTransparent?:boolean}} style
     */
    window.applyNoteEditableCanvas = function($noteEditable, style) {
        if (!$noteEditable || !$noteEditable.length) {
            return;
        }
        style = style || {};
        var bgColor = style.backgroundColor || '';
        var bgImage = style.backgroundImage || '';
        if (style.isTransparent || (!bgColor && !bgImage)) {
            bgColor = '#f8f9fb';
            bgImage = '';
        }
        $noteEditable.css({
            backgroundColor: bgColor,
            backgroundImage: bgImage,
            backgroundSize: bgImage ? (style.backgroundSize || 'cover') : '',
            backgroundPosition: bgImage ? (style.backgroundPosition || 'center') : '',
            backgroundRepeat: bgImage ? (style.backgroundRepeat || 'no-repeat') : '',
            borderRadius: style.borderRadius || '',
            padding: style.padding || '12px'
        });
    };

    window.noteEditableHexToRgba = hexToRgba;
    window.noteEditableNormalizeHex = normalizeHex;
})();

// Editor waiting page: container multipli
$(function() {
    if (!window.syncPageContainerEditorsBeforeSubmit) {
        window.syncPageContainerEditorsBeforeSubmit = function(scope) {
            var $scope = scope ? $(scope) : $(document);
            $scope.find('.waiting-container-editor').each(function() {
                var $editor = $(this);
                if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
                    var html = $editor.summernote('code') || '';
                    if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                        html = window.contestSanitizeSummernoteHtml(html);
                    }
                    $editor.val(html);
                }
            });
        };
    }

    function deferInit(fn) {
        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(fn, { timeout: 1500 });
        } else {
            setTimeout(fn, 0);
        }
    }
    function initWaitingEditor(options) {
        var list = options.list;
        var template = options.template;
        var addBtn = options.addBtn;
        var preview = options.preview;
        var emptyState = options.emptyState;
        var globalAlignInput = options.globalAlignInput;
        if (!list || !list.length || !preview || !preview.length || !template) {
            return;
        }

    var containerIndex = list.find('.waiting-container-item').length;
    var previewAlignDefault = preview.data('alignDefault') || 'center';

    function isHex(val) {
        return /^#?[0-9a-fA-F]{6}$/.test((val || '').trim());
    }

    function normalizeHex(val) {
        var hex = (val || '').trim();
        if (hex === '') {
            return '';
        }
        if (hex.charAt(0) !== '#') {
            hex = '#' + hex;
        }
        if (!/^#[0-9a-fA-F]{6}$/.test(hex)) {
            return '';
        }
        return hex;
    }

    function hexToRgba(hex, opacity) {
        var clean = normalizeHex(hex);
        if (!clean) {
            return '';
        }
        var r = parseInt(clean.substr(1, 2), 16);
        var g = parseInt(clean.substr(3, 2), 16);
        var b = parseInt(clean.substr(5, 2), 16);
        var alpha = Math.max(0, Math.min(100, parseInt(opacity, 10) || 0)) / 100;
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }

    function getMarginValue($input, fallback) {
        if (!$input || !$input.length) {
            return fallback;
        }
        var val = parseInt($input.val(), 10);
        if (isNaN(val)) {
            return fallback;
        }
        return Math.max(0, val);
    }

    function getContainerMargins($container) {
        var defaults = { top: 0, right: 0, bottom: 16, left: 0 };
        return {
            top: getMarginValue($container.find('.waiting-container-margin-top'), defaults.top),
            right: getMarginValue($container.find('.waiting-container-margin-right'), defaults.right),
            bottom: getMarginValue($container.find('.waiting-container-margin-bottom'), defaults.bottom),
            left: getMarginValue($container.find('.waiting-container-margin-left'), defaults.left)
        };
    }

    function getRadiusValue($input, fallback) {
        if (!$input || !$input.length) {
            return fallback;
        }
        var val = parseInt($input.val(), 10);
        if (isNaN(val)) {
            return fallback;
        }
        return Math.max(0, val);
    }

    function getContainerRadius($container) {
        var defaults = { top: 8, right: 8, bottom: 8, left: 8 };
        return {
            top: getRadiusValue($container.find('.waiting-container-radius-top'), defaults.top),
            right: getRadiusValue($container.find('.waiting-container-radius-right'), defaults.right),
            bottom: getRadiusValue($container.find('.waiting-container-radius-bottom'), defaults.bottom),
            left: getRadiusValue($container.find('.waiting-container-radius-left'), defaults.left)
        };
    }

    function getPreviewUrl($input) {
        if (!$input || !$input.length) {
            return '';
        }
        var node = $input[0];
        if (!node.files || !node.files[0]) {
            return '';
        }
        var existing = $input.data('previewUrl');
        if (existing) {
            return existing;
        }
        var url = URL.createObjectURL(node.files[0]);
        $input.data('previewUrl', url);
        return url;
    }

    function revokePreviewUrl($input) {
        if (!$input || !$input.length) {
            return;
        }
        var url = $input.data('previewUrl');
        if (url) {
            URL.revokeObjectURL(url);
            $input.removeData('previewUrl');
        }
    }

    function normalizeAlign(value, fallback) {
        var align = (value || '').toString().toLowerCase().trim();
        if (align === 'start') {
            align = 'left';
        } else if (align === 'end') {
            align = 'right';
        }
        if (['left', 'center', 'right', 'justify'].indexOf(align) === -1) {
            align = fallback || previewAlignDefault;
        }
        return align;
    }

    function getContainerContentType($container) {
        var type = ($container.find('.waiting-container-type').val() || 'text').toString().toLowerCase().trim();
        if (type !== 'image') {
            type = 'text';
        }
        return type;
    }

    function bindColorInputs($container) {
        $container.find('.color-input').each(function() {
            var picker = this;
            if (picker.dataset.bound === '1') {
                return;
            }
            picker.dataset.bound = '1';
            var targetId = picker.getAttribute('data-target');
            var target = document.getElementById(targetId);
            if (!target) return;

            picker.addEventListener('input', function() {
                target.value = picker.value;
                scheduleWaitingPreview();
            });

            target.addEventListener('input', function() {
                var val = target.value.trim();
                if (isHex(val)) {
                    if (val.charAt(0) !== '#') {
                        val = '#' + val;
                    }
                    picker.value = val;
                }
                scheduleWaitingPreview();
            });
        });
    }

    function bindOpacityControls($container) {
        var $slider = $container.find('.waiting-bg-opacity-slider');
        var $input = $container.find('.waiting-bg-opacity-input');
        if (!$slider.length || !$input.length) {
            return;
        }
        if ($slider.data('bound')) {
            return;
        }
        $slider.data('bound', '1');
        $slider.on('input', function() {
            $input.val($slider.val());
            scheduleWaitingPreview();
        });
        $input.on('input', function() {
            var val = Math.max(0, Math.min(100, parseInt($input.val(), 10) || 0));
            $input.val(val);
            $slider.val(val);
            scheduleWaitingPreview();
        });
        $input.on('blur', function() {
            var val = Math.max(0, Math.min(100, parseInt($input.val(), 10) || 0));
            $input.val(val);
            $slider.val(val);
            scheduleWaitingPreview();
        });
    }

    function applyBackgroundMode($container) {
        var mode = ($container.find('.waiting-container-bg-mode').val() || 'color').toString().toLowerCase().trim();
        var $colorRow = $container.find('.waiting-container-bg-color');
        var $imageRow = $container.find('.waiting-container-bg-image');
        if (mode === 'image') {
            $colorRow.hide();
            $imageRow.show();
        } else {
            $colorRow.show();
            $imageRow.hide();
        }
    }

    function updateAlignFromSelect($container) {
        var $alignInput = $container.find('.waiting-container-align');
        var $select = $container.find('.waiting-container-align-select');
        if (!$alignInput.length || !$select.length) {
            return;
        }
        var align = normalizeAlign($select.val(), previewAlignDefault);
        $select.val(align);
        $alignInput.val(align);
    }

    function applyContainerType($container) {
        var type = getContainerContentType($container);
        var $text = $container.find('.waiting-container-content-text');
        var $image = $container.find('.waiting-container-content-image');
        var $alignWrapper = $container.find('.waiting-container-align-select-wrapper');
        if (type === 'image') {
            $text.hide();
            $image.show();
            $alignWrapper.removeClass('d-none');
            var currentAlign = normalizeAlign($container.find('.waiting-container-align').val(), previewAlignDefault);
            $container.find('.waiting-container-align-select').val(currentAlign);
            updateAlignFromSelect($container);
        } else {
            $text.show();
            $image.hide();
            $alignWrapper.addClass('d-none');
        }
    }

    function getContainerBackgroundCss($container) {
        var mode = ($container.find('.waiting-container-bg-mode').val() || 'color').toString().toLowerCase().trim();
        var opacityValue = parseInt($container.find('.waiting-bg-opacity-input').val(), 10);
        if (isNaN(opacityValue)) {
            opacityValue = 100;
        }
        var isTransparent = mode === 'transparent' || (mode === 'color' && opacityValue <= 0);
        var result = {
            backgroundColor: '',
            backgroundImage: '',
            backgroundSize: '',
            backgroundPosition: '',
            isTransparent: isTransparent
        };
        if (mode === 'color' && !isTransparent) {
            var color = $container.find('.waiting-container-bg-color-text').val() || $container.find('.color-input').val() || '';
            var rgba = hexToRgba(color, opacityValue);
            if (rgba) {
                result.backgroundColor = rgba;
            }
        } else if (mode === 'image') {
            var imageUrl = '';
            var $bgFile = $container.find('.waiting-bg-file-input');
            var fileUrl = getPreviewUrl($bgFile);
            if (fileUrl) {
                imageUrl = fileUrl;
            } else {
                var $bgValue = $container.find('.waiting-bg-image-value');
                imageUrl = ($bgValue.data('previewSrc') || $bgValue.val() || '').trim();
            }
            if (imageUrl) {
                result.backgroundImage = "url('" + imageUrl.replace(/'/g, "\\'") + "')";
                result.backgroundSize = 'cover';
                result.backgroundPosition = 'center';
            }
        }
        return result;
    }

    function applyPreviewBackground($previewItem, $container) {
        var css = getContainerBackgroundCss($container);
        $previewItem.toggleClass('is-transparent', !!css.isTransparent);
        $previewItem.css({
            backgroundColor: '',
            backgroundImage: '',
            backgroundSize: '',
            backgroundPosition: ''
        });
        if (css.backgroundColor) {
            $previewItem.css('background-color', css.backgroundColor);
        }
        if (css.backgroundImage) {
            $previewItem.css({
                backgroundImage: css.backgroundImage,
                backgroundSize: css.backgroundSize,
                backgroundPosition: css.backgroundPosition
            });
        }
    }

    function applyEditorCanvasStyle($container) {
        if (typeof window.applyNoteEditableCanvas !== 'function') {
            return;
        }
        var $note = $container.data('noteEditable');
        if (!$note || !$note.length) {
            var $editor = $container.find('.waiting-container-editor');
            $note = $editor.next('.note-editor').find('.note-editable');
            if ($note.length) {
                $container.data('noteEditable', $note);
            }
        }
        if (!$note || !$note.length) {
            return;
        }
        var css = getContainerBackgroundCss($container);
        var radius = getContainerRadius($container);
        window.applyNoteEditableCanvas($note, {
            backgroundColor: css.backgroundColor,
            backgroundImage: css.backgroundImage,
            backgroundSize: css.backgroundSize,
            backgroundPosition: css.backgroundPosition,
            isTransparent: css.isTransparent,
            borderRadius: radius.top + 'px ' + radius.right + 'px ' + radius.bottom + 'px ' + radius.left + 'px',
            padding: '12px'
        });
    }

    function readCommandAlign() {
        try {
            if (document.queryCommandState && document.queryCommandState('justifyCenter')) {
                return 'center';
            }
            if (document.queryCommandState && document.queryCommandState('justifyRight')) {
                return 'right';
            }
            if (document.queryCommandState && document.queryCommandState('justifyFull')) {
                return 'justify';
            }
            if (document.queryCommandState && document.queryCommandState('justifyLeft')) {
                return 'left';
            }
        } catch (err) {
            return '';
        }
        return '';
    }

    function replaceWaitingPlaceholders(html) {
        var placeholders = {
            '{{CONTEST_NAME}}': preview.data('placeholderContestName') || '',
            '{{START_DATE}}': preview.data('placeholderStartDate') || '',
            '{{END_DATE}}': preview.data('placeholderEndDate') || '',
            '{{MIN_SPEND}}': preview.data('placeholderMinSpend') || '',
            '{{REGOLAMENTO_LINK}}': preview.data('placeholderRegolamentoLink') || '',
            '{{PRIZE_WIN_AMOUNT}}': preview.data('placeholderPrizeWinAmount') || '',
            '{{PRIZE_TYPE}}': preview.data('placeholderPrizeType') || ''
        };
        Object.keys(placeholders).forEach(function(key) {
            var val = placeholders[key];
            html = html.split(key).join(val);
            // href Summernote: {{…}} → %7B%7B…%7D%7D
            try {
                var encoded = encodeURIComponent(key);
                if (encoded && encoded !== key) {
                    html = html.split(encoded).join(val);
                }
            } catch (err) { /* ignore */ }
        });
        return html;
    }

    function decodeWaitingHtml(html) {
        if (!html) {
            return '';
        }
        return html.replace(/&lt;a\b([\s\S]*?)&gt;([\s\S]*?)&lt;\/a&gt;/gi, function(match, attrs) {
            var hasBtnClass = /class\s*=\s*(['"])[\s\S]*?\bbtn\b[\s\S]*?\1/i.test(attrs) ||
                /class\s*=\s*[^"'\s>]*\bbtn\b[^"'\s>]*/i.test(attrs);
            if (!hasBtnClass) {
                return match;
            }
            return match.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        });
    }

    function sanitizeWaitingHtml(html) {
        var allowedTags = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'STRONG', 'EM', 'U', 'B', 'I', 'BR', 'A', 'UL', 'OL', 'LI', 'SPAN', 'DIV', 'IMG', 'FONT'];
        var allowedAttrs = {
            STYLE: true, CLASS: true, HREF: true, SRC: true, ALT: true, COLOR: true,
            TARGET: true, REL: true, WIDTH: true, HEIGHT: true, TITLE: true, ID: true
        };
        if (typeof html === 'string') {
            html = html.replace(/font-family\s*:\s*"([^"]+)"/gi, "font-family: '$1'");
            html = html.replace(/font-family\s*:\s*&quot;([^&]+)&quot;/gi, "font-family: '$1'");
        }
        var container = document.createElement('div');
        container.innerHTML = html;
        Array.prototype.slice.call(container.querySelectorAll('*')).forEach(function(node) {
            if (allowedTags.indexOf(node.tagName) === -1) {
                if (node.tagName === 'SCRIPT' || node.tagName === 'STYLE') {
                    node.remove();
                    return;
                }
                var frag = document.createDocumentFragment();
                while (node.firstChild) {
                    frag.appendChild(node.firstChild);
                }
                node.parentNode.replaceChild(frag, node);
                return;
            }
            Array.prototype.slice.call(node.attributes || []).forEach(function(attr) {
                var name = (attr.name || '').toUpperCase();
                if (!allowedAttrs[name]) {
                    node.removeAttribute(attr.name);
                    return;
                }
                if (name === 'STYLE' && attr.value) {
                    node.setAttribute('style', String(attr.value).replace(/font-family\s*:\s*"([^"]+)"/gi, "font-family: '$1'"));
                }
            });
        });
        return container.innerHTML;
    }

    function getEditorHtml($container) {
        var $editor = $container.find('.waiting-container-editor');
        if (!$editor.length) {
            return '';
        }
        if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
            return $editor.summernote('code');
        }
        return $editor.val() || '';
    }

    function getButtonPreviewConfig(buttonKey) {
        var prefix = 'buttons_' + buttonKey + '_';
        var mode = (($('[name="' + prefix + 'mode"]').val() || 'color') + '').toLowerCase();
        var bg = (($('[name="' + prefix + 'bg"]').val() || '') + '').trim();
        var textColor = (($('[name="' + prefix + 'text_color"]').val() || '') + '').trim();
        var border = (($('[name="' + prefix + 'border"]').val() || '') + '').trim();
        var image = ($('#preview_button_' + buttonKey + '_image').attr('src') || '').trim();
        if ($('#preview_button_' + buttonKey + '_image').hasClass('d-none')) {
            image = '';
        }
        return {
            mode: mode,
            bg: bg,
            textColor: textColor,
            border: border,
            image: image
        };
    }

    function applyContestButtonStyles($scope) {
        if (!$scope || !$scope.length) {
            return;
        }
        var customOn = $('#style_customization_buttons_colors').is(':checked');
        if (!customOn) {
            $scope.find('.btn').each(function() {
                $(this).css({
                    backgroundImage: '',
                    backgroundColor: '',
                    borderColor: '',
                    color: ''
                });
            });
            return;
        }
        var primary = getButtonPreviewConfig('primary');
        var secondary = getButtonPreviewConfig('secondary');
        $scope.find('.btn').each(function() {
            var $btn = $(this);
            var cls = ($btn.attr('class') || '').toLowerCase();
            var isSecondary = cls.indexOf('btn-secondary') !== -1 ||
                cls.indexOf('btn-outline-secondary') !== -1 ||
                cls.indexOf('btn-thank-outline') !== -1;
            var conf = isSecondary ? secondary : primary;
            if (conf.mode === 'image' && conf.image) {
                $btn.css({
                    backgroundImage: "url('" + conf.image.replace(/'/g, "\\'") + "')",
                    backgroundSize: 'cover',
                    backgroundPosition: 'center center',
                    backgroundRepeat: 'no-repeat',
                    backgroundColor: 'transparent'
                });
            } else {
                $btn.css({
                    backgroundImage: 'none',
                    backgroundColor: conf.bg || '',
                    borderColor: conf.border || ''
                });
            }
            if (conf.textColor) {
                $btn.css('color', conf.textColor);
            }
        });
    }

    function readButtonFontFamilyCss(buttonKey) {
        var font = ($('[name="buttons_' + buttonKey + '_font"]').val() || '').toString().trim();
        if (font) {
            return "'" + font.replace(/'/g, '') + "', sans-serif";
        }
        return 'Arial, Helvetica, sans-serif';
    }

    function readButtonFontSizeCss(buttonKey) {
        return ($('[name="buttons_' + buttonKey + '_font_size"]').val() || '').toString().trim();
    }

    function updateWaitingButtonCssVars() {
        var $roots = $('.waiting-page-preview');
        $('.waiting-page-section .note-editor .note-editable').each(function() {
            $roots = $roots.add(this);
        });
        if (!$roots.length) {
            return;
        }
        var primaryFont = readButtonFontFamilyCss('primary');
        var secondaryFont = readButtonFontFamilyCss('secondary');
        var primarySize = readButtonFontSizeCss('primary');
        var secondarySize = readButtonFontSizeCss('secondary');
        var customOn = $('#style_customization_buttons_colors').is(':checked');
        var vars = {
            '--waiting-btn-primary-font': primaryFont,
            '--waiting-btn-secondary-font': secondaryFont
        };
        if (primarySize) {
            vars['--waiting-btn-primary-font-size'] = primarySize;
        }
        if (secondarySize) {
            vars['--waiting-btn-secondary-font-size'] = secondarySize;
        }
        if (customOn) {
            var primary = getButtonPreviewConfig('primary');
            var secondary = getButtonPreviewConfig('secondary');
            vars['--waiting-primary'] = primary.bg || '';
            vars['--waiting-primary-hover'] = ($('[name="buttons_primary_hover_bg"]').val() || primary.bg || '');
            vars['--waiting-primary-text'] = primary.textColor || '#ffffff';
            vars['--waiting-secondary'] = secondary.bg || '';
            vars['--waiting-secondary-hover'] = ($('[name="buttons_secondary_hover_bg"]').val() || secondary.bg || '');
            vars['--waiting-secondary-text'] = secondary.textColor || '#ffffff';
            $roots.css(vars);
            if (!primarySize) {
                $roots.each(function() { this.style.removeProperty('--waiting-btn-primary-font-size'); });
            }
            if (!secondarySize) {
                $roots.each(function() { this.style.removeProperty('--waiting-btn-secondary-font-size'); });
            }
            return;
        }
        var palettePrimary = ($('#colors_primary_text').val() || '').trim();
        var paletteSecondary = ($('#colors_secondary_text').val() || '').trim();
        var paletteTertiary = ($('#colors_tertiary_text').val() || '').trim();
        if (isHex(palettePrimary)) {
            vars['--waiting-primary'] = palettePrimary;
            vars['--waiting-primary-hover'] = 'color-mix(in srgb, ' + palettePrimary + ' 85%, #000000)';
            vars['--waiting-primary-text'] = '#ffffff';
            vars['--waiting-secondary'] = isHex(paletteSecondary) ? paletteSecondary : '#6c757d';
            vars['--waiting-secondary-hover'] = isHex(paletteTertiary) ? paletteTertiary : paletteSecondary;
            vars['--waiting-secondary-text'] = '#ffffff';
        }
        $roots.css(vars);
        if (!primarySize) {
            $roots.each(function() { this.style.removeProperty('--waiting-btn-primary-font-size'); });
        }
        if (!secondarySize) {
            $roots.each(function() { this.style.removeProperty('--waiting-btn-secondary-font-size'); });
        }
    }
    window.updateWaitingButtonCssVars = updateWaitingButtonCssVars;

    function syncButtonsCustomColorsPanels() {
        var customOn = $('#style_customization_buttons_colors').is(':checked');
        var editorWrap = document.getElementById('buttons-custom-editor-wrap');
        if (editorWrap) {
            editorWrap.style.display = customOn ? '' : 'none';
        }
        document.querySelectorAll('.button-style-panel').forEach(function(stylePanel) {
            if (typeof window.applyButtonColorPanelsVisibility === 'function') {
                window.applyButtonColorPanelsVisibility(stylePanel, customOn);
            }
        });
    }

    function syncFooterTextColorsPanel() {
        var customOn = $('#style_customization_footer_text_colors').is(':checked');
        var panel = document.getElementById('footer-text-colors-panel');
        if (panel) {
            panel.style.display = customOn ? '' : 'none';
        }
    }

    function syncHelpPageStyleEditor() {
        var customOn = $('#style_customization_help_page_colors').is(':checked');
        var wrap = document.getElementById('help-page-style-editor-wrap');
        if (wrap) {
            // Anteprima sempre visibile (font size/family); nasconde solo i selettori colore
            wrap.style.display = '';
        }
        document.querySelectorAll('#help-page-style-editor-wrap .faq-style-editor-col').forEach(function(col) {
            col.style.display = customOn ? '' : 'none';
        });
        document.querySelectorAll('#help-page-style-editor-wrap .faq-preview-col').forEach(function(col) {
            if (customOn) {
                col.classList.remove('col-xl-12');
                col.classList.add('col-xl-4');
            } else {
                col.classList.remove('col-xl-4');
                col.classList.add('col-xl-12');
            }
        });
    }

    $('#style_customization_buttons_colors').on('change', function() {
        syncButtonsCustomColorsPanels();
        updateWaitingButtonCssVars();
        scheduleWaitingPreview();
    });
    syncButtonsCustomColorsPanels();

    $('#style_customization_footer_text_colors').on('change', function() {
        syncFooterTextColorsPanel();
        if (typeof window.scheduleFooterPreview === 'function') {
            window.scheduleFooterPreview();
        }
    });
    syncFooterTextColorsPanel();

    $('#style_customization_help_page_colors').on('change', syncHelpPageStyleEditor);
    syncHelpPageStyleEditor();

    function syncGlobalAlign() {
        if (!globalAlignInput.length) {
            return;
        }
        var $firstAlign = list.find('.waiting-container-align').first();
        var align = previewAlignDefault;
        if ($firstAlign.length) {
            align = normalizeAlign($firstAlign.val(), previewAlignDefault);
        }
        globalAlignInput.val(align);
    }

    var waitingPreviewScheduled = false;
    function scheduleWaitingPreview() {
        if (waitingPreviewScheduled) {
            return;
        }
        waitingPreviewScheduled = true;
        var run = function() {
            waitingPreviewScheduled = false;
            rebuildWaitingPreview();
        };
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(run);
        } else {
            setTimeout(run, 0);
        }
    }

    function rebuildWaitingPreview() {
        updateWaitingButtonCssVars();
        preview.empty();
        var hasContainers = false;
        list.find('.waiting-container-item').each(function(index) {
            var $container = $(this);
            var alignValue = normalizeAlign($container.find('.waiting-container-align').val(), previewAlignDefault);
            var $previewItem = $('<div class="waiting-container-preview"></div>');
            $previewItem.attr('draggable', 'true');
            $previewItem.attr('data-container-index', index);
            $previewItem.css('text-align', alignValue);
            applyPreviewBackground($previewItem, $container);
            applyEditorCanvasStyle($container);
            var margins = getContainerMargins($container);
            $previewItem.css('margin', margins.top + 'px ' + margins.right + 'px ' + margins.bottom + 'px ' + margins.left + 'px');
            var radius = getContainerRadius($container);
            $previewItem.css('border-radius', radius.top + 'px ' + radius.right + 'px ' + radius.bottom + 'px ' + radius.left + 'px');

            if (getContainerContentType($container) === 'image') {
                var src = '';
                var $imageFile = $container.find('.waiting-image-file-input');
                var fileImageUrl = getPreviewUrl($imageFile);
                if (fileImageUrl) {
                    src = fileImageUrl;
                } else {
                    var $imageValue = $container.find('.waiting-image-src-value');
                    src = ($imageValue.data('previewSrc') || $imageValue.val() || '').trim();
                }
                var alt = ($container.find('.waiting-container-image-alt').val() || '').trim();
                var width = ($container.find('.waiting-container-image-width').val() || '').trim();
                if (src) {
                    var $img = $('<img>').addClass('img-fluid').attr('src', src).attr('alt', alt);
                    if (width) {
                        $img.css('max-width', width);
                    }
                    $previewItem.append($img);
                } else {
                    $previewItem.append('<p class="text-muted mb-0">Nessun contenuto impostato.</p>');
                }
            } else {
                var raw = getEditorHtml($container);
                var content = decodeWaitingHtml(raw || '');
                content = replaceWaitingPlaceholders(content);
                content = sanitizeWaitingHtml(content);
                if (content.trim() === '') {
                    content = '<p class="text-muted mb-0">Nessun contenuto impostato.</p>';
                }
                $previewItem.append(content);
                applyContestButtonStyles($previewItem);
            }
            preview.append($previewItem);
            hasContainers = true;
        });
        if (!hasContainers) {
            var $emptyPreview = $('<div class="waiting-container-preview"></div>');
            $emptyPreview.css('text-align', previewAlignDefault);
            $emptyPreview.html('<p class="text-muted mb-0">Nessun contenuto impostato.</p>');
            preview.append($emptyPreview);
        }
        appendPageButtonFooterPreview();
        syncGlobalAlign();
    }

    function appendPageButtonFooterPreview() {
        var previewId = preview.attr('id') || '';
        var storeMap = {
            'participation-page-preview': {
                text: 'store_participation_page_button_footer_text',
                color: 'store_participation_page_button_footer_text_color'
            },
            'form-page-preview': {
                text: 'store_form_page_button_footer_text',
                color: 'store_form_page_button_footer_text_color'
            }
        };
        var ids = storeMap[previewId];
        if (!ids) {
            return;
        }
        var textEl = document.getElementById(ids.text);
        var raw = textEl ? String(textEl.value || '').trim() : '';
        if (!raw) {
            return;
        }
        raw = replaceWaitingPlaceholders(raw);
        var safe = $('<div>').text(raw).html().replace(/\n/g, '<br>');
        var colorEl = document.getElementById(ids.color);
        var color = colorEl ? String(colorEl.value || '').trim() : '';
        if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
            color = String($('#colors_text_text').val() || '').trim();
        }
        if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
            color = '#212529';
        }
        var $footer = $('<div class="registration-button-footer-text mt-3 text-center"></div>');
        $footer.css({
            color: color,
            fontSize: '0.7rem',
            lineHeight: '1.4'
        });
        $footer.html(safe);
        if (previewId === 'participation-page-preview') {
            var $host = preview.find('.waiting-container-preview').filter(function() {
                return $(this).find('.btn, a.btn, button.btn').length > 0;
            }).last();
            if (!$host.length) {
                $host = preview.find('.waiting-container-preview').last();
            }
            if ($host.length) {
                $host.append($footer);
                return;
            }
        }
        preview.append($footer);
    }

    function syncAlignFromEditor($container) {
        if (getContainerContentType($container) !== 'text') {
            return;
        }
        var $alignInput = $container.find('.waiting-container-align');
        var alignDefault = $alignInput.data('alignDefault') || previewAlignDefault;
        var noteEditable = $container.data('noteEditable');
        var align = readCommandAlign();
        if (!align && noteEditable && noteEditable.length) {
            align = noteEditable.css('text-align') || '';
        }
        if (!align) {
            align = $alignInput.val() || alignDefault;
        }
        align = normalizeAlign(align, alignDefault);
        if ($alignInput.length) {
            $alignInput.val(align);
        }
        if (noteEditable && noteEditable.length) {
            noteEditable.css('text-align', align);
        }
    }

    function replaceButtonTagsInEditor($container) {
        var noteEditable = $container.data('noteEditable');
        if (!noteEditable || !noteEditable.length || $container.data('isReplacingButtons')) {
            return;
        }
        if (getContainerContentType($container) !== 'text') {
            return;
        }
        var buttons = noteEditable[0].querySelectorAll('a.btn');
        if (!buttons.length) {
            return;
        }
        $container.data('isReplacingButtons', true);
        Array.prototype.slice.call(buttons).forEach(function(node) {
            var html = node.outerHTML || '';
            var textNode = document.createTextNode(html);
            node.parentNode.replaceChild(textNode, node);
        });
        $container.data('isReplacingButtons', false);
    }

    function setWaitingEditorModeToggle($container, mode) {
        var $toggle = $container.find('.waiting-editor-mode-toggle');
        if (!$toggle.length) {
            return;
        }
        $toggle.find('[data-editor-mode]').removeClass('is-active');
        $toggle.find('[data-editor-mode="' + mode + '"]').addClass('is-active');
    }

    function isWaitingEditorInCodeview($editor) {
        if (!$editor || !$editor.length || !$editor.data('summernote')) {
            return false;
        }
        var $noteEditor = $editor.next('.note-editor');
        if ($noteEditor.hasClass('codeview')) {
            return true;
        }
        try {
            return !!$editor.summernote('codeview.isActivated');
        } catch (e) {
            return false;
        }
    }

    function setWaitingEditorCodeview($editor, wantHtml) {
        if (!$editor || !$editor.length || typeof $.fn.summernote !== 'function' || !$editor.data('summernote')) {
            return;
        }
        var inCode = isWaitingEditorInCodeview($editor);
        if (wantHtml && !inCode) {
            try {
                $editor.summernote('codeview.activate');
            } catch (e1) {
                $editor.summernote('codeview.toggle');
            }
        } else if (!wantHtml && inCode) {
            try {
                $editor.summernote('codeview.deactivate');
            } catch (e2) {
                $editor.summernote('codeview.toggle');
            }
        }
    }

    function syncWaitingEditorModeFromCodeview($container) {
        var $editor = $container.find('.waiting-container-editor');
        var mode = isWaitingEditorInCodeview($editor) ? 'html' : 'visual';
        setWaitingEditorModeToggle($container, mode);
    }

    function initContainerEditor($container) {
        var $editor = $container.find('.waiting-container-editor');
        var $alignInput = $container.find('.waiting-container-align');
        var alignDefault = $alignInput.data('alignDefault') || previewAlignDefault;
        if (!$editor.length) {
            return;
        }
        if (typeof $.fn.summernote === 'function') {
            if ($editor.data('summernote')) {
                return;
            }
            $editor.summernote($.extend({
                height: 220,
                lang: 'it-IT',
                fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
                toolbar: [
                    ['font', ['fontname', 'bold', 'italic', 'underline', 'clear', 'fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph', 'height']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ],
                disableDragAndDrop: true,
                callbacks: {
                    onInit: function() {
                        if (typeof window.contestSummernoteSanitizeEditor === 'function') {
                            window.contestSummernoteSanitizeEditor($editor);
                        }
                        setWaitingEditorModeToggle($container, 'visual');
                        var $noteEditor = $editor.next('.note-editor');
                        $noteEditor.find('.btn-codeview').on('click', function() {
                            setTimeout(function() {
                                syncWaitingEditorModeFromCodeview($container);
                            }, 0);
                        });
                    },
                    onChange: function(contents) {
                        if ($container.data('isReplacingButtons')) {
                            return;
                        }
                        if ($container.data('isSanitizingFonts')) {
                            return;
                        }
                        if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                            var raw = (contents == null ? ($editor.summernote('code') || '') : contents).toString();
                            var fixed = window.contestSanitizeSummernoteHtml(raw);
                            if (fixed !== raw) {
                                $container.data('isSanitizingFonts', true);
                                $editor.summernote('code', fixed);
                                $container.data('isSanitizingFonts', false);
                            }
                        }
                        scheduleWaitingPreview();
                        syncAlignFromEditor($container);
                        replaceButtonTagsInEditor($container);
                    }
                }
            }, typeof window.contestSummernoteFontOpts === 'function' ? window.contestSummernoteFontOpts() : {}));
            $container.off('click.waitingEditorMode').on('click.waitingEditorMode', '.waiting-editor-mode-toggle [data-editor-mode]', function(e) {
                e.preventDefault();
                var mode = ($(this).data('editorMode') || 'visual').toString();
                setWaitingEditorCodeview($editor, mode === 'html');
                setWaitingEditorModeToggle($container, mode);
                setTimeout(function() {
                    syncWaitingEditorModeFromCodeview($container);
                }, 0);
            });
            var noteEditable = $editor.next('.note-editor').find('.note-editable');
            $container.data('noteEditable', noteEditable);
            if (noteEditable.length) {
                noteEditable.css('text-align', normalizeAlign($alignInput.val() || alignDefault, alignDefault));
                noteEditable.on('keyup mouseup', function() {
                    syncAlignFromEditor($container);
                    scheduleWaitingPreview();
                });
                noteEditable.on('dragover drop', function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                });
                applyEditorCanvasStyle($container);
            }
            replaceButtonTagsInEditor($container);
        } else {
            $editor.on('input', function() {
                scheduleWaitingPreview();
            });
            $editor.on('dragover drop', function(event) {
                event.preventDefault();
            });
        }
    }

    function updateEmptyState() {
        var empty = list.find('.waiting-container-item').length === 0;
        emptyState.toggle(empty);
    }

    function refreshContainerTitles() {
        list.find('.waiting-container-item').each(function(index) {
            $(this).find('.waiting-container-title').text('Container ' + (index + 1));
        });
        updateMoveButtons();
    }

    function updateMoveButtons() {
        var $items = list.find('.waiting-container-item');
        $items.find('.btn-move-waiting-container').prop('disabled', false).attr('aria-disabled', 'false');
        if ($items.length <= 1) {
            $items.find('.btn-move-waiting-container').prop('disabled', true).attr('aria-disabled', 'true');
            return;
        }
        $items.first().find('.btn-move-waiting-container[data-direction="up"]').prop('disabled', true).attr('aria-disabled', 'true');
        $items.last().find('.btn-move-waiting-container[data-direction="down"]').prop('disabled', true).attr('aria-disabled', 'true');
    }

    function buildContainerId() {
        return 'container_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    }

    function addContainer() {
        if (!template) {
            return;
        }
        var html = template.innerHTML;
        var newId = buildContainerId();
        html = html.replace(/__INDEX__/g, containerIndex)
            .replace(/__ID__/g, newId)
            .replace(/__ALIGN__/g, previewAlignDefault);
        var $item = $(html);
        list.append($item);
        containerIndex += 1;
        updateEmptyState();
        refreshContainerTitles();
        initContainerEditor($item);
        bindColorInputs($item);
        bindOpacityControls($item);
        applyBackgroundMode($item);
        applyContainerType($item);
        scheduleWaitingPreview();
    }

    list.on('click', '.btn-remove-waiting-container', function() {
        var $item = $(this).closest('.waiting-container-item');
        var $editor = $item.find('.waiting-container-editor');
        revokePreviewUrl($item.find('.waiting-bg-file-input'));
        revokePreviewUrl($item.find('.waiting-image-file-input'));
        if ($editor.length && typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
            $editor.summernote('destroy');
        }
        $item.remove();
        updateEmptyState();
        refreshContainerTitles();
        scheduleWaitingPreview();
    });

    list.on('click', '.btn-move-waiting-container', function() {
        var $item = $(this).closest('.waiting-container-item');
        var direction = ($(this).data('direction') || '').toString().toLowerCase();
        if (direction === 'up') {
            var $prev = $item.prev('.waiting-container-item');
            if ($prev.length) {
                $item.insertBefore($prev);
            }
        } else if (direction === 'down') {
            var $next = $item.next('.waiting-container-item');
            if ($next.length) {
                $item.insertAfter($next);
            }
        }
        refreshContainerTitles();
        scheduleWaitingPreview();
    });

    var draggingPreviewIndex = null;

    preview.on('dragstart', '.waiting-container-preview', function(event) {
        var $previewItem = $(this);
        var index = parseInt($previewItem.attr('data-container-index'), 10);
        if (isNaN(index)) {
            return;
        }
        draggingPreviewIndex = index;
        $previewItem.addClass('dragging');
        var dt = event.originalEvent && event.originalEvent.dataTransfer ? event.originalEvent.dataTransfer : null;
        if (dt) {
            dt.effectAllowed = 'move';
            dt.setData('text/plain', String(index));
        }
    });

    preview.on('dragend', '.waiting-container-preview', function() {
        draggingPreviewIndex = null;
        preview.find('.waiting-container-preview').removeClass('drag-over dragging');
    });

    preview.on('dragover', '.waiting-container-preview', function(event) {
        event.preventDefault();
        var dt = event.originalEvent && event.originalEvent.dataTransfer ? event.originalEvent.dataTransfer : null;
        if (dt) {
            dt.dropEffect = 'move';
        }
        preview.find('.waiting-container-preview').not(this).removeClass('drag-over');
        $(this).addClass('drag-over');
    });

    preview.on('dragleave', '.waiting-container-preview', function() {
        $(this).removeClass('drag-over');
    });

    preview.on('drop', '.waiting-container-preview', function(event) {
        event.preventDefault();
        var $targetPreview = $(this);
        var targetIndex = parseInt($targetPreview.attr('data-container-index'), 10);
        var sourceIndex = draggingPreviewIndex;
        if (isNaN(targetIndex)) {
            return;
        }
        if (sourceIndex === null || sourceIndex === undefined) {
            var dt = event.originalEvent && event.originalEvent.dataTransfer ? event.originalEvent.dataTransfer : null;
            if (dt) {
                var raw = parseInt(dt.getData('text/plain'), 10);
                if (!isNaN(raw)) {
                    sourceIndex = raw;
                }
            }
        }
        if (sourceIndex === null || sourceIndex === undefined || sourceIndex === targetIndex) {
            preview.find('.waiting-container-preview').removeClass('drag-over dragging');
            return;
        }
        var $items = list.find('.waiting-container-item');
        var $source = $items.eq(sourceIndex);
        var $target = $items.eq(targetIndex);
        if (!$source.length || !$target.length) {
            return;
        }
        if (sourceIndex < targetIndex) {
            $source.insertAfter($target);
        } else {
            $source.insertBefore($target);
        }
        refreshContainerTitles();
        scheduleWaitingPreview();
    });

    list.on('change', '.waiting-container-bg-mode', function() {
        var $item = $(this).closest('.waiting-container-item');
        applyBackgroundMode($item);
        scheduleWaitingPreview();
    });

    list.on('change', '.waiting-container-type', function() {
        var $item = $(this).closest('.waiting-container-item');
        applyContainerType($item);
        scheduleWaitingPreview();
    });

    list.on('change', '.waiting-container-align-select', function() {
        var $item = $(this).closest('.waiting-container-item');
        updateAlignFromSelect($item);
        scheduleWaitingPreview();
    });

    list.on('input', '.waiting-container-margin', function() {
        scheduleWaitingPreview();
    });

    list.on('input', '.waiting-container-radius-input', function() {
        scheduleWaitingPreview();
    });

    list.on('input', '.waiting-container-image-alt, .waiting-container-image-width', function() {
        scheduleWaitingPreview();
    });

    list.on('change', '.waiting-bg-file-input, .waiting-image-file-input', function() {
        var $input = $(this);
        revokePreviewUrl($input);
        var fileName = $input[0] && $input[0].files && $input[0].files[0] ? $input[0].files[0].name : 'Carica immagine (PNG/JPEG)';
        var $label = $input.next('.custom-file-label');
        if ($label.length) {
            $label.text(fileName);
        }
        scheduleWaitingPreview();
    });

    if (addBtn.length) {
        addBtn.on('click', function() {
            addContainer();
        });
    }

    list.find('.waiting-container-item').each(function() {
        var $item = $(this);
        initContainerEditor($item);
        bindColorInputs($item);
        bindOpacityControls($item);
        applyBackgroundMode($item);
        applyContainerType($item);
    });

    // Keep waiting/thank-you preview aligned with "Bottoni" config changes.
    $(document).on('input change', '#section-buttons input, #section-buttons select, #section-buttons textarea', function() {
        updateWaitingButtonCssVars();
        scheduleWaitingPreview();
    });
    $(document).on('input change', '#section-palette input, #section-palette select', function() {
        updateWaitingButtonCssVars();
        scheduleWaitingPreview();
    });

        updateEmptyState();
        refreshContainerTitles();
        scheduleWaitingPreview();

    }

    window.syncThankyouLoseIwQuizHint = function() {
        var $hint = $('#thankyou-lose-iw-quiz-hint');
        var $quizFailedEditor = $('#thankyou-quiz-failed-editor-block');
        var $estrazioneQuizFailedEditor = $('#thankyou-estrazione-quiz-failed-editor-block');
        var mode = ($('select[name="contest_mode"]').val() || '').toString().toLowerCase().trim();
        var quizOn = $('#config-quiz-enabled-checkbox').is(':checked');
        var reqAll = $('#quiz_require_all_correct').is(':checked');
        var shouldShowIw = mode === 'instantwin' && quizOn && reqAll;
        var shouldShowEstrazione = mode !== 'instantwin' && quizOn && reqAll;
        if ($hint.length) {
            $hint.toggleClass('d-none', !shouldShowIw);
        }
        if ($quizFailedEditor.length) {
            $quizFailedEditor.toggleClass('d-none', !shouldShowIw);
        }
        if ($estrazioneQuizFailedEditor.length) {
            $estrazioneQuizFailedEditor.toggleClass('d-none', !shouldShowEstrazione);
        }
    };

    deferInit(function() {
        $(document).on('change', 'select[name="contest_mode"], #config-quiz-enabled-checkbox, #quiz_require_all_correct', window.syncThankyouLoseIwQuizHint);
        window.syncThankyouLoseIwQuizHint();
    });

    deferInit(function() {
        [
            ['waiting-containers-list', 'waiting-container-template', 'add-waiting-container', 'waiting-page-preview', 'waiting-containers-empty', 'waiting_page_align'],
            ['participation-page-containers-list', 'participation-page-container-template', 'add-participation-page-container', 'participation-page-preview', 'participation-page-containers-empty', 'participation_page_align'],
            ['waiting-ended-containers-list', 'waiting-ended-container-template', 'add-waiting-ended-container', 'waiting-page-ended-preview', 'waiting-ended-containers-empty', 'waiting_page_ended_align'],
            ['form-page-containers-list', 'form-page-container-template', 'add-form-page-container', 'form-page-preview', 'form-page-containers-empty', 'form_page_align'],
            ['help-page-containers-list', 'help-page-container-template', 'add-help-page-container', 'help-page-preview', 'help-page-containers-empty', 'help_page_align'],
            ['thankyou-win-containers-list', 'thankyou-win-container-template', 'add-thankyou-win-container', 'thankyou-win-preview', 'thankyou-win-containers-empty', 'thankyou_instantwin_win_page_align'],
            ['thankyou-lose-containers-list', 'thankyou-lose-container-template', 'add-thankyou-lose-container', 'thankyou-lose-preview', 'thankyou-lose-containers-empty', 'thankyou_instantwin_loss_page_align'],
            ['thankyou-quiz-failed-containers-list', 'thankyou-quiz-failed-container-template', 'add-thankyou-quiz-failed-container', 'thankyou-quiz-failed-preview', 'thankyou-quiz-failed-containers-empty', 'thankyou_instantwin_quiz_failed_page_align'],
            ['thankyou-partecipazione-containers-list', 'thankyou-partecipazione-container-template', 'add-thankyou-partecipazione-container', 'thankyou-partecipazione-preview', 'thankyou-partecipazione-containers-empty', 'thankyou_estrazione_page_align'],
            ['thankyou-estrazione-qfail-containers-list', 'thankyou-estrazione-qfail-container-template', 'add-thankyou-estrazione-qfail-container', 'thankyou-estrazione-qfail-preview', 'thankyou-estrazione-qfail-containers-empty', 'thankyou_estrazione_quiz_failed_page_align'],
            ['quiz-page-containers-list', 'quiz-page-container-template', 'add-quiz-page-container', 'quiz-page-header-preview', 'quiz-page-containers-empty', 'quiz_page_align'],
            ['quiz-choice-containers-list', 'quiz-choice-container-template', 'add-quiz-choice-container', 'quiz-choice-page-preview', 'quiz-choice-containers-empty', 'quiz_choice_page_align']
        ].forEach(function(config) {
            initWaitingEditor({
                list: $('#' + config[0]),
                template: document.getElementById(config[1]),
                addBtn: $('#' + config[2]),
                preview: $('#' + config[3]),
                emptyState: $('#' + config[4]),
                globalAlignInput: $('#' + config[5])
            });
        });
    });

    deferInit(function() {
        function syncParticipationPageMode() {
            var enabled = $('#participation_page_enabled').is(':checked');
            $('#participation-page-editor-wrap').toggleClass('d-none', !enabled);
            $('#participation-page-preview-actions').toggleClass('d-none', !enabled);
            $('.anteprima-participation-page').toggleClass('d-none', !enabled);
        }

        $(document).on('change', '#participation_page_enabled', syncParticipationPageMode);
        syncParticipationPageMode();

        window.syncQrVerifyAnteprima = function () {
            var enabled = $('#qr_access_enabled').is(':checked');
            $('.anteprima-qr-verify-only').toggleClass('d-none', !enabled);
        };
        $(document).on('change', '#qr_access_enabled', window.syncQrVerifyAnteprima);
        window.syncQrVerifyAnteprima();
    });

    $('#config-form').on('submit.pageContainerEditors', function() {
        if (typeof window.syncPageContainerEditorsBeforeSubmit === 'function') {
            window.syncPageContainerEditorsBeforeSubmit(this);
        }
        if (typeof window.syncQuizQuestionBlockBeforeSubmit === 'function') {
            window.syncQuizQuestionBlockBeforeSubmit();
        }
    });

    deferInit(function() {
        var quizQblockPreviewTimer = null;

        function bindQuizQblockColorPicker(swatchId, textId) {
            var $swatch = $(swatchId);
            var $text = $(textId);
            if (!$swatch.length || !$text.length) {
                return;
            }
            $swatch.on('input change', function() {
                $text.val(this.value);
            });
            $text.on('input change', function() {
                var v = (this.value || '').trim();
                if (/^#[0-9A-Fa-f]{6}$/.test(v) || /^#[0-9A-Fa-f]{3}$/.test(v)) {
                    $swatch.val(v.length === 4 ? v.replace(/^#?([0-9A-Fa-f])([0-9A-Fa-f])([0-9A-Fa-f])$/, '#$1$1$2$2$3$3') : v);
                }
            });
        }

        window.syncQuizQuestionBlockBeforeSubmit = function() {
            var $ed = $('#quiz_qblock_html');
            if ($ed.length && typeof $.fn.summernote === 'function' && $ed.data('summernote')) {
                var html = $ed.summernote('code') || '';
                if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                    html = window.contestSanitizeSummernoteHtml(html);
                }
                $ed.val(html);
            }
            [
                ['#quiz_qblock_bg_color_swatch', '#quiz_qblock_bg_color'],
                ['#quiz_answer_tick_color_swatch', '#quiz_answer_tick_color'],
                ['#quiz_submit_btn_color_swatch', '#quiz_submit_button_bg_color']
            ].forEach(function(pair) {
                var $s = $(pair[0]);
                var $t = $(pair[1]);
                if ($s.length && $t.length) {
                    $t.val($s.val());
                }
            });
        };

        function collectQuizPagePreviewFormData() {
            window.syncQuizQuestionBlockBeforeSubmit();
            var fd = new FormData();
            var $panel = $('#quiz-question-block-panel');
            if (!$panel.length) {
                return fd;
            }
            $panel.find('input, select, textarea').each(function() {
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
            var $ed = $('#quiz_qblock_html');
            if ($ed.length) {
                var qHtml = ($ed.data('summernote') ? $ed.summernote('code') : $ed.val()) || '';
                if (typeof window.contestSanitizeSummernoteHtml === 'function') {
                    qHtml = window.contestSanitizeSummernoteHtml(qHtml);
                }
                fd.set('quiz_qblock_html', qHtml);
            }
            return fd;
        }

        function refreshQuizPagePreviewIframe() {
            var $iframe = $('#quiz-page-preview-iframe');
            if (!$iframe.length) {
                return;
            }
            fetch('ajax/set_quiz_preview_draft.php', { method: 'POST', body: collectQuizPagePreviewFormData() })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.ok) {
                        $iframe.attr('src', 'ajax/preview_quiz.php?wizard=1&live=1&_=' + Date.now());
                    }
                })
                .catch(function() { /* ignore */ });
        }

        function scheduleQuizPagePreview() {
            clearTimeout(quizQblockPreviewTimer);
            quizQblockPreviewTimer = setTimeout(refreshQuizPagePreviewIframe, 450);
        }

        var $quizQblockPanel = $('#quiz-question-block-panel');
        if ($quizQblockPanel.length) {
            bindQuizQblockColorPicker('#quiz_qblock_bg_color_swatch', '#quiz_qblock_bg_color');
            bindQuizQblockColorPicker('#quiz_answer_tick_color_swatch', '#quiz_answer_tick_color');
            bindQuizQblockColorPicker('#quiz_submit_btn_color_swatch', '#quiz_submit_button_bg_color');

            function toggleQuizQblockBgRows() {
                var mode = $quizQblockPanel.find('.quiz-qblock-bg-mode').val();
                $quizQblockPanel.find('.quiz-qblock-bg-color-row').toggle(mode === 'color');
                $quizQblockPanel.find('.quiz-qblock-bg-image-row').toggle(mode === 'image');
            }
            $quizQblockPanel.on('change', '.quiz-qblock-bg-mode', toggleQuizQblockBgRows);
            toggleQuizQblockBgRows();

            $quizQblockPanel.on('change', 'input.custom-file-input', function() {
                var f = this.files && this.files[0];
                $(this).next('.custom-file-label').text(f ? f.name : 'Scegli immagine...');
            });

            var $quizQblockEd = $('#quiz_qblock_html');
            if ($quizQblockEd.length && typeof $.fn.summernote === 'function' && !$quizQblockEd.data('summernote')) {
                $quizQblockEd.summernote($.extend({
                    height: 160,
                    lang: 'it-IT',
                    fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
                    toolbar: [
                        ['font', ['fontname', 'bold', 'italic', 'underline', 'clear', 'fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph', 'height']],
                        ['insert', ['link']],
                        ['view', ['codeview']]
                    ],
                    disableDragAndDrop: true,
                    callbacks: {
                        onInit: function() {
                            if (typeof window.contestSummernoteSanitizeEditor === 'function') {
                                window.contestSummernoteSanitizeEditor($quizQblockEd);
                            }
                        },
                        onChange: function() {
                            if (typeof window.contestSummernoteSanitizeEditor === 'function') {
                                window.contestSummernoteSanitizeEditor($quizQblockEd);
                            }
                            scheduleQuizPagePreview();
                        }
                    }
                }, typeof window.contestSummernoteFontOpts === 'function' ? window.contestSummernoteFontOpts() : {}));
            }

            function syncQuizQblockEditorCanvas() {
                if (typeof window.applyNoteEditableCanvas !== 'function') {
                    return;
                }
                var $note = $quizQblockEd.next('.note-editor').find('.note-editable');
                if (!$note.length) {
                    return;
                }
                var mode = ($quizQblockPanel.find('.quiz-qblock-bg-mode').val() || 'color').toString().toLowerCase().trim();
                var opacity = parseInt($quizQblockPanel.find('input[name="quiz_qblock_bg_opacity"]').val(), 10);
                if (isNaN(opacity)) {
                    opacity = 100;
                }
                var style = {
                    backgroundColor: '',
                    backgroundImage: '',
                    backgroundSize: '',
                    backgroundPosition: '',
                    isTransparent: mode === 'transparent' || (mode === 'color' && opacity <= 0),
                    borderRadius: '8px',
                    padding: '12px'
                };
                if (mode === 'color' && !style.isTransparent) {
                    var color = ($('#quiz_qblock_bg_color').val() || '').trim();
                    var rgba = typeof window.noteEditableHexToRgba === 'function'
                        ? window.noteEditableHexToRgba(color, opacity)
                        : '';
                    if (rgba) {
                        style.backgroundColor = rgba;
                    }
                } else if (mode === 'image') {
                    var imageUrl = ($('#quiz_qblock_bg_image').val() || '').trim();
                    var $file = $quizQblockPanel.find('input[name="upload_quiz_qblock_bg"]');
                    if ($file.length && $file[0].files && $file[0].files[0]) {
                        imageUrl = URL.createObjectURL($file[0].files[0]);
                    }
                    if (imageUrl) {
                        style.backgroundImage = "url('" + imageUrl.replace(/'/g, "\\'") + "')";
                        style.backgroundSize = 'cover';
                        style.backgroundPosition = 'center';
                    }
                }
                window.applyNoteEditableCanvas($note, style);
            }

            syncQuizQblockEditorCanvas();
            $quizQblockPanel.on('change input', 'input, select, textarea', function() {
                syncQuizQblockEditorCanvas();
                scheduleQuizPagePreview();
            });
        }
    });

    deferInit(function() {
        function syncHelpFormMode() {
            var formEnabled = $('#help_form_enabled').is(':checked');
            $('.help-form-enabled-only').toggleClass('d-none', !formEnabled);
        }

        $(document).on('change', '#help_form_enabled', syncHelpFormMode);
        syncHelpFormMode();
    });

    var emailPreviewUrl = <?php echo json_encode('res/inc/email_preview.php'); ?>;
    var emailPreviewDefaults = {
        name: <?php echo json_encode($email_preview_name); ?>,
        prize: <?php echo json_encode($email_preview_prize_amount); ?>,
        voucher: <?php echo json_encode($email_preview_voucher); ?>,
        voucherEanSample: '9750080022003'
    };

    function resolveEmailPreviewVoucher(displayMode) {
        var mode = (displayMode || '').toString().trim();
        if (mode === 'barcode' || mode === 'both') {
            return emailPreviewDefaults.voucherEanSample;
        }
        return emailPreviewDefaults.voucher;
    }

    function setEmailPreviewHtml(iframe, html) {
        if (!iframe) {
            return;
        }
        if ('srcdoc' in iframe) {
            iframe.srcdoc = html;
            return;
        }
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        if (!doc) {
            return;
        }
        doc.open();
        doc.write(html);
        doc.close();
    }

    function getEmailEditorHtml($editor) {
        if (!$editor || !$editor.length) {
            return '';
        }
        var html = '';
        if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
            html = $editor.summernote('code') || '';
        } else {
            html = $editor.val() || '';
        }
        if (typeof window.contestSanitizeSummernoteHtml === 'function') {
            html = window.contestSanitizeSummernoteHtml(html);
        }
        return html;
    }

    function updateEmailPreview($card) {
        if (!emailPreviewUrl || !$card || !$card.length) {
            return;
        }
        var type = ($card.data('emailType') || '').toString();
        if (type === '') {
            return;
        }
        var $editor = $card.find('textarea.email-template-editor').first();
        var body = getEmailEditorHtml($editor);
        var $iframe = $card.find('.email-template-main-preview-iframe').first();
        if (!$iframe.length) {
            $iframe = $card.find('.email-preview-iframe').not('.prize-custom-email-preview-iframe').first();
        }
        if (!$iframe.length) {
            return;
        }
        // Usa il valore corrente del banner (anche se non ancora salvato)
        var mailBanner = ($('input[name="mail_banner"]').val() || '').toString();
        var voucherDisplayMode = ($card.find('select[name="email_voucher_code_display"]').val() || '').toString().trim();
        var data = {
            type: type,
            name: emailPreviewDefaults.name,
            prize: emailPreviewDefaults.prize,
            voucher: resolveEmailPreviewVoucher(voucherDisplayMode),
            body: body,
            mail_banner: mailBanner
        };
        if (type === 'instantwin_prize') {
            if (voucherDisplayMode) {
                data.voucher_display_mode = voucherDisplayMode;
            }
            var vBorder = ($card.find('input[name="email_voucher_block_border_color"]').val() || '').toString().trim();
            var vBg = ($card.find('input[name="email_voucher_block_bg_color"]').val() || '').toString().trim();
            var vText = ($card.find('input[name="email_voucher_block_text_color"]').val() || '').toString().trim();
            if (vBorder) data.voucher_block_border_color = vBorder;
            if (vBg) data.voucher_block_bg_color = vBg;
            if (vText) data.voucher_block_text_color = vText;
            var prizeImagePath = '';
            $('input[name^="prize_email_template["][name$="[template_path]"]').each(function() {
                var val = ($(this).val() || '').toString().trim();
                if (val !== '' && prizeImagePath === '') {
                    prizeImagePath = val;
                }
            });
            if (prizeImagePath !== '') {
                data.prize_image_template_path = prizeImagePath;
            }
            var $overlayRow = $('.prize-email-template-row').filter(function() {
                return ($(this).find('input[name$="[template_path]"]').val() || '').toString().trim() !== '';
            }).first();
            if ($overlayRow.length) {
                data.overlay_enabled = $overlayRow.find('input[name$="[overlay_enabled]"]').is(':checked') ? '1' : '0';
                data.overlay_x = ($overlayRow.find('input[name$="[overlay_x]"]').val() || '').toString();
                data.overlay_y = ($overlayRow.find('input[name$="[overlay_y]"]').val() || '').toString();
                data.overlay_font_size = ($overlayRow.find('input[name$="[overlay_font_size]"]').val() || '').toString();
                data.overlay_color = ($overlayRow.find('input[name$="[overlay_color]"]').val() || '').toString();
            }
        }
        $.ajax({
            url: emailPreviewUrl,
            method: 'POST',
            data: data
        }).done(function(html) {
            setEmailPreviewHtml($iframe[0], html);
        }).fail(function() {
            setEmailPreviewHtml($iframe[0], '<p style="font-family:Arial,sans-serif;padding:16px;color:#b02a37;">Preview non disponibile.</p>');
        });
    }

    function scheduleEmailPreview($card) {
        if (!$card || !$card.length) {
            return;
        }
        var timer = $card.data('emailPreviewTimer');
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(function() {
            updateEmailPreview($card);
        }, 400);
        $card.data('emailPreviewTimer', timer);
    }

    function initEmailTemplateEditors() {
        var $editors = $('.email-template-editor');
        if (!$editors.length || typeof $.fn.summernote !== 'function') {
            return;
        }
        $editors.each(function() {
            var $editor = $(this);
            if ($editor.data('summernote')) {
                return;
            }
            $editor.summernote($.extend({
                height: 220,
                lang: 'it-IT',
                fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
                toolbar: [
                    ['font', ['fontname', 'bold', 'italic', 'underline', 'clear', 'fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph', 'height']],
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
                        if (typeof window.contestSummernoteSanitizeEditor === 'function') {
                            window.contestSummernoteSanitizeEditor($editor);
                        }
                        scheduleEmailPreview($editor.closest('.email-template-card'));
                    }
                }
            }, typeof window.contestSummernoteFontOpts === 'function' ? window.contestSummernoteFontOpts() : {}));
        });
    }

    var SMS_TEMPLATE_MAX_SEPTETS = <?php echo (int) GST_SMS_TEMPLATE_MAX_SEPTETS; ?>;
    var SMS_GSM7_CHARS = '@£$¥èéùìòÇØøÅåΔ_ΦΓΛΩΠΨΣΘΞ ÆæßÉ !"#¤%&\'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà^{}\\[~]|€';
    var SMS_GSM7_EXTENDED_CHARS = '^{}\\[~]|€';
    var SMS_BULLET_CHARS = /[\u2022\u2023\u25E6\u2043\u2219\u00B7\u25AA\u25CF\u25CB\u2024\u204C\u204D\u221A\u2713\u2714\u2717\u2718]/g;

    function smsGsm7SeptetCount(text) {
        text = (text || '').toString();
        if (!text) {
            return 0;
        }
        var count = 0;
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            count += SMS_GSM7_EXTENDED_CHARS.indexOf(ch) !== -1 ? 2 : 1;
        }
        return count;
    }

    function smsGsm7TruncateToSeptets(text, maxSeptets) {
        text = (text || '').toString();
        if (!text || maxSeptets <= 0) {
            return '';
        }
        var out = '';
        var septets = 0;
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            var cost = SMS_GSM7_EXTENDED_CHARS.indexOf(ch) !== -1 ? 2 : 1;
            if (septets + cost > maxSeptets) {
                break;
            }
            out += ch;
            septets += cost;
        }
        return out;
    }

    function smsSanitizeTemplateText(text, enforceMaxSeptets) {
        text = (text || '').toString();
        text = text.replace(/[\u2018\u2019\u201A\u201B\u2032\u2035]/g, "'");
        text = text.replace(/[\u201C\u201D\u201E\u201F\u2033\u2036]/g, '"');
        text = text.replace(SMS_BULLET_CHARS, '-');
        text = text.replace(/\s+/g, ' ').trim();
        var out = '';
        for (var i = 0; i < text.length; i++) {
            var ch = text.charAt(i);
            if (SMS_GSM7_CHARS.indexOf(ch) !== -1) {
                out += ch;
            }
        }
        if (enforceMaxSeptets) {
            out = smsGsm7TruncateToSeptets(out, SMS_TEMPLATE_MAX_SEPTETS);
        }
        return out;
    }

    function syncSmsTemplateValidity($textarea) {
        if (!$textarea || !$textarea.length || !$textarea[0]) {
            return false;
        }
        var text = ($textarea.val() || '').toString();
        var septets = smsGsm7SeptetCount(text);
        var tooLong = septets > SMS_TEMPLATE_MAX_SEPTETS;
        $textarea.toggleClass('is-invalid', tooLong);
        if (tooLong) {
            $textarea[0].setCustomValidity('Massimo ' + SMS_TEMPLATE_MAX_SEPTETS + ' unità GSM per un SMS (1 credito).');
        } else {
            $textarea[0].setCustomValidity('');
        }
        return !tooLong;
    }

    function updateSmsTemplateCounter($textarea) {
        if (!$textarea || !$textarea.length) {
            return;
        }
        var text = ($textarea.val() || '').toString();
        var fieldId = ($textarea.attr('id') || '').toString();
        var $counter = fieldId ? $('#' + fieldId + '_counter') : $();
        if (!$counter.length) {
            return;
        }
        var septets = smsGsm7SeptetCount(text);
        var tooLong = septets > SMS_TEMPLATE_MAX_SEPTETS;
        $counter.text(septets + '/' + SMS_TEMPLATE_MAX_SEPTETS + ' GSM · 1 credito SMS');
        $counter.removeClass('is-warning is-danger');
        if (tooLong) {
            $counter.addClass('is-danger');
        }
    }

    function applySmsTemplateSanitize($textarea, enforceMaxSeptets) {
        if (!$textarea || !$textarea.length) {
            return;
        }
        var current = ($textarea.val() || '').toString();
        var sanitized = smsSanitizeTemplateText(current, !!enforceMaxSeptets);
        if (sanitized !== current) {
            var pos = $textarea[0].selectionStart;
            $textarea.val(sanitized);
            if (typeof pos === 'number' && $textarea[0].setSelectionRange) {
                var nextPos = Math.max(0, Math.min(pos - (current.length - sanitized.length), sanitized.length));
                $textarea[0].setSelectionRange(nextPos, nextPos);
            }
        }
        syncSmsTemplateValidity($textarea);
        updateSmsTemplateCounter($textarea);
    }

    function initSmsTemplateCounters() {
        $('.sms-template-input').each(function() {
            applySmsTemplateSanitize($(this), false);
        });
    }

    $(document).on('keydown', '.sms-template-input', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    $(document).on('input paste', '.sms-template-input', function() {
        var $textarea = $(this);
        setTimeout(function() {
            applySmsTemplateSanitize($textarea, true);
        }, 0);
    });

    deferInit(function() {
        initEmailTemplateEditors();
        initPrizeCustomEmailEditors();
        initSmsTemplateCounters();
    });

    function getPrizeCustomEmailEditorHtml($editor) {
        if (!$editor || !$editor.length) {
            return '';
        }
        var html = '';
        if (typeof $.fn.summernote === 'function' && $editor.data('summernote')) {
            html = $editor.summernote('code') || '';
        } else {
            html = $editor.val() || '';
        }
        if (typeof window.contestSanitizeSummernoteHtml === 'function') {
            html = window.contestSanitizeSummernoteHtml(html);
        }
        return html;
    }

    function updatePrizeCustomEmailPreview($row) {
        if (!$row || !$row.length || !emailPreviewUrl) {
            return;
        }
        var $panel = $row.find('.prize-custom-email-panel').first();
        if (!$panel.length || $panel.hasClass('d-none')) {
            return;
        }
        var $iframe = $row.find('.prize-custom-email-preview-iframe').first();
        if (!$iframe.length) {
            return;
        }
        var $editor = $row.find('.prize-custom-email-editor').first();
        var body = getPrizeCustomEmailEditorHtml($editor);
        var prizeKey = ($editor.attr('data-pei-key') || '').toString();
        var templatePath = ($row.find('input[name$="[template_path]"]').val() || '').toString().trim();
        var mailBanner = ($('input[name="mail_banner"]').val() || '').toString();
        var perPrizeVoucherDisplay = ($row.find('select.prize-voucher-display-select').val() || '').toString().trim();
        var globalVoucherDisplay = ($('select[name="email_voucher_code_display"]').val() || '').toString().trim();
        var effectiveVoucherDisplay = perPrizeVoucherDisplay && perPrizeVoucherDisplay !== 'inherit'
            ? perPrizeVoucherDisplay
            : globalVoucherDisplay;
        var data = {
            type: 'instantwin_prize',
            name: emailPreviewDefaults.name,
            prize: emailPreviewDefaults.prize,
            voucher: resolveEmailPreviewVoucher(effectiveVoucherDisplay),
            body: body,
            mail_banner: mailBanner,
            prize_email_key: prizeKey,
            prize_image_template_path: templatePath
        };
        if (perPrizeVoucherDisplay) {
            data.voucher_code_display = perPrizeVoucherDisplay;
        }
        if (globalVoucherDisplay) {
            data.voucher_display_mode = globalVoucherDisplay;
        }
        var vBorder = ($('input[name="email_voucher_block_border_color"]').val() || '').toString().trim();
        var vBg = ($('input[name="email_voucher_block_bg_color"]').val() || '').toString().trim();
        var vText = ($('input[name="email_voucher_block_text_color"]').val() || '').toString().trim();
        if (vBorder) data.voucher_block_border_color = vBorder;
        if (vBg) data.voucher_block_bg_color = vBg;
        if (vText) data.voucher_block_text_color = vText;
        data.overlay_enabled = $row.find('input[name$="[overlay_enabled]"]').is(':checked') ? '1' : '0';
        data.overlay_x = ($row.find('input[name$="[overlay_x]"]').val() || '').toString();
        data.overlay_y = ($row.find('input[name$="[overlay_y]"]').val() || '').toString();
        data.overlay_font_size = ($row.find('input[name$="[overlay_font_size]"]').val() || '').toString();
        data.overlay_color = ($row.find('input[name$="[overlay_color]"]').val() || '').toString();
        $.ajax({
            url: emailPreviewUrl,
            method: 'POST',
            data: data
        }).done(function(html) {
            setEmailPreviewHtml($iframe[0], html);
        }).fail(function() {
            setEmailPreviewHtml($iframe[0], '<p style="font-family:Arial,sans-serif;padding:16px;color:#b02a37;">Preview non disponibile.</p>');
        });
    }

    function schedulePrizeCustomEmailPreview($row) {
        if (!$row || !$row.length) {
            return;
        }
        var timer = $row.data('prizeCustomEmailPreviewTimer');
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(function() {
            updatePrizeCustomEmailPreview($row);
        }, 400);
        $row.data('prizeCustomEmailPreviewTimer', timer);
    }

    function initPrizeCustomEmailEditors() {
        var $editors = $('.prize-custom-email-editor');
        if (!$editors.length || typeof $.fn.summernote !== 'function') {
            $('.prize-email-template-row').each(function() {
                var $row = $(this);
                if ($row.find('.prize-custom-email-panel:visible').length) {
                    updatePrizeCustomEmailPreview($row);
                }
            });
            return;
        }
        $editors.each(function() {
            var $editor = $(this);
            if ($editor.data('summernote')) {
                return;
            }
            $editor.summernote($.extend({
                height: 220,
                lang: 'it-IT',
                fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32', '36'],
                toolbar: [
                    ['font', ['fontname', 'bold', 'italic', 'underline', 'clear', 'fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph', 'height']],
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
                        if (typeof window.contestSummernoteSanitizeEditor === 'function') {
                            window.contestSummernoteSanitizeEditor($editor);
                        }
                        schedulePrizeCustomEmailPreview($editor.closest('.prize-email-template-row'));
                    }
                }
            }, typeof window.contestSummernoteFontOpts === 'function' ? window.contestSummernoteFontOpts() : {}));
        });
        $('.prize-email-template-row').each(function() {
            var $row = $(this);
            if (!$row.find('.prize-custom-email-panel').hasClass('d-none')) {
                updatePrizeCustomEmailPreview($row);
            }
        });
    }

    $(document).on('change', '.prize-custom-body-toggle', function() {
        var $row = $(this).closest('.prize-email-template-row');
        var $panel = $row.find('.prize-custom-email-panel').first();
        if ($(this).is(':checked')) {
            $panel.removeClass('d-none');
            initPrizeCustomEmailEditors();
            schedulePrizeCustomEmailPreview($row);
        } else {
            $panel.addClass('d-none');
        }
    });

    $(document).on('input change', '.prize-custom-email-editor', function() {
        schedulePrizeCustomEmailPreview($(this).closest('.prize-email-template-row'));
    });

    $(document).on('shown.bs.collapse', '#prize-email-templates-accordion .collapse', function() {
        var $row = $(this).closest('.prize-email-template-row');
        if ($row.find('.prize-custom-email-panel:visible').length) {
            initPrizeCustomEmailEditors();
            schedulePrizeCustomEmailPreview($row);
        }
    });

    $(document).on('input change', '.email-template-editor', function() {
        scheduleEmailPreview($(this).closest('.email-template-card'));
    });
    $(document).on('input change', 'select[name="email_voucher_code_display"], select.prize-voucher-display-select, input[name="email_voucher_block_border_color"], input[name="email_voucher_block_bg_color"], input[name="email_voucher_block_text_color"], .color-input[data-target^="email_voucher_block"]', function() {
        var $card = $('.email-template-card[data-email-type="instantwin_prize"]');
        if ($card.length) {
            scheduleEmailPreview($card.first());
        }
        if ($(this).is('select.prize-voucher-display-select')) {
            schedulePrizeCustomEmailPreview($(this).closest('.prize-email-template-row'));
        }
    });
    function refreshPrizeOverlayPreview($row) {
        if (!$row || !$row.length) {
            return;
        }
        var templatePath = ($row.find('input[name$="[template_path]"]').val() || '').toString().trim();
        var $wrap = $row.find('.prize-template-preview-wrap').first();
        if (templatePath === '' || !$wrap.length) {
            return;
        }
        var enabled = $row.find('input[name$="[overlay_enabled]"]').is(':checked');
        var params = {
            template: templatePath,
            enabled: enabled ? '1' : '0',
            x: ($row.find('input[name$="[overlay_x]"]').val() || '200').toString(),
            y: ($row.find('input[name$="[overlay_y]"]').val() || '350').toString(),
            font_size: ($row.find('input[name$="[overlay_font_size]"]').val() || '24').toString(),
            color: ($row.find('input[name$="[overlay_color]"]').val() || '#000000').toString(),
            code: 'ABC123',
            _: Date.now()
        };
        var url = enabled
            ? ('res/inc/prize_overlay_preview.php?' + $.param(params))
            : ('../../' + templatePath.replace(/^\/+/, ''));
        var $img = $wrap.find('.prize-template-preview-img').first();
        if ($img.length) {
            $img.attr('src', url);
        } else {
            $wrap.html('<img src="' + url.replace(/"/g, '&quot;') + '" alt="" class="img-thumbnail prize-template-preview-img" style="max-height:120px;max-width:100%;">');
        }
    }

    $(document).on('input change', '.prize-overlay-fields input, input[name$="[overlay_enabled]"]', function() {
        var $row = $(this).closest('.prize-email-template-row');
        refreshPrizeOverlayPreview($row);
        schedulePrizeCustomEmailPreview($row);
        var $card = $('.email-template-card[data-email-type="instantwin_prize"]');
        if ($card.length) {
            scheduleEmailPreview($card.first());
        }
    });

    $(document).on('change', 'input[id^="upload_prize_email_template_"]', function() {
        var $input = $(this);
        var $row = $input.closest('.prize-email-template-row');
        $row.find('.prize-template-remove-flag').val('0');
        var file = $input[0] && $input[0].files ? $input[0].files[0] : null;
        var $row = $input.closest('.prize-email-template-row');
        var $wrap = $row.find('.prize-template-preview-wrap').first();
        if (!$wrap.length) {
            return;
        }
        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function(ev) {
            var src = ev.target && ev.target.result ? ev.target.result : '';
            if (src === '') {
                return;
            }
            $wrap.html('<img src="' + src.replace(/"/g, '&quot;') + '" alt="" class="img-thumbnail prize-template-preview-img" style="max-height:120px;max-width:100%;">'
                + '<div class="small text-muted mt-1">Anteprima locale — salva per applicare all\'email.</div>');
        };
        reader.readAsDataURL(file);
        var $card = $('.email-template-card[data-email-type="instantwin_prize"]');
        if ($card.length) {
            scheduleEmailPreview($card.first());
        }
    });

    deferInit(function() {
        $('.email-template-card').each(function() {
            updateEmailPreview($(this));
        });
    });

    const $emailModeCards = $('.email-template-card[data-email-modes]');
    const $contestModeSelect = $('select[name="contest_mode"]');
    const initialEmailMode = <?php echo json_encode($contestMode); ?>;
    const $autoPrizeWrapper = $('#instantwin_prize_flow_radios');
    const $autoPrizeFlowPanelIw = $('#instantwin_prize_flow_panel');
    const $autoPrizeHidden = $('#instantwin_auto_prize_with_receipt_hidden');
    const $prizeFlowRadios = $('.instantwin-prize-flow-radio');
    const $prizeFlowWarning = $('#instantwin_prize_flow_warning');
    const $prizeFlowStatusBanner = $('#instantwin_prize_flow_status_banner');
    const $emailFlowAutoNote = $('#email_flow_auto_note');
    const $emailBccEnabled = $('#email_bcc_instantwin_prize_enabled');
    const $emailBccDetailsPanel = $('#email_bcc_details_panel');
    const $scontrinoCheckbox = $('#field_scontrino');
    const $receiptTypesPanel = $('#receipt_types_panel');
    const $maxUploadPanel = $('#max_upload_panel');
    const $receiptHelpPanel = $('#receipt_help_panel');
    const $minSpendPanel = $('#min_spend_panel');
    const $importoCheckbox = $('#field_importo');
    const $fidelityConfigPanel = $('#fidelity_config_panel');
    const $fidelityCheckbox = $('#field_fidelity');
    const $logoDisplayModeSelect = $('#logo_display_mode');
    const $logoDesktopSizeSelect = $('#logo_desktop_size');
    const $logoDesktopCustomPanel = $('#logo_desktop_custom_panel');
    const $brandLayoutPageSelector = $('#brand_layout_page_selector');
    let brandLayoutPreviewDevice = 'mobile';
    let brandLayoutPreviewTimer = null;
    const brandLayoutPresetValues = {
        attached: { mobile: 0, tablet: 0, desktop: 0 },
        overlap_light: { mobile: -24, tablet: -40, desktop: -56 },
        overlap_strong: { mobile: -120, tablet: -80, desktop: -120 },
        separated: { mobile: 16, tablet: 24, desktop: 32 }
    };

    const clampBrandLayoutOverlap = function(value, device) {
        const mins = { mobile: -120, tablet: -80, desktop: -120 };
        const min = mins[device] || -120;
        const num = parseInt(value, 10);
        const safe = isNaN(num) ? 0 : num;
        return Math.max(min, Math.min(120, safe));
    };

    const getActiveBrandLayoutPage = function() {
        return ($brandLayoutPageSelector.val() || 'registration').toString();
    };

    const getBrandLayoutPagePanel = function(pageKey) {
        return $('.brand-layout-page-panel[data-brand-page="' + pageKey + '"]').first();
    };

    const getBrandLayoutOverlapForPageDevice = function(pageKey, device) {
        const $panel = getBrandLayoutPagePanel(pageKey);
        if (!$panel.length) {
            return 0;
        }
        const customize = $panel.find('.brand-page-layout-customize').is(':checked');
        const mobile = clampBrandLayoutOverlap($panel.find('.brand-page-layout-slider[data-device="mobile"]').val(), 'mobile');
        if (!customize) {
            return clampBrandLayoutOverlap(mobile, device);
        }
        const $slider = $panel.find('.brand-page-layout-slider[data-device="' + device + '"]');
        if (!$slider.length) {
            return mobile;
        }
        return clampBrandLayoutOverlap($slider.val(), device);
    };

    const updateBrandLayoutOverlapLabel = function() {
        const $wrap = $('#brand-layout-preview-wrap');
        if (!$wrap.length) {
            return;
        }
        const mode = ($logoDisplayModeSelect.val() || 'logo').toString();
        if (mode === 'none') {
            $wrap.addClass('d-none');
            return;
        }
        $wrap.removeClass('d-none');
        const pageKey = getActiveBrandLayoutPage();
        const pageLabel = getBrandLayoutPagePanel(pageKey).data('brandPageLabel') || pageKey;
        const overlap = getBrandLayoutOverlapForPageDevice(pageKey, brandLayoutPreviewDevice);
        const overlapDesc = overlap < 0
            ? 'container in sovrapposizione'
            : (overlap > 0 ? 'spazio tra visual e container' : 'container attaccato al visual');
        $('#brand-layout-preview-overlap-label').text(
            pageLabel + ' - ' + brandLayoutPreviewDevice + ': ' + overlap + 'px (' + overlapDesc + ')'
        );
    };

    const updateBrandLayoutPreviewDeviceFrame = function() {
        const $iframeWrap = $('#brand-layout-preview-iframe-wrap');
        if ($iframeWrap.length) {
            $iframeWrap.removeClass('is-mobile is-tablet is-desktop').addClass('is-' + brandLayoutPreviewDevice);
        }
    };

    const collectBrandLayoutPreviewFormData = function() {
        const fd = new FormData();
        const $sections = $('#section-branding, #section-sfondo, #section-faq');
        if (!$sections.length) {
            return fd;
        }
        $sections.find('input, select, textarea').each(function() {
            const el = this;
            const name = el.getAttribute('name');
            if (!name || el.type === 'file') {
                return;
            }
            // Solo campi branding/sfondo + tipografia titolo FAQ rilevanti per l'anteprima layout
            const keepHelpField = name === 'help_accordion_title_font_size'
                || name === 'help_accordion_title_margin_top'
                || name === 'help_page_font_family'
                || name === 'help_page_font_size';
            const isHelpSection = $(el).closest('#section-faq').length > 0;
            if (isHelpSection && !keepHelpField) {
                return;
            }
            if (el.type === 'checkbox') {
                if (el.checked) {
                    fd.append(name, el.value || '1');
                }
                return;
            }
            if (el.type === 'radio' && !el.checked) {
                return;
            }
            fd.append(name, el.value);
        });
        fd.set('preview_page', getActiveBrandLayoutPage());
        fd.set('preview_device', brandLayoutPreviewDevice);
        return fd;
    };

    const refreshBrandLayoutPreviewIframe = function() {
        const $iframe = $('#brand-layout-preview-iframe');
        if (!$iframe.length) {
            updateBrandLayoutOverlapLabel();
            return;
        }
        updateBrandLayoutOverlapLabel();
        updateBrandLayoutPreviewDeviceFrame();
        const pageKey = getActiveBrandLayoutPage();
        fetch('ajax/set_brand_layout_preview_draft.php', { method: 'POST', body: collectBrandLayoutPreviewFormData() })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.ok) {
                    $iframe.attr(
                        'src',
                        'ajax/preview_brand_layout.php?live=1&page=' + encodeURIComponent(pageKey)
                            + '&device=' + encodeURIComponent(brandLayoutPreviewDevice)
                            + '&_=' + Date.now()
                    );
                }
            })
            .catch(function() { /* ignore */ });
    };

    const scheduleBrandLayoutPreview = function() {
        clearTimeout(brandLayoutPreviewTimer);
        brandLayoutPreviewTimer = window.setTimeout(refreshBrandLayoutPreviewIframe, 450);
    };

    const updateBrandLayoutPreview = function() {
        updateBrandLayoutOverlapLabel();
        scheduleBrandLayoutPreview();
    };
    window.updateBrandLayoutPreview = updateBrandLayoutPreview;

    const updateBrandLayoutValueLabels = function() {
        $('.brand-page-layout-slider, .logo-top-spacing-slider, .brand-header-logo-gap-slider, .brand-header-top-spacing-slider').each(function() {
            const id = $(this).attr('id');
            $('.brand-layout-value[data-for="' + id + '"]').text($(this).val() + 'px');
        });
    };

    const updateBrandLayoutPageVisibility = function() {
        const pageKey = getActiveBrandLayoutPage();
        $('.brand-layout-page-panel').addClass('d-none').removeClass('is-active');
        const $panel = getBrandLayoutPagePanel(pageKey);
        $panel.removeClass('d-none').addClass('is-active');
        const customize = $panel.find('.brand-page-layout-customize').is(':checked');
        $panel.find('.brand-page-layout-device-field').toggleClass('d-none', !customize);
    };

    const applyBrandLayoutPresetToPage = function(pageKey, preset) {
        const values = brandLayoutPresetValues[preset];
        if (!values) {
            return;
        }
        const $panel = getBrandLayoutPagePanel(pageKey);
        $panel.find('.brand-page-layout-slider[data-device="mobile"]').val(values.mobile);
        $panel.find('.brand-page-layout-slider[data-device="tablet"]').val(values.tablet);
        $panel.find('.brand-page-layout-slider[data-device="desktop"]').val(values.desktop);
        updateBrandLayoutValueLabels();
    };

    const updateBrandDisplayOptions = function() {
        if (!$logoDisplayModeSelect.length) {
            return;
        }
        const mode = ($logoDisplayModeSelect.val() || 'logo').toString();
        const showLogo = mode === 'logo' || mode === 'both';
        const showHeader = mode === 'header' || mode === 'both';
        const showLayoutPanels = mode !== 'none';
        $('.brand-logo-options').toggleClass('d-none', !showLogo);
        $('.brand-header-options').toggleClass('d-none', !showHeader);
        $('.brand-logo-only-spacing').toggleClass('d-none', !(showLogo && !showHeader));
        $('.brand-header-top-spacing-panel').toggleClass('d-none', !showHeader);
        $('.brand-header-logo-gap-panel').toggleClass('d-none', !(showLogo && showHeader));
        $('.brand-layout-overlap-panel').toggleClass('d-none', !showLayoutPanels);
        if (!showLayoutPanels) {
            const $collapseBody = $('#brand-layout-collapse-body');
            $collapseBody.addClass('d-none');
            $('.brand-layout-collapse-toggle').attr('aria-expanded', 'false');
            window.sessionStorage.setItem('brand_layout_collapse_open', '0');
        }
        updateLogoDesktopCustomPanel();
        updateBrandLayoutPreview();
    };

    const updateLogoDesktopCustomPanel = function() {
        if (!$logoDesktopSizeSelect.length || !$logoDesktopCustomPanel.length) {
            return;
        }
        const isCustom = ($logoDesktopSizeSelect.val() || '') === 'custom';
        const showLogo = !$logoDisplayModeSelect.length || ['logo', 'both'].includes(($logoDisplayModeSelect.val() || 'logo').toString());
        $logoDesktopCustomPanel.toggleClass('d-none', !isCustom || !showLogo);
    };
    if ($logoDisplayModeSelect.length) {
        updateBrandDisplayOptions();
        $logoDisplayModeSelect.on('change', updateBrandDisplayOptions);
    }
    if ($logoDesktopSizeSelect.length) {
        updateLogoDesktopCustomPanel();
        $logoDesktopSizeSelect.on('change', function() {
            updateLogoDesktopCustomPanel();
            scheduleBrandLayoutPreview();
        });
    }
    if ($brandLayoutPageSelector.length) {
        updateBrandLayoutPageVisibility();
        $brandLayoutPageSelector.on('change', function() {
            updateBrandLayoutPageVisibility();
            updateBrandLayoutPreview();
        });
    }
    $(document).on('change', '.brand-page-layout-preset', function() {
        const $panel = $(this).closest('.brand-layout-page-panel');
        const pageKey = ($panel.data('brandPage') || '').toString();
        const preset = ($(this).val() || 'attached').toString();
        if (preset !== 'custom' && pageKey) {
            applyBrandLayoutPresetToPage(pageKey, preset);
        }
        updateBrandLayoutPageVisibility();
        updateBrandLayoutPreview();
    });
    $(document).on('change', '.brand-page-layout-customize', function() {
        updateBrandLayoutPageVisibility();
        updateBrandLayoutPreview();
    });
    $(document).on('input change', '.brand-page-layout-slider', function() {
        const $panel = $(this).closest('.brand-layout-page-panel');
        $panel.find('.brand-page-layout-preset').val('custom');
        updateBrandLayoutValueLabels();
        updateBrandLayoutPreview();
    });
    $(document).on('input change', '.logo-top-spacing-slider', function() {
        updateBrandLayoutValueLabels();
        updateBrandLayoutPreview();
    });
    $(document).on('input change', '.brand-header-logo-gap-slider', function() {
        updateBrandLayoutValueLabels();
        updateBrandLayoutPreview();
    });
    $(document).on('input change', '.brand-header-top-spacing-slider', function() {
        updateBrandLayoutValueLabels();
        updateBrandLayoutPreview();
    });
    $(document).on('click', '.brand-layout-preview-device', function() {
        brandLayoutPreviewDevice = ($(this).data('device') || 'mobile').toString();
        $('.brand-layout-preview-device').removeClass('active');
        $(this).addClass('active');
        updateBrandLayoutPreview();
    });
    $(document).on('change', '#upload_logo, #upload_header_image, #upload_logo_mobile, #upload_header_image_mobile', function() {
        window.setTimeout(scheduleBrandLayoutPreview, 80);
    });
    $('#section-sfondo').on('change input', 'input, select, textarea', function() {
        const name = (this.getAttribute('name') || '');
        if (name.indexOf('upload_background_') === 0) {
            return;
        }
        scheduleBrandLayoutPreview();
    });
    $('#logo_desktop_width_percent, #logo_desktop_max_height_px').on('input change', scheduleBrandLayoutPreview);
    $(document).on('click', '.brand-layout-collapse-toggle', function() {
        const target = ($(this).data('target') || '').toString();
        if (!target) {
            return;
        }
        const $target = $(target);
        if (!$target.length) {
            return;
        }
        $target.toggleClass('d-none');
        const isOpen = !$target.hasClass('d-none');
        $(this).attr('aria-expanded', isOpen ? 'true' : 'false');
        window.sessionStorage.setItem('brand_layout_collapse_open', isOpen ? '1' : '0');
    });
    const collapseOpen = window.sessionStorage.getItem('brand_layout_collapse_open') === '1';
    if (collapseOpen) {
        $('#brand-layout-collapse-body').removeClass('d-none');
        $('.brand-layout-collapse-toggle').attr('aria-expanded', 'true');
    }
    updateBrandLayoutValueLabels();
    updateBrandLayoutPreview();
    const $cellulareDigitsPanel = $('#cellulare_digits_panel');
    const $prefissoCellulareCheckbox = $('#field_prefisso_cellulare');
    const $autoPrizeDependentEmails = $('#email_sending_instantwin_confirm, #email_sending_instantwin_cancel');

    const normalizeEmailMode = function(value) {
        const normalized = (value || '').toString().trim().toLowerCase();
        return normalized === 'instantwin' ? 'instantwin' : 'estrazione';
    };

    // Cambio modalità concorso: toggle UI senza ricaricare la pagina (miglior UX).
    const updateThankyouModeVisibility = function(mode) {
        const normalizedMode = normalizeEmailMode(mode);
        const isInstantwin = normalizedMode === 'instantwin';
        const shared = isInstantwin && $('#instantwin_shared_thankyou_page').is(':checked');
        const quizFailedVisible = !$('#thankyou-quiz-failed-editor-block').hasClass('d-none');
        $('#section-thank-you-shared-toggle').toggleClass('d-none', !isInstantwin);
        $('#section-thank-you-win').toggleClass('d-none', !isInstantwin);
        $('#section-thank-you-lose').toggleClass('d-none', !isInstantwin || (shared && !quizFailedVisible));
        $('#thankyou-lose-only-block').toggleClass('d-none', shared);
        $('#section-thank-you-partecipazione').toggleClass('d-none', isInstantwin);
        $('#thankyou-win-title').html(shared
            ? '<i class="fa fa-trophy mr-2"></i>Thank-you'
            : '<i class="fa fa-trophy mr-2"></i>Thank-you WIN');
        $('#thankyou-win-note').text(shared
            ? 'Pagina unica mostrata dopo Instant Win (vincita o non vincita). Il risultato è comunicato via email.'
            : 'Pagina mostrata quando l\'utente vince nell\'Instant Win.');
        $('#thankyou-win-panel-title').text(shared ? 'Container pagina - Thank-you' : 'Container pagina - Thank-you WIN');
        $('.anteprima-iw-shared-or-win').text(shared ? 'Thank you' : 'Thank you Vincita');
        $('.anteprima-iw-loss-only').toggleClass('d-none', shared);
    };

    $(document).on('change', '#instantwin_shared_thankyou_page', function() {
        const mode = $contestModeSelect.length ? $contestModeSelect.val() : 'estrazione';
        updateThankyouModeVisibility(mode);
    });

    const updateEmailModeVisibility = function(mode) {
        const normalizedMode = normalizeEmailMode(mode);
        $emailModeCards.each(function() {
            const $card = $(this);
            const modesAttr = ($card.data('emailModes') || '').toString();
            const modes = modesAttr === '' ? [] : modesAttr.split(/\s+/).filter(Boolean);
            const shouldShow = modes.length === 0 || modes.includes('all') || modes.includes(normalizedMode);
            $card.toggleClass('d-none', !shouldShow);
        });
    };

    const activeMode = $contestModeSelect.length ? $contestModeSelect.val() : initialEmailMode;
    if ($emailModeCards.length) {
        updateEmailModeVisibility(activeMode);
        if ($contestModeSelect.length) {
            $contestModeSelect.on('change', function() {
                updateEmailModeVisibility($(this).val());
            });
        }
    }

    // Thank-you: mostra/nasconde le sezioni in base alla modalità selezionata.
    updateThankyouModeVisibility(activeMode);
    if ($contestModeSelect.length) {
        $contestModeSelect.on('change', function() {
            updateThankyouModeVisibility($(this).val());
        });
    }

    // Tipi di premi (solo Instant Win): mostra/nascondi
    const updatePremiTipiVisibility = function(mode) {
        const isIw = normalizeEmailMode(mode) === 'instantwin';
        $('.premi-tipi-section.instantwin-dependent-section').toggleClass('d-none', !isIw);
    };
    updatePremiTipiVisibility(activeMode);

    const getIwPrizeDistribution = function() {
        const sel = document.getElementById('instantwin_prize_distribution');
        return sel ? String(sel.value || 'default') : 'default';
    };
    const isIwPdvDistribution = function() {
        return getIwPrizeDistribution() === 'pdv';
    };
    const isIwCediDistribution = function() {
        return getIwPrizeDistribution() === 'cedi';
    };
    const syncIwPdvPersonalizzazioneWizard = function(mode) {
        const isIw = normalizeEmailMode(mode) === 'instantwin';
        const perPdv = isIw && isIwPdvDistribution();
        const perCedi = isIw && isIwCediDistribution();
        $('.iw-pdv-pers-hint').toggleClass('d-none', !perPdv);
        $('.iw-cedi-pers-hint').toggleClass('d-none', !perCedi);
        document.querySelectorAll('.iw-pdv-personalizzazione-wizard-step').forEach(function(el) {
            el.style.display = isIw ? '' : 'none';
        });
        var card = document.getElementById('section-iw-pdv-personalizzazione');
        if (card) {
            card.setAttribute('data-should-hide', isIw ? 'false' : 'true');
            card.style.display = isIw ? '' : 'none';
        }
        var pdvBlock = document.getElementById('iw-pdv-repartition-block');
        if (pdvBlock) {
            pdvBlock.style.display = perPdv ? '' : 'none';
        }
        var cediMatrix = document.getElementById('premi-cedi-matrix-block');
        if (cediMatrix) {
            cediMatrix.classList.toggle('d-none', !perCedi);
            cediMatrix.setAttribute('data-cedi-matrix-active', perCedi ? '1' : '0');
            cediMatrix.querySelectorAll('input, select, textarea').forEach(function(el) {
                if (el.closest('#cedi-wizard-step-1') || el.closest('#cedi-wizard-step-2')) {
                    el.disabled = false;
                    return;
                }
                el.disabled = !perCedi;
            });
            if (perCedi && typeof window.rebuildCediPremiMatrix === 'function') {
                window.rebuildCediPremiMatrix();
            }
            if (perCedi && typeof window.bootCediWeekPlanner === 'function') {
                window.bootCediWeekPlanner();
            }
        }
        var premiTipi = document.getElementById('premi-tipi-container');
        var premiTipiSection = document.querySelector('.premi-tipi-section');
        if (premiTipiSection) {
            premiTipiSection.classList.toggle('premi-tipi-section--cedi-mode', !!perCedi);
        }
        document.querySelectorAll('.premi-tipi-standard-hint').forEach(function(el) {
            el.classList.toggle('d-none', perCedi);
        });
        document.querySelectorAll('.premi-tipi-cedi-hint').forEach(function(el) {
            el.classList.toggle('d-none', !perCedi);
        });
        if (premiTipi) {
            premiTipi.classList.toggle('d-none', perCedi);
            premiTipi.querySelectorAll('input, select, textarea').forEach(function(el) {
                el.disabled = false;
            });
        }
        var premiAdd = document.getElementById('premi-add-row');
        if (premiAdd) {
            premiAdd.classList.toggle('d-none', perCedi);
            premiAdd.disabled = false;
        }
        if (typeof window.updateProgress === 'function') {
            window.updateProgress();
        }
    };
    const syncEsperienzaPartecipanteUi = function(mode) {
        const isIw = normalizeEmailMode(mode) === 'instantwin';
        if (!isIw) {
            const sel = document.getElementById('instantwin_prize_distribution');
            if (sel && sel.value !== 'default') {
                sel.value = 'default';
            }
        }
        syncIwPdvPersonalizzazioneWizard(mode);
    };
    syncEsperienzaPartecipanteUi(activeMode);
    $('#instantwin_prize_distribution').on('change', function() {
        syncEsperienzaPartecipanteUi($contestModeSelect.length ? $contestModeSelect.val() : activeMode);
    });
    if ($contestModeSelect.length) {
        $contestModeSelect.on('change', function() {
            syncEsperienzaPartecipanteUi($(this).val());
            updatePremiTipiVisibility($(this).val());
        });
    }

    // Tipi di premi: aggiungi/rimuovi riga + conferma riduzione quantità
    (function() {
        var premiRowIndex = $('#premi-tipi-container .premi-tipo-row').length;
        var initialPremiQuantita = {};
        $('#premi-tipi-container .premi-tipo-row').each(function() {
            var $row = $(this);
            var $input = $row.find('input[name*="[quantita_totale]"]');
            var m = ($input.attr('name') || '').match(/premi\[(\d+)\]/);
            if (m) {
                initialPremiQuantita[m[1]] = parseInt($input.val(), 10) || 0;
            }
        });
        var $form = $('#config-form');
        if ($form.length) {
            $form.on('submit.premiQuantity submit.codiceLength submit.codicePool', function(e) {
                // Validazione Tipi di premi (solo se sezione visibile - Instant Win)
                if (!$('.premi-tipi-section').hasClass('d-none')) {
                    var premiErrors = [];
                    $('#premi-tipi-container .premi-tipo-row').each(function(idx) {
                        var $row = $(this);
                        var $valore = $row.find('input[name*="[valore]"]');
                        var $tipo = $row.find('input[name*="[tipo_premio]"]');
                        var $qt = $row.find('input[name*="[quantita_totale]"]');
                        var valoreRaw = ($valore.val() || '').trim();
                        var valoreNorm = valoreRaw.replace(/\./g, '').replace(',', '.');
                        // Vuoto = 0 (premio senza importo)
                        var valore = valoreNorm === '' ? 0 : parseFloat(valoreNorm);
                        var tipo = ($tipo.val() || '').trim();
                        var qt = parseInt($qt.val(), 10) || 0;
                        if (valoreRaw !== '' || qt > 0 || tipo !== '') {
                            if (isNaN(valore) || valore < 0 || valore > 1000000) {
                                premiErrors.push('Riga ' + (idx + 1) + ': Valore deve essere un numero tra 0 e 1.000.000 (vuoto = 0).');
                            }
                            if (qt < 1 || qt > 1000000) {
                                premiErrors.push('Riga ' + (idx + 1) + ': Quantità deve essere tra 1 e 1.000.000.');
                            }
                            if (qt > 0 && tipo.length > 0 && tipo.length < 2) {
                                premiErrors.push('Riga ' + (idx + 1) + ': Tipo premio deve avere almeno 2 caratteri.');
                            }
                        }
                    });
                    if (premiErrors.length > 0) {
                        e.preventDefault();
                        $('#config-form-alerts .config-premi-validation-alert').remove();
                        var alertHtml = '<div class="alert alert-danger alert-dismissible mb-3 config-premi-validation-alert" role="alert">' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>' +
                            '<strong>Errore Tipi di premi</strong><p class="mb-1">Correggi i seguenti errori:</p>' +
                            '<ul class="mb-0 pl-3">' + premiErrors.map(function(m) { return '<li>' + $('<div>').text(m).html() + '</li>'; }).join('') + '</ul>' +
                            '</div>';
                        $('#config-form-alerts').prepend(alertHtml);
                        $('html, body').animate({ scrollTop: $('#config-form-alerts').offset().top - 100 }, 300);
                        return false;
                    }
                }
                var cpCount = parseInt($form.attr('data-codici-partecipazione-count') || '0', 10);
                var codiceEnabled = $('#field_codice_partecipazione').is(':checked');
                var codiceMode = ($('#codice_partecipazione_mode').val() || 'pool').toString();
                if (codiceEnabled && (codiceMode === 'pool' || codiceMode === 'direct_win') && cpCount === 0) {
                    e.preventDefault();
                    var modeLabel = codiceMode === 'direct_win' ? 'Instant Win + codice' : (isInstantWinSelected() ? 'Instant Win (pool codici)' : 'Pool codici');
                    Swal.fire({
                        title: 'Pool codici vuoto',
                        html: '<p>Hai selezionato la modalità <strong>' + $('<div>').text(modeLabel).html() + '</strong>, ma la tabella <code>codici_partecipazione</code> è vuota.</p>' +
                            '<p class="mb-0">Senza codici importati in <strong>Gestione Dati</strong>, nessun giocatore potrà iscriversi.</p>' +
                            '<p class="mt-2 mb-0 text-muted">Vuoi salvare comunque la configurazione?</p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#f0ad4e',
                        confirmButtonText: 'Salva comunque',
                        cancelButtonText: 'Annulla'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $form.off('submit.premiQuantity submit.codiceLength submit.codicePool');
                            if (typeof window.syncPageContainerEditorsBeforeSubmit === 'function') {
                                window.syncPageContainerEditorsBeforeSubmit($form[0]);
                            }
                            if ($form[0].requestSubmit) {
                                $form[0].requestSubmit();
                            } else {
                                $form[0].submit();
                            }
                        }
                    });
                    return false;
                }
                var initialLen = parseInt($form.attr('data-initial-codice-length') || '12', 10);
                var $lenInput = $form.find('input[name="instantwin_codice_length"]');
                var newLen = $lenInput.length ? (parseInt($lenInput.val(), 10) || 0) : initialLen;
                if (cpCount > 0 && initialLen !== newLen) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Modifica Lunghezza codice',
                        html: '<p>Stai modificando la lunghezza del codice da <strong>' + initialLen + '</strong> a <strong>' + newLen + '</strong>.</p><p>La tabella <code>codici_partecipazione</code> contiene già <strong>' + cpCount + '</strong> codici. Al salvataggio, tutti i codici partecipazione esistenti verranno eliminati.</p><p class="mb-0 text-danger"><strong>Dovrai reimportare i codici in Gestione Dati.</strong></p><p class="mt-2 mb-0">Continuare?</p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Sì, continua',
                        cancelButtonText: 'Annulla'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $form.off('submit.premiQuantity submit.codiceLength submit.codicePool');
                            if (typeof window.syncPageContainerEditorsBeforeSubmit === 'function') {
                                window.syncPageContainerEditorsBeforeSubmit($form[0]);
                            }
                            if ($form[0].requestSubmit) {
                                $form[0].requestSubmit();
                            } else {
                                $form[0].submit();
                            }
                        }
                    });
                    return false;
                }
                var reduced = [];
                $('#premi-tipi-container .premi-tipo-row').each(function() {
                    var $row = $(this);
                    var $input = $row.find('input[name*="[quantita_totale]"]');
                    var m = ($input.attr('name') || '').match(/premi\[(\d+)\]/);
                    if (m && initialPremiQuantita.hasOwnProperty(m[1])) {
                        var current = parseInt($input.val(), 10) || 0;
                        var initial = initialPremiQuantita[m[1]];
                        var cpCount = parseInt($row.attr('data-codici-premio-count') || '0', 10);
                        if (current < initial && cpCount > 0) {
                            var tipo = $row.find('input[name*="[tipo_premio]"]').val() || 'premio';
                            reduced.push({ tipo: tipo, initial: initial, current: current });
                        }
                    }
                });
                if (reduced.length > 0) {
                    e.preventDefault();
                    var listHtml = '<ul class="text-left mb-0 pl-3">';
                    reduced.forEach(function(r) {
                        listHtml += '<li><strong>' + $('<div>').text(r.tipo).html() + '</strong>: da ' + r.initial + ' a ' + r.current + '</li>';
                    });
                    listHtml += '</ul>';
                    Swal.fire({
                        title: 'Riduzione quantità premi',
                        html: '<p>Riducendo la quantità dei seguenti premi, al salvataggio verranno eliminati i codici premio in eccesso (non ancora utilizzati) dalla base dati:</p>' + listHtml + '<p class="mt-2 mb-0">Continuare?</p>',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Sì, continua',
                        cancelButtonText: 'Annulla'
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            $form.off('submit.premiQuantity submit.codiceLength submit.codicePool');
                            if (typeof window.syncPageContainerEditorsBeforeSubmit === 'function') {
                                window.syncPageContainerEditorsBeforeSubmit($form[0]);
                            }
                            if ($form[0].requestSubmit) {
                                $form[0].requestSubmit();
                            } else {
                                $form[0].submit();
                            }
                        }
                    });
                    return false;
                }
            });
        }
        $('#premi-add-row').on('click', function() {
            var idx = premiRowIndex++;
            var html = '<div class="premi-tipo-row row align-items-end mb-2" data-index="' + idx + '" data-codici-premio-count="0">' +
                '<div class="col-md-3"><label class="small">Valore (€)</label><input type="text" inputmode="decimal" name="premi[' + idx + '][valore]" class="form-control form-control-sm js-format-number-it js-premi-valore" data-max="1000000" value="" placeholder="0 = senza importo" title="Vuoto = 0. Solo cifre, max 1.000.000"></div>' +
                '<div class="col-md-3"><label class="small">Tipo premio</label><input type="text" name="premi[' + idx + '][tipo_premio]" class="form-control form-control-sm js-premi-tipo" maxlength="255" value="" placeholder="Prepagata" title="Min. 2 caratteri, max 255"></div>' +
                '<div class="col-md-2"><label class="small">Quantità</label><input type="number" min="1" max="1000000" name="premi[' + idx + '][quantita_totale]" class="form-control form-control-sm js-premi-quantita" value="" placeholder="200" title="Solo cifre, max 1.000.000"></div>' +
                '<input type="hidden" name="premi[' + idx + '][is_active]" value="1">' +
                '<div class="col-md-2"><button type="button" class="btn btn-outline-danger btn-sm premi-remove-row" title="Rimuovi"><i class="fa fa-trash"></i></button></div>' +
                '</div>';
            $('#premi-tipi-container').append(html);
            if (typeof $ === 'function' && $.fn.tooltip) {
                $('#premi-tipi-container .premi-tipo-row:last').find('[data-toggle="tooltip"]').tooltip();
            }
        });
        $(document).on('click', '.premi-remove-row', function() {
            var $row = $(this).closest('.premi-tipo-row');
            var cpCount = parseInt($row.attr('data-codici-premio-count') || '0', 10);
            if (cpCount > 0) {
                var tipo = $row.find('input[name*="[tipo_premio]"]').val() || 'questo premio';
                var safeTipo = $('<div>').text(tipo).html();
                Swal.fire({
                    title: 'Rimuovere questo premio?',
                    html: '<p>Stai per rimuovere il premio <strong>' + safeTipo + '</strong>.</p><p>La tabella codici_premio contiene <strong>' + cpCount + '</strong> codici associati a questo premio. Al salvataggio della configurazione, verranno eliminati tutti.</p><p class="mb-0">Continuare?</p>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Sì, rimuovi',
                    cancelButtonText: 'Annulla'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $row.remove();
                    }
                });
            } else {
                $row.remove();
            }
        });
    })();

    // Anteprima header: aggiorna i pulsanti Thank you in base alla modalità senza salvare
    const updateAnteprimaThankyouButtons = function(mode) {
        const isIw = normalizeEmailMode(mode) === 'instantwin';
        $('.anteprima-thankyou-btns[data-mode="instantwin"]').toggle(isIw);
        $('.anteprima-thankyou-btns[data-mode="estrazione"]').toggle(!isIw);
    };
    updateAnteprimaThankyouButtons(activeMode);
    if ($contestModeSelect.length) {
        $contestModeSelect.on('change', function() {
            updateAnteprimaThankyouButtons($(this).val());
        });
    }

    // Anteprima: mostra solo se Publicato=No (Sito rimosso quando pubblicato)
    const updateAnteprimaSitoVisibility = function() {
        const $pubSelect = $('#config-published-select');
        if (!$pubSelect.length) return;
        const isPub = $pubSelect.val() === '1';
        $('.config-anteprima-preview').toggleClass('d-none', isPub);
    };
    updateAnteprimaSitoVisibility();
    $('#config-published-select').on('change', updateAnteprimaSitoVisibility);

    const isInstantWinSelected = function() {
        const mode = $contestModeSelect.length ? $contestModeSelect.val() : initialEmailMode;
        return normalizeEmailMode(mode) === 'instantwin';
    };
    const isScontrinoEnabled = function() {
        if ($scontrinoCheckbox.length) {
            return $scontrinoCheckbox.is(':checked');
        }
        return true;
    };
    const getPrizeFlowValue = function() {
        const $checked = $prizeFlowRadios.filter(':checked');
        if ($checked.length) {
            return ($checked.val() || 'auto_immediate').toString();
        }
        return 'auto_immediate';
    };
    const isAutoImmediatePrizeFlow = function() {
        return !isScontrinoEnabled() || getPrizeFlowValue() === 'auto_immediate';
    };
    const updatePrizeFlowStatusBanner = function() {
        if (!$prizeFlowStatusBanner.length) {
            return;
        }
        if (!isInstantWinSelected()) {
            $prizeFlowStatusBanner.html('<span class="badge badge-secondary mr-1">N/D</span> Seleziona Instant Win per configurare la gestione vincita.');
            return;
        }
        if (!isScontrinoEnabled()) {
            $prizeFlowStatusBanner.html('<span class="badge badge-secondary mr-1">Standard</span> Conferma manager disponibile in Partecipazioni.');
            return;
        }
        if (isAutoImmediatePrizeFlow()) {
            $prizeFlowStatusBanner.html('<span class="badge badge-success mr-1">Attivo</span> Codice inviato subito dopo la vincita (email premio con codice).');
            return;
        }
        $prizeFlowStatusBanner.html('<span class="badge badge-info mr-1">Attivo</span> Conferma manager: email di attesa senza codice, codice dopo la conferma.');
    };
    const updatePrizeFlowWarning = function() {
        if (!$prizeFlowWarning.length) {
            return;
        }
        const show = isInstantWinSelected()
            && isScontrinoEnabled()
            && !isAutoImmediatePrizeFlow()
            && !$('#email_sending_instantwin_confirm').is(':checked')
            && $('#email_sending_instantwin_prize').is(':checked');
        $prizeFlowWarning.toggleClass('d-none', !show);
    };
    const syncAutoPrizeHiddenField = function() {
        if (!$autoPrizeHidden.length) {
            return;
        }
        const autoOn = isInstantWinSelected() && isScontrinoEnabled() && isAutoImmediatePrizeFlow();
        $autoPrizeHidden.val(autoOn ? '1' : '0');
    };
    const updateAutoPrizeVisibility = function() {
        const showScontrinoFlow = isInstantWinSelected() && isScontrinoEnabled();
        if ($autoPrizeWrapper.length) {
            $autoPrizeWrapper.toggleClass('d-none', !showScontrinoFlow);
        }
        if ($autoPrizeFlowPanelIw.length) {
            $autoPrizeFlowPanelIw.toggleClass('d-none', !isInstantWinSelected());
        }
        if ($emailFlowAutoNote.length) {
            $emailFlowAutoNote.toggleClass('d-none', !showScontrinoFlow || !isAutoImmediatePrizeFlow());
        }
        updateAutoPrizeDependents();
        syncAutoPrizeHiddenField();
        updatePrizeFlowStatusBanner();
        updatePrizeFlowWarning();
    };
    const updateAutoPrizeDependents = function() {
        if (!$autoPrizeDependentEmails.length) {
            return;
        }
        const autoFlow = isAutoImmediatePrizeFlow();
        const disableManagerEmails = isInstantWinSelected() && isScontrinoEnabled() && autoFlow;
        $autoPrizeDependentEmails.prop('disabled', disableManagerEmails);
        if (disableManagerEmails) {
            $autoPrizeDependentEmails.prop('checked', false);
        }
        const $confirmCard = $('.email-template-card[data-email-type="instantwin_confirm"]');
        if ($confirmCard.length) {
            $confirmCard.toggleClass('email-flow-card-muted', disableManagerEmails);
        }
        const $cancelCard = $('.email-template-card[data-email-type="instantwin_cancel"]');
        if ($cancelCard.length) {
            $cancelCard.toggleClass('email-flow-card-muted', disableManagerEmails);
        }
    };
    const updateReceiptTypesVisibility = function() {
        if (!$receiptTypesPanel.length && !$maxUploadPanel.length && !$receiptHelpPanel.length) return;
        const show = isScontrinoEnabled();
        $receiptTypesPanel.toggleClass('d-none', !show);
        $maxUploadPanel.toggleClass('d-none', !show);
        $receiptHelpPanel.toggleClass('d-none', !show);
        
        // Se scontrino è disabilitato, deselezionare tutti i checkbox dei tipi di file
        if (!show) {
            $receiptTypesPanel.find('input[type="checkbox"][name="receipt_allowed_extensions[]"]').prop('checked', false);
        }
    };
    const updateMinSpendVisibility = function() {
        if (!$minSpendPanel.length) return;
        const show = $importoCheckbox.length ? $importoCheckbox.is(':checked') : true;
        $minSpendPanel.toggleClass('d-none', !show);
    };
    const updateFidelityConfigVisibility = function() {
        if (!$fidelityConfigPanel.length) return;
        const show = $fidelityCheckbox.length ? $fidelityCheckbox.is(':checked') : false;
        $fidelityConfigPanel.toggleClass('d-none', !show);
    };
    const updateCellulareDigitsVisibility = function() {
        if (!$cellulareDigitsPanel.length) return;
        const show = $prefissoCellulareCheckbox.length ? $prefissoCellulareCheckbox.is(':checked') : false;
        $cellulareDigitsPanel.toggleClass('d-none', !show);
    };
    const updateCodicePartecipazionePanels = function() {
        const $modeSelect = $('#codice_partecipazione_mode');
        const $lengthPanel = $('#instantwin_codice_length_panel');
        const $strictPanel = $('#codice_partecipazione_strict_panel');
        const $poolEmptyAlert = $('#codice_partecipazione_pool_empty_alert');
        const $hint = $('#codice_partecipazione_mode_hint');
        const $form = $('#config-form');
        const cpCount = $form.length ? parseInt($form.attr('data-codici-partecipazione-count') || '0', 10) : 0;
        const codiceFieldEnabled = $('#field_codice_partecipazione').is(':checked');
        const $lenInput = $('input[name="instantwin_codice_length"]');
        const isIw = isInstantWinSelected();
        if (!$modeSelect.length) {
            return;
        }
        $modeSelect.find('option[data-iw-only="1"]').prop('disabled', !isIw);
        if (!isIw && $modeSelect.val() === 'direct_win') {
            $modeSelect.val('pool');
        }
        const mode = ($modeSelect.val() || 'pool').toString();
        const isCombinazione = mode === 'combinazione';
        const poolLabel = isIw ? 'Instant Win (pool codici)' : 'Pool codici';
        $modeSelect.find('option[value="pool"]').text(poolLabel);
        if ($lengthPanel.length) {
            $lengthPanel.toggleClass('d-none', isCombinazione);
        }
        if ($strictPanel.length) {
            $strictPanel.toggleClass('d-none', !isCombinazione);
        }
        if ($lenInput.length && isCombinazione) {
            $lenInput.val('13').prop('readonly', true);
        } else if ($lenInput.length) {
            $lenInput.prop('readonly', false);
        }
        if ($hint.length) {
            const hints = {
                pool: isIw
                    ? 'Il codice deve esistere nel pool DB; la vincita è decisa dal motore Instant Win.'
                    : 'Il codice deve esistere nel pool DB; partecipazione valida per l\'estrazione finale.',
                direct_win: 'Il codice deve esistere nel pool DB; se ha un premio pre-assegnato vince subito, altrimenti perde.',
                combinazione: 'Senza pool DB: controllo della combinazione a 13 cifre (plages A…GG).'
            };
            $hint.text(hints[mode] || '');
        }
        if ($poolEmptyAlert.length) {
            const showPoolWarning = codiceFieldEnabled && (mode === 'pool' || mode === 'direct_win') && cpCount === 0;
            $poolEmptyAlert.toggleClass('d-none', !showPoolWarning);
        }
    };
    const updateCodiceModeVisibility = function() {
        updateCodicePartecipazionePanels();
    };
    const updatePdvConfigVisibility = function() {
        const $pdvConfigPanel = $('#pdv_config_panel');
        const $pdvGroupCheckbox = $('#field_pdv_group');
        if (!$pdvConfigPanel.length) return;
        const show = $pdvGroupCheckbox.length && $pdvGroupCheckbox.is(':checked');
        $pdvConfigPanel.toggleClass('d-none', !show);
    };
    const updatePdvModeOptions = function() {
        const mode = $('#pdv_config_mode').val() || 'combinato';
        $('#pdv_combinato_options').toggleClass('d-none', mode !== 'combinato');
        $('#pdv_cascata_options').toggleClass('d-none', mode !== 'cascata');
    };
    const updateConsensiTextsVisibility = function() {
        const $block = $('#consensi_texts_block');
        if (!$block.length) return;
        const privacyChecked = $('#field_privacy').is(':checked');
        const marketingChecked = $('#field_marketing').is(':checked');
        const profilazioneChecked = $('#field_profilazione').is(':checked');
        const anyChecked = privacyChecked || marketingChecked || profilazioneChecked;
        $block.toggleClass('d-none', !anyChecked);
        $('#consensi_text_privacy_row').toggleClass('d-none', !privacyChecked);
        $('#consensi_text_marketing_row').toggleClass('d-none', !marketingChecked);
        $('#consensi_text_profilazione_row').toggleClass('d-none', !profilazioneChecked);
    };
    updateAutoPrizeVisibility();
    updateReceiptTypesVisibility();
    updateMinSpendVisibility();
    updateFidelityConfigVisibility();
    updateCellulareDigitsVisibility();
    updateCodicePartecipazionePanels();
    updatePdvConfigVisibility();
    updatePdvModeOptions();
    updateConsensiTextsVisibility();
    $(document).on('change', '#field_privacy, #field_marketing, #field_profilazione', updateConsensiTextsVisibility);
    $(document).on('change', '#codice_partecipazione_mode, #field_codice_partecipazione', updateCodicePartecipazionePanels);
    if ($contestModeSelect.length) {
        $contestModeSelect.on('change', function() {
            updateAutoPrizeVisibility();
            updateCodicePartecipazionePanels();
        });
    }
    // Listener per il checkbox scontrino: aggiornare visibilità campo auto prize
    // Usare delegazione eventi per gestire anche elementi caricati dinamicamente
    $(document).on('change', '#field_scontrino', function() {
        updateAutoPrizeVisibility();
        updateReceiptTypesVisibility();
    });
    if ($importoCheckbox.length) {
        $importoCheckbox.on('change', updateMinSpendVisibility);
    }
    if ($fidelityCheckbox.length) {
        $fidelityCheckbox.on('change', updateFidelityConfigVisibility);
    }
    $(document).on('change', '#field_pdv_group', updatePdvConfigVisibility);
    $(document).on('change', '#pdv_config_mode', function() {
        updatePdvModeOptions();
        $(document).trigger('positionGridRefresh');
    });
    $(document).on('click', '.pdv-label-field-chip', function() {
        var field = ($(this).data('field') || '').toString().trim().toLowerCase();
        if (!field) return;
        var $input = $('input[name="pdv_config[combinato][label_fields_str]"]').first();
        if (!$input.length) return;
        var current = ($input.val() || '').toString();
        var parts = current.split(',').map(function(p) { return p.trim().toLowerCase(); }).filter(Boolean);
        if (parts.indexOf(field) === -1) {
            parts.push(field);
            $input.val(parts.join(', ')).trigger('input').trigger('change');
        }
    });
    if ($prefissoCellulareCheckbox.length) {
        $prefissoCellulareCheckbox.on('change', updateCellulareDigitsVisibility);
    }
    $(document).on('click', '.cellulare-clear-btn', function() {
        var id = $(this).data('target');
        if (id) {
            $('#' + id).val('').trigger('change');
        }
    });
    if ($prizeFlowRadios.length) {
        $prizeFlowRadios.on('change', function() {
            if (getPrizeFlowValue() === 'manager_confirm') {
                $('#email_sending_instantwin_confirm').prop('checked', true);
                $('#email_sending_instantwin_cancel').prop('checked', true);
            }
            updateAutoPrizeVisibility();
        });
    }
    $('#email_sending_instantwin_confirm').on('change', function() {
        if (isInstantWinSelected() && isScontrinoEnabled() && !$(this).is(':checked')) {
            $('#instantwin_prize_flow_auto').prop('checked', true);
        }
        updateAutoPrizeVisibility();
    });
    $('#email_sending_instantwin_prize').on('change', updatePrizeFlowWarning);
    if ($emailBccEnabled.length && $emailBccDetailsPanel.length) {
        $emailBccEnabled.on('change', function() {
            $emailBccDetailsPanel.toggleClass('d-none', !$(this).is(':checked'));
        });
    }
    updateAutoPrizeVisibility();
});

// Payload FAQ JSON (campo in testa al form config)
function syncFaqJsonPayload() {
    var payload = document.getElementById('config-faq-json');
    var list = document.getElementById('faq-list');
    if (!payload || !list) {
        return;
    }
    var items = [];
    list.querySelectorAll('.faq-block').forEach(function(block, index) {
        var qEl = block.querySelector('[name="faq_q[]"]');
        var aEl = block.querySelector('[name="faq_a[]"]');
        var orderEl = block.querySelector('[name="faq_order[]"]');
        var orderVal = orderEl ? parseInt(orderEl.value, 10) : NaN;
        items.push({
            q: qEl ? qEl.value : '',
            a: aEl ? aEl.value : '',
            order: !isNaN(orderVal) ? orderVal : (index + 1)
        });
    });
    try {
        payload.value = JSON.stringify(items);
    } catch (e) {
        payload.value = '[]';
    }
}
window.syncFaqJsonPayload = syncFaqJsonPayload;

// Gestione FAQ dinamiche: aggiungi/rimuovi/ordina blocchi
$(function() {
    var $faqList = $('#faq-list');

    function cloneFaqBlock() {
        var template = document.getElementById('faq-template');
        if (!template) {
            return null;
        }
        if (template.content && template.content.firstElementChild) {
            return template.content.firstElementChild.cloneNode(true);
        }
        var wrap = document.createElement('div');
        wrap.innerHTML = template.innerHTML;
        return wrap.firstElementChild;
    }

    function renumberFaqOrders() {
        if (!$faqList.length) {
            return;
        }
        $faqList.find('.faq-block').each(function(index) {
            var $orderInput = $(this).find('input[name="faq_order[]"]');
            if ($orderInput.length) {
                $orderInput.val(index + 1);
            }
        });
        syncFaqJsonPayload();
    }

    function sortFaqByOrder() {
        if (!$faqList.length) {
            return;
        }
        var blocks = $faqList.find('.faq-block').get();
        blocks.sort(function(a, b) {
            var va = parseInt($(a).find('input[name="faq_order[]"]').val(), 10);
            var vb = parseInt($(b).find('input[name="faq_order[]"]').val(), 10);
            if (isNaN(va)) {
                va = 9007199254740991;
            }
            if (isNaN(vb)) {
                vb = 9007199254740991;
            }
            if (va === vb) {
                return 0;
            }
            return va < vb ? -1 : 1;
        });
        $.each(blocks, function(_, block) {
            $faqList.append(block);
        });
        renumberFaqOrders();
    }

    function addFaq() {
        var listEl = document.getElementById('faq-list');
        var block = cloneFaqBlock();
        if (!listEl || !block) {
            return;
        }
        listEl.appendChild(block);
        renumberFaqOrders();
    }

    $(document).on('click', '#section-faq #add-faq', function(event) {
        event.preventDefault();
        addFaq();
    });

    $(document).on('click', '#section-faq .btn-remove-faq', function() {
        var $list = $('#faq-list');
        var blocks = $list.find('.faq-block');
        if (blocks.length > 1) {
            $(this).closest('.faq-block').remove();
        } else {
            $(this).closest('.faq-block').find('input, textarea').val('');
        }
        renumberFaqOrders();
    });

    $(document).on('focus', '#section-faq input[name="faq_order[]"]', function() {
        $(this).data('prevOrder', $(this).val());
    });
    $(document).on('change blur', '#section-faq input[name="faq_order[]"]', function() {
        var $list = $('#faq-list');
        var $input = $(this);
        var newVal = parseInt($input.val(), 10);
        if (isNaN(newVal)) {
            return;
        }
        var maxBlocks = $list.find('.faq-block').length;
        if (newVal > maxBlocks) {
            var $block = $input.closest('.faq-block');
            $list.append($block[0]);
            renumberFaqOrders();
            $input.data('prevOrder', String(maxBlocks));
            return;
        }
        var oldVal = $input.data('prevOrder');
        var oldNum = oldVal !== undefined && oldVal !== '' ? parseInt(oldVal, 10) : null;
        var $block = $input.closest('.faq-block');
        var $other = $list.find('.faq-block').not($block).find('input[name="faq_order[]"]').filter(function() {
            return parseInt($(this).val(), 10) === newVal;
        });
        if ($other.length) {
            var swapVal = (oldNum !== null && !isNaN(oldNum)) ? String(oldNum) : '';
            $other.val(swapVal);
            $other.data('prevOrder', swapVal);
            sortFaqByOrder();
        }
        $input.data('prevOrder', $input.val());
        syncFaqJsonPayload();
    });

    $(document).on('input change', '#section-faq [name="faq_q[]"], #section-faq [name="faq_a[]"]', function() {
        syncFaqJsonPayload();
    });

    renumberFaqOrders();
});

(function() {
    if (window.__concorsoWizardInit) {
        return;
    }
    window.__concorsoWizardInit = true;

    var wizard = document.getElementById('config-wizard');
    var form = document.getElementById('config-form');
    if (!wizard || !form) {
        return;
    }

    var stepButtons = Array.prototype.slice.call(wizard.querySelectorAll('.wizard-step'));
    var stepCards = Array.prototype.slice.call(document.querySelectorAll('.config-step'));
    if (!stepButtons.length || !stepCards.length) {
        return;
    }

    var steps = [];
    stepButtons.forEach(function(btn) {
        var step = btn.getAttribute('data-step');
        if (step && steps.indexOf(step) === -1) {
            steps.push(step);
        }
    });

    var actions = Array.prototype.slice.call(document.querySelectorAll('.wizard-actions'));
    var currentStep = steps[0];
    // Rendere currentStep accessibile globalmente
    window.currentStep = currentStep;

    // Funzione helper per ottenere i steps visibili
    function getVisibleSteps() {
        var visibleSteps = [];
        stepButtons.forEach(function(btn) {
            if (btn.offsetParent !== null) { // Verifica se l'elemento è visibile
                var step = btn.getAttribute('data-step');
                if (step && visibleSteps.indexOf(step) === -1) {
                    visibleSteps.push(step);
                }
            }
        });
        return visibleSteps;
    }
    
    // Rendere updateProgress accessibile globalmente per permettere aggiornamenti esterni
    window.updateProgress = function() {
        // Ricalcolare i steps visibili (escludere quelli nascosti)
        var visibleSteps = getVisibleSteps();
        
        var index = visibleSteps.indexOf(currentStep);
        // Se currentStep non è nelle steps visibili, usare l'indice originale come fallback
        if (index === -1) {
            index = steps.indexOf(currentStep);
            // Se ancora non trovato, usare 0 come fallback
            if (index === -1) {
                index = 0;
            }
        }
        var total = visibleSteps.length;
        var progress = total ? Math.round(((index + 1) / total) * 100) : 0;
        var bar = document.getElementById('wizard-progress-bar');
        if (bar) {
            bar.style.width = progress + '%';
        }
        var text = document.getElementById('wizard-progress-text');
        if (text) {
            text.textContent = 'Passo ' + (index + 1) + ' di ' + total;
        }
        actions.forEach(function(action) {
            var prevBtn = action.querySelector('.wizard-prev');
            var nextBtn = action.querySelector('.wizard-next');
            if (prevBtn) {
                prevBtn.disabled = index <= 0;
            }
            if (nextBtn) {
                nextBtn.disabled = index >= total - 1;
            }
        });
    }
    
    window.setActiveStep = function(step, options) {
        currentStep = step;
        window.currentStep = step; // Aggiornare anche la variabile globale
        var stepInput = document.getElementById('concorso-step-input');
        if (stepInput) stepInput.value = step;
        stepCards.forEach(function(card) {
            var active = card.getAttribute('data-step') === step;
            // Verificare se la card ha data-should-hide="true" (per Gestione Dati)
            var shouldHide = card.getAttribute('data-should-hide') === 'true';
            if (shouldHide) {
                // Se deve essere nascosta completamente, usare display: none
                card.style.display = 'none';
            } else {
                // Altrimenti, gestire normalmente con is-hidden
                card.classList.toggle('is-hidden', !active);
                // Rimuovere display: none inline se presente (per permettere a is-hidden di funzionare)
                if (active) {
                    card.style.display = '';
                } else {
                    // Se non è attivo, assicurarsi che non ci sia display: none inline
                    // che potrebbe override is-hidden
                    if (card.style.display === 'none' && !shouldHide) {
                        card.style.display = '';
                    }
                }
            }
        });
        stepButtons.forEach(function(btn) {
            var active = btn.getAttribute('data-step') === step;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-current', active ? 'step' : 'false');
        });
        updateProgress();
        if (step === '3' && typeof window.updateBrandLayoutPreview === 'function') {
            window.setTimeout(window.updateBrandLayoutPreview, 50);
        }
        if (!options || options.scroll !== false) {
            wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    function getStepFields(step) {
        var fields = [];
        stepCards.forEach(function(card) {
            if (card.getAttribute('data-step') === step) {
                fields = fields.concat(Array.prototype.slice.call(card.querySelectorAll('input, select, textarea')));
            }
        });
        return fields;
    }

    function validateStep(step) {
        var fields = getStepFields(step);
        for (var i = 0; i < fields.length; i++) {
            var field = fields[i];
            if (!field.willValidate) {
                continue;
            }
            if (!field.checkValidity()) {
                field.reportValidity();
                return false;
            }
        }
        return true;
    }

    function stepFromElement(el) {
        if (!el) {
            return null;
        }
        var card = el.closest('.config-step');
        return card ? card.getAttribute('data-step') : null;
    }

    stepButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var step = btn.getAttribute('data-step');
            if (step) {
                setActiveStep(step, { scroll: false });
            }
        });
        btn.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                btn.click();
            }
        });
    });

    actions.forEach(function(action) {
        var prevBtn = action.querySelector('.wizard-prev');
        var nextBtn = action.querySelector('.wizard-next');
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                var visibleSteps = getVisibleSteps();
                var index = visibleSteps.indexOf(currentStep);
                // Se currentStep non è visibile, cercare il precedente step visibile
                if (index === -1) {
                    index = visibleSteps.length;
                }
                if (index > 0) {
                    setActiveStep(visibleSteps[index - 1]);
                }
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (!validateStep(currentStep)) {
                    return;
                }
                var visibleSteps = getVisibleSteps();
                var index = visibleSteps.indexOf(currentStep);
                // Se currentStep non è visibile, cercare il prossimo step visibile
                if (index === -1) {
                    index = -1;
                }
                if (index < visibleSteps.length - 1) {
                    setActiveStep(visibleSteps[index + 1]);
                }
            });
        }
    });

    form.addEventListener('submit', function(event) {
        if (typeof window.syncPageContainerEditorsBeforeSubmit === 'function') {
            window.syncPageContainerEditorsBeforeSubmit(form);
        }
        if (typeof window.syncFaqJsonPayload === 'function') {
            window.syncFaqJsonPayload();
        }
        if (typeof window.syncGestioneDatiColumnsPayload === 'function') {
            window.syncGestioneDatiColumnsPayload();
        }
        // Checkbox disabilitati (es. auto-premio) non vengono inviati: ripristinare prima del submit.
        if ($autoPrizeDependentEmails && $autoPrizeDependentEmails.length) {
            $autoPrizeDependentEmails.prop('disabled', false);
        }
        form.querySelectorAll('#section-faq .help-style-color-input').forEach(function(input) {
            var v = (input.value || '').trim();
            if (v !== '' && !/^#[0-9A-Fa-f]{6}$/.test(v)) {
                input.value = '';
            }
            input.setCustomValidity('');
        });
        $('.sms-template-input').each(function() {
            applySmsTemplateSanitize($(this), false);
        });
        if (form.checkValidity()) {
            return;
        }
        event.preventDefault();
        var invalid = form.querySelector(':invalid');
        if (!invalid) {
            return;
        }
        var step = stepFromElement(invalid);
        if (step) {
            setActiveStep(step, { scroll: true });
        }
        setTimeout(function() {
            invalid.reportValidity();
        }, 0);
    });

    var hashTarget = window.location.hash ? document.querySelector(window.location.hash) : null;
    if (hashTarget) {
        var hashStep = stepFromElement(hashTarget);
        if (hashStep) {
            setActiveStep(hashStep, { scroll: false });
            hashTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
    }

    // Step 9 (Gestione dati) o 10 (Quiz): URL o sessionStorage
    var openDeepStep = null;
    var urlParams = new URLSearchParams(window.location.search);
    var stepFromUrl = urlParams.get('concorso_step');
    if (stepFromUrl === '9' || stepFromUrl === '10' || stepFromUrl === '11') {
        openDeepStep = stepFromUrl;
        urlParams.delete('concorso_step');
        var newSearch = urlParams.toString();
        var newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '') + window.location.hash;
        window.history.replaceState({}, '', newUrl);
    }
    if (!openDeepStep && typeof Storage !== 'undefined') {
        var savedStep = sessionStorage.getItem('concorso_active_step');
        if (savedStep === '9' || savedStep === '10' || savedStep === '11') {
            sessionStorage.removeItem('concorso_active_step');
            openDeepStep = savedStep;
        }
    }
    if (openDeepStep) {
        setTimeout(function() {
            setActiveStep(openDeepStep, { scroll: true });
        }, 100);
        return;
    }

    function syncQuizConfigPanelVisibility() {
        var quizCb = document.getElementById('config-quiz-enabled-checkbox');
        var panel = document.getElementById('config-quiz-options-panel');
        if (panel && quizCb) {
            panel.style.display = quizCb.checked ? '' : 'none';
        }
    }
    function syncAnteprimaQuizPreviewOnly() {
        var quizCb = document.getElementById('config-quiz-enabled-checkbox');
        var quizOn = quizCb && quizCb.checked;
        document.querySelectorAll('.anteprima-quiz-preview-only').forEach(function(el) {
            el.style.display = quizOn ? '' : 'none';
        });
        var allCorrectCb = document.getElementById('quiz_require_all_correct');
        var requireAllOn = allCorrectCb && allCorrectCb.checked;
        var showFailPreview = quizOn && requireAllOn;
        document.querySelectorAll('.anteprima-quiz-fail-preview-only').forEach(function(el) {
            el.style.display = showFailPreview ? '' : 'none';
        });
    }
    function syncQuizUiState() {
        var quizCb = document.getElementById('config-quiz-enabled-checkbox');
        var on = quizCb && quizCb.checked;
        syncQuizConfigPanelVisibility();
        syncAnteprimaQuizPreviewOnly();
        document.querySelectorAll('.quiz-wizard-step').forEach(function(el) {
            el.style.display = on ? '' : 'none';
        });
        ['section-quiz', 'section-quiz-domande'].forEach(function(id) {
            var card = document.getElementById(id);
            if (card) {
                card.setAttribute('data-should-hide', on ? 'false' : 'true');
                if (!on) {
                    card.style.display = 'none';
                } else {
                    card.style.display = '';
                }
            }
        });
        if (typeof window.updateGestioneDatiVisibility === 'function') {
            window.updateGestioneDatiVisibility();
        }
        if (typeof window.updateProgress === 'function') {
            window.updateProgress();
        }
    }
    function syncQuizChoiceLayoutVisibility() {
        var modeSelect = document.getElementById('config-quiz-mode-select');
        var wrap = document.getElementById('quiz-choice-layout-wrap');
        var isChoice = modeSelect && modeSelect.value === 'choice';
        if (wrap) {
            wrap.classList.toggle('d-none', !isChoice);
            wrap.querySelectorAll('input, select, textarea, button').forEach(function(el) {
                var n = el.getAttribute('name') || '';
                if (n.indexOf('quiz_choice') === 0) {
                    el.disabled = !isChoice;
                }
            });
        }
    }
    window.syncQuizUiState = syncQuizUiState;
    syncQuizUiState();
    syncQuizChoiceLayoutVisibility();

    var quizCb = document.getElementById('config-quiz-enabled-checkbox');
    if (quizCb) {
        quizCb.addEventListener('change', function() {
            syncQuizUiState();
            if (typeof window.syncThankyouLoseIwQuizHint === 'function') {
                window.syncThankyouLoseIwQuizHint();
            }
        });
    }
    var quizModeSelect = document.getElementById('config-quiz-mode-select');
    if (quizModeSelect) {
        quizModeSelect.addEventListener('change', syncQuizChoiceLayoutVisibility);
    }
    var quizRequireAllCorrectCb = document.getElementById('quiz_require_all_correct');
    if (quizRequireAllCorrectCb) {
        quizRequireAllCorrectCb.addEventListener('change', function() {
            syncAnteprimaQuizPreviewOnly();
            if (typeof window.syncThankyouLoseIwQuizHint === 'function') {
                window.syncThankyouLoseIwQuizHint();
            }
        });
    }

    // In caso di errori di validazione (reload pagina), ripristinare lo step da cui si proviene
    var stepInput = document.getElementById('concorso-step-input');
    if (stepInput && stepInput.value) {
        var savedStep = stepInput.value.trim();
        if (steps.indexOf(savedStep) !== -1) {
            setActiveStep(savedStep, { scroll: true });
            return;
        }
    }

    setActiveStep(currentStep, { scroll: false });
})();

(function() {
    function defaultTooltipFor(control) {
        if (!control) {
            return 'Configura questa opzione.';
        }
        var tag = control.tagName ? control.tagName.toLowerCase() : '';
        if (tag === 'select') {
            return 'Seleziona l\'opzione da usare per questa configurazione.';
        }
        if (tag === 'textarea') {
            return 'Inserisci il contenuto per questa configurazione.';
        }
        if (tag === 'input') {
            var type = (control.getAttribute('type') || 'text').toLowerCase();
            if (type === 'checkbox' || type === 'radio') {
                return 'Abilita o disabilita questa opzione.';
            }
            if (type === 'file') {
                return 'Carica il file richiesto per questa configurazione.';
            }
            if (type === 'number' || type === 'range') {
                return 'Imposta un valore numerico per questa configurazione.';
            }
            if (type === 'date') {
                return 'Imposta la data per questa configurazione.';
            }
            if (type === 'email') {
                return 'Inserisci un indirizzo email valido per questa configurazione.';
            }
            if (type === 'url') {
                return 'Inserisci un URL valido per questa configurazione.';
            }
            if (type === 'color') {
                return 'Scegli un colore per questa configurazione.';
            }
            return 'Inserisci il valore richiesto per questa configurazione.';
        }
        return 'Configura questa opzione.';
    }
    function applyConfigTooltips(root) {
        var scope = root || document;
        var groups = scope.querySelectorAll('.form-group, .form-check');
        groups.forEach(function(group) {
            var label = group.querySelector('label');
            if (!label) {
                return;
            }
            var existingTooltip = label.querySelector('[data-toggle="tooltip"]');
            var existingTitle = existingTooltip ? (existingTooltip.getAttribute('title') || '').trim() : '';
            if (label.hasAttribute('data-no-tooltip')) {
                return;
            }
            if (label.closest('.form-check') && group.closest('[data-no-checkbox-tooltips="1"]')) {
                label.removeAttribute('data-toggle');
                label.removeAttribute('data-placement');
                label.removeAttribute('title');
                label.querySelectorAll('.config-tooltip, [data-toggle="tooltip"], .fa-question-circle').forEach(function(node) {
                    var wrapper = node.classList && node.classList.contains('config-tooltip') ? node : node.closest('.config-tooltip');
                    if (wrapper && wrapper.parentNode) {
                        wrapper.parentNode.removeChild(wrapper);
                        return;
                    }
                    if (node && node.parentNode) {
                        node.parentNode.removeChild(node);
                    }
                });
                var control = group.querySelector('input, select, textarea');
                if (control) {
                    control.removeAttribute('data-toggle');
                    control.removeAttribute('data-placement');
                    control.removeAttribute('title');
                }
                return;
            }
            // Rimuovi tooltip da label/input, saranno messi solo sull'icona.
            label.removeAttribute('data-toggle');
            label.removeAttribute('data-placement');
            label.removeAttribute('title');
            group.querySelectorAll('.config-tooltip, [data-toggle="tooltip"], .fa-question-circle').forEach(function(node) {
                var wrapper = node.classList && node.classList.contains('config-tooltip') ? node : node.closest('.config-tooltip');
                if (wrapper && wrapper.parentNode) {
                    wrapper.parentNode.removeChild(wrapper);
                    return;
                }
                if (node && node.parentNode) {
                    node.parentNode.removeChild(node);
                }
            });
            var control = group.querySelector('input, select, textarea');
            if (control) {
                control.removeAttribute('data-toggle');
                control.removeAttribute('data-placement');
                control.removeAttribute('title');
            }
            var help = group.querySelector('.form-text.d-none');
            if (!help) {
                help = group.querySelector('.form-text');
                if (help) {
                    var helpText = help.textContent.trim();
                    if (/^(Attuale:|Anteprima)/i.test(helpText)) {
                        help = null;
                    }
                }
            }
            var title = '';
            if (help) {
                title = help.textContent.trim();
            } else if (existingTitle) {
                title = existingTitle;
            } else {
                title = defaultTooltipFor(control);
            }
            title = title.replace(/\s+/g, ' ');
            var icon = document.createElement('span');
            icon.className = 'text-muted ml-1 config-tooltip';
            icon.setAttribute('data-toggle', 'tooltip');
            icon.setAttribute('data-placement', 'top');
            icon.setAttribute('title', title);
            icon.innerHTML = '<i class="fa fa-question-circle"></i>';
            label.appendChild(icon);
        });
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.tooltip === 'function') {
            jQuery(function($) {
                $('[data-toggle="tooltip"]', scope).tooltip();
            });
        }
    }

    var form = document.getElementById('config-form');
    if (!form) {
        return;
    }
    window.applyConfigTooltips = applyConfigTooltips;
    applyConfigTooltips(form);
})();

(function() {
    var form = document.getElementById('config-form');
    if (!form) {
        return;
    }

    var maxBytes = parseInt(form.getAttribute('data-config-upload-max-bytes') || '0', 10);
    var maxLabel = form.getAttribute('data-config-upload-max-label') || '2 MB';
    if (!maxBytes || maxBytes < 1) {
        return;
    }

    Array.prototype.slice.call(form.querySelectorAll('.custom-file-input')).forEach(function(input) {
        var fileLabel = input.nextElementSibling;
        if (fileLabel && !fileLabel.hasAttribute('data-default-label')) {
            fileLabel.setAttribute('data-default-label', fileLabel.textContent.trim() || 'Scegli file...');
        }
    });

    function fileSizeMb(bytes) {
        return (bytes / 1024 / 1024).toFixed(2).replace('.', ',');
    }

    function showUploadError(messages) {
        var alerts = document.getElementById('config-form-alerts');
        if (!alerts) {
            window.alert(messages.join('\n'));
            return;
        }
        var previous = alerts.querySelector('.config-upload-validation-alert');
        if (previous) {
            previous.remove();
        }
        var html = '<div class="alert alert-danger alert-dismissible mb-3 config-upload-validation-alert">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Chiudi"><span aria-hidden="true">&times;</span></button>' +
            '<ul class="mb-0 pl-3">' + messages.map(function(message) {
                return '<li>' + String(message).replace(/[&<>"']/g, function(char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                }) + '</li>';
            }).join('') + '</ul></div>';
        alerts.insertAdjacentHTML('afterbegin', html);
        if (typeof jQuery !== 'undefined') {
            jQuery('html, body').animate({ scrollTop: jQuery(alerts).offset().top - 100 }, 300);
        } else {
            alerts.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function collectOversizedFiles() {
        var messages = [];
        Array.prototype.slice.call(form.querySelectorAll('input[type="file"]')).forEach(function(input) {
            Array.prototype.slice.call(input.files || []).forEach(function(file) {
                if (file.size > maxBytes) {
                    var label = input.closest('.form-group') ? input.closest('.form-group').querySelector('label') : null;
                    var fieldName = label ? label.textContent.replace(/\s+/g, ' ').trim() : (input.name || 'File');
                    messages.push(fieldName + ': "' + file.name + '" dimensione ' + fileSizeMb(file.size) + ' MB. Limite: ' + maxLabel + '.');
                }
            });
        });
        return messages;
    }

    function collectMissingPageContainerImages() {
        var messages = [];
        Array.prototype.slice.call(form.querySelectorAll('.public-page-container-panel')).forEach(function(panel) {
            var mode = panel.querySelector('.public-page-container-mode');
            if (!mode || mode.value !== 'image') {
                return;
            }
            var imageInput = panel.querySelector('.public-page-container-image-input');
            var hiddenImage = panel.querySelector('input[type="hidden"][name$="[image]"]');
            var hasSelectedFile = !!(imageInput && imageInput.files && imageInput.files.length);
            var hasSavedImage = !!(hiddenImage && hiddenImage.value && hiddenImage.value.trim());
            if (hasSelectedFile || hasSavedImage) {
                return;
            }
            var title = panel.querySelector('h6');
            var label = title ? title.textContent.replace(/\s+/g, ' ').trim() : 'Container pagina';
            messages.push(label + ': seleziona un\'immagine oppure imposta la modalita su Colore.');
        });
        return messages;
    }

    function restoreFileInputPreview(input) {
        if (!input) {
            return;
        }
        input.value = '';

        var fileLabel = input.nextElementSibling;
        if (fileLabel) {
            fileLabel.textContent = fileLabel.getAttribute('data-default-label') || 'Scegli file...';
        }

        var targetId = input.getAttribute('data-preview-target');
        if (!targetId) {
            return;
        }
        var img = document.getElementById(targetId);
        if (!img) {
            return;
        }
        var current = (img.getAttribute('data-current-src') || '').trim();
        if (current) {
            img.src = current;
            img.classList.remove('d-none');
        } else {
            img.classList.add('d-none');
            img.removeAttribute('src');
        }
    }

    function resetOversizedFileInputs() {
        Array.prototype.slice.call(form.querySelectorAll('input[type="file"]')).forEach(function(input) {
            var hasOversizedFile = Array.prototype.slice.call(input.files || []).some(function(file) {
                return file.size > maxBytes;
            });
            if (hasOversizedFile) {
                restoreFileInputPreview(input);
            }
        });
    }

    form.addEventListener('submit', function(event) {
        var messages = collectOversizedFiles().concat(collectMissingPageContainerImages());
        if (messages.length) {
            event.preventDefault();
            event.stopPropagation();
            resetOversizedFileInputs();
            showUploadError(messages);
        }
    });

    form.addEventListener('change', function(event) {
        var input = event.target;
        if (!input || input.type !== 'file' || !input.files || !input.files.length) {
            return;
        }
        var messages = collectOversizedFiles();
        if (messages.length) {
            resetOversizedFileInputs();
            showUploadError(messages);
        }
    });
})();

if (typeof window.bootCediWeekPlanner === 'function') {
    window.bootCediWeekPlanner();
}

(function () {
    var $toggle = $('#cookie_consent_enabled');
    var $details = $('#cookie-consent-details');
    if (!$toggle.length || !$details.length) {
        return;
    }

    function syncDetailsVisibility() {
        if ($toggle.is(':checked')) {
            $details.removeClass('d-none');
            updateCcPreview();
        } else {
            $details.addClass('d-none');
        }
        if (typeof syncFooterCookiePolicyBlockFromBanner === 'function') {
            syncFooterCookiePolicyBlockFromBanner();
        }
    }

    function cookieRowHtml(prefix) {
        return (
            '<div class="form-row align-items-end cc-cookie-row mb-2">' +
            '<div class="form-group col-md-3 mb-1"><label class="small mb-0">Nome</label><input type="text" class="form-control form-control-sm" name="' + prefix + '_name[]" value=""></div>' +
            '<div class="form-group col-md-3 mb-1"><label class="small mb-0">Finalità</label><input type="text" class="form-control form-control-sm" name="' + prefix + '_purpose[]" value=""></div>' +
            '<div class="form-group col-md-2 mb-1"><label class="small mb-0">Durata</label><input type="text" class="form-control form-control-sm" name="' + prefix + '_duration[]" value=""></div>' +
            '<div class="form-group col-md-3 mb-1"><label class="small mb-0">Fornitore</label><input type="text" class="form-control form-control-sm" name="' + prefix + '_provider[]" value=""></div>' +
            '<div class="form-group col-md-1 mb-1"><button type="button" class="btn btn-sm btn-outline-danger cc-cookie-remove" title="Rimuovi">&times;</button></div>' +
            '</div>'
        );
    }

    $('#cc-add-tech-cookie').on('click', function () {
        $('#cc-cookies-technical').append(cookieRowHtml('cookie_consent_tech'));
    });
    $(document).on('click', '.cc-cookie-remove', function () {
        var $row = $(this).closest('.cc-cookie-row');
        if ($row.data('cc-core')) {
            return;
        }
        var $wrap = $(this).closest('.cc-cookie-rows');
        $row.remove();
        if ($wrap.find('.cc-cookie-row').length === 0) {
            $wrap.append(cookieRowHtml('cookie_consent_tech'));
        }
    });

    function styleVal(key) {
        var $hex = $('.js-cc-color-hex[data-cc-style="' + key + '"]');
        return $hex.length ? String($hex.val() || '') : '';
    }

    function paletteHex(name, fallback) {
        var $el = $('#colors_' + name + '_text');
        if (!$el.length) {
            $el = $('input[name="colors_' + name + '"]');
        }
        var v = $el.length ? String($el.val() || '').trim() : '';
        return /^#[0-9A-Fa-f]{6}$/.test(v) ? v : fallback;
    }

    function buttonHex(which, prop, fallback) {
        var map = {
            bg: 'buttons_' + which + '_bg',
            text: 'buttons_' + which + '_text_color'
        };
        var name = map[prop];
        if (!name) return fallback;
        var $el = $('#' + name + '_text');
        if (!$el.length) {
            $el = $('input[name="' + name + '"]');
        }
        var v = $el.length ? String($el.val() || '').trim() : '';
        return /^#[0-9A-Fa-f]{6}$/.test(v) ? v : fallback;
    }

    function resolveCcPreviewColors() {
        var primary = paletteHex('primary', '#0d6efd');
        var secondary = paletteHex('secondary', '#6c757d');
        var tertiary = paletteHex('tertiary', '#0a58ca');
        var text = paletteHex('text', '#212529');
        var custom = $('#style_customization_cookie_banner_colors').is(':checked');
        if (custom) {
            return {
                bg: styleVal('bg_color') || '#ffffff',
                textC: styleVal('text_color') || text,
                link: styleVal('link_color') || secondary,
                linkHover: tertiary,
                border: styleVal('border_color') || primary,
                btnBg: styleVal('button_bg') || primary,
                btnText: styleVal('button_text') || '#ffffff',
                btn2Bg: styleVal('button_secondary_bg') || '#ffffff',
                btn2Text: styleVal('button_secondary_text') || secondary,
                btn2Hover: tertiary
            };
        }
        var btnCustom = $('#style_customization_buttons_colors').is(':checked');
        var btnBg = primary;
        var btnText = '#ffffff';
        var btn2Bg = '#ffffff';
        var btn2Text = secondary;
        if (btnCustom) {
            btnBg = buttonHex('primary', 'bg', primary);
            btnText = buttonHex('primary', 'text', '#ffffff');
            btn2Bg = buttonHex('secondary', 'bg', '#ffffff');
            btn2Text = buttonHex('secondary', 'text', secondary);
        }
        return {
            bg: '#ffffff',
            textC: text,
            link: secondary,
            linkHover: tertiary,
            border: primary,
            btnBg: btnBg,
            btnText: btnText,
            btn2Bg: btn2Bg,
            btn2Text: btn2Text,
            btn2Hover: tertiary
        };
    }

    function updateCcPreview() {
        var text = $('#cookie_consent_text').val() || '';
        var policy = $('#cookie_consent_policy_label').val() || '';
        var accept = $('#cookie_consent_accept_label').val() || '';
        var prefs = $('#cookie_consent_preferences_label').val() || '';
        var save = $('#cookie_consent_save_label').val() || '';
        var necT = $('#cookie_consent_necessary_title').val() || '';
        var necD = $('#cookie_consent_necessary_desc').val() || '';
        var mktT = $('#cookie_consent_marketing_title').val() || '';
        var mktD = $('#cookie_consent_marketing_desc').val() || '';

        $('#cc-prev-text').text(text);
        $('#cc-prev-policy').text(policy);
        $('#cc-prev-accept').text(accept);
        $('#cc-prev-prefs').text(prefs);
        $('#cc-prev-save').text(save);
        $('#cc-prev-modal-title').text(prefs);
        $('#cc-prev-nec-title').text(necT);
        $('#cc-prev-nec-desc').text(necD);
        $('#cc-prev-mkt-title').text(mktT);
        $('#cc-prev-mkt-desc').text(mktD);

        var c = resolveCcPreviewColors();
        var $banner = $('#cc-preview-banner');
        $banner.css({ background: c.bg, color: c.textC, borderTop: '3px solid ' + c.border });
        $('#cc-prev-policy').css('color', c.link);
        $('#cc-prev-accept').css({ background: c.btnBg, color: c.btnText, borderColor: c.btnBg });
        $('#cc-prev-prefs').css({ background: c.btn2Bg, color: c.btn2Text, borderColor: c.btn2Text });
        $('#cc-prev-save').css({ background: c.btnBg, color: c.btnText, borderColor: c.btnBg });
        $('#cc-preview-modal').css({ background: c.bg, color: c.textC, borderTop: '3px solid ' + c.border });
        $('#cc-prev-modal-title').css('color', c.link);
        $('#cc-prev-nec-badge').css('color', c.link);
        $('#cc-prev-nec-desc, #cc-prev-mkt-desc').css({ color: c.textC, opacity: 0.85 });

        var $hoverStyle = $('#cc-preview-hover-style');
        if (!$hoverStyle.length) {
            $hoverStyle = $('<style id="cc-preview-hover-style"></style>').appendTo('head');
        }
        $hoverStyle.text(
            '#cc-prev-policy:hover,#cc-prev-policy:focus{color:' + c.linkHover + ' !important;}' +
            '#cc-prev-prefs:hover,#cc-prev-prefs:focus{color:' + c.btn2Hover + ' !important;border-color:' + c.btn2Hover + ' !important;}'
        );
    }

    $toggle.on('change', syncDetailsVisibility);
    $('#style_customization_cookie_banner_colors').on('change', function () {
        var on = $(this).is(':checked');
        $('#cc-colors-panel').toggle(on);
        $('#cc-colors-palette-hint').toggle(!on);
        updateCcPreview();
    });
    $(document).on('input change', '.js-cc-preview-input, .js-cc-color, .js-cc-color-hex, #colors_primary_text, #colors_secondary_text, #colors_tertiary_text, #colors_text_text, #style_customization_buttons_colors, input[name^="colors_"], input[name^="buttons_"]', function () {
        var $el = $(this);
        if ($el.hasClass('js-cc-color')) {
            var key = $el.data('cc-style');
            $('.js-cc-color-hex[data-cc-style="' + key + '"]').val($el.val());
        }
        if ($el.hasClass('js-cc-color-hex')) {
            var hex = String($el.val() || '');
            if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                $('.js-cc-color[data-cc-style="' + $el.data('cc-style') + '"]').val(hex);
            }
        }
        updateCcPreview();
    });

    syncDetailsVisibility();
})();
