<?php
/**
 * Universal Data Fetcher System
 * 
 * This file provides a centralized data fetching system that can handle:
 * - Simple queries (direct database field mapping)
 * - Template auto-fetch (automatic placeholder filling based on template configuration)
 * - Complex queries (multi-table joins, calculations, transformations)
 * - Dependent queries (queries that use results from other queries)
 * - Batch queries (multiple independent queries at once)
 */

require_once '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class DataFetcher {
    private $conn;
    private $cache = [];
    
    /**
     * Initialize DataFetcher with database connection
     * @param mysqli $dbConnection - Database connection from db.php
     */
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Main method to execute any type of query configuration
     * 
     * @param array $queryConfig - Configuration array defining the query
     * @return array - Result data or error information
     */
    public function executeQuery($queryConfig) {
        $queryId = $queryConfig['id'] ?? uniqid();
        $cacheKey = $queryConfig['cache_key'] ?? null;
        
        // Check cache first to avoid duplicate queries
        if ($cacheKey && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        try {
            $result = $this->processQuery($queryConfig);
            
            // Cache result if cache key provided
            if ($cacheKey) {
                $this->cache[$cacheKey] = $result;
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("DataFetcher Error [{$queryId}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Query execution failed',
                'query_id' => $queryId,
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Route query to appropriate processor based on type
     */
    private function processQuery($config) {
        $queryType = $config['type'] ?? 'simple';
        
        switch ($queryType) {
            case 'simple':
                return $this->executeSimpleQuery($config);
            case 'template_auto_fetch':
                return $this->executeTemplateAutoFetch($config);
            case 'complex':
                return $this->executeComplexQuery($config);
            case 'dependent':
                return $this->executeDependentQuery($config);
            case 'batch':
                return $this->executeBatchQuery($config);
            default:
                throw new Exception("Unknown query type: {$queryType}");
        }
    }
    
    /**
     * Execute a simple SQL query with optional parameters
     */
    private function executeSimpleQuery($config) {
        $sql = $config['sql'];
        $params = $config['params'] ?? [];
        $returnType = $config['return_type'] ?? 'multiple';
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        
        // For INSERT, UPDATE, DELETE queries, return success info instead of result set
        if (stripos($sql, 'INSERT') === 0 || stripos($sql, 'UPDATE') === 0 || stripos($sql, 'DELETE') === 0) {
            return [
                'affected_rows' => $stmt->affected_rows,
                'insert_id' => $this->conn->insert_id,
                'success' => true
            ];
        }
        
        // For SELECT queries, return the result set
        $result = $stmt->get_result();
        
        if ($returnType === 'single') {
            return $result->fetch_assoc();
        } else {
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            return $data;
        }
    }
    
    /**
     * Execute template auto-fetch based on template configuration
     */
    private function executeTemplateAutoFetch($config) {
        $templateKey = $config['template'] ?? '';
        $context = $config['context'] ?? [];
        $studentName = $context['student_name'] ?? '';
        
        if (empty($templateKey) || empty($studentName)) {
            throw new Exception("Template key and student name are required for template auto-fetch");
        }
        
        return $this->getTemplateAutoFetchData([
            'template_key' => $templateKey,
            'student_name' => $studentName
        ]);
    }
    
    /**
     * Execute complex queries using custom handler methods
     */
    private function executeComplexQuery($config) {
        $handler = $config['handler'];
        $params = $config['params'] ?? [];
        
        if (!method_exists($this, $handler)) {
            throw new Exception("Complex query handler '{$handler}' not found");
        }
        
        return $this->$handler($params);
    }
    
    /**
     * Execute multiple queries where later queries depend on earlier results
     */
    private function executeDependentQuery($config) {
        $queries = $config['queries'];
        $results = [];
        
        foreach ($queries as $queryConfig) {
            // Replace placeholders with previous results
            if (isset($queryConfig['depends_on'])) {
                $dependsOn = $queryConfig['depends_on'];
                $dependentData = $results[$dependsOn] ?? null;
                
                if (!$dependentData) {
                    throw new Exception("Dependent query '{$dependsOn}' not found");
                }
                
                // Replace placeholders in SQL and parameters
                $queryConfig['sql'] = $this->replacePlaceholders($queryConfig['sql'], $dependentData);
                $queryConfig['params'] = $this->replaceParamPlaceholders($queryConfig['params'] ?? [], $dependentData);
            }
            
            $queryId = $queryConfig['id'];
            $results[$queryId] = $this->executeSimpleQuery($queryConfig);
        }
        
        return $results;
    }
    
    /**
     * Execute multiple independent queries at once
     */
    private function executeBatchQuery($config) {
        $queries = $config['queries'];
        $results = [];
        
        foreach ($queries as $queryConfig) {
            $queryId = $queryConfig['id'];
            $results[$queryId] = $this->executeSimpleQuery($queryConfig);
        }
        
        return $results;
    }
    
    /**
     * Determine SQL parameter types for bind_param()
     */
    private function getParamTypes($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }
    
    /**
     * Replace placeholders like {StudentID} in SQL with actual values
     */
    private function replacePlaceholders($sql, $data) {
        foreach ($data as $key => $value) {
            $sql = str_replace("{{$key}}", $value, $sql);
        }
        return $sql;
    }
    
    /**
     * Replace placeholders like {StudentID} in parameter arrays
     */
    private function replaceParamPlaceholders($params, $data) {
        $newParams = [];
        foreach ($params as $param) {
            if (is_string($param) && strpos($param, '{') === 0) {
                $key = trim($param, '{}');
                $newParams[] = $data[$key] ?? $param;
            } else {
                $newParams[] = $param;
            }
        }
        return $newParams;
    }
    
    // ==========================================
    // COMPLEX QUERY HANDLERS
    // ==========================================
    
    /**
     * Get template auto-fetch data based on template configuration
     * 
     * USAGE: Use in complex query with handler "getTemplateAutoFetchData"
     * PARAMS: {"template_key": "template_name", "student_name": "Student Name"}
     */
    public function getTemplateAutoFetchData($params) {
        $templateKey = $params['template_key'] ?? '';
        $studentName = $params['student_name'] ?? '';
        
        // Load template config
        $templateConfigFile = __DIR__ . '/templateConfig.php';
        if (!file_exists($templateConfigFile)) {
            return [];
        }
        
        require_once $templateConfigFile;
        $templateConfig = getTemplateConfig($templateKey);
        
        $autoFetchData = [];
        if (isset($templateConfig['auto_fetch'])) {
            foreach ($templateConfig['auto_fetch'] as $placeholder => $config) {
                if (isset($config['query'])) {
                    try {
                        $stmt = $this->conn->prepare($config['query']);
                        $stmt->bind_param('s', $studentName);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $autoFetchData[$placeholder] = $row[$config['field']] ?? '';
                    } catch (Exception $e) {
                        error_log("Auto-fetch error for {$placeholder}: " . $e->getMessage());
                        $autoFetchData[$placeholder] = '';
                    }
                } elseif ($config['source'] === 'student') {
                    // Simple student field mapping
                    try {
                        $stmt = $this->conn->prepare("SELECT {$config['field']} FROM students WHERE Name = ?");
                        $stmt->bind_param('s', $studentName);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $autoFetchData[$placeholder] = $row[$config['field']] ?? '';
                    } catch (Exception $e) {
                        error_log("Simple auto-fetch error for {$placeholder}: " . $e->getMessage());
                        $autoFetchData[$placeholder] = '';
                    }
                }
            }
        }
        
        return $autoFetchData;
    }
    
    /**
     * Get student information with related attendance, scores, and payment data
     * 
     * USAGE: Use in complex query with handler "getStudentWithDetails"
     * PARAMS: {"student_name": "Student Name"}
     */
    public function getStudentWithDetails($params) {
        $studentName = $params['student_name'] ?? '';
        
        $sql = "SELECT s.*, 
                       COUNT(DISTINCT a.AttendanceID) as attendance_count,
                       AVG(tr.Score) as average_score,
                       SUM(CASE WHEN p.Status = 'pending' THEN p.Amount ELSE 0 END) as pending_amount
                FROM students s
                LEFT JOIN attendance a ON s.StudentID = a.StudentID
                LEFT JOIN test_results tr ON s.StudentID = tr.StudentID
                LEFT JOIN payments p ON s.StudentID = p.StudentID
                WHERE s.Name = ?
                GROUP BY s.StudentID";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $studentName);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
}

// Handle the incoming request
$input = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
}

$queryConfig = $input['query'] ?? null;
$trigger = $input['trigger'] ?? 'manual';

if (!$queryConfig) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No query configuration provided'
    ]);
    exit;
}

// Execute the query
$fetcher = new DataFetcher($conn);
$result = $fetcher->executeQuery($queryConfig);

// Return response
echo json_encode([
    'success' => true,
    'data' => $result,
    'trigger' => $trigger,
    'query_id' => $queryConfig['id'] ?? 'unknown',
    'timestamp' => time()
]);
?>