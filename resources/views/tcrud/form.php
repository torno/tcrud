<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= esc($action === 'add' ? $lang['add'] : $lang['edit']) . ' ' . esc($subjectSingular) ?></h1>
    <a href="<?= base_url($baseUrl) ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> <?= $lang['back'] ?>
    </a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach (session()->getFlashdata('errors') as $field => $error): ?>
                <li><strong><?= esc($field) ?>:</strong> <?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= base_url($baseUrl . '/' . ($action === 'add' ? 'tcadd' : 'tcedit/' . ($data->id ?? ''))) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    
    <div class="row g-3">
        <?php foreach ($fields as $field): 
            $type = $fieldTypes[$field] ?? 'text';
            $label = $fieldLabels[$field] ?? ucfirst($field);
            $value = old($field, $data->$field ?? '');
            $isReadonly = in_array($field, $fieldReadonly);
        ?>
            <div class="col-md-6">
                <label for="field_<?= $field ?>" class="form-label">
                    <?= esc($label) ?>
                    <?php if (strpos($validationRules[$field] ?? '', 'required') !== false): ?>
                        <span class="text-danger">*</span>
                    <?php endif; ?>
                </label>
                
                <?php if ($type === 'checkbox'): ?>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               name="<?= $field ?>" value="1" id="field_<?= $field ?>"
                               <?= $value == 1 ? 'checked' : '' ?> 
                               <?= $isReadonly ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="field_<?= $field ?>">
                            <?= $value == 1 ? $lang['yes'] : $lang['no'] ?>
                        </label>
                    </div>
                <?php elseif ($type === 'textarea'): ?>
                    <textarea class="form-control" name="<?= $field ?>" rows="4" <?= $isReadonly ? 'readonly' : '' ?>><?= esc($value) ?></textarea>
                <?php elseif ($type === 'wysiwyg'): ?>
                    <div class="wysiwyg-wrapper">
                        <div id="editor_<?= $field ?>" class="wysiwyg-editor" style="height: 250px;"><?= $value ?></div>
                        <input type="hidden" name="<?= $field ?>" id="input_<?= $field ?>" value="<?= esc($value) ?>">
                    </div>
                <?php elseif (isset($ntoNRelations[$field])): 
                    $config = $ntoNRelations[$field];
                    $options = $ntoNOptions[$field] ?? [];
                    $selectedValues = $ntoNValues[$field] ?? [];
                ?>
                    <select class="form-select crud-multiselect" name="<?= $field ?>[]" multiple <?= $isReadonly ? 'disabled' : '' ?>>
                        <?php foreach ($options as $option): ?>
                            <option value="<?= $option->id ?>" <?= in_array($option->id, $selectedValues) ? 'selected' : '' ?>>
                                <?= esc($option->{$config['display_field']}) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted"><?= $lang['select_multiple'] ?></small>    
                <?php elseif (isset($dependentRelations[$field])): 
                    $depConfig = $dependentRelations[$field];
                    $options = $dependentOptions[$field] ?? [];
                ?>
                    <select class="form-select crud-dependent-select" 
                            name="<?= $field ?>" 
                            data-depends-on="<?= $depConfig['depends_on'] ?>"
                            data-dependent-field="<?= $field ?>"
                            <?= $isReadonly ? 'disabled' : '' ?>>
                        <option value=""><?= $lang['select_option'] ?></option>
                        <?php foreach ($options as $option): ?>
                            <option value="<?= $option->id ?>" <?= $value == $option->id ? 'selected' : '' ?>>
                                <?= esc($option->{$depConfig['field']}) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'dropdown' || isset($relations[$field])): ?>
                    <select class="form-select" name="<?= $field ?>" <?= $isReadonly ? 'disabled' : '' ?>>
                        <option value=""><?= $lang['select_option'] ?></option>
                        <?php 
                        if (isset($relations[$field])) {
                            $relField = $relations[$field]['field'];
                            $options = $relationOptions[$field] ?? [];
                            foreach ($options as $option): ?>
                                <option value="<?= $option->id ?>" <?= $value == $option->id ? 'selected' : '' ?>>
                                    <?= esc($option->$relField) ?>
                                </option>
                            <?php endforeach;
                        } else {
                            foreach (($fieldOptions[$field] ?? []) as $k => $v): ?>
                                <option value="<?= $k ?>" <?= $value == $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                            <?php endforeach;
                        } 
                        ?>
                    </select>
                <?php elseif ($type === 'image' || $type === 'file'): 
                    $config = $uploadConfigs[$field] ?? ['upload_path' => 'uploads/', 'max_size' => 2048, 'allowed_types' => '*'];
                    $currentFile = $data->$field ?? '';
                    $isImage = $type === 'image';
                ?>
                    <div class="mb-3">
                        <?php if ($currentFile): ?>
                            <div class="mb-2">
                                <?php if ($isImage): ?>
                                    <img src="<?= base_url($currentFile) ?>" alt="Archivo actual" style="max-height: 150px; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                <?php else: ?>
                                    <i class="bi bi-file-earmark-pdf fs-1 me-2"></i>
                                    <a href="<?= base_url($currentFile) ?>" target="_blank" class="text-decoration-none">
                                        <?= basename($currentFile) ?>
                                    </a>
                                <?php endif; ?>
                                <div class="mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="delete_<?= $field ?>" value="1" id="delete_<?= $field ?>">
                                        <label class="form-check-label text-danger" for="delete_<?= $field ?>">
                                            <?= $lang['delete_current_file'] ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="<?= $field ?>" <?= $isImage ? 'accept="image/*"' : '' ?>>
                        <small class="text-muted">
                            <?= $lang['max_size'] ?> <?= $config['max_size'] ?> KB. 
                            <?= $lang['allowed_types'] ?>: <?= $config['allowed_types'] ?>
                        </small>
                    </div>
                <?php elseif ($type === 'email'): ?>
                    <input type="email" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'url'): ?>
                    <input type="url" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'time'): ?>
                    <input type="time" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'datetime'): ?>
                    <input type="datetime-local" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'color'): ?>
                    <input type="color" class="form-control form-control-color" name="<?= $field ?>" value="<?= esc($value ?: '#000000') ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'date'): ?>
                    <input type="date" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'number'): ?>
                    <input type="number" step="any" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php elseif ($type === 'password'): ?>
                    <input type="password" class="form-control" name="<?= $field ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                    <small class="text-muted"><?= $lang['leave_blank'] ?></small>
                <?php else: ?>
                    <input type="text" class="form-control" name="<?= $field ?>" value="<?= esc($value) ?>" <?= $isReadonly ? 'readonly' : '' ?>>
                <?php endif; ?>

                <?php if (isset($validationErrors[$field])): ?>
                    <div class="invalid-feedback d-block"><?= esc($validationErrors[$field]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?= $lang['save'] ?></button>
        <a href="<?= base_url($baseUrl) ?>" class="btn btn-light"><?= $lang['cancel'] ?></a>
    </div>
</form>