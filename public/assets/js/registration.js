document.addEventListener('DOMContentLoaded', () => {
    const fieldsEnabled = window.FIELDS_ENABLED || {};
    const fieldsRequired = window.FIELDS_REQUIRED || {};
    if (fieldsEnabled.scontrino === false) {
        fieldsEnabled.importo = false;
        fieldsEnabled.data_scontrino = false;
    }
    const maxReceiptSizeBytes = Number(window.MAX_FILE_SIZE_BYTES) > 0 ? Number(window.MAX_FILE_SIZE_BYTES) : 5 * 1024 * 1024;
    const maxReceiptSizeMb = Math.max(1, Math.ceil(maxReceiptSizeBytes / (1024 * 1024)));
    const receiptAllowedExtensions = Array.isArray(window.RECEIPT_ALLOWED_EXTENSIONS) && window.RECEIPT_ALLOWED_EXTENSIONS.length
        ? window.RECEIPT_ALLOWED_EXTENSIONS.map(ext => String(ext).toLowerCase())
        : ['jpg', 'jpeg', 'png', 'pdf'];
    const receiptAllowedLabel = (typeof window.RECEIPT_ALLOWED_LABEL === 'string' && window.RECEIPT_ALLOWED_LABEL.trim())
        ? window.RECEIPT_ALLOWED_LABEL
        : '';
    const importoInvalidMsg = "l'importo dello scontrino deve essere un numero valido. inclusi decimali, senza virgole o punti";

    const parseImportoScontrino = (raw) => {
        let s = String(raw || '').trim().replace(/\u00A0/g, ' ').replace(/€/g, '').trim();
        if (!s) {
            return null;
        }
        if (/^\d+\s+\d{1,2}$/.test(s)) {
            s = s.replace(/\s+/, '.');
        } else {
            s = s.replace(/\s+/g, '');
        }
        if (s.includes(',') && s.includes('.')) {
            return null;
        }
        if ((s.match(/,/g) || []).length > 1 || (s.match(/\./g) || []).length > 1) {
            return null;
        }
        s = s.replace(',', '.');
        if (!/^\d+(?:\.\d{1,2})?$/.test(s)) {
            return null;
        }
        const num = parseFloat(s);
        return Number.isNaN(num) ? null : Math.round(num * 100) / 100;
    };

    const formatList = (items) => {
        const cleaned = items.filter(Boolean);
        if (!cleaned.length) return '';
        if (cleaned.length === 1) return cleaned[0];
        if (cleaned.length === 2) return `${cleaned[0]} o ${cleaned[1]}`;
        const last = cleaned.pop();
        return `${cleaned.join(', ')} o ${last}`;
    };
    const extList = receiptAllowedExtensions.map(ext => String(ext).toUpperCase());
    const receiptAllowedExtLabel = formatList(extList);
    const receiptLabelText = receiptAllowedExtLabel || receiptAllowedLabel || 'JPG, PNG o PDF';
    const syncReceiptLabel = () => {
        const label = document.querySelector('label[for=\"scontrino\"]');
        if (!label) return;
        label.textContent = `Foto scontrino (${receiptLabelText}, max ${maxReceiptSizeMb} MB) *`;
    };
    syncReceiptLabel();
    const fidelityInput = document.getElementById('fidelity');

    const fidelityConfig = (() => {
        if (!fidelityInput) return { type: 'numeric', length: 13 };
        const typeAttr = (fidelityInput.dataset.fidelityType || fidelityInput.dataset.type || 'numeric').toLowerCase();
        const lenAttr = parseInt(fidelityInput.dataset.fidelityLength, 10);
        return {
            type: ['numeric', 'alphanumeric'].includes(typeAttr) ? typeAttr : 'numeric',
            length: Number.isInteger(lenAttr) && lenAttr > 0 ? lenAttr : 13
        };
    })();

    const syncSelect2InvalidState = (field, invalid) => {
        if (!field || field.tagName !== 'SELECT') {
            return;
        }
        const container = field.nextElementSibling;
        if (container && container.classList.contains('select2-container')) {
            const selection = container.querySelector('.select2-selection');
            if (selection) {
                selection.classList.toggle('is-invalid', invalid);
            }
        }
    };

    const syncFlatpickrInvalidState = (field, invalid) => {
        if (!field) {
            return;
        }
        const fp = field._flatpickr;
        if (!fp) {
            return;
        }
        const visibles = [];
        if (fp.altInput) {
            visibles.push(fp.altInput);
        }
        if (fp.input && fp.input !== field) {
            visibles.push(fp.input);
        }
        visibles.forEach((el) => {
            el.classList.toggle('is-invalid', !!invalid);
            if (!invalid) {
                el.classList.remove('is-valid');
            }
        });
    };

    const parseYmdDate = (value) => {
        const raw = String(value || '').trim();
        if (!raw) {
            return null;
        }
        let year;
        let month;
        let day;
        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
            const parts = raw.split('-');
            year = parseInt(parts[0], 10);
            month = parseInt(parts[1], 10);
            day = parseInt(parts[2], 10);
        } else if (/^\d{2}\/\d{2}\/\d{4}$/.test(raw)) {
            const parts = raw.split('/');
            day = parseInt(parts[0], 10);
            month = parseInt(parts[1], 10);
            year = parseInt(parts[2], 10);
        } else {
            return null;
        }
        if (!year || !month || !day) {
            return null;
        }
        const dt = new Date(year, month - 1, day);
        if (Number.isNaN(dt.getTime()) || dt.getFullYear() !== year || dt.getMonth() !== month - 1 || dt.getDate() !== day) {
            return null;
        }
        dt.setHours(0, 0, 0, 0);
        return dt;
    };

    const formatYmd = (dt) => {
        if (!(dt instanceof Date) || Number.isNaN(dt.getTime())) {
            return '';
        }
        const y = dt.getFullYear();
        const m = String(dt.getMonth() + 1).padStart(2, '0');
        const d = String(dt.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    const getReceiptDateLimits = () => {
        const field = document.getElementById('data_scontrino');
        const start = parseYmdDate(
            (field && (field.getAttribute('data-min') || field.getAttribute('min')))
            || window.CONTEST_START_DATE
            || ''
        );
        let end = parseYmdDate(
            (field && (field.getAttribute('data-max') || field.getAttribute('max')))
            || window.CONTEST_END_DATE
            || ''
        );
        const today = parseYmdDate(window.TODAY_DATE || formatYmd(new Date())) || (() => {
            const t = new Date();
            t.setHours(0, 0, 0, 0);
            return t;
        })();
        if (!end || end.getTime() > today.getTime()) {
            end = today;
        }
        return { start, end, today };
    };

    const escapeHtml = (str) => String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const clearRegistrationErrorSummary = () => {
        const wrap = document.getElementById('error-messages');
        if (wrap) {
            wrap.remove();
        }
    };

    const updateRegistrationErrorSummary = (messages) => {
        const form = document.getElementById('registration-form');
        if (!form) {
            return;
        }
        const unique = [];
        (messages || []).forEach((raw) => {
            let text = String(raw || '').trim();
            if (!text) {
                return;
            }
            if (text.charAt(0) !== '-') {
                text = '- ' + text;
            }
            if (!unique.includes(text)) {
                unique.push(text);
            }
        });
        if (!unique.length) {
            clearRegistrationErrorSummary();
            return;
        }
        let wrap = document.getElementById('error-messages');
        const html = '<ul class="list-unstyled mb-0"><li>' + unique.map(escapeHtml).join('</li><li>') + '</li></ul>';
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'error-messages';
            wrap.className = 'alert alert-danger mb-4';
            form.parentElement.insertBefore(wrap, form);
        }
        wrap.innerHTML = html;
    };

    const collectInlineErrorMessages = () => {
        const form = document.getElementById('registration-form');
        if (!form) {
            return [];
        }
        return Array.from(form.querySelectorAll('.error-message.d-block'))
            .map((el) => (el.textContent || '').trim())
            .filter(Boolean);
    };

    const showError = (id, message) => {
        const field = document.getElementById(id);
        if (!field) return;
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        syncSelect2InvalidState(field, true);
        syncFlatpickrInvalidState(field, true);
        let errorDiv = document.getElementById(`${id}-error`);
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = `${id}-error`;
            errorDiv.className = 'error-message text-danger small mt-1';
            const fp = field._flatpickr;
            const select2Container = field.nextElementSibling;
            if (fp && fp.altInput) {
                fp.altInput.insertAdjacentElement('afterend', errorDiv);
            } else if (select2Container && select2Container.classList.contains('select2-container')) {
                select2Container.insertAdjacentElement('afterend', errorDiv);
            } else {
                field.insertAdjacentElement('afterend', errorDiv);
            }
        }
        // .registration-form-row--inputs .error-message:not(.d-block) { display:none !important }
        errorDiv.classList.add('d-block');
        errorDiv.style.display = 'block';
        const text = String(message || '').trim();
        errorDiv.textContent = text && text.charAt(0) !== '-' ? ('- ' + text) : text;
    };

    const clearError = (id) => {
        const field = document.getElementById(id);
        if (!field) return;
        field.classList.remove('is-invalid');
        syncSelect2InvalidState(field, false);
        syncFlatpickrInvalidState(field, false);
        let errorDiv = document.getElementById(`${id}-error`);
        if (errorDiv) {
            errorDiv.classList.remove('d-block');
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';
        }
    };

    const cleanFidelity = (val) => fidelityConfig.type === 'alphanumeric'
        ? val.replace(/[^A-Za-z0-9]/g, '')
        : val.replace(/\D/g, '');

    const alphaRegex = /^[A-Za-zÀ-ÖØ-öø-ÿ \-']+$/;

    const fieldEnabled = (field) => fieldsEnabled[field] !== false && fieldsEnabled[field] !== 'false' && fieldsEnabled[field] !== 0 && fieldsEnabled[field] !== '0';
    const fieldRequired = (field) => fieldEnabled(field) && fieldsRequired[field] !== false && fieldsRequired[field] !== 'false' && fieldsRequired[field] !== 0 && fieldsRequired[field] !== '0';

    // Fonction pour formater une date YYYY-MM-DD en format italien dd/mm/yyyy
    const formatDateForDisplay = (dateStr) => {
        if (!dateStr) return '';
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return dateStr;
    };

    const validate = (onlyFields = null, collectList = true) => {
        let errors = [];
        const shouldCheck = (field) => {
            if (onlyFields && !onlyFields.includes(field)) {
                return false;
            }
            if (fieldRequired(field)) {
                return true;
            }
            const pdvMode = (typeof window.PDV_MODE !== 'undefined' ? window.PDV_MODE : 'combinato');
            const ids = field === 'indirizzo_pdv'
                ? (pdvMode === 'cascata'
                    ? ['prov_pdv', 'citta_pdv', 'indirizzo_pdv']
                    : ['indirizzo_pdv_combinato'])
                : [field];
            const hasValue = ids.some((id) => {
                const el = document.getElementById(id);
                if (!el) {
                    return false;
                }
                if (el.type === 'file') {
                    return !!(el.files && el.files.length);
                }
                if (el.type === 'checkbox' || el.type === 'radio') {
                    return el.checked;
                }
                return String(el.value || '').trim() !== '';
            });
            if (!hasValue) {
                ids.forEach(clearError);
            }
            return hasValue;
        };

        if (fieldEnabled('nome') && shouldCheck('nome')) {
            const v = (document.getElementById('nome')?.value || '').trim().replace(/\s+/g, ' ');
            const required = fieldRequired('nome');
            if (required && v.length < 2) showError('nome', 'Il nome è obbligatorio e deve contenere almeno 2 caratteri.');
            else if (!required && !v) clearError('nome');
            else if (v.length > 50) showError('nome', 'Il nome non può superare 50 caratteri.');
            else if (v.length < 2) showError('nome', 'Il nome deve contenere almeno 2 caratteri.');
            else if (!alphaRegex.test(v)) showError('nome', 'Il nome può contenere solo lettere, spazi, trattini e apostrofo (\').');
            else clearError('nome');
            if (collectList && ((required && v.length < 2) || (!required && v && v.length < 2) || v.length > 50 || (v && !alphaRegex.test(v)))) errors.push('Il nome non è stato inserito correttamente.');
        }

        if (fieldEnabled('cognome') && shouldCheck('cognome')) {
            const v = (document.getElementById('cognome')?.value || '').trim().replace(/\s+/g, ' ');
            const required = fieldRequired('cognome');
            if (required && v.length < 2) showError('cognome', 'Il cognome è obbligatorio e deve contenere almeno 2 caratteri.');
            else if (!required && !v) clearError('cognome');
            else if (v.length > 50) showError('cognome', 'Il cognome non può superare 50 caratteri.');
            else if (v.length < 2) showError('cognome', 'Il cognome deve contenere almeno 2 caratteri.');
            else if (!alphaRegex.test(v)) showError('cognome', 'Il cognome può contenere solo lettere, spazi, trattini e apostrofo (\').');
            else clearError('cognome');
            if (collectList && ((required && v.length < 2) || (!required && v && v.length < 2) || v.length > 50 || (v && !alphaRegex.test(v)))) errors.push('Il cognome non è stato inserito correttamente.');
        }

        if (fieldsEnabled.email !== false && shouldCheck('email')) {
            const v = (document.getElementById('email')?.value || '').trim().toLowerCase();
            document.getElementById('email').value = v;
            if (!v) showError('email', "L'email è obbligatoria.");
            else if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(v)) showError('email', 'Inserisci un indirizzo email valido.');
            else if (v.length > 254) showError('email', "L'email non può superare 254 caratteri.");
            else clearError('email');
            if (collectList && (!v || !/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(v) || v.length > 254)) errors.push("L'email è obbligatoria.");
        }

        if (fieldsEnabled.prefisso !== false && shouldCheck('prefisso')) {
            const el = document.getElementById('prefisso');
            let v = (el?.value || '').trim();
            if (v && !v.startsWith('+')) v = `+${v.replace(/^\+/, '')}`;
            v = v.replace(/[^\d+]/g, '').slice(0, 4);
            if (el) el.value = v || '+';
            if (!/^\+\d{1,3}$/.test(v)) {
                showError('prefisso', 'Inserisci un prefisso valido (es. +39).');
                if (collectList) errors.push('Prefisso dovrebbe contenere al massimo 3 caratteri.');
            } else clearError('prefisso');
        }

        if (fieldsEnabled.cellulare !== false && shouldCheck('cellulare')) {
            const el = document.getElementById('cellulare');
            let v = (el?.value || '').replace(/\s/g, '').replace(/\D/g, '');
            if (el) el.value = v;
            const digits = v;
            const minD = Math.max(6, Math.min(12, Number(window.CELLULARE_DIGITS_MIN) || 6));
            const maxD = Math.max(6, Math.min(12, Number(window.CELLULARE_DIGITS_MAX) || 12));
            const maxDigits = Math.max(minD, maxD);
            if (!v || digits.length < minD || digits.length > maxDigits) {
                const cellulareMsg = minD === maxDigits
                    ? 'Il numero di cellulare è obbligatorio e deve contenere esattamente ' + minD + ' cifre.'
                    : 'Il numero di cellulare è obbligatorio e deve contenere tra ' + minD + ' e ' + maxDigits + ' cifre.';
                showError('cellulare', cellulareMsg);
                if (collectList) errors.push(cellulareMsg);
            } else clearError('cellulare');
        }

        if (fieldsEnabled.cap !== false && fieldEnabled('cap') && shouldCheck('cap')) {
            const el = document.getElementById('cap');
            if (el) {
                let v = String(el.value || '').replace(/\D/g, '').slice(0, 5);
                el.value = v;
                const required = fieldRequired('cap');
                if (required && v.length !== 5) {
                    const capMsg = 'Il CAP è obbligatorio e deve contenere esattamente 5 cifre.';
                    showError('cap', capMsg);
                    if (collectList) errors.push(capMsg);
                } else if (!required && v === '') {
                    clearError('cap');
                } else if (v !== '' && v.length !== 5) {
                    const capMsg = 'Il CAP deve contenere esattamente 5 cifre.';
                    showError('cap', capMsg);
                    if (collectList) errors.push(capMsg);
                } else {
                    clearError('cap');
                }
            }
        }

        const provinciaMustValidate = fieldEnabled('provincia') && (
            (fieldRequired('provincia') && shouldCheck('provincia'))
            || (fieldEnabled('citta') && fieldRequired('citta') && shouldCheck('citta'))
        );
        if (provinciaMustValidate) {
            const v = (document.getElementById('provincia')?.value || '').trim();
            const provinciaRequiredNow = fieldRequired('provincia')
                || (fieldEnabled('citta') && fieldRequired('citta'));
            if (provinciaRequiredNow && !v) {
                const msg = 'Seleziona una provincia.';
                showError('provincia', msg);
                if (collectList) errors.push(msg);
            } else {
                clearError('provincia');
            }
        }

        if (fieldEnabled('citta') && shouldCheck('citta')) {
            const v = (document.getElementById('citta')?.value || '').trim();
            if (fieldRequired('citta') && !v) {
                const msg = 'Seleziona una città.';
                showError('citta', msg);
                if (collectList) errors.push(msg);
            } else {
                clearError('citta');
            }
        }

        if (fieldEnabled('indirizzo') && shouldCheck('indirizzo')) {
            const v = (document.getElementById('indirizzo')?.value || '').trim();
            if (fieldRequired('indirizzo') && !v) {
                const msg = "L'indirizzo è obbligatorio.";
                showError('indirizzo', msg);
                if (collectList) errors.push(msg);
            } else {
                clearError('indirizzo');
            }
        }

        if (fieldsEnabled.indirizzo_pdv !== false && shouldCheck('indirizzo_pdv')) {
            const pdvMode = (typeof window.PDV_MODE !== 'undefined' ? window.PDV_MODE : 'combinato');
            if (pdvMode === 'cascata') {
                const provVal = document.getElementById('prov_pdv')?.value || '';
                const cittaVal = document.getElementById('citta_pdv')?.value || '';
                const indirizzoVal = document.getElementById('indirizzo_pdv')?.value || '';
                if (!provVal) {
                    showError('prov_pdv', 'Seleziona una provincia del punto vendita.');
                    if (collectList) errors.push('Seleziona una provincia del punto vendita.');
                } else clearError('prov_pdv');
                if (!cittaVal) {
                    showError('citta_pdv', 'Seleziona una città del punto vendita.');
                    if (collectList) errors.push('Seleziona una città del punto vendita.');
                } else clearError('citta_pdv');
                if (!indirizzoVal) {
                    showError('indirizzo_pdv', 'Seleziona un punto vendita.');
                    if (collectList) errors.push('Seleziona un punto vendita.');
                } else clearError('indirizzo_pdv');
            } else {
                // Combinato: il select visibile ha id indirizzo_pdv_combinato (name resta indirizzo_pdv)
                const pdvFieldId = 'indirizzo_pdv_combinato';
                const v = document.getElementById(pdvFieldId)?.value || '';
                if (!v) {
                    showError(pdvFieldId, 'Seleziona un punto vendita.');
                    if (collectList) errors.push('Seleziona un punto vendita.');
                } else clearError(pdvFieldId);
            }
        }

        if (fieldsEnabled.importo !== false && shouldCheck('importo')) {
            const el = document.getElementById('importo');
            const v = (el?.value || '').trim();
            const num = parseImportoScontrino(v);
            const minamount = Number(window.MIN_SPEND || 0);
            const maxamount = 9999.99;
            if (num === null) {
                showError('importo', importoInvalidMsg);
                if (collectList) errors.push(importoInvalidMsg);
            } else if (num < 0) {
                const msg = "L'importo non può essere negativo.";
                showError('importo', msg);
                if (collectList) errors.push(msg);
            } else if (minamount > 0 && num < minamount) {
                const msg = "L'importo non può essere inferiore a €" + minamount.toFixed(2) + '.';
                showError('importo', msg);
                if (collectList) errors.push(msg);
            } else if (num > maxamount) {
                const msg = "L'importo non può essere superiore a €" + maxamount.toFixed(2) + '.';
                showError('importo', msg);
                if (collectList) errors.push(msg);
            } else clearError('importo');
        }

        if (fieldsEnabled.data_scontrino !== false && shouldCheck('data_scontrino')) {
            const field = document.getElementById('data_scontrino');
            const fp = field && field._flatpickr;
            let v = '';
            if (fp && fp.selectedDates && fp.selectedDates[0]) {
                v = formatYmd(fp.selectedDates[0]);
                if (field && field.value !== v) {
                    field.value = v;
                }
            } else {
                v = field?.value || '';
            }
            const requiredDate = fieldRequired('data_scontrino');
            if (!v) {
                if (requiredDate) {
                    const msg = 'La data dello scontrino è obbligatoria.';
                    showError('data_scontrino', msg);
                    if (collectList) errors.push(msg);
                } else {
                    clearError('data_scontrino');
                }
            } else {
                const receiptDate = parseYmdDate(v);
                const limits = getReceiptDateLimits();
                if (!receiptDate) {
                    const msg = 'Formato data non valido. Usa il formato GG/MM/AAAA.';
                    showError('data_scontrino', msg);
                    if (collectList) errors.push(msg);
                } else if (limits.start && receiptDate.getTime() < limits.start.getTime()) {
                    const msg = 'La data dello scontrino non può essere precedente all\'inizio del concorso.';
                    showError('data_scontrino', msg);
                    if (collectList) errors.push(msg);
                } else if (limits.end && receiptDate.getTime() > limits.end.getTime()) {
                    const msg = receiptDate.getTime() > limits.today.getTime()
                        ? 'La data dello scontrino non può essere futura.'
                        : 'La data dello scontrino non può essere successiva alla fine del concorso.';
                    showError('data_scontrino', msg);
                    if (collectList) errors.push(msg);
                } else {
                    clearError('data_scontrino');
                }
            }
        }

        if (fieldsEnabled.scontrino !== false && shouldCheck('scontrino')) {
            const input = document.getElementById('scontrino');
            const file = input?.files && input.files[0];
            if (!file) {
                showError('scontrino', 'Carica una foto dello scontrino.');
                if (collectList) errors.push('Carica una foto dello scontrino.');
            } else {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                const ok = receiptAllowedExtensions.includes(ext);
                if (!ok) {
                    const formatMsg = `Formato file non valido`;
                    showError('scontrino', formatMsg);
                    if (collectList) errors.push(formatMsg);
                } else if (file.size > maxReceiptSizeBytes) {
                    showError('scontrino', `File troppo grande. Massimo ${maxReceiptSizeMb} MB.`);
                    if (collectList) errors.push('Carica una foto dello scontrino.');
                } else clearError('scontrino');
            }
        }

        if (fieldsEnabled.privacy !== false && shouldCheck('privacy')) {
            const chk = document.getElementById('privacy');
            if (!chk || !chk.checked) {
                showError('privacy', "Devi accettare l'informativa privacy.");
                if (collectList) errors.push("Devi accettare l'informativa privacy per continuare.");
            } else clearError('privacy');
        }

        if (fieldEnabled('codice_partecipazione') && shouldCheck('codice_partecipazione')) {
            const codiceField = document.getElementById('codice_partecipazione');
            if (codiceField) {
                const rawVal = (codiceField.value || '').trim();
                const val = rawVal.replace(/\s+/g, '');
                if (rawVal !== val) {
                    codiceField.value = val;
                }
                const label = (codiceField.dataset.fieldLabel || 'Codice partecipazione').trim();
                const expectedLen = parseInt(codiceField.dataset.codiceExpectedLength || '0', 10);
                if (fieldRequired('codice_partecipazione') && !val) {
                    const requiredMsg = `- Il campo "${label}" è obbligatorio.`;
                    showError('codice_partecipazione', requiredMsg);
                    if (collectList) errors.push(requiredMsg);
                } else if (val && expectedLen > 0 && val.length !== expectedLen) {
                    const invalidMsg = `- Il campo "${label}" è errato.`;
                    showError('codice_partecipazione', invalidMsg);
                    if (collectList) errors.push(invalidMsg);
                } else {
                    clearError('codice_partecipazione');
                }
            }
        }

        if (fieldsEnabled.fidelity !== false && fidelityInput && shouldCheck('fidelity')) {
            const val = cleanFidelity(fidelityInput.value.trim());
            fidelityInput.value = val;
            const isNumeric = fidelityConfig.type === 'numeric';
            const pattern = isNumeric ? /^[0-9]+$/ : /^[A-Za-z0-9]+$/;
            const lengthOk = fidelityConfig.length ? val.length === fidelityConfig.length : val.length > 0;
            if (!val) {
                const fidRequiredMsg = 'il codice fidelity è obbligatorio.';
                showError('fidelity', fidRequiredMsg);
                if (collectList) errors.push('- ' + fidRequiredMsg);
            } else if (!pattern.test(val)) {
                showError('fidelity', 'Codice fedeltà non valido.');
                if (collectList) errors.push('Codice fidelity non valido.');
            } else if (!lengthOk) {
                const tipo = isNumeric ? 'numerici' : 'alfa-numerici';
                showError('fidelity', `Codice fedeltà deve avere ${fidelityConfig.length} caratteri ${tipo}.`);
                if (collectList) errors.push(`Il codice fidelity deve avere ${fidelityConfig.length} caratteri ${tipo}.`);
            } else clearError('fidelity');
        }

        // Validazione campi personalizzabili
        document.querySelectorAll('[id^="custom_"]').forEach(field => {
            const fieldId = field.id;
            if (onlyFields && !onlyFields.includes(fieldId)) return;
            
            const fieldType = field.type || '';
            const val = (field.value || '').trim();
            const exactLength = field.dataset.exactLength ? parseInt(field.dataset.exactLength, 10) : null;
            const maxLength = field.maxLength ? parseInt(field.maxLength, 10) : null;
            const minLength = field.minLength ? parseInt(field.minLength, 10) : null;
            const customErrorMessage = field.dataset.errorMessage || null;
            const isRequired = field.hasAttribute('required');
            const valLength = val.length;
            
            // Validazione campo obbligatorio
            if (isRequired && !val) {
                // Trouver le label associé directement au champ
                let label = 'Campo';
                
                // Méthode 1: Utiliser l'attribut data-field-label (le plus fiable pour les champs custom)
                if (field.dataset.fieldLabel) {
                    label = field.dataset.fieldLabel.trim();
                } else {
                    // Méthode 2: Chercher le label avec l'attribut 'for' correspondant
                    const labelElement = document.querySelector(`label[for="${fieldId}"]`);
                    if (labelElement) {
                        label = labelElement.textContent?.replace(' *', '').trim() || 'Campo';
                    } else {
                        // Méthode 3: Utiliser field.labels (propriété native du DOM)
                        if (field.labels && field.labels.length > 0) {
                            label = field.labels[0].textContent?.replace(' *', '').trim() || 'Campo';
                        } else {
                            // Méthode 4: Chercher dans le parent immédiat (col-md-6, col-12, form-check)
                            const parentCol = field.closest('.col-md-6, .col-12, .form-check');
                            if (parentCol) {
                                const labelInParent = parentCol.querySelector(`label[for="${fieldId}"]`);
                                if (labelInParent) {
                                    label = labelInParent.textContent?.replace(' *', '').trim() || 'Campo';
                                }
                            }
                        }
                    }
                }
                
                showError(fieldId, `Il campo "${label}" è obbligatorio.`);
                if (collectList) errors.push(`Il campo "${label}" è obbligatorio.`);
                return;
            }
            
            // Validation des champs de type date
            if (fieldType === 'date' && val !== '') {
                const dateMin = field.min || null;
                const dateMax = field.max || null;
                const dateValue = val; // Format YYYY-MM-DD
                
                if (dateMin && dateValue < dateMin) {
                    const errorMsg = customErrorMessage || `La data non può essere precedente a ${formatDateForDisplay(dateMin)}.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                if (dateMax && dateValue > dateMax) {
                    const errorMsg = customErrorMessage || `La data non può essere successiva a ${formatDateForDisplay(dateMax)}.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                clearError(fieldId);
                return;
            }
            
            // Mode codice numerico (CAP): data-int-exact-digits su type=text
            const intExactDigits = field.dataset.intExactDigits ? parseInt(field.dataset.intExactDigits, 10) : null;
            if (intExactDigits !== null && !isNaN(intExactDigits) && intExactDigits > 0 && val !== '') {
                if (!new RegExp('^\\d{' + intExactDigits + '}$').test(val)) {
                    const errorMsg = customErrorMessage || `Il campo deve contenere esattamente ${intExactDigits} cifre.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                clearError(fieldId);
                return;
            }

            // Validation des champs de type number (INT classico, senza cifre esatte)
            if (fieldType === 'number' && val !== '') {
                const numValue = parseInt(val, 10);
                const fieldMin = field.min !== '' ? parseInt(field.min, 10) : null;
                const fieldMax = field.max !== '' ? parseInt(field.max, 10) : null;
                
                // Vérifier que c'est un nombre valide
                if (isNaN(numValue)) {
                    const errorMsg = customErrorMessage || `Il campo deve essere un numero intero valido.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                // Vérifier les limites d'un INT signed
                if (numValue < -2147483648 || numValue > 2147483647) {
                    const errorMsg = customErrorMessage || `Il campo deve essere compreso tra -2,147,483,648 e 2,147,483,647.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                // Vérifier les limites min/max personnalisées
                if (fieldMin !== null && !isNaN(fieldMin) && numValue < fieldMin) {
                    const errorMsg = customErrorMessage || `Il campo deve essere maggiore o uguale a ${fieldMin.toLocaleString('it-IT')}.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                if (fieldMax !== null && !isNaN(fieldMax) && numValue > fieldMax) {
                    const errorMsg = customErrorMessage || `Il campo deve essere minore o uguale a ${fieldMax.toLocaleString('it-IT')}.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                clearError(fieldId);
                return;
            }
            
            // Ne pas appliquer les validations de longueur pour les champs email
            if (fieldType === 'email') {
                // Seulement la validation requise pour ces types
                clearError(fieldId);
                return;
            }
            
            // Se il campo non è vuoto, applica le validazioni di lunghezza (solo per text/textarea)
            if (val !== '' && (fieldType === 'text' || fieldType === 'textarea' || field.tagName === 'TEXTAREA')) {
                // Validazione lunghezza esatta
                if (exactLength !== null && exactLength > 0 && valLength !== exactLength) {
                    // Utiliser le message personnalisé s'il existe, sinon le message par défaut
                    const errorMsg = customErrorMessage || `Il campo deve avere esattamente ${exactLength} caratteri.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                // Validazione lunghezza minima (solo se exact_length non è impostato)
                if (exactLength === null && minLength !== null && minLength > 0 && valLength < minLength) {
                    // Utiliser le message personnalisé s'il existe, sinon le message par défaut
                    const errorMsg = customErrorMessage || `Il campo deve avere almeno ${minLength} caratteri.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                // Validazione lunghezza massima (solo se exact_length non è impostato)
                if (exactLength === null && maxLength !== null && maxLength > 0 && valLength > maxLength) {
                    // Utiliser le message personnalisé s'il existe, sinon le message par défaut
                    const errorMsg = customErrorMessage || `Il campo non può superare ${maxLength} caratteri.`;
                    showError(fieldId, errorMsg);
                    if (collectList) errors.push(errorMsg);
                    return;
                }
                
                // Sécurité par défaut pour les champs TEXT : limite par défaut 5000, configurable de 5000 à 65535
                // Vérifier si c'est un textarea (qui correspond au type TEXT)
                if (field.tagName === 'TEXTAREA') {
                    const textDefaultLimit = 5000; // Limite par défaut pour TEXT
                    const textMaxLimit = 65535; // Limite MySQL pour le type TEXT
                    // Si maxLength n'est pas défini, utiliser 5000 par défaut
                    // Sinon, s'assurer qu'il est entre 5000 et 65535
                    let effectiveMaxLength;
                    if (maxLength !== null && maxLength > 0) {
                        effectiveMaxLength = Math.max(textDefaultLimit, Math.min(maxLength, textMaxLimit));
                    } else {
                        effectiveMaxLength = textDefaultLimit;
                    }
                    
                    // Vérifier seulement si on dépasse la limite effective
                    if (valLength > effectiveMaxLength) {
                        const errorMsg = customErrorMessage || `Il campo non può superare ${effectiveMaxLength.toLocaleString('it-IT')} caratteri.`;
                        showError(fieldId, errorMsg);
                        if (collectList) errors.push(errorMsg);
                        return;
                    }
                }
                
                // Sécurité par défaut pour les champs VARCHAR (input type="text") : limite à 255 caractères si maxLength n'est pas défini
                if (fieldType === 'text' && field.tagName === 'INPUT') {
                    const varcharDefaultLimit = 255; // Limite par défaut pour VARCHAR
                    // Si maxLength n'est pas défini, utiliser 255 par défaut
                    const effectiveMaxLength = maxLength !== null && maxLength > 0 
                        ? maxLength 
                        : varcharDefaultLimit;
                    
                    // Vérifier seulement si on dépasse la limite effective
                    if (valLength > effectiveMaxLength) {
                        const errorMsg = customErrorMessage || `Il campo non può superare ${effectiveMaxLength} caratteri.`;
                        showError(fieldId, errorMsg);
                        if (collectList) errors.push(errorMsg);
                        return;
                    }
                }
            }
            
            // Se tutto è OK, rimuovi l'errore
            clearError(fieldId);
        });

        return errors;
    };

    const form = document.getElementById('registration-form');
    if (!form) return;

    function isCustomIntField(el) {
        if (!el || !el.id) return false;
        if (el.id === 'cap' && el.hasAttribute('data-cap-exact-digits')) return true;
        if (el.id.indexOf('custom_') !== 0) return false;
        return el.hasAttribute('data-int-exact-digits')
            || el.getAttribute('data-custom-type') === 'int'
            || el.type === 'number';
    }

    function sanitizeCustomIntField(field) {
        if (!isCustomIntField(field)) return;
        const exactCap = field.id === 'cap' && field.dataset.capExactDigits
            ? parseInt(field.dataset.capExactDigits, 10)
            : null;
        const exact = exactCap !== null && !isNaN(exactCap) && exactCap > 0
            ? exactCap
            : (field.dataset.intExactDigits ? parseInt(field.dataset.intExactDigits, 10) : null);
        if (exact !== null && !isNaN(exact) && exact > 0) {
            const digits = String(field.value || '').replace(/\D+/g, '').slice(0, exact);
            if (field.value !== digits) field.value = digits;
            return;
        }
        // INT classico (type=number): togliere e/E/+/-/. e lasciare solo cifre (segno meno opzionale in testa)
        let raw = String(field.value || '');
        const negative = raw.trim().startsWith('-');
        raw = raw.replace(/[^\d]/g, '');
        if (negative && raw !== '') raw = '-' + raw;
        if (field.value !== raw) field.value = raw;
    }

    function getExactDigitLimit(field) {
        if (!field) return null;
        if (field.id === 'cap' && field.dataset.capExactDigits) {
            const n = parseInt(field.dataset.capExactDigits, 10);
            return (!isNaN(n) && n > 0) ? n : null;
        }
        if (field.dataset.intExactDigits) {
            const n = parseInt(field.dataset.intExactDigits, 10);
            return (!isNaN(n) && n > 0) ? n : null;
        }
        return null;
    }

    // Delegazione: blocca lettere subito + ripulisce (anche dopo cache/vecchio JS)
    form.addEventListener('beforeinput', (e) => {
        const field = e.target;
        if (!isCustomIntField(field)) return;
        if (e.inputType && e.inputType.indexOf('insert') === 0 && typeof e.data === 'string' && e.data !== '') {
            const exact = getExactDigitLimit(field);
            if (exact) {
                if (/\D/.test(e.data)) e.preventDefault();
            } else if (/[eE+\-.]/.test(e.data) && !(/^\d+$/.test(e.data))) {
                // blocca e/E/+/. ; permetti cifre
                if (/[eE+.]/.test(e.data)) e.preventDefault();
            }
        }
    });

    form.addEventListener('keydown', (e) => {
        const field = e.target;
        if (!isCustomIntField(field)) return;
        const exact = getExactDigitLimit(field);
        // Permetti controllo/navigazione
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        const allow = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
        if (allow.indexOf(e.key) >= 0) return;
        if (exact) {
            if (!/^\d$/.test(e.key)) e.preventDefault();
            return;
        }
        // type=number: blocca e/E/+/. (la e è tipica della notazione scientifica)
        if (e.key === 'e' || e.key === 'E' || e.key === '+' || e.key === '.' || e.key === ',') {
            e.preventDefault();
        }
    });

    form.addEventListener('input', (e) => {
        const field = e.target;
        if (!isCustomIntField(field)) return;
        sanitizeCustomIntField(field);
        if (getExactDigitLimit(field)) {
            validate([field.id], false);
        }
    });

    form.addEventListener('blur', (e) => {
        const field = e.target;
        if (!isCustomIntField(field)) return;
        sanitizeCustomIntField(field);
        if (getExactDigitLimit(field)) {
            validate([field.id], false);
        }
    }, true);

    form.addEventListener('paste', (e) => {
        const field = e.target;
        if (!isCustomIntField(field)) return;
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text') || '';
        const exact = getExactDigitLimit(field);
        let digits = String(text).replace(/\D+/g, '');
        if (exact !== null) {
            digits = digits.slice(0, exact);
        }
        field.value = digits;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    });

    form.addEventListener('change', (e) => {
        const t = e.target;
        if (!t) {
            return;
        }
        if (t.id === 'data_scontrino') {
            validate(['data_scontrino'], false);
            return;
        }
        // Flatpickr altInput non ha id: se cambia, rivaluta la data scontrino
        if (t.classList && t.classList.contains('form-control') && t._flatpickr) {
            const src = t._flatpickr.input;
            if (src && src.id === 'data_scontrino') {
                validate(['data_scontrino'], false);
            }
        }
    });

    form.addEventListener('submit', (e) => {
        const exactDigitFields = Array.from(form.querySelectorAll('[id^="custom_"][data-int-exact-digits], #cap[data-cap-exact-digits]'));
        exactDigitFields.forEach(sanitizeCustomIntField);

        const allErrors = validate(null, true);
        const summaryMessages = collectInlineErrorMessages();
        const hasBlockingErrors = allErrors.length > 0 || summaryMessages.length > 0;
        if (hasBlockingErrors) {
            e.preventDefault();
            updateRegistrationErrorSummary(summaryMessages.length ? summaryMessages : allErrors);
            const errBox = document.getElementById('error-messages');
            if (errBox) {
                errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid && typeof firstInvalid.focus === 'function') {
                firstInvalid.focus();
            }
            return;
        }
        // Nessun errore reale: rimuovi eventuali is-invalid orfani (es. altInput flatpickr)
        form.querySelectorAll('.is-invalid').forEach((el) => {
            el.classList.remove('is-invalid');
        });
        clearRegistrationErrorSummary();
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.dataset.originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Registrazione in corso...';
            btn.disabled = true;
        }
    });
});



