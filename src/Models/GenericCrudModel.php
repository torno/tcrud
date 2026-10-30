<?php

namespace Torno\TCrud\Models;

use CodeIgniter\Model;

class GenericCrudModel extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $allowedFields = [];
    protected $useTimestamps = false;
    protected $returnType = 'object';

    // Configuraciones del CRUD que el modelo necesita conocer
    protected array $relations = [];
    protected array $ntoNRelations = [];
    protected array $whereConditions = [];
    protected array $filters = [];
    protected array $searchColumns = [];

    protected array $dependentRelations = [];

    public function setDependentRelations(array $relations): self 
    { 
        $this->dependentRelations = $relations; 
        return $this; 
    }

    public function setTable(string $table): self { $this->table = $table; return $this; }
    public function setPrimaryKey(string $key): self { $this->primaryKey = $key; return $this; }
    public function setAllowedFields(array $fields): self { $this->allowedFields = $fields; return $this; }
    public function setRelations(array $relations): self { $this->relations = $relations; return $this; }
    public function setNtoNRelations(array $ntoNRelations): self { $this->ntoNRelations = $ntoNRelations; return $this; }
    public function setWhereConditions(array $whereConditions): self { $this->whereConditions = $whereConditions; return $this; }
    public function setFilters(array $filters): self { $this->filters = $filters; return $this; }
    public function setSearchColumns(array $columns): self { $this->searchColumns = $columns; return $this; }

    /**
     * Construye el builder con joins, where y filtros aplicados
     */
    protected function buildQueryBuilder()
    {
        $builder = $this->db->table($this->table);
        
        $selectFields = [$this->table . '.*'];
        foreach ($this->relations as $field => $relation) {
            $alias = $relation['table'] . '_' . $relation['field'];
            $selectFields[] = $relation['table'] . '.' . $relation['field'] . ' AS ' . $alias;
            
            // Construir el ON del JOIN con los where incluidos
            $joinCondition = $relation['table'] . '.id = ' . $this->table . '.' . $field;
            if (is_array($relation['where'])) {
                foreach ($relation['where'] as $k => $v) {
                    $joinCondition .= ' AND ' . $relation['table'] . '.' . $k . ' = ' . $this->db->escape($v);
                }
            }
            
            $builder->join($relation['table'], $joinCondition, 'left');
        }
        foreach ($this->dependentRelations as $field => $relation) {
            $alias = $relation['table'] . '_' . $relation['field'];
            $selectFields[] = $relation['table'] . '.' . $relation['field'] . ' AS ' . $alias;
            
            $joinCondition = $relation['table'] . '.id = ' . $this->table . '.' . $field;
            if (is_array($relation['where'])) {
                foreach ($relation['where'] as $k => $v) {
                    $joinCondition .= ' AND ' . $relation['table'] . '.' . $k . ' = ' . $this->db->escape($v);
                }
            }
            
            $builder->join($relation['table'], $joinCondition, 'left');
        }
        $builder->select(implode(', ', $selectFields));

        // Where conditions
        foreach ($this->whereConditions as $condition) {
            if (count($condition) === 3) {
                $builder->where($condition[0], $condition[1], $condition[2]);
            } elseif (count($condition) === 2) {
                $builder->where($condition[0], $condition[1]);
            }
        }

        // Filtros
        foreach ($this->filters as $field => $value) {
            if ($value === '' || $value === null) continue;
            
            if (isset($this->relations[$field])) {
                $relTable = $this->relations[$field]['table'];
                $relField = $this->relations[$field]['field'];
                $builder->like($relTable . '.' . $relField, $value);
            } else {
                $dbFields = $this->db->getFieldNames($this->table);
                if (in_array($field, $dbFields)) {
                    $builder->like($this->table . '.' . $field, $value);
                }
            }
        }

        return $builder;
    }

    public function getAllFilteredData(?string $orderColumn = null, string $orderDir = 'asc'): array
    {
        $builder = $this->buildQueryBuilder();
        
        if ($orderColumn) {
            if (isset($this->relations[$orderColumn])) {
                $alias = $this->relations[$orderColumn]['table'] . '_' . $this->relations[$orderColumn]['field'];
                $builder->orderBy($alias, $orderDir);
            } else {
                $builder->orderBy($this->table . '.' . $orderColumn, $orderDir);
            }
        }
        
        return $builder->get()->getResult();
    }


    public function getDependentOptions(
        string $table,
        string $displayField,
        string $foreignKey,
        $foreignValue,
        array $where = []
    ): array {
        $builder = $this->db->table($table);
        $builder->select('id, ' . $displayField . ' AS label');
        $builder->where($foreignKey, $foreignValue);
        
        foreach ($where as $k => $v) {
            $builder->where($k, $v);
        }
        
        $builder->orderBy($displayField, 'ASC');
        $rows = $builder->get()->getResult();
        
        $options = [];
        foreach ($rows as $row) {
            $options[] = [
                'value' => $row->id,
                'label' => $row->label
            ];
        }
        
        return $options;
    }

    /**
     * Cuenta registros totales (sin filtros de búsqueda)
     */
    public function countTotal(): int
    {
        $builder = $this->buildQueryBuilder();
        return $builder->countAllResults(false);
    }

    /**
     * Cuenta registros filtrados (con búsqueda global)
     */
    public function countFiltered(string $searchValue = ''): int
    {
        $builder = $this->buildQueryBuilder();
        
        if (!empty($searchValue)) {
            $builder->groupStart();
            foreach ($this->searchColumns as $col) {
                if (isset($this->relations[$col])) {
                    $relTable = $this->relations[$col]['table'];
                    $relField = $this->relations[$col]['field'];
                    $builder->orLike($relTable . '.' . $relField, $searchValue);
                } else {
                    $dbFields = $this->db->getFieldNames($this->table);
                    if (in_array($col, $dbFields)) {
                        $builder->orLike($this->table . '.' . $col, $searchValue);
                    }
                }
            }
            $builder->groupEnd();
        }
        
        return $builder->countAllResults(false);
    }

    /**
     * Obtiene los datos para DataTables
     */
    public function getDataTablesData(
        string $searchValue = '',
        ?string $orderColumn = null,
        string $orderDir = 'asc',
        int $start = 0,
        int $length = 15
    ): array {
        $builder = $this->buildQueryBuilder();

        // Búsqueda global
        if (!empty($searchValue)) {
            $builder->groupStart();
            foreach ($this->searchColumns as $col) {
                if (isset($this->relations[$col])) {
                    $relTable = $this->relations[$col]['table'];
                    $relField = $this->relations[$col]['field'];
                    $builder->orLike($relTable . '.' . $relField, $searchValue);
                } else {
                    $dbFields = $this->db->getFieldNames($this->table);
                    if (in_array($col, $dbFields)) {
                        $builder->orLike($this->table . '.' . $col, $searchValue);
                    }
                }
            }
            $builder->groupEnd();
        }

        // Ordenamiento
        if ($orderColumn) {
            if (isset($this->relations[$orderColumn])) {
                $alias = $this->relations[$orderColumn]['table'] . '_' . $this->relations[$orderColumn]['field'];
                $builder->orderBy($alias, $orderDir);
            } else {
                $builder->orderBy($this->table . '.' . $orderColumn, $orderDir);
            }
        }

        // Paginación
        if ($length > 0) {
            $builder->limit($length, $start);
        }

        return $builder->get()->getResult();
    }

    /**
     * Obtiene los valores de una relación N a N para un registro
     */
    public function getNtoNValues(string $field, int $recordId): array
    {
        if (!isset($this->ntoNRelations[$field])) {
            return [];
        }

        $config = $this->ntoNRelations[$field];
        
        $builder = $this->db->table($config['pivot_table'])
                      ->select($config['related_table'] . '.' . $config['display_field'])
                      ->join(
                          $config['related_table'],
                          $config['related_table'] . '.id = ' . $config['pivot_table'] . '.' . $config['pivot_related_field'],
                          'left'
                      )
                      ->where($config['pivot_table'] . '.' . $config['pivot_primary_field'], $recordId);
        
        if ($config['order_field']) {
            $builder->orderBy($config['pivot_table'] . '.' . $config['order_field'], 'ASC');
        }
        
        $rows = $builder->get()->getResult();
        return array_map(fn($r) => $r->{$config['display_field']}, $rows);
    }

    /**
     * Obtiene los IDs seleccionados de una relación N a N (para el formulario)
     */
    public function getNtoNIds(string $field, int $recordId): array
    {
        if (!isset($this->ntoNRelations[$field])) {
            return [];
        }

        $config = $this->ntoNRelations[$field];
        
        $builder = $this->db->table($config['pivot_table'])
                      ->where($config['pivot_primary_field'], $recordId);
        
        if ($config['order_field']) {
            $builder->orderBy($config['order_field'], 'ASC');
        }
        
        $rows = $builder->get()->getResult();
        return array_map(fn($r) => $r->{$config['pivot_related_field']}, $rows);
    }

    /**
     * Guarda las relaciones N a N de un registro
     */
    public function saveNtoN(string $field, int $recordId, array $selectedIds): void
    {
        if (!isset($this->ntoNRelations[$field])) {
            return;
        }

        $config = $this->ntoNRelations[$field];
        
        // Eliminar relaciones antiguas
        $this->db->table($config['pivot_table'])
                 ->where($config['pivot_primary_field'], $recordId)
                 ->delete();
        
        // Insertar nuevas relaciones
        $order = 0;
        foreach ($selectedIds as $relatedId) {
            $insertData = [
                $config['pivot_primary_field'] => $recordId,
                $config['pivot_related_field'] => (int)$relatedId,
            ];
            
            if ($config['order_field']) {
                $insertData[$config['order_field']] = $order;
            }
            
            $this->db->table($config['pivot_table'])->insert($insertData);
            $order++;
        }
    }

    /**
     * Elimina las relaciones N a N de un registro
     */
    public function deleteNtoN(int $recordId): void
    {
        foreach ($this->ntoNRelations as $field => $config) {
            $this->db->table($config['pivot_table'])
                     ->where($config['pivot_primary_field'], $recordId)
                     ->delete();
        }
    }
}