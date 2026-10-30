<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= esc($subjectPlural) ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <?php if ($showAdd): ?>
            <a href="<?= base_url($baseUrl . '/tcadd') ?>" class="btn btn-primary me-2">
                <i class="bi bi-plus-lg me-1"></i> <?= $lang['add'] ?> <?= esc($subjectSingular) ?>
            </a>
        <?php endif; ?>

        <?php if ($showExport): ?>
            <button class="btn btn-outline-success me-2" id="openExportModal" data-mode="csv">
                <i class="bi bi-download"></i> <?= $lang['export'] ?>
            </button>
        <?php endif; ?>
        <?php if ($showPrint): ?>
            <button class="btn btn-outline-primary me-2" id="openPrintModal">
                <i class="bi bi-printer"></i> <?= $lang['print'] ?>
            </button>
        <?php endif; ?>
        
        <?php foreach ($extraButtons as $btn): ?>
            <?php if ($btn['url']): ?>
                <a href="<?= base_url($btn['url']) ?>" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-<?= $btn['icon'] ?> me-1"></i> <?= esc($btn['label']) ?>
                </a>
            <?php elseif ($btn['js']): ?>
                <button class="btn btn-outline-secondary me-2" onclick="<?= $btn['js'] ?>">
                    <i class="bi bi-<?= $btn['icon'] ?> me-1"></i> <?= esc($btn['label']) ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if ($showBulkDelete): ?>
            <button id="bulkDeleteBtn" class="btn btn-danger" style="display:none;">
                <i class="bi bi-trash me-1"></i> <?= $lang['bulk_delete'] ?> (<span id="selectedCount">0</span>)
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle w-100" id="crudTable" 
            data-columns='<?= json_encode(array_merge(
            array_map(function($c) use ($callbackColumns, $fieldLabels) {
                $isVirtual = isset($callbackColumns[$c]);
                return [
                    "data" => $c, 
                    "name" => $c,
                    "title" => $fieldLabels[$c] ?? ucfirst($c),
                    "orderable" => !$isVirtual,
                    "searchable" => !$isVirtual
                ];
            }, $columns),
            [["data" => "checkbox", "name" => "checkbox", "title" => "", "orderable" => false, "searchable" => false, "className" => "text-center", "width" => "40px"]],
            [["data" => "actions", "name" => "actions", "title" => $lang['actions'], "orderable" => false, "searchable" => false, "className" => "text-end"]]
        )) ?>' data-state='<?= json_encode($savedState ?? []) ?>' data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>" data-i18n-url="<?= $i18nUrl ?>" data-i18n='<?= json_encode($jsTranslations) ?>' data-dependent-url="<?= base_url($baseUrl . '/tcdependent') ?>">
        <thead class="table-light">
            <tr>
                <?php foreach ($columns as $col): ?>
                    <th><?= esc($fieldLabels[$col] ?? ucfirst($col)) ?></th>
                <?php endforeach; ?>
                <th class="text-center" style="width: 40px;">
                    <input type="checkbox" id="selectAll" class="form-check-input">
                </th>
                <th class="text-end" style="width: 120px;"><?= $lang['actions'] ?></th>
            </tr>
            <?php if (!empty($filterFields)): ?>
                <tr class="tcrud-filter-row">
                    <?php foreach ($columns as $col): ?>
                        <th class="p-1">
                            <?php if (isset($filterFields[$col])): ?>
                                <div class="tcrud-filter-wrapper">
                                    <input type="text" 
                                        class="form-control form-control-sm tcrud-filter-input" 
                                        data-field="<?= $col ?>"
                                        placeholder="<?= $lang['search'] ?>..."
                                        value="<?= esc($activeFilters[$col] ?? '') ?>">
                                    <button class="tcrud-filter-clear" 
                                            type="button" 
                                            data-field="<?= $col ?>"
                                            style="<?= empty($activeFilters[$col]) ? 'display:none;' : '' ?>">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="text-center p-1">
                        <button id="tcrudClearAllFilters" class="btn btn-sm btn-outline-danger" title="<?= $lang['clear_filters'] ?>">
                            <i class="bi bi-eraser"></i>
                        </button>
                    </th>
                    <th class="p-1"></th>
                </tr>
                <?php endif; ?>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal de selección de columnas para exportar/imprimir -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $lang['select_columns'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php foreach ($exportColumns as $col => $config): ?>
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input export-col-checkbox" 
                                       type="checkbox" 
                                       value="<?= $col ?>" 
                                       id="export_col_<?= $col ?>"
                                       <?= $config['checked'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="export_col_<?= $col ?>">
                                    <?= esc($config['label']) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($showExport): ?>
                    <button type="button" class="btn btn-success" id="exportCSVBtn">
                        <i class="bi bi-file-earmark-spreadsheet"></i> <?= $lang['export_csv'] ?>
                    </button>
                    <button type="button" class="btn btn-danger" id="exportPDFBtn">
                        <i class="bi bi-file-earmark-pdf"></i> <?= $lang['export_pdf'] ?>
                    </button>
                <?php endif; ?>
                <?php if ($showPrint): ?>
                    <button type="button" class="btn btn-primary" id="printBtn">
                        <i class="bi bi-printer"></i> <?= $lang['print'] ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $lang['cancel'] ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para previsualización de imágenes -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $lang['image_preview'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="<?= $lang['image_preview'] ?>" class="img-fluid">
            </div>
        </div>
    </div>
</div>