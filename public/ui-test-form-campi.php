<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UI Test - Form & Campi v2</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --ui-bg: #f3f5f9;
            --ui-card: #fff;
            --ui-border: #dfe4ea;
            --ui-muted: #6b7280;
            --ui-title: #0f172a;
            --ui-accent: #2563eb;
            --ui-soft: #f8fafc;
        }
        body { background: var(--ui-bg); }
        .ui-wrap { max-width: 1280px; margin: 22px auto; padding: 0 12px; }
        .ui-header {
            background: var(--ui-card);
            border: 1px solid var(--ui-border);
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .ui-header-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ui-title);
        }
        .ui-header-sub {
            margin: 2px 0 0;
            font-size: .84rem;
            color: var(--ui-muted);
        }
        .ui-header-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .ui-layout {
            display: grid;
            grid-template-columns: 300px minmax(0,1fr);
            gap: 14px;
        }
        .panel {
            background: var(--ui-card);
            border: 1px solid var(--ui-border);
            border-radius: 14px;
            overflow: hidden;
        }
        .menu-head, .editor-head {
            border-bottom: 1px solid var(--ui-border);
            padding: 12px 14px;
            background: #fff;
        }
        .menu-list { padding: 10px; }
        .menu-item {
            border: 1px solid var(--ui-border);
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
        }
        .menu-item.active {
            border-color: var(--ui-accent);
            box-shadow: 0 0 0 2px rgba(37,99,235,.14);
            background: #f8fbff;
        }
        .menu-title { font-weight: 700; color: var(--ui-title); margin: 0; font-size: .95rem; }
        .menu-sub { margin: 2px 0 0; color: var(--ui-muted); font-size: .8rem; }
        .editor-body { padding: 14px; background: var(--ui-soft); }
        .field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .field-option {
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            background: #fff;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .sub-card {
            margin-top: 12px;
            border: 1px solid #cfd8e3;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .sub-title { font-size: .8rem; color: #334155; font-weight: 700; margin-bottom: 8px; }
        .or-sep { color: var(--ui-muted); font-size: .86rem; display: flex; align-items: flex-end; justify-content: center; padding-bottom: 8px; }
        .hidden { display: none !important; }
        @media (max-width: 991.98px) {
            .ui-layout { grid-template-columns: 1fr; }
            .field-grid { grid-template-columns: 1fr; }
            .ui-header {
                padding: 8px 10px;
                margin-bottom: 10px;
            }
            .ui-header-sub {
                display: none;
            }
            .ui-header-actions {
                width: 100%;
            }
            .ui-header-actions .btn {
                flex: 1 1 calc(50% - 4px);
            }
        }
        .section-block {
            margin-top: 14px;
        }
        .section-shell {
            display: grid;
            grid-template-columns: 300px minmax(0,1fr);
            gap: 14px;
        }
        .section-menu-head,
        .section-editor-head {
            border-bottom: 1px solid var(--ui-border);
            padding: 12px 14px;
            background: #fff;
        }
        .section-menu-list { padding: 10px; }
        .section-menu-item {
            border: 1px solid var(--ui-border);
            border-radius: 12px;
            background: #fff;
            padding: 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .section-menu-item.active {
            border-color: var(--ui-accent);
            box-shadow: 0 0 0 2px rgba(37,99,235,.14);
            background: #f8fbff;
        }
        .section-menu-title { margin: 0; font-size: .92rem; font-weight: 700; color: var(--ui-title); }
        .section-menu-sub { margin: 2px 0 0; color: var(--ui-muted); font-size: .8rem; }
        .section-editor-body { padding: 14px; background: var(--ui-soft); }
        .custom-list {
            display: grid;
            gap: 8px;
        }
        .custom-item {
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            background: #fff;
            padding: 9px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .custom-item-meta { color: var(--ui-muted); font-size: .8rem; }
        .preview-box {
            border: 1px dashed #c9d5e4;
            border-radius: 12px;
            min-height: 120px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: .92rem;
        }
        @media (max-width: 991.98px) {
            .section-shell { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="ui-wrap">
    <div class="ui-layout">
        <aside class="panel">
            <div class="menu-head">
                <strong>Menu gruppi</strong>
                <div class="small text-muted mt-1" id="summaryCount">0 / 6 attivi</div>
            </div>
            <div class="menu-list" id="menuList">
                <div class="menu-item active" data-target="grp-personali">
                    <div>
                        <p class="menu-title">Dati personali</p>
                        <p class="menu-sub">Anagrafica, cellulare e indirizzo.</p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input group-enable" type="checkbox" checked data-group="grp-personali">
                    </div>
                </div>
                <div class="menu-item" data-target="grp-fidelity">
                    <div>
                        <p class="menu-title">Fidelity</p>
                        <p class="menu-sub">Codice fidelity.</p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input group-enable" type="checkbox" checked data-group="grp-fidelity">
                    </div>
                </div>
                <div class="menu-item" data-target="grp-pdv">
                    <div>
                        <p class="menu-title">Punto vendita</p>
                        <p class="menu-sub">PDV combinato o cascata.</p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input group-enable" type="checkbox" checked data-group="grp-pdv">
                    </div>
                </div>
                <div class="menu-item" data-target="grp-scontrino">
                    <div>
                        <p class="menu-title">Scontrino</p>
                        <p class="menu-sub">Importo, data, upload.</p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input group-enable" type="checkbox" checked data-group="grp-scontrino">
                    </div>
                </div>
                <div class="menu-item" data-target="grp-codice">
                    <div>
                        <p class="menu-title">Codice partecipazione</p>
                        <p class="menu-sub">Label, lunghezza, modalità.</p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input group-enable" type="checkbox" checked data-group="grp-codice">
                    </div>
                </div>
                <div class="menu-item" data-target="grp-consensi">
                    <div>
                        <p class="menu-title">Consensi</p>
                        <p class="menu-sub">Privacy, marketing, profilazione.</p>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input group-enable" type="checkbox" checked data-group="grp-consensi">
                    </div>
                </div>
            </div>
        </aside>

        <section class="panel">
            <div class="editor-head">
                <strong id="editorTitle">Dati personali</strong>
                <div class="small text-muted" id="editorSub">Configura i campi del gruppo selezionato.</div>
            </div>
            <div class="editor-body">
                <div id="grp-personali" class="editor-group">
                    <div class="field-grid">
                        <label class="field-option"><input type="checkbox" checked> Nome</label>
                        <label class="field-option"><input type="checkbox" checked> Cognome</label>
                        <label class="field-option"><input type="checkbox" checked> Email</label>
                        <label class="field-option"><input type="checkbox" checked id="f-prefcell"> Prefisso/Cellulare</label>
                        <label class="field-option"><input type="checkbox" checked> Provincia</label>
                        <label class="field-option"><input type="checkbox" checked> Città</label>
                        <label class="field-option"><input type="checkbox" checked> Indirizzo</label>
                    </div>
                    <div class="sub-card" id="panel-cellulare">
                        <div class="sub-title">Numero di cifre (cellulare)</div>
                        <div class="row g-2">
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1">Numero di cifre esatto</label>
                                <input class="form-control" value="10">
                            </div>
                            <div class="col-12 col-md-1 or-sep">oppure</div>
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Valore min</label>
                                <input class="form-control" placeholder="Es: 6">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Valore max</label>
                                <input class="form-control" placeholder="Es: 12">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="grp-fidelity" class="editor-group hidden">
                    <div class="field-grid">
                        <label class="field-option"><input type="checkbox" checked id="f-fidelity"> Fidelity</label>
                    </div>
                    <div class="sub-card" id="panel-fidelity">
                        <div class="sub-title">Fidelity</div>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-1">Tipo codice fidelity</label>
                                <select class="form-select"><option>Numerico</option></select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-1">Lunghezza</label>
                                <input class="form-control" value="13">
                            </div>
                            <div class="col-12">
                                <label class="field-option"><input type="checkbox" checked> Fidelity univoca (1 partecipazione massima)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="grp-pdv" class="editor-group hidden">
                    <div class="field-grid">
                        <label class="field-option"><input type="checkbox" checked> Provincia/Città/Indirizzo PDV</label>
                    </div>
                </div>

                <div id="grp-scontrino" class="editor-group hidden">
                    <div class="field-grid">
                        <label class="field-option"><input type="checkbox" checked> Importo</label>
                        <label class="field-option"><input type="checkbox" checked> Data scontrino</label>
                        <label class="field-option"><input type="checkbox" checked> Scontrino</label>
                    </div>
                    <div class="sub-card">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-1">Spesa minima</label>
                                <input class="form-control" value="10,00">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-1">Dimensione file max</label>
                                <select class="form-select"><option>5 MB</option></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="grp-codice" class="editor-group hidden">
                    <div class="field-grid">
                        <label class="field-option"><input type="checkbox" checked id="f-codice-enable"> Abilita codice partecipazione</label>
                    </div>
                    <div class="sub-card" id="panel-codice">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-1">Label codice</label>
                                <input class="form-control" value="Codice partecipazione">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label mb-1">Lunghezza codice</label>
                                <input class="form-control" value="12">
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Modalità codice</label>
                                <select class="form-select"><option>Instant Win</option></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="grp-consensi" class="editor-group hidden">
                    <div class="field-grid">
                        <label class="field-option"><input type="checkbox" checked> Privacy</label>
                        <label class="field-option"><input type="checkbox"> Marketing</label>
                        <label class="field-option"><input type="checkbox"> Profilazione</label>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="section-block">
        <div class="section-shell">
            <aside class="panel">
                <div class="section-menu-head">
                    <strong>Campi personalizzati</strong>
                    <div class="small text-muted mt-1">Gestione campi extra</div>
                </div>
                <div class="section-menu-list" id="customMenu">
                    <div class="section-menu-item active" data-custom-target="cf-anagrafica">
                        <div>
                            <p class="section-menu-title">Anagrafica</p>
                            <p class="section-menu-sub">Voucher, tessera, codice cliente.</p>
                        </div>
                        <span class="badge text-bg-light">3</span>
                    </div>
                    <div class="section-menu-item" data-custom-target="cf-preferenze">
                        <div>
                            <p class="section-menu-title">Preferenze</p>
                            <p class="section-menu-sub">Opt-in e dati opzionali.</p>
                        </div>
                        <span class="badge text-bg-light">2</span>
                    </div>
                    <div class="section-menu-item" data-custom-target="cf-empty">
                        <div>
                            <p class="section-menu-title">Nuovo gruppo</p>
                            <p class="section-menu-sub">Nessun campo ancora.</p>
                        </div>
                        <span class="badge text-bg-light">0</span>
                    </div>
                </div>
            </aside>

            <section class="panel">
                <div class="section-editor-head d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <strong id="customEditorTitle">Anagrafica</strong>
                        <div class="small text-muted">Definisci etichetta, tipo e validazioni.</div>
                    </div>
                    <button class="btn btn-sm btn-outline-primary">+ Aggiungi campo</button>
                </div>
                <div class="section-editor-body">
                    <div id="cf-anagrafica" class="custom-editor-group">
                        <div class="custom-list">
                            <div class="custom-item">
                                <div>
                                    <strong>Codice voucher</strong>
                                    <div class="custom-item-meta">text • required • max 20</div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Modifica</button>
                            </div>
                            <div class="custom-item">
                                <div>
                                    <strong>Numero tessera</strong>
                                    <div class="custom-item-meta">number • opzionale • 8 cifre</div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Modifica</button>
                            </div>
                            <div class="custom-item">
                                <div>
                                    <strong>ID cliente esterno</strong>
                                    <div class="custom-item-meta">text • opzionale</div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Modifica</button>
                            </div>
                        </div>
                    </div>
                    <div id="cf-preferenze" class="custom-editor-group hidden">
                        <div class="custom-list">
                            <div class="custom-item">
                                <div>
                                    <strong>Interessi prodotto</strong>
                                    <div class="custom-item-meta">select • opzionale</div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Modifica</button>
                            </div>
                            <div class="custom-item">
                                <div>
                                    <strong>Canale preferito</strong>
                                    <div class="custom-item-meta">radio • opzionale</div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary">Modifica</button>
                            </div>
                        </div>
                    </div>
                    <div id="cf-empty" class="custom-editor-group hidden">
                        <div class="preview-box">Nessun campo configurato. Aggiungi il primo campo personalizzato.</div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="section-block">
        <div class="section-shell">
            <aside class="panel">
                <div class="section-menu-head">
                    <strong>Container form</strong>
                    <div class="small text-muted mt-1">Aspetto del box form pubblico</div>
                </div>
                <div class="section-menu-list" id="containerMenu">
                    <div class="section-menu-item active" data-container-target="ct-style">
                        <div>
                            <p class="section-menu-title">Stile</p>
                            <p class="section-menu-sub">Colore, immagine, opacità.</p>
                        </div>
                    </div>
                    <div class="section-menu-item" data-container-target="ct-padding">
                        <div>
                            <p class="section-menu-title">Spaziatura</p>
                            <p class="section-menu-sub">Padding e raggio angoli.</p>
                        </div>
                    </div>
                    <div class="section-menu-item" data-container-target="ct-preview">
                        <div>
                            <p class="section-menu-title">Anteprima</p>
                            <p class="section-menu-sub">Verifica resa visuale.</p>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="panel">
                <div class="section-editor-head">
                    <strong id="containerEditorTitle">Stile</strong>
                    <div class="small text-muted">Configurazioni visual del container form.</div>
                </div>
                <div class="section-editor-body">
                    <div id="ct-style" class="container-editor-group">
                        <div class="row g-2">
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1">Mode</label>
                                <select class="form-select">
                                    <option>Color</option>
                                    <option>Image</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1">Colore</label>
                                <input class="form-control" value="#ffffff">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label mb-1">Opacità</label>
                                <input type="range" class="form-range" value="100">
                            </div>
                        </div>
                    </div>
                    <div id="ct-padding" class="container-editor-group hidden">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1">Top</label>
                                <input class="form-control" value="16">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1">Right</label>
                                <input class="form-control" value="16">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1">Bottom</label>
                                <input class="form-control" value="16">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label mb-1">Left</label>
                                <input class="form-control" value="16">
                            </div>
                        </div>
                    </div>
                    <div id="ct-preview" class="container-editor-group hidden">
                        <div class="preview-box">Anteprima container form (placeholder visual)</div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
(() => {
    const menuItems = Array.from(document.querySelectorAll('.menu-item'));
    const groups = Array.from(document.querySelectorAll('.editor-group'));
    const enables = Array.from(document.querySelectorAll('.group-enable'));
    const title = document.getElementById('editorTitle');
    const subtitle = document.getElementById('editorSub');
    const summary = document.getElementById('summaryCount');

    const descriptions = {
        'grp-personali': 'Anagrafica, cellulare e indirizzo.',
        'grp-fidelity': 'Configura il codice fidelity.',
        'grp-pdv': 'Configura la selezione punto vendita.',
        'grp-scontrino': 'Configura regole scontrino e upload.',
        'grp-codice': 'Configura il codice partecipazione.',
        'grp-consensi': 'Configura i consensi utente.'
    };

    function setActive(targetId) {
        menuItems.forEach((item) => item.classList.toggle('active', item.dataset.target === targetId));
        groups.forEach((g) => g.classList.toggle('hidden', g.id !== targetId));
        const activeItem = menuItems.find((m) => m.dataset.target === targetId);
        if (activeItem && title) {
            title.textContent = activeItem.querySelector('.menu-title')?.textContent || 'Gruppo';
            subtitle.textContent = descriptions[targetId] || '';
        }
    }

    function reorderMenu() {
        const activeItems = [];
        const inactiveItems = [];
        menuItems.forEach((item) => {
            const sw = item.querySelector('.group-enable');
            if (sw && sw.checked) {
                activeItems.push(item);
            } else {
                inactiveItems.push(item);
            }
        });
        activeItems.concat(inactiveItems).forEach((item) => {
            item.parentNode.appendChild(item);
        });
    }

    function updateSummary() {
        const on = enables.filter((el) => el.checked).length;
        if (summary) summary.textContent = on + ' / ' + enables.length + ' attivi';
    }

    function applyGroupEnable(groupId, enabled) {
        const group = document.getElementById(groupId);
        if (!group) return;
        group.style.opacity = enabled ? '1' : '0.45';
        group.style.pointerEvents = enabled ? '' : 'none';
    }

    menuItems.forEach((item) => {
        item.addEventListener('click', (e) => {
            if (e.target.closest('.group-enable')) return;
            setActive(item.dataset.target);
        });
    });

    enables.forEach((sw) => {
        sw.addEventListener('change', () => {
            applyGroupEnable(sw.dataset.group, sw.checked);
            reorderMenu();
            updateSummary();
        });
        applyGroupEnable(sw.dataset.group, sw.checked);
    });

    const prefCell = document.getElementById('f-prefcell');
    const panelCell = document.getElementById('panel-cellulare');
    const fidelity = document.getElementById('f-fidelity');
    const panelFidelity = document.getElementById('panel-fidelity');
    const codiceEnable = document.getElementById('f-codice-enable');
    const panelCodice = document.getElementById('panel-codice');

    function syncConditionalPanels() {
        if (panelCell && prefCell) panelCell.classList.toggle('hidden', !prefCell.checked);
        if (panelFidelity && fidelity) panelFidelity.classList.toggle('hidden', !fidelity.checked);
        if (panelCodice && codiceEnable) panelCodice.classList.toggle('hidden', !codiceEnable.checked);
    }

    prefCell?.addEventListener('change', syncConditionalPanels);
    fidelity?.addEventListener('change', syncConditionalPanels);
    codiceEnable?.addEventListener('change', syncConditionalPanels);

    setActive('grp-personali');
    reorderMenu();
    updateSummary();
    syncConditionalPanels();

    // Campi personalizzati mock navigation
    const customItems = Array.from(document.querySelectorAll('[data-custom-target]'));
    const customGroups = Array.from(document.querySelectorAll('.custom-editor-group'));
    const customEditorTitle = document.getElementById('customEditorTitle');
    customItems.forEach((item) => {
        item.addEventListener('click', () => {
            const target = item.dataset.customTarget;
            customItems.forEach((x) => x.classList.toggle('active', x === item));
            customGroups.forEach((g) => g.classList.toggle('hidden', g.id !== target));
            if (customEditorTitle) {
                customEditorTitle.textContent = item.querySelector('.section-menu-title')?.textContent || 'Campi personalizzati';
            }
        });
    });

    // Container form mock navigation
    const containerItems = Array.from(document.querySelectorAll('[data-container-target]'));
    const containerGroups = Array.from(document.querySelectorAll('.container-editor-group'));
    const containerEditorTitle = document.getElementById('containerEditorTitle');
    containerItems.forEach((item) => {
        item.addEventListener('click', () => {
            const target = item.dataset.containerTarget;
            containerItems.forEach((x) => x.classList.toggle('active', x === item));
            containerGroups.forEach((g) => g.classList.toggle('hidden', g.id !== target));
            if (containerEditorTitle) {
                containerEditorTitle.textContent = item.querySelector('.section-menu-title')?.textContent || 'Container form';
            }
        });
    });
})();
</script>
</body>
</html>
