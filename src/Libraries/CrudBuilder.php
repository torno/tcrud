<?php

namespace Torno\TCrud\Libraries;

class CrudBuilder
{
    // --- PROPIEDADES DE ESTADO ---
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected string $subjectSingular = '';
    protected string $subjectPlural = '';
    
    protected array $columns = [];
    protected array $fields = [];
    protected array $addFields = [];
    protected array $editFields = [];
    
    protected array $fieldTypes = [];      
    protected array $fieldOptions = [];    
    protected array $fieldLabels = [];     
    protected array $fieldReadonly = [];
    protected array $fieldDisabled = [];
    protected array $validationRules = [];
    
    protected array $relations = [];       
    protected array $callbacks = [];       
    protected array $ntoNRelations = [];
    
    protected bool $showAdd = true;
    protected bool $showEdit = true;
    protected bool $showDelete = true;
    protected bool $showView = true;
    protected bool $showClone = true;
    protected bool $showBulkDelete = false;

    protected array $clearOnClone = [];
    protected $request;

    protected array $extraButtons = [];
    protected array $actionButtons = [];
    protected array $callbackColumns = [];
    protected array $whereConditions = [];
    protected array $uploadConfigs = [];
    protected array $hiddenFields = [];
    protected array $filterFields = [];

    protected bool $showExport = true;
    protected bool $showPrint = true;
    protected bool $showInlineEdit = false;
    protected array $inlineEditFields = [];

    protected array $dependentRelations = [];

    public function __construct()
    {
        $this->request = service('request');
    }

    // --- MÉTODOS FLUIDOS (Configuración) ---
    
    public function setTable(string $table): self { $this->table = $table; return $this; }
    public function setPrimaryKey(string $key): self { $this->primaryKey = $key; return $this; }
    public function setSubject(string $singular, ?string $plural = null): self {
        $this->subjectSingular = $singular;
        $this->subjectPlural = $plural ?? $singular . 's';
        return $this;
    }
    

    public function setDependentRelation(
        string $field,
        string $dependsOn,
        string $relatedTable,
        string $displayField,
        string $foreignKey,
        array $where = []
    ): self {
        $this->dependentRelations[$field] = [
            'depends_on'    => $dependsOn,
            'table'         => $relatedTable,
            'field'         => $displayField,
            'foreign_key'   => $foreignKey,
            'where'         => $where,
        ];
        return $this;
    }

    public function columns(array $columns): self { $this->columns = $columns; return $this; }
    public function fields(array $fields): self { $this->fields = $fields; return $this; }
    public function addFields(array $fields): self { $this->addFields = $fields; return $this; }
    public function editFields(array $fields): self { $this->editFields = $fields; return $this; }
    public function enableBulkDelete(): self { $this->showBulkDelete = true; return $this; }
    public function disableExport(): self { $this->showExport = false; return $this; }
    public function disablePrint(): self { $this->showPrint = false; return $this; }

    public function enableInlineEdit(array $fields = []): self
    {
        $this->showInlineEdit = true;
        $this->inlineEditFields = $fields;
        return $this;
    }

    protected function isInlineEditable(string $field): bool
    {
        if (!$this->showInlineEdit) return false;
        
        if (isset($this->callbackColumns[$field])) return false;
        if (isset($this->ntoNRelations[$field])) return false;
        if (isset($this->uploadConfigs[$field])) return false;
        
        if (isset($this->hiddenFields[$field])) {
            $hiddenAction = $this->hiddenFields[$field]['action'];
            if ($hiddenAction === 'both' || $hiddenAction === 'all') {
                return false;
            }
        }
        
        if (isset($this->fieldTypes[$field]) && $this->fieldTypes[$field] === 'wysiwyg') return false;
        
        if (!empty($this->inlineEditFields) && !in_array($field, $this->inlineEditFields)) {
            return false;
        }
        
        return true;
    }
    
    public function where(string|array $field, ?string $operator = null, $value = null): self
    {
        if (is_array($field)) {
            foreach ($field as $key => $val) {
                $this->whereConditions[] = [$key, '=', $val];
            }
        } elseif ($operator === null) {
            $this->whereConditions[] = [$field, '=', $value];
        } else {
            $this->whereConditions[] = [$field, $operator, $value];
        }
        return $this;
    }

    public function displayAs(string|array $field, ?string $label = null): self {
        if (is_array($field)) {
            $this->fieldLabels = array_merge($this->fieldLabels, $field);
        } else {
            $this->fieldLabels[$field] = $label;
        }
        return $this;
    }

    public function callbackColumn(string $field, callable $callback): self
    {
        $this->callbackColumns[$field] = $callback;
        return $this;
    }

    public function callbackAfterInsert(callable $callback): self { $this->callbacks['afterInsert'] = $callback; return $this; }
    public function callbackAfterUpdate(callable $callback): self { $this->callbacks['afterUpdate'] = $callback; return $this; }
    public function callbackAfterDelete(callable $callback): self { $this->callbacks['afterDelete'] = $callback; return $this; }
    public function callbackBeforeUpload(callable $callback): self { $this->callbacks['beforeUpload'] = $callback; return $this; }
    public function callbackAfterUpload(callable $callback): self { $this->callbacks['afterUpload'] = $callback; return $this; }
    public function callbackBeforeDeleteFile(callable $callback): self { $this->callbacks['beforeDeleteFile'] = $callback; return $this; }
    public function callbackAfterDeleteFile(callable $callback): self { $this->callbacks['afterDeleteFile'] = $callback; return $this; }
    public function callbackAfterClone(callable $callback): self { $this->callbacks['afterClone'] = $callback; return $this; }
    public function callbackBeforeBulkDelete(callable $callback): self { $this->callbacks['beforeBulkDelete'] = $callback; return $this; }
    public function callbackAfterBulkDelete(callable $callback): self { $this->callbacks['afterBulkDelete'] = $callback; return $this; }
    public function callbackBeforeNtoN(callable $callback): self { $this->callbacks['beforeNtoN'] = $callback; return $this; }
    public function callbackAfterNtoN(callable $callback): self { $this->callbacks['afterNtoN'] = $callback; return $this; }

    public function fieldType(string $field, string $type, array $options = []): self
    {
        $this->fieldTypes[$field] = $type;
        
        if ($type === 'file' || $type === 'image') {
            $this->uploadConfigs[$field] = array_merge([
                'upload_path' => 'uploads/',
                'max_size' => 2048,
                'allowed_types' => $type === 'image' ? 'jpg|png|gif|webp' : 'pdf|doc|docx|zip',
                'required' => false,
                'thumbnail' => $type === 'image',
                'thumbnail_size' => [150, 150]
            ], $options);
        } elseif ($options) {
            $this->fieldOptions[$field] = $options;
        }
        
        return $this;
    }

    public function setHidden(string $field, $value = null, string $action = 'both'): self
    {
        $this->hiddenFields[$field] = [
            'value' => $value,
            'action' => $action
        ];
        return $this;
    }

    public function addExtra(string $label, string $icon, string $url = '', string $js = ''): self
    {
        $this->extraButtons[] = [
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'js' => $js
        ];
        return $this;
    }

    public function addAction(string $label, string $icon, string $url = '', string $js = ''): self
    {
        $this->actionButtons[] = [
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
            'js' => $js
        ];
        return $this;
    }

    public function fieldRequired(string|array $fields): self {
        $fieldsArray = is_array($fields) ? $fields : [$fields];
        foreach ($fieldsArray as $f) {
            $currentRules = $this->validationRules[$f] ?? '';
            $this->validationRules[$f] = $currentRules ? $currentRules . '|required' : 'required';
        }
        return $this;
    }

    public function fieldReadOnly(string|array $fields): self {
        $fieldsArray = is_array($fields) ? $fields : [$fields];
        foreach ($fieldsArray as $f) $this->fieldReadonly[] = $f;
        return $this;
    }

    public function fieldDisabled(string|array $fields): self {
        $fieldsArray = is_array($fields) ? $fields : [$fields];
        foreach ($fieldsArray as $f) $this->fieldDisabled[] = $f;
        return $this;
    }

    public function setRelation(string $field, string $relatedTable, string $displayField, ?string $orderBy = null, array|callable $where = []): self {
        $this->relations[$field] = [
            'table' => $relatedTable,
            'field' => $displayField,
            'order' => $orderBy,
            'where' => $where
        ];
        return $this;
    }

    public function setRelationNtoN(
        string $field,
        string $pivotTable,
        string $relatedTable,
        string $pivotPrimaryField,
        string $pivotRelatedField,
        string $displayField,
        ?string $orderField = null
    ): self {
        $this->ntoNRelations[$field] = [
            'pivot_table'         => $pivotTable,
            'related_table'       => $relatedTable,
            'pivot_primary_field' => $pivotPrimaryField,
            'pivot_related_field' => $pivotRelatedField,
            'display_field'       => $displayField,
            'order_field'         => $orderField,
        ];
        return $this;
    }

    public function unsetAdd(): self { $this->showAdd = false; return $this; }
    public function unsetEdit(): self { $this->showEdit = false; return $this; }
    public function unsetDelete(): self { $this->showDelete = false; return $this; }
    public function unsetView(): self { $this->showView = false; return $this; }
    public function unsetClone(): self { $this->showClone = false; return $this; }

    public function callbackBeforeInsert(callable $callback): self { $this->callbacks['beforeInsert'] = $callback; return $this; }
    public function callbackBeforeUpdate(callable $callback): self { $this->callbacks['beforeUpdate'] = $callback; return $this; }
    public function callbackBeforeDelete(callable $callback): self { $this->callbacks['beforeDelete'] = $callback; return $this; }
    public function callbackBeforeClone(callable $callback): self { $this->callbacks['beforeClone'] = $callback; return $this; }
    public function clearOnClone(array $fields): self { $this->clearOnClone = $fields; return $this; }

    public function setValidationRules(array $rules): self
    {
        $this->validationRules = array_merge($this->validationRules, $rules);
        return $this;
    }

    public function setValidationRule(string $field, string $rules): self
    {
        $this->validationRules[$field] = $rules;
        return $this;
    }

    // --- MÉTODO DE EJECUCIÓN PRINCIPAL ---
    public function render()
    {
        $action = $this->getCurrentAction();
        $id = $this->getCurrentId();
        $baseUrl = $this->getBaseUrl();

        if ($action === 'tcdelete') {
            return $this->processDelete($id, $baseUrl);
        }

        if ($action === 'tcbulkdelete') {
            return $this->processBulkDelete($baseUrl);
        }

        if ($action === 'tcfilter') {
            return $this->processFilter();
        }

        if ($action === 'tcclearfilters') {
            return $this->processClearFilters();
        }

        if ($action === 'tcexportcsv') {
            if (!$this->showExport) {
                return redirect()->to($this->getBaseUrl())->with('error', $this->translate('export_disabled'));
            }
            return $this->processExportCSV();
        }

        if ($action === 'tcexportpdf') {
            if (!$this->showExport) {
                return redirect()->to($this->getBaseUrl())->with('error', $this->translate('export_disabled'));
            }
            return $this->processExportPDF();
        }

        if ($action === 'tcprint') {
            if (!$this->showPrint) {
                return redirect()->to($this->getBaseUrl())->with('error', $this->translate('print_disabled'));
            }
            return $this->processPrint();
        }

        if ($action === 'tcinlineedit') {
            if (!$this->showInlineEdit) {
                return $this->jsonResponse(false, $this->translate('inline_edit_disabled'));
            }
            return $this->processInlineEdit($id);
        }

        if ($action === 'tcdependent') {
            return $this->processDependentRelation();
        }

        if ($action === 'tcadd') {
            if ($this->request->getMethod() === 'POST') {
                return $this->processForm(null, $baseUrl);
            }
            $html = $this->renderForm('add', null, $baseUrl);
            return $this->wrapOutput($html);
        }

        if ($action === 'tcedit') {
            if ($this->request->getMethod() === 'POST') {
                return $this->processForm($id, $baseUrl);
            }
            $html = $this->renderForm('edit', $id, $baseUrl);
            return $this->wrapOutput($html);
        }

        if ($action === 'tcclone') {
            if ($this->request->getMethod() === 'POST') {
                return $this->processClone($id, $baseUrl);
            }
            $html = $this->renderForm('add', $id, $baseUrl, true);
            return $this->wrapOutput($html);
        }

        if ($this->request->getGet('tcrud_ajax') == 1) {
            return $this->renderDataTablesAjax($baseUrl);
        }

        if ($action === 'tcview') {
            return $this->renderView($id, $baseUrl);
        }

        return $this->renderList($baseUrl);
    }

    // --- MÉTODOS DE FILTROS ---
    protected function processFilter()
    {
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');
        
        if ($field) {
            $this->saveFilterToSession($field, $value);
        }
        
        return $this->jsonResponse(true, $this->translate('filter_applied'));
    }

    protected function processClearFilters()
    {
        $this->clearFiltersFromSession();
        return $this->jsonResponse(true, $this->translate('filters_cleared'));
    }

    protected function jsonResponse(bool $success, string $message): object
    {
        return (object) [
            'is_json' => true,
            'output' => json_encode(['success' => $success, 'message' => $message])
        ];
    }

    // --- MÉTODOS DE ELIMINACIÓN ---
    protected function processDelete(?int $id = null, string $baseUrl)
    {
        if (!$id) {
            return redirect()->to($baseUrl)->with('error', $this->translate('error_invalid_id'));
        }

        $model = $this->getModel();
        
        if (isset($this->callbacks['beforeDelete'])) {
            call_user_func($this->callbacks['beforeDelete'], $id);
        }
        
        $data = $model->find($id);
        if ($data) {
            foreach ($this->uploadConfigs as $field => $config) {
                if (isset($data->$field) && $data->$field) {
                    if (isset($this->callbacks['beforeDeleteFile'])) {
                        call_user_func($this->callbacks['beforeDeleteFile'], $data->$field);
                    }
                    
                    $this->deleteFile($data->$field);
                    
                    if (isset($this->callbacks['afterDeleteFile'])) {
                        call_user_func($this->callbacks['afterDeleteFile'], $data->$field);
                    }
                }
            }
        }
        
        $model->deleteNtoN($id);
        $model->delete($id);

        if (isset($this->callbacks['afterDelete'])) {
            call_user_func($this->callbacks['afterDelete'], $id);
        }

        // Invalidar caché después de eliminar
        if (config('TCrud')->enableCache) {
            foreach ($this->relations as $field => $relation) {
                $cacheKey = $this->getCacheKey('relation', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
            foreach ($this->ntoNRelations as $field => $config) {
                $cacheKey = $this->getCacheKey('ntoN', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
            foreach ($this->dependentRelations as $field => $config) {
                $cacheKey = $this->getCacheKey('dependent', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
        }

        return redirect()->to($baseUrl)->with('success', $this->translate('success_delete'));
    }

    protected function processBulkDelete(string $baseUrl)
    {
        $ids = $this->request->getPost('ids');
        
        if (empty($ids)) {
            return $this->jsonResponse(false, $this->translate('error_no_selection'));
        }
        
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        if (isset($this->callbacks['beforeBulkDelete'])) {
            $ids = call_user_func($this->callbacks['beforeBulkDelete'], $ids);
        }
        
        $model = $this->getModel();
        
        foreach ($ids as $id) {
            if (isset($this->callbacks['beforeDelete'])) {
                call_user_func($this->callbacks['beforeDelete'], $id);
            }
            
            $data = $model->find($id);
            if ($data) {
                foreach ($this->uploadConfigs as $field => $config) {
                    if (isset($data->$field) && $data->$field) {
                        if (isset($this->callbacks['beforeDeleteFile'])) {
                            call_user_func($this->callbacks['beforeDeleteFile'], $data->$field);
                        }
                        
                        $this->deleteFile($data->$field);
                        
                        if (isset($this->callbacks['afterDeleteFile'])) {
                            call_user_func($this->callbacks['afterDeleteFile'], $data->$field);
                        }
                    }
                }
            }
            
            $model->deleteNtoN($id);
            $model->delete($id);
            
            if (isset($this->callbacks['afterDelete'])) {
                call_user_func($this->callbacks['afterDelete'], $id);
            }
        }
        
        if (isset($this->callbacks['afterBulkDelete'])) {
            call_user_func($this->callbacks['afterBulkDelete'], $ids);
        }
        
        // Invalidar caché después de eliminar
        if (config('TCrud')->enableCache) {
            foreach ($this->relations as $field => $relation) {
                $cacheKey = $this->getCacheKey('relation', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
            foreach ($this->ntoNRelations as $field => $config) {
                $cacheKey = $this->getCacheKey('ntoN', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
            foreach ($this->dependentRelations as $field => $config) {
                $cacheKey = $this->getCacheKey('dependent', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
        }
        
        return $this->jsonResponse(true, count($ids) . ' ' . $this->translate('success_bulk_delete'));
    }

    // --- MÉTODOS DE CLONACIÓN ---
    protected function processClone(?int $id = null, string $baseUrl)
    {
        if (!$id) {
            return redirect()->to($baseUrl)->with('error', $this->translate('error_invalid_id'));
        }

        $model = $this->getModel();
        $data = $model->find($id);
        
        if (!$data) {
            return redirect()->to($baseUrl)->with('error', $this->translate('error_record_not_found'));
        }

        $dataArray = (array) $data;
        unset($dataArray[$this->primaryKey]);
        
        foreach ($this->uploadConfigs as $field => $config) {
            $dataArray[$field] = null;
        }
        
        foreach ($this->clearOnClone as $field) {
            if (isset($dataArray[$field])) {
                $dataArray[$field] = '';
            }
        }
        
        if (isset($this->callbacks['beforeClone'])) {
            $dataArray = call_user_func($this->callbacks['beforeClone'], $dataArray);
        }

        $allowedFields = $model->db->getFieldNames($this->table);
        
        $filteredData = [];
        foreach ($dataArray as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $filteredData[$key] = $value;
            }
        }

        $model->setAllowedFields($allowedFields);
        $model->insert($filteredData);
        $newId = $model->getInsertID();
        
        if (isset($this->callbacks['afterClone'])) {
            call_user_func($this->callbacks['afterClone'], $filteredData, $newId);
        }

        return redirect()->to($baseUrl)->with('success', $this->translate('success_clone'));
    }

    // --- MÉTODOS DE VISTA DETALLE ---
    protected function renderView(?int $id = null, string $baseUrl)
    {
        if (!$id) {
            return redirect()->to($baseUrl)->with('error', $this->translate('error_invalid_id'));
        }

        $model = $this->getModel();
        $data = $model->find($id);
        
        if (!$data) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException($this->translate('error_record_not_found'));
        }

        $ntoNValues = [];
        foreach ($this->ntoNRelations as $field => $config) {
            $ntoNValues[$field] = $model->getNtoNValues($field, $id);
        }

        $config = config('TCrud');
        $cssFiles = [];
        $jsFiles = [];

        foreach ($config->assets as $key => $url) {
            if ($config->coreAssetsAlreadyIncluded && in_array($key, ['bootstrap_css', 'bootstrap_js', 'bootstrap_icons'])) {
                continue;
            }
            if (str_ends_with($url, '.css')) {
                $cssFiles[] = $url;
            } else {
                $jsFiles[] = $url;
            }
        }

        $fields = array_unique(array_merge($this->fields, $this->addFields, $this->editFields));

        $output = view('tcrud/view', [
            'subjectSingular' => $this->subjectSingular,
            'fields'          => $fields,
            'fieldLabels'     => $this->fieldLabels,
            'fieldTypes'      => $this->fieldTypes,
            'fieldOptions'    => $this->fieldOptions,
            'data'            => $data,
            'relations'       => $this->relations,
            'ntoNRelations'   => $this->ntoNRelations,
            'ntoNValues'      => $ntoNValues,
            'baseUrl'         => $baseUrl,
            'showEdit'        => $this->showEdit,
            'callbackColumns' => $this->callbackColumns,
            'uploadConfigs'   => $this->uploadConfigs,
            'hiddenFields'    => $this->hiddenFields,
            'primaryKey'      => $this->primaryKey,
            'lang' => [
                'add'                => $this->translate('add'),
                'edit'               => $this->translate('edit'),
                'delete'             => $this->translate('delete'),
                'view'               => $this->translate('view'),
                'clone'              => $this->translate('clone'),
                'save'               => $this->translate('save'),
                'cancel'             => $this->translate('cancel'),
                'back'               => $this->translate('back'),
                'export'             => $this->translate('export'),
                'print'              => $this->translate('print'),
                'export_csv'         => $this->translate('export_csv'),
                'export_pdf'         => $this->translate('export_pdf'),
                'actions'            => $this->translate('actions'),
                'search'             => $this->translate('search'),
                'clear_filters'      => $this->translate('clear_filters'),
                'bulk_delete'        => $this->translate('bulk_delete'),
                'select_columns'     => $this->translate('select_columns'),
                'image_preview'      => $this->translate('image_preview'),
                'yes'                => $this->translate('yes'),
                'no'                 => $this->translate('no'),
                'select_option'      => $this->translate('select_option'),
                'leave_blank'        => $this->translate('leave_blank'),
                'max_size'           => $this->translate('max_size'),
                'allowed_types'      => $this->translate('allowed_types'),
                'delete_current_file'=> $this->translate('delete_current_file'),
                'select_multiple'    => $this->translate('select_multiple'),
            ],
        ]);

        return (object) [
            'output'    => $output,
            'css_files' => $cssFiles,
            'js_files'  => $jsFiles
        ];
    }

    // --- MÉTODO AUXILIAR: OBTENER MODELO CONFIGURADO ---
    protected function getModel(): \Torno\TCrud\Models\GenericCrudModel
	{
		$model = new \Torno\TCrud\Models\GenericCrudModel();
        $model->setTable($this->table)
              ->setPrimaryKey($this->primaryKey)
              ->setRelations($this->relations)
              ->setNtoNRelations($this->ntoNRelations)
              ->setWhereConditions($this->whereConditions)
              ->setFilters($this->getFiltersFromSession())
              ->setDependentRelations($this->dependentRelations)
              ->setSearchColumns($this->columns);
        return $model;
    }

    // --- MÉTODOS DE FILTROS EN SESIÓN ---
    protected function getFilterSessionKey(): string
    {
        return 'tcrud_filters_' . $this->table;
    }

    protected function getFiltersFromSession(): array
    {
        return session()->get($this->getFilterSessionKey()) ?? [];
    }

    protected function saveFilterToSession(string $field, ?string $value): void
    {
        $filters = $this->getFiltersFromSession();
        
        if ($value === null || $value === '') {
            unset($filters[$field]);
        } else {
            $filters[$field] = $value;
        }
        
        session()->set($this->getFilterSessionKey(), $filters);
    }

    protected function clearFiltersFromSession(): void
    {
        session()->remove($this->getFilterSessionKey());
    }

    // --- DETECCIÓN DE URI ---
    protected function getCurrentAction(): string
    {
        $segments = service('uri')->getSegments();
        foreach ($segments as $segment) {
            if (strpos($segment, 'tc') === 0) {
                return $segment;
            }
        }
        return 'list';
    }

    protected function getCurrentId(): ?int
    {
        $segments = service('uri')->getSegments();
        for ($i = 0; $i < count($segments); $i++) {
            if (strpos($segments[$i], 'tc') === 0 && isset($segments[$i + 1]) && is_numeric($segments[$i + 1])) {
                return (int) $segments[$i + 1];
            }
        }
        return null;
    }

    protected function getBaseUrl(): string
    {
        $uri = service('uri');
        $segments = $uri->getSegments();
        
        $newSegments = [];
        $skipNext = false;
        foreach ($segments as $segment) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }
            if (strpos($segment, 'tc') === 0) {
                $skipNext = true;
                continue;
            }
            if (is_numeric($segment) && isset($segments[array_search($segment, $segments) - 1]) && strpos($segments[array_search($segment, $segments) - 1], 'tc') === 0) {
                continue;
            }
            $newSegments[] = $segment;
        }
        
        $baseUrl = implode('/', $newSegments);
        return '/' . ltrim($baseUrl, '/');
    }

    // --- RENDERIZADO DEL LISTADO ---
    protected function renderList(string $baseUrl): object
    {
        $config = config('TCrud');
        $cssFiles = [];
        $jsFiles = [];

        foreach ($config->assets as $key => $url) {
            if ($config->coreAssetsAlreadyIncluded && in_array($key, ['bootstrap_css', 'bootstrap_js', 'bootstrap_icons'])) {
                continue;
            }
            if (str_ends_with($url, '.css')) {
                $cssFiles[] = $url;
            } else {
                $jsFiles[] = $url;
            }
        }

        $this->buildFilterFields();
        $activeFilters = $this->getFiltersFromSession();
        $savedState = $this->getStateFromSession();

        $lang = config('TCrud')->language ?? 'es';
        $i18nFile = ($lang === 'en') ? 'en-GB.json' : 'es-ES.json';

        $output = view('tcrud/list', [
            'subjectSingular' => $this->subjectSingular,
            'subjectPlural'   => $this->subjectPlural,
            'columns'         => $this->columns,
            'fieldLabels'     => $this->fieldLabels,
            'showAdd'         => $this->showAdd,
            'relations'       => $this->relations,
            'ntoNRelations'   => $this->ntoNRelations,
            'callbacks'       => $this->callbacks,
            'primaryKey'      => $this->primaryKey,
            'showBulkDelete'  => $this->showBulkDelete,
            'extraButtons'    => $this->extraButtons,
            'actionButtons'   => $this->actionButtons,
            'callbackColumns' => $this->callbackColumns,
            'whereConditions' => $this->whereConditions,
            'filterFields'    => $this->filterFields,
            'activeFilters'   => $activeFilters,
            'savedState'      => $savedState,
            'baseUrl'         => $baseUrl,
            'exportColumns' => $this->getExportColumns(),
            'showExport' => $this->showExport,
            'showPrint' => $this->showPrint,
            'i18nUrl' => base_url('assets/tcrud/i18n/' . $i18nFile),
            'jsTranslations' => [
                'confirm_delete'              => $this->translate('confirm_delete'),
                'confirm_delete_text'         => $this->translate('confirm_delete_text'),
                'confirm_delete_yes'          => $this->translate('confirm_delete_yes'),
                'confirm_clone'               => $this->translate('confirm_clone'),
                'confirm_clone_text'          => $this->translate('confirm_clone_text'),
                'confirm_clone_yes'           => $this->translate('confirm_clone_yes'),
                'confirm_bulk_delete'         => $this->translate('confirm_bulk_delete'),
                'confirm_bulk_delete_text'    => $this->translate('confirm_bulk_delete_text'),
                'confirm_bulk_delete_yes'     => $this->translate('confirm_bulk_delete_yes'),
                'cancel'                      => $this->translate('cancel'),
                'error_no_selection_title'    => $this->translate('error_no_selection_title'),
                'error_no_selection'          => $this->translate('error_no_selection'),
                'error_title'                   => $this->translate('error_title'),
                'error_request'               => $this->translate('error_request'),
                'deleted_title'               => $this->translate('deleted_title'),
                'select_at_least_one'         => $this->translate('select_at_least_one'),
                'success_save'                => $this->translate('success_save'),
                'success_delete'              => $this->translate('success_delete'),
                'success_clone'               => $this->translate('success_clone'),
            ], 
            'lang' => [
                'add'                => $this->translate('add'),
                'edit'               => $this->translate('edit'),
                'delete'             => $this->translate('delete'),
                'view'               => $this->translate('view'),
                'clone'              => $this->translate('clone'),
                'save'               => $this->translate('save'),
                'cancel'             => $this->translate('cancel'),
                'back'               => $this->translate('back'),
                'export'             => $this->translate('export'),
                'print'              => $this->translate('print'),
                'export_csv'         => $this->translate('export_csv'),
                'export_pdf'         => $this->translate('export_pdf'),
                'actions'            => $this->translate('actions'),
                'search'             => $this->translate('search'),
                'clear_filters'      => $this->translate('clear_filters'),
                'bulk_delete'        => $this->translate('bulk_delete'),
                'select_columns'     => $this->translate('select_columns'),
                'image_preview'      => $this->translate('image_preview'),
                'yes'                => $this->translate('yes'),
                'no'                 => $this->translate('no'),
                'select_option'      => $this->translate('select_option'),
                'leave_blank'        => $this->translate('leave_blank'),
                'max_size'           => $this->translate('max_size'),
                'allowed_types'      => $this->translate('allowed_types'),
                'delete_current_file'=> $this->translate('delete_current_file'),
                'select_multiple'    => $this->translate('select_multiple'),
            ],
        ]);


        return (object) [
            'output'    => $output,
            'css_files' => $cssFiles,
            'js_files'  => $jsFiles
        ];
    }

    protected function buildFilterFields(): void
    {
        $this->filterFields = [];
        
        foreach ($this->columns as $col) {
            if (isset($this->callbackColumns[$col])) {
                continue;
            }
            if (isset($this->ntoNRelations[$col])) {
                continue;
            }
            
            $label = $this->fieldLabels[$col] ?? ucfirst($col);
            $this->filterFields[$col] = [
                'label' => $label,
                'is_relation' => isset($this->relations[$col])
            ];
        }
    }

    // --- RENDERIZADO DEL FORMULARIO ---
    protected function renderForm(string $action, ?int $id = null, string $baseUrl, bool $isClone = false)
    {
        $model = $this->getModel();

        $activeFields = ($action === 'add') 
            ? array_merge($this->fields, $this->addFields) 
            : array_merge($this->fields, $this->editFields);
        $activeFields = array_unique($activeFields);

        $hiddenForAction = [];
        foreach ($this->hiddenFields as $field => $config) {
            $actionConfig = $config['action'];
            
            $shouldHide = false;
            if ($actionConfig === 'both' && in_array($action, ['add', 'edit'])) {
                $shouldHide = true;
            } elseif ($actionConfig === 'all') {
                $shouldHide = true;
            } elseif ($actionConfig === $action) {
                $shouldHide = true;
            }
            
            if ($shouldHide) {
                $hiddenForAction[] = $field;
            }
        }
        $activeFields = array_diff($activeFields, $hiddenForAction);

        $data = (object)[];
        
        if ($action === 'edit' && $id) {
            $data = $model->find($id);
            if (!$data) throw new \CodeIgniter\Exceptions\PageNotFoundException($this->translate('error_record_not_found'));
        }

        if ($isClone && $id) {
            $data = $model->find($id);
            if (!$data) throw new \CodeIgniter\Exceptions\PageNotFoundException($this->translate('error_record_not_found'));
            
            $dataArray = (array) $data;
            
            foreach ($this->uploadConfigs as $field => $config) {
                $dataArray[$field] = null;
            }
            
            foreach ($this->clearOnClone as $field) {
                if (isset($dataArray[$field])) {
                    $dataArray[$field] = '';
                }
            }
            
            $data = (object) $dataArray;
        }

        $ntoNValues = [];
        if ($id) {
            foreach ($this->ntoNRelations as $field => $config) {
                $ntoNValues[$field] = $model->getNtoNIds($field, $id);
            }
        }

        // ============================================================
        // CACHEAR OPCIONES DE RELACIONES (dropdowns)
        // ============================================================
        $relationOptions = [];
        foreach ($this->relations as $field => $relation) {
            $cacheKey = $this->getCacheKey('relation', $this->table . '_' . $field);
            $cached = $this->getFromCache($cacheKey);
            
            if ($cached !== null) {
                $relationOptions[$field] = $cached;
            } else {
                $db = \Config\Database::connect();
                $builder = $db->table($relation['table']);
                
                if (is_array($relation['where'])) {
                    foreach ($relation['where'] as $k => $v) {
                        $builder->where($k, $v);
                    }
                }
                
                if ($relation['order']) {
                    $builder->orderBy($relation['order']);
                }
                
                $options = $builder->get()->getResult();
                $relationOptions[$field] = $options;
                $this->saveToCache($cacheKey, $options);
            }
        }

        // ============================================================
        // CACHEAR OPCIONES DE N a N
        // ============================================================
        $ntoNOptions = [];
        foreach ($this->ntoNRelations as $field => $config) {
            $cacheKey = $this->getCacheKey('ntoN', $this->table . '_' . $field);
            $cached = $this->getFromCache($cacheKey);
            
            if ($cached !== null) {
                $ntoNOptions[$field] = $cached;
            } else {
                $db = \Config\Database::connect();
                $options = $db->table($config['related_table'])->get()->getResult();
                $ntoNOptions[$field] = $options;
                $this->saveToCache($cacheKey, $options);
            }
        }

        // ============================================================
        // CACHEAR OPCIONES DE RELACIONES DEPENDIENTES
        // ============================================================
        $dependentOptions = [];
        foreach ($this->dependentRelations as $field => $config) {
            // Si es edit y el padre tiene valor, cargar solo las opciones del padre
            if ($action === 'edit' && $id) {
                $parentField = $config['depends_on'];
                $parentValue = $data->$parentField ?? null;
                
                if ($parentValue) {
                    $cacheKey = $this->getCacheKey('dependent', $this->table . '_' . $field . '_' . $parentValue);
                    $cached = $this->getFromCache($cacheKey);
                    
                    if ($cached !== null) {
                        $dependentOptions[$field] = $cached;
                    } else {
                        $db = \Config\Database::connect();
                        $builder = $db->table($config['table']);
                        $builder->where($config['foreign_key'], $parentValue);
                        if (!empty($config['where'])) {
                            foreach ($config['where'] as $k => $v) {
                                $builder->where($k, $v);
                            }
                        }
                        $options = $builder->get()->getResult();
                        $dependentOptions[$field] = $options;
                        $this->saveToCache($cacheKey, $options);
                    }
                } else {
                    $dependentOptions[$field] = [];
                }
            } else {
                // En add, cargar todas las opciones (o ninguna)
                $dependentOptions[$field] = [];
            }
        }

        return view('tcrud/form', [
            'action'             => $action,
            'subjectSingular'    => $this->subjectSingular,
            'fields'             => $activeFields,
            'fieldTypes'         => $this->fieldTypes,
            'fieldLabels'        => $this->fieldLabels,
            'fieldOptions'       => $this->fieldOptions,
            'fieldReadonly'      => $this->fieldReadonly,
            'fieldDisabled'      => $this->fieldDisabled,
            'data'               => $data,
            'validationErrors'   => [],
            'baseUrl'            => $baseUrl,
            'relations'          => $this->relations,
            'ntoNRelations'      => $this->ntoNRelations,
            'ntoNValues'         => $ntoNValues,
            'validationRules'    => $this->validationRules,
            'uploadConfigs'      => $this->uploadConfigs,
            'dependentRelations' => $this->dependentRelations,
            'relationOptions'    => $relationOptions,
            'ntoNOptions'        => $ntoNOptions,
            'dependentOptions'   => $dependentOptions,
            'lang' => [
                'add'                => $this->translate('add'),
                'edit'               => $this->translate('edit'),
                'delete'             => $this->translate('delete'),
                'view'               => $this->translate('view'),
                'clone'              => $this->translate('clone'),
                'save'               => $this->translate('save'),
                'cancel'             => $this->translate('cancel'),
                'back'               => $this->translate('back'),
                'export'             => $this->translate('export'),
                'print'              => $this->translate('print'),
                'export_csv'         => $this->translate('export_csv'),
                'export_pdf'         => $this->translate('export_pdf'),
                'actions'            => $this->translate('actions'),
                'search'             => $this->translate('search'),
                'clear_filters'      => $this->translate('clear_filters'),
                'bulk_delete'        => $this->translate('bulk_delete'),
                'select_columns'     => $this->translate('select_columns'),
                'image_preview'      => $this->translate('image_preview'),
                'yes'                => $this->translate('yes'),
                'no'                 => $this->translate('no'),
                'select_option'      => $this->translate('select_option'),
                'leave_blank'        => $this->translate('leave_blank'),
                'max_size'           => $this->translate('max_size'),
                'allowed_types'      => $this->translate('allowed_types'),
                'delete_current_file'=> $this->translate('delete_current_file'),
                'select_multiple'    => $this->translate('select_multiple'),
            ],
        ]);
    }

    // --- PROCESAMIENTO DEL FORMULARIO ---
    protected function processForm(?int $id = null, string $baseUrl)
    {
        $validation = \Config\Services::validation();
        
        $rules = $this->validationRules;
        
        if (empty($rules)) {
            foreach ($this->fields as $field) {
                $rules[$field] = 'permit_empty|string';
            }
        }
        
        foreach ($rules as $field => $rule) {
            if (strpos($rule, '{id}') !== false && $id !== null) {
                $rules[$field] = str_replace('{id}', (string)$id, $rule);
            } elseif (strpos($rule, '{id}') !== false) {
                $rules[$field] = str_replace(',id,{id}', '', $rule);
            }
        }
        
        $validation->setRules($rules);
        $postData = $this->request->getPost();
        
        $model = $this->getModel();
        $existingData = null;
        if ($id !== null) {
            $existingData = $model->find($id);
        }
        
        foreach ($this->relations as $field => $relation) {
            if (isset($postData[$field]) && $postData[$field] !== '') {
                $postData[$field] = (int) $postData[$field];
            }
        }
        
        $currentAction = ($id === null) ? 'add' : 'edit';
        foreach ($this->hiddenFields as $field => $config) {
            $forcedValue = $config['value'];
            $actionConfig = $config['action'];
            
            $applies = false;
            if ($actionConfig === 'both' && in_array($currentAction, ['add', 'edit'])) {
                $applies = true;
            } elseif ($actionConfig === 'all') {
                $applies = true;
            } elseif ($actionConfig === $currentAction) {
                $applies = true;
            }
            
            if (!$applies) continue;
            
            if ($currentAction === 'edit') {
                if ($existingData && isset($existingData->$field)) {
                    $postData[$field] = $existingData->$field;
                }
                continue;
            }
            
            if ($forcedValue !== null) {
                $postData[$field] = $forcedValue;
            }
        }
        
        foreach ($this->uploadConfigs as $field => $config) {
            $file = $this->request->getFile($field);
            $deleteFile = $this->request->getPost('delete_' . $field);
            
            if ($deleteFile && $existingData && isset($existingData->$field) && $existingData->$field) {
                if (isset($this->callbacks['beforeDeleteFile'])) {
                    call_user_func($this->callbacks['beforeDeleteFile'], $existingData->$field);
                }
                
                $this->deleteFile($existingData->$field);
                
                if (isset($this->callbacks['afterDeleteFile'])) {
                    call_user_func($this->callbacks['afterDeleteFile'], $existingData->$field);
                }
                
                $postData[$field] = null;
                continue;
            }
            
            if ($file && $file->isValid() && $file->getError() === UPLOAD_ERR_OK) {
                if (isset($this->callbacks['beforeUpload'])) {
                    $file = call_user_func($this->callbacks['beforeUpload'], $file, $field);
                    if ($file === false) {
                        return redirect()->back()->withInput()->with('error', $this->translate('upload_cancelled') . ': ' . $field);
                    }
                }
                
                $uploadedPath = $this->uploadFile($file, $config);
                if ($uploadedPath) {
                    if ($existingData && isset($existingData->$field) && $existingData->$field) {
                        $this->deleteFile($existingData->$field);
                    }
                    $postData[$field] = $uploadedPath;
                    
                    if (isset($this->callbacks['afterUpload'])) {
                        call_user_func($this->callbacks['afterUpload'], $uploadedPath, $field);
                    }
                } else {
                    return redirect()->back()->withInput()->with('error', $this->translate('error_upload') . ': ' . $field);
                }
            } else {
                if ($existingData && isset($existingData->$field)) {
                    $postData[$field] = $existingData->$field;
                }
            }
        }
        
        $forbiddenFields = ['csrf_test_name', 'form_action', 'confirm_password'];
        foreach ($forbiddenFields as $field) {
            if (isset($postData[$field])) unset($postData[$field]);
        }
        
        foreach (array_keys($this->uploadConfigs) as $field) {
            if (isset($postData['delete_' . $field])) unset($postData['delete_' . $field]);
        }
        
        foreach (array_keys($this->ntoNRelations) as $field) {
            if (isset($postData[$field])) unset($postData[$field]);
        }
        
        foreach ($this->fieldTypes as $field => $type) {
            if ($type === 'wysiwyg' && isset($postData[$field])) {
                $postData[$field] = $this->sanitizeHtml($postData[$field]);
            }
        }
        
        foreach ($this->fieldTypes as $field => $type) {
            if ($type === 'checkbox') {
                $postData[$field] = isset($postData[$field]) ? 1 : 0;
            }
        }
        
        if (!$validation->run($postData)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        if ($id === null && isset($this->callbacks['beforeInsert'])) {
            $postData = call_user_func($this->callbacks['beforeInsert'], $postData);
        } elseif ($id !== null && isset($this->callbacks['beforeUpdate'])) {
            $postData = call_user_func($this->callbacks['beforeUpdate'], $postData, $id);
        }

        $allowedFields = $model->db->getFieldNames($this->table);
        
        $filteredData = [];
        foreach ($postData as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $filteredData[$key] = $value;
            }
        }

        $model->setAllowedFields($allowedFields);
        
        try {
            if ($id === null) {
                $model->insert($filteredData);
                $newId = $model->getInsertID();
                
                if (isset($this->callbacks['afterInsert'])) {
                    call_user_func($this->callbacks['afterInsert'], $filteredData, $newId);
                }
            } else {
                $model->update($id, $filteredData);
                $newId = $id;
                
                if (isset($this->callbacks['afterUpdate'])) {
                    call_user_func($this->callbacks['afterUpdate'], $filteredData, $id);
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $this->translate('error_save') . ': ' . $e->getMessage());
        }

        foreach ($this->ntoNRelations as $field => $config) {
            $selectedIds = $this->request->getPost($field) ?? [];
            if (!is_array($selectedIds)) {
                $selectedIds = [];
            }
            
            if (isset($this->callbacks['beforeNtoN'])) {
                $selectedIds = call_user_func($this->callbacks['beforeNtoN'], $field, $selectedIds, $newId);
            }
            
            $model->saveNtoN($field, $newId, $selectedIds);
            
            if (isset($this->callbacks['afterNtoN'])) {
                call_user_func($this->callbacks['afterNtoN'], $field, $selectedIds, $newId);
            }
        }

        // Invalidar caché después de guardar
        if (config('TCrud')->enableCache) {
            foreach ($this->relations as $field => $relation) {
                $cacheKey = $this->getCacheKey('relation', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
            foreach ($this->ntoNRelations as $field => $config) {
                $cacheKey = $this->getCacheKey('ntoN', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
            foreach ($this->dependentRelations as $field => $config) {
                $cacheKey = $this->getCacheKey('dependent', $this->table . '_' . $field);
                cache()->delete($cacheKey);
            }
        }

        return redirect()->to($baseUrl)->with('success', $this->translate('success_save'));
    }

    // --- DATATABLES AJAX ---
    protected function renderDataTablesAjax(string $baseUrl): object
    {
        $state = [
            'start' => (int)($this->request->getGet('start') ?? 0),
            'length' => (int)($this->request->getGet('length') ?? 15),
            'order_column' => (int)($this->request->getGet('order')[0]['column'] ?? 0),
            'order_dir' => strtolower($this->request->getGet('order')[0]['dir'] ?? 'desc'),
            'search' => trim($this->request->getGet('search')['value'] ?? ''),
        ];
        $this->saveStateToSession($state);

        $model = $this->getModel();
        
        $searchValue = trim($this->request->getGet('search')['value'] ?? '');
        $orderColumnIndex = (int)($this->request->getGet('order')[0]['column'] ?? 0);
        $orderDir = strtolower($this->request->getGet('order')[0]['dir'] ?? 'asc');
        $start = (int)($this->request->getGet('start') ?? 0);
        $length = (int)($this->request->getGet('length') ?? 15);
        
        $orderCol = null;
        if (isset($this->columns[$orderColumnIndex])) {
            $orderCol = $this->columns[$orderColumnIndex];
            if (isset($this->callbackColumns[$orderCol]) || isset($this->ntoNRelations[$orderCol])) {
                $orderCol = null;
            }
        }
        
        $totalRecords = $model->countTotal();
        $filteredRecords = $model->countFiltered($searchValue);
        $results = $model->getDataTablesData($searchValue, $orderCol, $orderDir, $start, $length);
        
        $data = [];
        foreach ($results as $row) {
            $rowData = [];
            $rowId = $row->{$this->primaryKey};
            
            foreach ($this->columns as $col) {
                $rawValue = ''; 
                if (isset($this->callbackColumns[$col])) {
                    $rowData[$col] = call_user_func($this->callbackColumns[$col], $row);
                    continue;
                }
                
                if (isset($this->ntoNRelations[$col])) {
                    $names = $model->getNtoNValues($col, $row->{$this->primaryKey});
                    if (!empty($names)) {
                        $badges = array_map(fn($n) => '<span class="badge bg-secondary me-1">' . esc($n) . '</span>', $names);
                        $rowData[$col] = implode('', $badges);
                    } else {
                        $rowData[$col] = '<span class="text-muted">—</span>';
                    }
                    continue;
                }
                
                if (isset($this->uploadConfigs[$col])) {
                    $config = $this->uploadConfigs[$col];
                    $isImage = isset($this->fieldTypes[$col]) && $this->fieldTypes[$col] === 'image';
                    $val = $row->$col ?? '';
                    
                    if ($val) {
                        if ($isImage) {
                            $thumb = $this->getThumbnailPath($val, $config['thumbnail_size'] ?? [150, 150]);
                            $fullUrl = base_url($val);
                            $thumbUrl = $thumb ? base_url($thumb) : $fullUrl;
                            $rowData[$col] = '<a href="#" class="image-thumbnail" data-full="' . $fullUrl . '"><img src="' . $thumbUrl . '" alt="Thumbnail" class="img-thumbnail" style="max-height: 50px; cursor: pointer;"></a>';
                        } else {
                            $ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
                            $icon = $this->getFileIcon($ext);
                            $filename = basename($val);
                            $rowData[$col] = '<a href="' . base_url($val) . '" target="_blank" class="file-icon" data-filename="' . $filename . '"><i class="bi bi-' . $icon . ' fs-3" style="cursor: pointer;"></i></a>';
                        }
                    } else {
                        $rowData[$col] = '<span class="text-muted">—</span>';
                    }
                    continue;
                }
                
                if (isset($this->fieldTypes[$col]) && $this->fieldTypes[$col] === 'checkbox') {
                    $val = $row->$col ?? 0;
                    $badge = $val == 1 ? 'success' : 'secondary';
                    $text = $val == 1 ? $this->translate('yes') : $this->translate('no');
                    $rowData[$col] = '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                    continue;
                }

                if (isset($this->hiddenFields[$col])) {
                    $hiddenAction = $this->hiddenFields[$col]['action'];
                    if ($hiddenAction === 'both' || $hiddenAction === 'all') {
                        $val = $row->$col ?? '';
                        $rowData[$col] = $val ?: '<span class="text-muted">—</span>';
                        continue;
                    }
                }

                $val = $row->$col ?? '';
                $rawValue = $row->$col ?? '';

                if (isset($this->relations[$col])) {
                    $alias = $this->relations[$col]['table'] . '_' . $this->relations[$col]['field'];
                    $val = $row->$alias ?? $val;
                }

                if (isset($this->dependentRelations[$col])) {
                    $alias = $this->dependentRelations[$col]['table'] . '_' . $this->dependentRelations[$col]['field'];
                    $val = $row->$alias ?? $val;
                }

                if (isset($this->fieldTypes[$col]) && $this->fieldTypes[$col] === 'dropdown' && isset($this->fieldOptions[$col])) {
                    $val = $this->fieldOptions[$col][$rawValue] ?? $rawValue;
                }

                if (isset($this->callbacks['column_' . $col])) {
                    $val = call_user_func($this->callbacks['column_' . $col], $val, $row);
                } else {
                    $val = esc((string)$val);
                }

                if ($this->isInlineEditable($col)) {
                    $fieldType = $this->fieldTypes[$col] ?? 'text';
                    
                    $options = [];
                    if ($fieldType === 'dropdown' && isset($this->fieldOptions[$col])) {
                        $options = $this->fieldOptions[$col];
                    }
                    
                    if (isset($this->relations[$col])) {
                        $fieldType = 'relation';
                        $relation = $this->relations[$col];
                        $relBuilder = $model->db->table($relation['table']);
                        
                        if (is_array($relation['where'])) {
                            foreach ($relation['where'] as $k => $v) {
                                $relBuilder->where($k, $v);
                            }
                        }
                        
                        if ($relation['order']) {
                            $relBuilder->orderBy($relation['order']);
                        }
                        
                        $relRows = $relBuilder->get()->getResult();
                        foreach ($relRows as $relRow) {
                            $options[$relRow->id] = $relRow->{$relation['field']};
                        }
                    }
                    
                    $optionsAttr = '';
                    if (!empty($options)) {
                        $optionsAttr = ' data-options=\'' . json_encode($options) . '\'';
                    }
                    
                    $val = '<span class="tcrud-inline-cell" 
                                data-id="' . $rowId . '" 
                                data-field="' . $col . '" 
                                data-value="' . esc((string)$rawValue) . '"
                                data-type="' . $fieldType . '"' . $optionsAttr . '>' 
                        . $val 
                        . '</span>'
                        . '<i class="bi bi-pencil tcrud-inline-edit-icon" 
                                data-id="' . $rowId . '" 
                                data-field="' . $col . '" 
                                data-value="' . esc((string)$rawValue) . '"
                                data-type="' . $fieldType . '"' . $optionsAttr . '></i>';
                }
                $rowData[$col] = $val;
            }

            $rowData['checkbox'] = '<input type="checkbox" class="form-check-input row-checkbox" value="' . $rowId . '">';

            $actions = $this->buildActions($rowId, $baseUrl);
            $rowData['actions'] = $actions;

            $data[] = $rowData;
        }

        $response = [
            'draw'            => (int)($this->request->getGet('draw') ?? 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data
        ];

        return (object) [
            'output'    => json_encode($response, JSON_UNESCAPED_UNICODE),
            'css_files' => [],
            'js_files'  => [],
            'is_json'   => true
        ];
    }

    protected function buildActions(int $rowId, string $baseUrl): string
    {
        $actions = '<div class="btn-group btn-group-sm" role="group">';
        
        $visibleActions = 0;
        if ($this->showView) $visibleActions++;
        if ($this->showEdit) $visibleActions++;
        if ($this->showClone) $visibleActions++;
        if ($this->showDelete) $visibleActions++;
        $visibleActions += count($this->actionButtons);

        if ($visibleActions <= 2) {
            if ($this->showView) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcview/' . $rowId) . '" class="btn btn-outline-info" title="' . $this->translate('view') . '"><i class="bi bi-eye"></i></a>';
            }
            if ($this->showEdit) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcedit/' . $rowId) . '" class="btn btn-outline-warning" title="' . $this->translate('edit') . '"><i class="bi bi-pencil"></i></a>';
            }
            if ($this->showClone) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcclone/' . $rowId) . '" class="btn btn-outline-secondary" title="' . $this->translate('clone') . '"><i class="bi bi-files"></i></a>';
            }
            if ($this->showDelete) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcdelete/' . $rowId) . '" class="btn btn-outline-danger" title="' . $this->translate('delete') . '"><i class="bi bi-trash"></i></a>';
            }
            foreach ($this->actionButtons as $btn) {
                if ($btn['url']) {
                    $url = str_replace('{id}', $rowId, $btn['url']);
                    $actions .= '<a href="' . base_url($url) . '" class="btn btn-outline-secondary" title="' . esc($btn['label']) . '"><i class="bi bi-' . $btn['icon'] . '"></i></a>';
                } elseif ($btn['js']) {
                    $js = str_replace('{id}', $rowId, $btn['js']);
                    $actions .= '<button class="btn btn-outline-secondary" onclick="' . $js . '" title="' . esc($btn['label']) . '"><i class="bi bi-' . $btn['icon'] . '"></i></button>';
                }
            }
        } else {
            if ($this->showEdit) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcedit/' . $rowId) . '" class="btn btn-outline-warning" title="' . $this->translate('edit') . '"><i class="bi bi-pencil"></i></a>';
            } elseif ($this->showView) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcview/' . $rowId) . '" class="btn btn-outline-info" title="' . $this->translate('view') . '"><i class="bi bi-eye"></i></a>';
            } elseif ($this->showClone) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcclone/' . $rowId) . '" class="btn btn-outline-secondary" title="' . $this->translate('clone') . '"><i class="bi bi-files"></i></a>';
            } elseif ($this->showDelete) {
                $actions .= '<a href="' . base_url($baseUrl . '/tcdelete/' . $rowId) . '" class="btn btn-outline-danger" title="' . $this->translate('delete') . '"><i class="bi bi-trash"></i></a>';
            }
            
            $actions .= '<button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">';
            $actions .= '<span class="visually-hidden">' . $this->translate('more_actions') . '</span>';
            $actions .= '</button>';
            $actions .= '<ul class="dropdown-menu dropdown-menu-end">';
            
            if ($this->showView) {
                $actions .= '<li><a class="dropdown-item" href="' . base_url($baseUrl . '/tcview/' . $rowId) . '"><i class="bi bi-eye me-2"></i> ' . $this->translate('view') . '</a></li>';
            }
            if ($this->showEdit) {
                $actions .= '<li><a class="dropdown-item" href="' . base_url($baseUrl . '/tcedit/' . $rowId) . '"><i class="bi bi-pencil me-2"></i> ' . $this->translate('edit') . '</a></li>';
            }
            if ($this->showClone) {
                $actions .= '<li><a class="dropdown-item" href="' . base_url($baseUrl . '/tcclone/' . $rowId) . '"><i class="bi bi-files me-2"></i> ' . $this->translate('clone') . '</a></li>';
            }
            if ($this->showDelete) {
                $actions .= '<li><a class="dropdown-item text-danger" href="' . base_url($baseUrl . '/tcdelete/' . $rowId) . '"><i class="bi bi-trash me-2"></i> ' . $this->translate('delete') . '</a></li>';
            }
            foreach ($this->actionButtons as $btn) {
                if ($btn['url']) {
                    $url = str_replace('{id}', $rowId, $btn['url']);
                    $actions .= '<li><a class="dropdown-item" href="' . base_url($url) . '"><i class="bi bi-' . $btn['icon'] . ' me-2"></i> ' . esc($btn['label']) . '</a></li>';
                } elseif ($btn['js']) {
                    $js = str_replace('{id}', $rowId, $btn['js']);
                    $actions .= '<li><button class="dropdown-item" onclick="' . $js . '"><i class="bi bi-' . $btn['icon'] . ' me-2"></i> ' . esc($btn['label']) . '</button></li>';
                }
            }
            $actions .= '</ul>';
        }
        
        $actions .= '</div>';
        return $actions;
    }

    // --- UTILIDADES DE ARCHIVOS ---
    protected function uploadFile($file, array $config): string|false
    {
        if (!$file || !$file->isValid()) return false;

        $uploadPath = FCPATH . $config['upload_path'];
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);

        $maxSize = $config['max_size'] * 1024;
        if ($file->getSize() > $maxSize) return false;

        $allowedTypes = explode('|', $config['allowed_types']);
        $ext = $file->getExtension();
        if (!in_array($ext, $allowedTypes)) return false;

        $newName = time() . '_' . uniqid() . '.' . $ext;
        $filePath = $config['upload_path'] . $newName;
        
        if (!$file->move($uploadPath, $newName)) return false;

        if ($config['thumbnail'] && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $this->generateThumbnail($uploadPath . $newName, $config['thumbnail_size']);
        }

        return $filePath;
    }

    protected function generateThumbnail(string $filePath, array $size)
    {
        if (!file_exists($filePath)) return;
        
        $info = pathinfo($filePath);
        $thumbName = $info['filename'] . '-' . $size[0] . 'x' . $size[1] . '-thumb.' . $info['extension'];
        $thumbPath = $info['dirname'] . '/' . $thumbName;
        
        copy($filePath, $thumbPath);
    }

    protected function deleteFile(?string $filePath): void
    {
        if (empty($filePath)) return;
        
        $fullPath = FCPATH . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
            
            $info = pathinfo($fullPath);
            $thumbPattern = $info['filename'] . '-*-thumb.' . $info['extension'];
            foreach (glob($info['dirname'] . '/' . $thumbPattern) as $thumb) {
                unlink($thumb);
            }
        }
    }

    protected function getThumbnailPath(string $filePath, array $size): ?string
    {
        if (empty($filePath)) return null;
        
        $info = pathinfo($filePath);
        $thumbName = $info['filename'] . '-' . $size[0] . 'x' . $size[1] . '-thumb.' . $info['extension'];
        $thumbPath = $info['dirname'] . '/' . $thumbName;
        
        if (file_exists(FCPATH . $thumbPath)) {
            return $thumbPath;
        }
        return null;
    }

    protected function getFileIcon(string $ext): string
    {
        $icons = [
            'pdf'  => 'filetype-pdf',
            'doc'  => 'filetype-doc',
            'docx' => 'filetype-docx',
            'xls'  => 'filetype-xls',
            'xlsx' => 'filetype-xlsx',
            'ppt'  => 'filetype-ppt',
            'pptx' => 'filetype-pptx',
            'zip'  => 'filetype-zip',
            'rar'  => 'filetype-rar',
            'txt'  => 'filetype-txt',
            'csv'  => 'filetype-csv',
            'json' => 'filetype-json',
            'xml'  => 'filetype-xml',
            'html' => 'filetype-html',
            'css'  => 'filetype-css',
            'js'   => 'filetype-js',
            'php'  => 'filetype-php',
            'exe'  => 'filetype-exe',
            'mp3'  => 'filetype-mp3',
            'mp4'  => 'filetype-mp4',
            'avi'  => 'filetype-avi',
            'mov'  => 'filetype-mov',
            'wav'  => 'filetype-wav',
            'jpg'  => 'filetype-jpg',
            'jpeg' => 'filetype-jpg',
            'png'  => 'filetype-png',
            'gif'  => 'filetype-gif',
            'webp' => 'filetype-webp',
            'svg'  => 'filetype-svg',
        ];
        
        return $icons[$ext] ?? 'file-earmark';
    }

    // --- SANITIZACIÓN ---
    protected function sanitizeHtml(string $html): string
    {
        $allowedTags = '<p><br><strong><b><em><i><u><s><strike><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><a><img><span><div><sub><sup>';
        
        $html = strip_tags($html, $allowedTags);
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);
        $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $html);
        
        return $html;
    }

    // --- WRAP OUTPUT ---
    protected function wrapOutput(string $html): object
    {
        $config = config('TCrud');
        $cssFiles = [];
        $jsFiles = [];

        foreach ($config->assets as $key => $url) {
            if ($config->coreAssetsAlreadyIncluded && in_array($key, ['bootstrap_css', 'bootstrap_js', 'bootstrap_icons'])) {
                continue;
            }
            if (str_ends_with($url, '.css')) {
                $cssFiles[] = $url;
            } else {
                $jsFiles[] = $url;
            }
        }

        return (object) [
            'output'    => $html,
            'css_files' => $cssFiles,
            'js_files'  => $jsFiles
        ];
    }

    protected function getStateSessionKey(): string
    {
        return 'tcrud_state_' . $this->table;
    }

    protected function getStateFromSession(): array
    {
        return session()->get($this->getStateSessionKey()) ?? [];
    }

    protected function saveStateToSession(array $state): void
    {
        $current = $this->getStateFromSession();
        $merged = array_merge($current, $state);
        session()->set($this->getStateSessionKey(), $merged);
    }

    protected function getExportColumns(): array
    {
        $columns = [];
        
        foreach ($this->columns as $col) {
            $label = $this->fieldLabels[$col] ?? ucfirst($col);
            $columns[$col] = [
                'label' => $label,
                'checked' => true,
            ];
        }
        
        $allFields = array_unique(array_merge($this->fields, $this->addFields, $this->editFields));
        foreach ($allFields as $field) {
            if (isset($columns[$field])) continue;
            if ($field === 'password') continue;
            if (isset($this->hiddenFields[$field]) && $this->hiddenFields[$field]['value'] === null) continue;
            
            $label = $this->fieldLabels[$field] ?? ucfirst($field);
            $columns[$field] = [
                'label' => $label,
                'checked' => false,
            ];
        }
        
        return $columns;
    }

    protected function getExportData(array $selectedColumns): array
    {
        $model = $this->getModel();
        $results = $model->getAllFilteredData();
        
        $data = [];
        foreach ($results as $row) {
            $rowData = [];
            foreach ($selectedColumns as $col) {
                $rowData[$col] = $this->getExportValue($col, $row, $model);
            }
            $data[] = $rowData;
        }
        
        return $data;
    }

    protected function getExportValue(string $col, $row, $model): string
    {
        if (isset($this->callbackColumns[$col])) {
            $val = call_user_func($this->callbackColumns[$col], $row);
            return trim(strip_tags($val));
        }
        
        if (isset($this->ntoNRelations[$col])) {
            $names = $model->getNtoNValues($col, $row->{$this->primaryKey});
            return implode(', ', $names);
        }
        
        if (isset($this->fieldTypes[$col]) && $this->fieldTypes[$col] === 'checkbox') {
            return ($row->$col ?? 0) == 1 ? $this->translate('yes') : $this->translate('no');
        }
        
        if (isset($this->uploadConfigs[$col]) && isset($this->fieldTypes[$col]) && $this->fieldTypes[$col] === 'image') {
            return !empty($row->$col) ? $this->translate('image') : '';
        }
        
        if (isset($this->uploadConfigs[$col])) {
            return !empty($row->$col) ? $this->translate('file') : '';
        }
        
        if (isset($this->fieldTypes[$col]) && $this->fieldTypes[$col] === 'wysiwyg') {
            $val = $row->$col ?? '';
            return trim(strip_tags(html_entity_decode($val, ENT_QUOTES, 'UTF-8')));
        }
        
        if (isset($this->relations[$col])) {
            $alias = $this->relations[$col]['table'] . '_' . $this->relations[$col]['field'];
            return $row->$alias ?? '';
        }
        
        $val = $row->$col ?? '';
        
        if (isset($this->fieldTypes[$col])) {
            $type = $this->fieldTypes[$col];
            if ($type === 'date' && $val) {
                return date('d/m/Y', strtotime($val));
            }
            if ($type === 'datetime' && $val) {
                return date('d/m/Y H:i', strtotime($val));
            }
            if ($type === 'time' && $val) {
                return date('H:i', strtotime($val));
            }
        }
        
        return (string)$val;
    }

    protected function processExportCSV()
    {
        $selectedColumns = $this->request->getPost('columns') ?? [];
        if (empty($selectedColumns)) {
            return $this->jsonResponse(false, $this->translate('select_at_least_one'));
        }
        
        $data = $this->getExportData($selectedColumns);
        $exportColumns = $this->getExportColumns();
        
        $filename = $this->table . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        
        $headers = [];
        foreach ($selectedColumns as $col) {
            $headers[] = $exportColumns[$col]['label'] ?? $col;
        }
        fputcsv($output, $headers, ',');
        
        foreach ($data as $row) {
            $line = [];
            foreach ($selectedColumns as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($output, $line, ',');
        }
        
        fclose($output);
        exit;
    }

    protected function processPrint()
    {
        $selectedColumns = $this->request->getPost('columns') ?? [];
        if (empty($selectedColumns)) {
            return $this->jsonResponse(false, $this->translate('select_at_least_one'));
        }
        
        $data = $this->getExportData($selectedColumns);
        $exportColumns = $this->getExportColumns();
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $this->subjectPlural . '</title>';
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
            h1 { font-size: 18px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            @media print { body { padding: 0; } }
        </style></head><body>';
        
        $html .= '<h1>' . esc($this->subjectPlural) . '</h1>';
        $html .= '<table><thead><tr>';
        foreach ($selectedColumns as $col) {
            $html .= '<th>' . esc($exportColumns[$col]['label'] ?? $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($selectedColumns as $col) {
                $html .= '<td>' . esc($row[$col] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '<script>window.onload = function() { window.print(); }</script>';
        $html .= '</body></html>';
        
        return (object) [
            'output' => $html,
            'is_html' => true,
            'css_files' => [],
            'js_files' => []
        ];
    }

    protected function processExportPDF()
    {
        $selectedColumns = $this->request->getPost('columns') ?? [];
        if (empty($selectedColumns)) {
            return $this->jsonResponse(false, $this->translate('select_at_least_one'));
        }
        
        $data = $this->getExportData($selectedColumns);
        $exportColumns = $this->getExportColumns();
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h1 { font-size: 16px; margin-bottom: 15px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
            th { background-color: #eee; font-weight: bold; }
        </style></head><body>';
        
        $html .= '<h1>' . esc($this->subjectPlural) . '</h1>';
        $html .= '<table><thead><tr>';
        foreach ($selectedColumns as $col) {
            $html .= '<th>' . esc($exportColumns[$col]['label'] ?? $col) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($selectedColumns as $col) {
                $html .= '<td>' . esc($row[$col] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table></body></html>';
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        $filename = $this->table . '_' . date('Y-m-d') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    protected function processInlineEdit(?int $id = null)
    {
        if (!$id) {
            return $this->jsonResponse(false, $this->translate('error_invalid_id'));
        }
        
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');
        
        if (!$field || !$this->isInlineEditable($field)) {
            return $this->jsonResponse(false, $this->translate('error_field_not_editable'));
        }
        
        $rule = $this->validationRules[$field] ?? 'permit_empty';
        if (strpos($rule, '{id}') !== false) {
            $rule = str_replace('{id}', (string)$id, $rule);
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([$field => $rule]);
        
        if (!$validation->run([$field => $value])) {
            return $this->jsonResponse(false, implode(' ', $validation->getErrors()));
        }
        
        $data = [$field => $value];
        if (isset($this->callbacks['beforeUpdate'])) {
            $data = call_user_func($this->callbacks['beforeUpdate'], $data, $id);
        }
        
        $model = $this->getModel();
        $allowedFields = $model->db->getFieldNames($this->table);
        $model->setAllowedFields($allowedFields);
        $model->update($id, $data);
        
        if (isset($this->callbacks['afterUpdate'])) {
            call_user_func($this->callbacks['afterUpdate'], $data, $id);
        }
        
        return $this->jsonResponse(true, $this->translate('success_inline_edit'));
    }

    protected function processDependentRelation()
    {
        $field = $this->request->getPost('field');
        $parentValue = $this->request->getPost('parent_value');
        
        if (!$field || !isset($this->dependentRelations[$field])) {
            return $this->jsonResponse(false, $this->translate('error_dependent_relation'));
        }
        
        $config = $this->dependentRelations[$field];
        $model = $this->getModel();
        $options = $model->getDependentOptions(
            $config['table'],
            $config['field'],
            $config['foreign_key'],
            $parentValue,
            $config['where']
        );
        
        return (object) [
            'is_json' => true,
            'output' => json_encode([
                'success' => true,
                'options' => $options
            ])
        ];
    }

    protected function translate(string $key): string
    {
        $lang = config('TCrud')->language ?? 'es';
        return lang('TCrud.' . $key, [], $lang);
    }


    // --- CACHÉ ---

    protected function getCacheKey(string $type, string $identifier): string
    {
        $prefix = config('TCrud')->cachePrefix ?? 'tcrud_';
        return $prefix . $type . '_' . $identifier;
    }

    protected function getFromCache(string $key)
    {
        if (!config('TCrud')->enableCache) {
            return null;
        }
        return cache($key);
    }

    protected function saveToCache(string $key, $data): void
    {
        if (!config('TCrud')->enableCache) {
            return;
        }
        $ttl = config('TCrud')->cacheTTL ?? 3600;
        if ($ttl > 0) {
            cache()->save($key, $data, $ttl);
        }
    }

    public function clearCache(?string $table = null): void
    {
        $prefix = config('TCrud')->cachePrefix ?? 'tcrud_';
        if ($table) {
            $keys = [
                $prefix . 'relation_' . $table,
                $prefix . 'ntoN_' . $table,
                $prefix . 'dependent_' . $table,
            ];
            foreach ($keys as $key) {
                cache()->delete($key);
            }
        } else {
            // Borrar todo el caché de T-CRUD
            $cache = \Config\Services::cache();
            $cache->clean();
        }
    }

}