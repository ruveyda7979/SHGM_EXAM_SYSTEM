<?php
/**
 * SHGM Exam System - Base Model
 */

abstract class TN_Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $timestamps = true;
    protected $softDelete = false;
    protected $validationRules = [];
    protected $logger;
    protected $debug = false;
    protected $queryCache = [];

    public function __construct()
    {
        $this->debug = defined('APP_DEBUG') ? APP_DEBUG : false;
        $this->initializeDatabase();

        if (class_exists('TN_Logger')) {
            $this->logger = TN_Logger::getInstance();
        }

        if (!$this->table) {
            $this->setTableName();
        }

        if ($this->debug) {
            error_log('[TN_MODEL] ' . get_class($this) . ' initialized, table: ' . $this->table);
        }
    }

    protected function initializeDatabase()
    {
        if (class_exists('TN_Database')) {
            $this->db = TN_Database::getInstance();
            return;
        }

        try {
            $dsn  = function_exists('tn_db_dsn') ? tn_db_dsn() : '';
            $user = defined('DB_USERNAME') ? DB_USERNAME : null;
            $pass = defined('DB_PASSWORD') ? DB_PASSWORD : null;
            $opts = function_exists('tn_db_config') ? tn_db_config()['options'] : [];

            $this->db = new PDO($dsn, $user, $pass, $opts);
        } catch (PDOException $e) {
            if ($this->logger) $this->logger->error('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    protected function setTableName()
    {
        $className = get_class($this);

        if (strpos($className, 'TN_') === 0) {
            $tableName = strtolower(str_replace(['TN_', 'Model'], '', $className));
            $this->table = 'tn_' . $this->pluralize($tableName);
        } elseif (strpos($className, 'HA_') === 0) {
            $tableName = strtolower(str_replace(['HA_', 'Model'], '', $className));
            $this->table = 'ha_' . $this->pluralize($tableName);
        } elseif (strpos($className, 'RP_') === 0) {
            $tableName = strtolower(str_replace(['RP_', 'Model'], '', $className));
            $this->table = 'rp_' . $this->pluralize($tableName);
        }
    }

    protected function pluralize($word)
    {
        $map = [
            'user' => 'users',
            'student' => 'students',
            'exam' => 'exams',
            'question' => 'questions',
            'answer' => 'answers',
            'session' => 'sessions',
            'recording' => 'recordings',
            'report' => 'reports',
            'category' => 'categories'
        ];
        if (isset($map[$word])) return $map[$word];

        if (substr($word, -1) === 'y') return substr($word, 0, -1) . 'ies';
        if (in_array(substr($word, -1), ['s', 'x', 'z']) || in_array(substr($word, -2), ['ch', 'sh'])) return $word . 'es';
        return $word . 's';
    }

    public function all($columns = ['*'])
    {
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->table;
        if ($this->softDelete) $sql .= ' WHERE deleted_at IS NULL';
        $sql .= ' ORDER BY ' . $this->primaryKey . ' DESC';
        return $this->query($sql);
    }

    public function find($id, $columns = ['*'])
    {
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->table . ' WHERE ' . $this->primaryKey . ' = :id';
        if ($this->softDelete) $sql .= ' AND deleted_at IS NULL';
        $result = $this->query($sql, ['id' => $id]);
        return isset($result[0]) ? $result[0] : null;
    }

    public function findWhere($conditions, $columns = ['*'])
    {
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->table;
        $params = [];

        $where = [];
        foreach ($conditions as $field => $value) {
            $where[] = $field . ' = :' . $field;
            $params[$field] = $value;
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        if ($this->softDelete) $sql .= ' AND deleted_at IS NULL';

        $result = $this->query($sql, $params);
        return isset($result[0]) ? $result[0] : null;
    }

    public function where($conditions, $columns = ['*'], $orderBy = null, $limit = null)
    {
        $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->table;
        $params = [];

        $where = [];
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $placeholders = [];
                $i = 0;
                foreach ($value as $val) {
                    $ph = $field . '_' . $i++;
                    $placeholders[] = ':' . $ph;
                    $params[$ph] = $val;
                }
                $where[] = $field . ' IN (' . implode(', ', $placeholders) . ')';
            } else {
                $where[] = $field . ' = :' . $field;
                $params[$field] = $value;
            }
        }

        $sql .= ' WHERE ' . implode(' AND ', $where);
        if ($this->softDelete) $sql .= ' AND deleted_at IS NULL';
        if ($orderBy) $sql .= ' ORDER BY ' . $orderBy;
        if ($limit) $sql .= ' LIMIT ' . (int)$limit;

        return $this->query($sql, $params);
    }

    public function create($data)
    {
        if (!$this->validate($data)) return false;

        $data = $this->filterFillable($data);

        if ($this->timestamps) {
            $fmt = defined('TN_DATETIME_FORMAT') ? TN_DATETIME_FORMAT : 'Y-m-d H:i:s';
            $data['created_at'] = date($fmt);
            $data['updated_at'] = date($fmt);
        }

        $fields = array_keys($data);
        $placeholders = [];
        foreach ($fields as $f) $placeholders[] = ':' . $f;

        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';

        if ($this->execute($sql, $data)) {
            $id = $this->db->lastInsertId();
            return $this->find($id);
        }
        return false;
    }

    public function update($id, $data)
    {
        if (!$this->validate($data, $id)) return false;

        $data = $this->filterFillable($data);

        if ($this->timestamps) {
            $fmt = defined('TN_DATETIME_FORMAT') ? TN_DATETIME_FORMAT : 'Y-m-d H:i:s';
            $data['updated_at'] = date($fmt);
        }

        $sets = [];
        foreach ($data as $field => $value) $sets[] = $field . ' = :' . $field;

        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $this->primaryKey . ' = :id';
        $data['id'] = $id;
        return $this->execute($sql, $data);
    }

    public function delete($id)
    {
        if ($this->softDelete) {
            $fmt = defined('TN_DATETIME_FORMAT') ? TN_DATETIME_FORMAT : 'Y-m-d H:i:s';
            return $this->update($id, ['deleted_at' => date($fmt)]);
        }
        $sql = 'DELETE FROM ' . $this->table . ' WHERE ' . $this->primaryKey . ' = :id';
        return $this->execute($sql, ['id' => $id]);
    }

    public function count($conditions = [])
    {
        $sql = 'SELECT COUNT(*) as total FROM ' . $this->table;
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = $field . ' = :' . $field;
                $params[$field] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($this->softDelete) {
            $sql .= empty($conditions) ? ' WHERE' : ' AND';
            $sql .= ' deleted_at IS NULL';
        }

        $result = $this->query($sql, $params);
        return (int)(isset($result[0]['total']) ? $result[0]['total'] : 0);
    }

    public function paginate($page = 1, $perPage = 15, $conditions = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

        $sql = 'SELECT * FROM ' . $this->table;
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = $field . ' = :' . $field;
                $params[$field] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($this->softDelete) {
            $sql .= empty($conditions) ? ' WHERE' : ' AND';
            $sql .= ' deleted_at IS NULL';
        }

        if ($orderBy) $sql .= ' ORDER BY ' . $orderBy;
        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $data = $this->query($sql, $params);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page'     => (int)$perPage,
                'total'        => (int)$total,
                'total_pages'  => (int)$totalPages,
                'has_prev'     => $page > 1,
                'has_next'     => $page < $totalPages
            ]
        ];
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if ($this->debug) $this->logQuery($sql, $params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            if ($this->logger) $this->logger->error('Query failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            if ($this->debug) throw $e;
            return [];
        }
    }

    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            if ($this->debug) $this->logQuery($sql, $params);

            return $result;

        } catch (PDOException $e) {
            if ($this->logger) $this->logger->error('Execute failed: ' . $e->getMessage() . ' SQL: ' . $sql);
            if ($this->debug) throw $e;
            return false;
        }
    }

    public function beginTransaction() { return $this->db->beginTransaction(); }
    public function commit()           { return $this->db->commit(); }
    public function rollback()         { return $this->db->rollback(); }

    protected function filterFillable($data)
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->guarded)) continue;
            if (!empty($this->fillable) && !in_array($key, $this->fillable)) continue;
            $filtered[$key] = $value;
        }
        return $filtered;
    }

    protected function validate($data, $id = null)
    {
        if (empty($this->validationRules)) return true;

        if (class_exists('TN_Validator')) {
            $validator = new TN_Validator();
            $result = $validator->validate($data, $this->validationRules);
            return $result === true;
        }

        foreach ($this->validationRules as $field => $rules) {
            $ruleArray = explode('|', $rules);
            foreach ($ruleArray as $rule) {
                if ($rule === 'required' && (!isset($data[$field]) || $data[$field] === '')) {
                    if ($this->logger) $this->logger->warning("Validation failed: {$field} is required");
                    return false;
                }
            }
        }
        return true;
    }

    protected function logQuery($sql, $params)
    {
        if (!$this->logger) return;
        $logMessage = 'SQL Query: ' . $sql;
        if (!empty($params)) $logMessage .= ' | Params: ' . json_encode($params);
        $this->logger->debug($logMessage);
    }

    public function getStats()
    {
        return [
            'table' => $this->table,
            'primary_key' => $this->primaryKey,
            'fillable_count' => count($this->fillable),
            'guarded_count' => count($this->guarded),
            'timestamps' => $this->timestamps,
            'soft_delete' => $this->softDelete,
            'validation_rules_count' => count($this->validationRules),
            'debug_mode' => $this->debug
        ];
    }

    public function checkTableStructure()
    {
        try {
            return $this->query('DESCRIBE ' . $this->table);
        } catch (Exception $e) {
            return false;
        }
    }

    public function debugInfo()
    {
        if (!$this->debug) return;

        echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;padding:15px;margin:10px 0;font-family:monospace;'>";
        echo "<h4>" . get_class($this) . " Debug Info</h4>";

        $stats = $this->getStats();
        foreach ($stats as $key => $value) {
            echo "<strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> " .
                 (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "<br>";
        }

        echo "<br><strong>Fillable Fields:</strong> " . implode(', ', $this->fillable) . "<br>";
        echo "<strong>Guarded Fields:</strong> " . implode(', ', $this->guarded) . "<br>";

        $structure = $this->checkTableStructure();
        if ($structure) {
            echo "<br><strong>Table Structure:</strong><br>";
            echo "<table style='border-collapse:collapse;font-size:12px;'>";
            echo "<tr style='background:#e9ecef;'>";
            echo "<th style='border:1px solid #dee2e6;padding:3px;'>Field</th>";
            echo "<th style='border:1px solid #dee2e6;padding:3px;'>Type</th>";
            echo "<th style='border:1px solid #dee2e6;padding:3px;'>Null</th>";
            echo "<th style='border:1px solid #dee2e6;padding:3px;'>Key</th>";
            echo "</tr>";

            foreach ($structure as $column) {
                echo "<tr>";
                echo "<td style='border:1px solid #dee2e6;padding:3px;'>" . $column['Field'] . "</td>";
                echo "<td style='border:1px solid #dee2e6;padding:3px;'>" . $column['Type'] . "</td>";
                echo "<td style='border:1px solid #dee2e6;padding:3px;'>" . $column['Null'] . "</td>";
                echo "<td style='border:1px solid #dee2e6;padding:3px;'>" . $column['Key'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        echo "</div>";
    }
}
