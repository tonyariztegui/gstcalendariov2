<?php
$containers = advent_containers($calendar);
$containerLabels = ['prize' => 'Premi', 'video' => 'Video', 'link' => 'Link'];
$containerCopy = [
    'prize' => 'Immagine del premio, descrizione e codice sotto il barcode.',
    'video' => 'Video YouTube, senza immagine né codice.',
    'link' => 'Immagine e pulsante del link. Il colore del pulsante resta quello principale.',
];
?>
<div id="surface-pane-container" role="tabpanel" aria-labelledby="surface-tab-container" data-surface-pane="container" hidden>
    <div class="configurazione-panel">
        <div class="configurazione-panel-header"><span class="configurazione-panel-icon"><i class="fa fa-window-maximize" aria-hidden="true"></i></span><div><h6>Contenitore</h6><p>Modello Classico. L’aspetto vale per Premi, Video e Link. Il contenuto resta proprio di ogni tipo.</p></div></div>
        <?php $appearance = $containers['prize']; $appearancePrefix = 'containers[appearance]'; ?>
        <div class="advent-container-layout">
            <div class="advent-container-previews">
                <?php foreach ($containerLabels as $type => $label): ?>
                    <div class="advent-container-preview" data-container-preview="<?= $type ?>" aria-label="Anteprima del contenitore <?= advent_escape($label) ?>" <?= $type === 'prize' ? '' : 'hidden' ?>>
                        <div class="advent-container-preview-head"><strong>Anteprima</strong><label>Dispositivo <select class="form-control form-control-sm js-container-device"><option value="desktop">Computer</option><option value="tablet">Tablet</option><option value="mobile">Smartphone</option></select></label></div>
                        <div class="advent-container-preview-stage">
                            <div class="advent-container-preview-dialog">
                                <span class="advent-container-preview-close" aria-hidden="true"><span data-preview-close-icon>×</span><img data-preview-close-image alt="" hidden></span>
                                <span class="advent-container-preview-kicker">CASELLA 3 · 03/12</span>
                                <strong class="advent-container-preview-title">DIXAN FUSTONE MIS.95</strong>
                                <span class="advent-container-preview-media is-photo" <?= $type === 'video' ? 'hidden' : '' ?>></span>
                                <span class="advent-container-preview-media is-video" <?= $type === 'video' ? '' : 'hidden' ?>><span>Video</span></span>
                                <span class="advent-container-preview-action" <?= $type === 'link' ? '' : 'hidden' ?>>Apri link</span>
                                <p class="advent-container-preview-description">Una descrizione breve sotto il contenuto.</p>
                                <div class="advent-container-preview-code" <?= $type === 'prize' ? '' : 'hidden' ?>><span class="advent-container-preview-bars" aria-hidden="true"></span><small>Codice premio: <b>9876545656765</b></small></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="advent-container-settings">
        <details class="advent-disclosure advent-container-appearance" open>
            <summary>Aspetto del contenitore</summary>
            <p class="text-muted small">Vale per Premi, Video e Link.</p>
            <div class="advent-fields">
                <?php foreach (['width_desktop' => 'Larghezza su computer (px)', 'width_tablet' => 'Larghezza su tablet (px)', 'width_mobile' => 'Larghezza su smartphone (px)'] as $key => $fieldLabel): ?>
                    <div class="advent-field"><label for="container-appearance-<?= $key ?>"><?= $fieldLabel ?></label><input class="form-control" id="container-appearance-<?= $key ?>" name="<?= $appearancePrefix ?>[<?= $key ?>]" type="number" min="280" max="900" step="1" value="<?= (int) $appearance[$key] ?>"></div>
                <?php endforeach; ?>
                <div class="advent-field"><label for="container-appearance-padding">Spazio interno (px)</label><input class="form-control" id="container-appearance-padding" name="<?= $appearancePrefix ?>[padding]" type="number" min="12" max="64" step="1" value="<?= (int) $appearance['padding'] ?>"></div>
                <div class="advent-field"><label for="container-appearance-radius">Angoli arrotondati (px)</label><input class="form-control" id="container-appearance-radius" name="<?= $appearancePrefix ?>[radius]" type="number" min="0" max="40" step="1" value="<?= (int) $appearance['radius'] ?>"></div>
                <div class="advent-field"><label for="container-appearance-background-mode">Sfondo</label><select class="form-control" id="container-appearance-background-mode" name="<?= $appearancePrefix ?>[background_mode]"><option value="color" <?= $appearance['background_mode'] !== 'image' ? 'selected' : '' ?>>Colore</option><option value="image" <?= $appearance['background_mode'] === 'image' ? 'selected' : '' ?>>Immagine</option></select></div>
                <div class="advent-field" data-container-background="color" <?= $appearance['background_mode'] === 'image' ? 'hidden' : '' ?>><label for="container-appearance-background-color">Colore di sfondo</label><input class="form-control" id="container-appearance-background-color" name="<?= $appearancePrefix ?>[background_color]" type="color" value="<?= advent_escape($appearance['background_color']) ?>"></div>
                <div class="advent-field advent-field--wide" data-container-background="image" <?= $appearance['background_mode'] === 'image' ? '' : 'hidden' ?>><?php advent_editor_image($appearancePrefix . '[background_image]', 'Immagine di sfondo', (string) $appearance['background_image'], 'container_background'); ?></div>
                <div class="advent-container-close" data-container-close>
                    <div class="advent-fields">
                        <div class="advent-field"><label for="container-appearance-close-kind">Aspetto della chiusura</label><select class="form-control" id="container-appearance-close-kind" name="<?= $appearancePrefix ?>[close_kind]"><option value="icon" <?= $appearance['close_kind'] !== 'image' ? 'selected' : '' ?>>Croce</option><option value="image" <?= $appearance['close_kind'] === 'image' ? 'selected' : '' ?>>Immagine</option></select></div>
                        <div class="advent-field"><label for="container-appearance-close-shape">Forma</label><select class="form-control" id="container-appearance-close-shape" name="<?= $appearancePrefix ?>[close_shape]"><option value="circle" <?= $appearance['close_shape'] !== 'square' ? 'selected' : '' ?>>Rotonda</option><option value="square" <?= $appearance['close_shape'] === 'square' ? 'selected' : '' ?>>Quadrata</option></select></div>
                        <div class="advent-field"><label for="container-appearance-close-size">Dimensione (px)</label><input class="form-control" id="container-appearance-close-size" name="<?= $appearancePrefix ?>[close_size]" type="number" min="28" max="72" step="1" value="<?= (int) $appearance['close_size'] ?>"></div>
                        <div class="advent-field" data-container-close-icon <?= $appearance['close_kind'] === 'image' ? 'hidden' : '' ?>><label for="container-appearance-close-color">Colore della croce</label><input class="form-control" id="container-appearance-close-color" name="<?= $appearancePrefix ?>[close_color]" type="color" value="<?= advent_escape($appearance['close_color']) ?>"></div>
                        <div class="advent-field" data-container-close-icon <?= $appearance['close_kind'] === 'image' ? 'hidden' : '' ?>><label for="container-appearance-close-background">Sfondo della chiusura</label><input class="form-control" id="container-appearance-close-background" name="<?= $appearancePrefix ?>[close_background]" type="color" value="<?= advent_escape($appearance['close_background']) ?>"></div>
                        <div class="advent-field" data-container-close-icon <?= $appearance['close_kind'] === 'image' ? 'hidden' : '' ?>><label for="container-appearance-close-border">Bordo della chiusura</label><input class="form-control" id="container-appearance-close-border" name="<?= $appearancePrefix ?>[close_border]" type="color" value="<?= advent_escape($appearance['close_border']) ?>"></div>
                        <div class="advent-field advent-field--wide" data-container-close-image <?= $appearance['close_kind'] === 'image' ? '' : 'hidden' ?>><?php advent_editor_image($appearancePrefix . '[close_image]', 'Immagine al posto della croce', (string) $appearance['close_image'], 'container_close'); ?></div>
                    </div>
                </div>
            </div>
        </details>
                <details class="advent-disclosure advent-container-content">
                    <summary>Contenuto</summary>
                    <div class="advent-container-tabs" role="tablist" aria-label="Tipo di contenitore">
                        <?php foreach ($containerLabels as $type => $label): ?>
                            <button class="advent-container-tab" type="button" role="tab" id="container-tab-<?= $type ?>" aria-controls="container-panel-<?= $type ?>" aria-selected="<?= $type === 'prize' ? 'true' : 'false' ?>" data-container-tab="<?= $type ?>"><?= $label ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($containerLabels as $type => $label): $box = $containers[$type]; $prefix = 'containers[' . $type . ']'; ?>
                        <div class="advent-container-panel" id="container-panel-<?= $type ?>" role="tabpanel" aria-labelledby="container-tab-<?= $type ?>" data-container-type="<?= $type ?>" <?= $type === 'prize' ? '' : 'hidden' ?>>
                            <p class="text-muted small"><?= advent_escape($containerCopy[$type]) ?></p>
                            <div class="advent-fields">
                                <div class="advent-field"><label for="container-<?= $type ?>-show-kicker">Titolo principale</label><select class="form-control" id="container-<?= $type ?>-show-kicker" name="<?= $prefix ?>[show_kicker]"><option value="1" <?= !empty($box['show_kicker']) ? 'selected' : '' ?>>Visibile</option><option value="0" <?= empty($box['show_kicker']) ? 'selected' : '' ?>>Nascosto</option></select><small class="text-muted">Per esempio CASELLA 3 · 03/12.</small></div>
                                <div class="advent-field"><label for="container-<?= $type ?>-show-title">Titolo secondario</label><select class="form-control" id="container-<?= $type ?>-show-title" name="<?= $prefix ?>[show_title]"><option value="1" <?= !empty($box['show_title']) ? 'selected' : '' ?>>Visibile</option><option value="0" <?= empty($box['show_title']) ? 'selected' : '' ?>>Nascosto</option></select><small class="text-muted">Per esempio DIXAN FUSTONE MIS.95.</small></div>
                                <div class="advent-field"><label for="container-<?= $type ?>-media">Altezza massima del contenuto (px)</label><input class="form-control" id="container-<?= $type ?>-media" name="<?= $prefix ?>[media_max_height]" type="number" min="120" max="640" step="1" value="<?= (int) $box['media_max_height'] ?>"><small class="text-muted"><?= $type === 'video' ? 'Altezza del riquadro video.' : 'Altezza dell’immagine.' ?> Il valore attuale è 420.</small></div>
                                <div class="advent-field"><label for="container-<?= $type ?>-show-description">Descrizione</label><select class="form-control" id="container-<?= $type ?>-show-description" name="<?= $prefix ?>[show_description]"><option value="1" <?= !empty($box['show_description']) ? 'selected' : '' ?>>Visibile</option><option value="0" <?= empty($box['show_description']) ? 'selected' : '' ?>>Nascosto</option></select></div>
                                <?php if ($type === 'prize'): ?>
                                    <div class="advent-field"><label for="container-prize-show-code">Codice sotto il barcode</label><select class="form-control" id="container-prize-show-code" name="<?= $prefix ?>[show_code]"><option value="1" <?= !empty($box['show_code']) ? 'selected' : '' ?>>Visibile</option><option value="0" <?= empty($box['show_code']) ? 'selected' : '' ?>>Nascosto</option></select><small class="text-muted">Il barcode resta. Si nasconde solo la riga Codice premio.</small></div>
                                <?php else: ?>
                                    <input type="hidden" name="<?= $prefix ?>[show_code]" value="1">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </details>
            </div>
        </div>
    </div>
</div>
