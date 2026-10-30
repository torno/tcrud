<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $lang['view'] ?> <?= esc($subjectSingular) ?></h1>
    <div>
        <a href="<?= base_url($baseUrl) ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> <?= $lang['back'] ?>
        </a>
        <?php if ($showEdit): ?>
            <a href="<?= base_url($baseUrl . '/tcedit/' . $data->{$primaryKey}) ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> <?= $lang['edit'] ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row">
            <?php 
            foreach ($fields as $field): 
                $label = $fieldLabels[$field] ?? ucfirst($field);
                
                if (isset($callbackColumns[$field])) {
                    $value = call_user_func($callbackColumns[$field], $data);
                } 
                elseif (isset($uploadConfigs[$field])) {
                    $config = $uploadConfigs[$field];
                    $isImage = isset($fieldTypes[$field]) && $fieldTypes[$field] === 'image';
                    $value = $data->$field ?? '';
                    
                    if ($value) {
                        if ($isImage) {
                            $value = '<a href="' . base_url($value) . '" target="_blank">
                                    <img src="' . base_url($value) . '" alt="Imagen" style="max-height: 300px; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                                   </a>';
                        } else {
                            $value = '<a href="' . base_url($value) . '" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-download me-1"></i> ' . basename($value) . '
                                   </a>';
                        }
                    } else {
                        $value = '<span class="text-muted">—</span>';
                    }
                }
                elseif (isset($hiddenFields[$field])) {
                    $value = $data->$field ?? '';
                    $fieldType = $fieldTypes[$field] ?? 'text';
                    
                    if ($fieldType === 'date' && $value) {
                        $value = date('d/m/Y', strtotime($value));
                    } elseif ($fieldType === 'datetime' && $value) {
                        $value = date('d/m/Y H:i', strtotime($value));
                    } elseif ($fieldType === 'time' && $value) {
                        $value = date('H:i', strtotime($value));
                    } elseif ($fieldType === 'checkbox') {
                        $badge = $value == 1 ? 'success' : 'secondary';
                        $text = $value == 1 ? $lang['yes'] : $lang['no'];
                        $value = '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                    } elseif (isset($relations[$field])) {
                        $db = \Config\Database::connect();
                        $rel = $relations[$field];
                        $result = $db->table($rel['table'])->where('id', $value)->get()->getRow();
                        $value = $result ? $result->{$rel['field']} : 'N/A';
                    } else {
                        $value = esc((string)$value);
                    }
                }
                elseif (isset($ntoNRelations[$field])) {
                    $names = $ntoNValues[$field] ?? [];
                    if (!empty($names)) {
                        $badges = array_map(fn($n) => '<span class="badge bg-secondary me-1">' . esc($n) . '</span>', $names);
                        $value = implode('', $badges);
                    } else {
                        $value = '<span class="text-muted">—</span>';
                    }
                }
                elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'checkbox') {
                    $value = $data->$field ?? 0;
                    $badge = $value == 1 ? 'success' : 'secondary';
                    $text = $value == 1 ? $lang['yes'] : $lang['no'];
                    $value = '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                }
                elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'email') {
                    $value = $data->$field ?? '';
                    $value = $value ? '<a href="mailto:' . esc($value) . '">' . esc($value) . '</a>' : '<span class="text-muted">—</span>';
                }
                elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'url') {
                    $value = $data->$field ?? '';
                    $value = $value ? '<a href="' . esc($value) . '" target="_blank">' . esc($value) . '</a>' : '<span class="text-muted">—</span>';
                }
                elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'time') {
                    $value = $data->$field ?? '';
                    $value = $value ? date('H:i', strtotime($value)) : '<span class="text-muted">—</span>';
                }
                elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'datetime') {
                    $value = $data->$field ?? '';
                    $value = $value ? date('d/m/Y H:i', strtotime($value)) : '<span class="text-muted">—</span>';
                }
                elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'color') {
                    $value = $data->$field ?? '';
                    $value = $value 
                        ? '<span style="display:inline-block;width:20px;height:20px;background:' . esc($value) . ';border:1px solid #ddd;border-radius:3px;vertical-align:middle;"></span> ' . esc($value)
                        : '<span class="text-muted">—</span>';
                }
                else {
                    $value = $data->$field ?? '';
                    
                    if (isset($relations[$field])) {
                        $db = \Config\Database::connect();
                        $rel = $relations[$field];
                        $result = $db->table($rel['table'])->where('id', $value)->get()->getRow();
                        $value = $result ? $result->{$rel['field']} : 'N/A';
                    }
                    
                    if (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'dropdown' && isset($fieldOptions[$field])) {
                        $value = $fieldOptions[$field][$value] ?? $value;
                    }
                    
                    if (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'date' && $value) {
                        $value = date('d/m/Y', strtotime($value));
                    }
                }
            ?>
                <dt class="col-sm-3"><?= esc($label) ?></dt>
                <dd class="col-sm-9">
                    <?php if (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'wysiwyg' && !isset($callbackColumns[$field]) && !isset($uploadConfigs[$field])): ?>
                        <div class="border rounded p-3 bg-light"><?= $value ?></div>
                    <?php elseif (isset($fieldTypes[$field]) && $fieldTypes[$field] === 'textarea' && !isset($callbackColumns[$field]) && !isset($uploadConfigs[$field])): ?>
                        <div class="border rounded p-2 bg-light"><?= nl2br(esc($value)) ?></div>
                    <?php else: ?>
                        <?= $value ?: '<span class="text-muted">—</span>' ?>
                    <?php endif; ?>
                </dd>
            <?php endforeach; ?>
        </dl>
    </div>
</div>