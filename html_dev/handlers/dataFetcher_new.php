<?php
/**
 * Universal Data Fetcher System
 * 
 * This file provides a centralized data fetching system that can handle:
 * - Simple queries (direct database field mapping)
 * - Complex queries (multi-table joins, calculations, transformations)
 * - Dependent queries (queries that use results from other queries)
 * - Batch queries (multiple independent queries at once)
 * 
 * HOW TO USE:
 * Send POST request with JSON containing a 'query' configuration object.
 * 
 * DEPENDENCIES:
 * - Requires ../config/db.php for database connection
 * - Uses existing database connection variable $conn
 * 
 * EXTENDING:
 * - Add new complex query handlers as methods in DataFetcher class
 * - Modify processQuery() to handle new query types
 * - Add new placeholder replacement logic in replaceContextPlaceholders()
 */

require_once '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS request for CORS
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
     * 
     * HOW TO EXTEND:
     * Add new query types in the switch statement in processQuery()
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
     * 
     * QUERY TYPES:
     * - 'simple': Direct SQL query with optional parameters
     * - 'dependent': Multiple queries where later ones depend on earlier results
     * - 'complex': Custom handler method for advanced processing
     * - 'batch': Multiple independent queries executed together
     */
    private function processQuery($config) {
        $queryType = $config['type'] ?? 'simple';
        
        switch ($queryType) {
            case 'simple':
                return $this->executeSimpleQuery($config);
            case 'dependent':
                return $this->executeDependentQuery($config);
            case 'complex':
                return $this->executeComplexQuery($config);
            case 'batch':
                return $this->executeBatchQuery($config);
            default:
                throw new Exception("Unknown query type: {$queryType}");
        }
    }
    
    /**
     * Execute a simple SQL query with optional parameters
     * 
     * CONFIGURATION EXAMPLE:
     * {
     *   "type": "simple",
     *   "sql": "SELECT Name FROM students WHERE StudentID = ?",
     *   "params": [5],
     *   "return_type": "single"  // or "multiple"
     * }
     */
    private function executeSimpleQuery($config) {
        $sql = $config['sql'];
        $params = $config['params'] ?? [];
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($params)) {
            $types = $this->getParamTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Return single row or multiple rows based on configuration
        if ($config['return_type'] === 'single') {
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
     * Execute multiple queries where later queries depend on earlier results
     * 
     * CONFIGURATION EXAMPLE:
     * {
     *   "type": "dependent",
     *   "queries": [
     *     {
     *       "id": "student",
     *       "sql": "SELECT * FROM students WHERE Name = ?",
     *       "params": ["Max"],
     *       "return_type": "single"
     *     },
     *     {
     *       "id": "payments",
     *       "depends_on": "student",
     *       "sql": "SELECT * FROM payments WHERE StudentID = {StudentID}",
     *       "return_type": "multiple"
     *     }
     *   ]
     * }
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
     * Execute complex queries using custom handler methods
     * 
     * CONFIGURATION EXAMPLE:
     * {
     *   "type": "complex",
     *   "handler": "getStudentWithDetails",
     *   "params": {"student_name": "Max"}
     * }
     * 
     * HOW TO ADD NEW HANDLERS:
     * Add public methods to this class with the naming pattern: handler_name($params)
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
     * Execute multiple independent queries at once
     * 
     * CONFIGURATION EXAMPLE:
     * {
     *   "type": "batch",
     *   "queries": [
     *     {"id": "students", "sql": "SELECT COUNT(*) as count FROM students", "return_type": "single"},
     *     {"id": "payments", "sql": "SELECT COUNT(*) as count FROM payments", "return_type": "single"}
     *   ]
     * }
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
     * i = integer, d = double/float, s = string
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
    // Add your custom complex queries here
    // ==========================================
    
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
    
    /**
     * Get template auto-fetch data based on template configuration
     * 
     * USAGE: Use in complex query with handler "getTemplateAutoFetchData"
     * PARAMS: {"template_key": "template_name", "student_name": "Student Name"}
     * 
     * DEPENDENCIES: Requires templateConfig.php for template configurations
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
     * Get monthly progress summary for a student
     * 
     * USAGE: Use in complex query with handler "getMonthlyProgress"
     * PARAMS: {"student_name": "Student Name"}
     */
    public function getMonthlyProgress($params) {
        $studentName = $params['student_name'] ?? '';
        $data = [];
        
        // Complex query with multiple JOINs and calculations
        $query = "
            SELECT 
                s.Name,
                s.Level,
                COUNT(DISTINCT CASE WHEN MONTH(a.AttendanceDate) = MONTH(NOW()) AND YEAR(a.AttendanceDate) = YEAR(NOW()) THEN a.AttendanceID END) as ClassesAttended,
                COUNT(DISTINCT CASE WHEN MONTH(sc.ScheduleDate) = MONTH(NOW()) AND YEAR(sc.ScheduleDate) = YEAR(NOW()) THEN sc.ScheduleID END) as ClassesScheduled,
                ROUND(AVG(CASE WHEN MONTH(tr.TestDate) = MONTH(NOW()) AND YEAR(tr.TestDate) = YEAR(NOW()) THEN tr.Score END), 1) as AverageScore,
                SUM(CASE WHEN p.Status = 'paid' AND MONTH(p.PaymentDate) = MONTH(NOW()) AND YEAR(p.PaymentDate) = YEAR(NOW()) THEN p.Amount ELSE 0 END) as PaidAmount,
                SUM(CASE WHEN p.Status = 'pending' THEN p.Amount ELSE 0 END) as PendingAmount
            FROM students s
            LEFT JOIN attendance a ON s.StudentID = a.StudentID 
            LEFT JOIN schedules sc ON s.StudentID = sc.StudentID
            LEFT JOIN test_results tr ON s.StudentID = tr.StudentID
            LEFT JOIN payments p ON s.StudentID = p.StudentID
            WHERE s.Name = ?
            GROUP BY s.StudentID
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $studentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            // Calculate attendance percentage
            $attendanceRate = $row['ClassesScheduled'] > 0 
                ? round(($row['ClassesAttended'] / $row['ClassesScheduled']) * 100, 1)
                : 0;
                
            // Determine performance status
            $performanceStatus = $this->getPerformanceStatus($row['AverageScore']);
            
            $data = [
                'NAME' => $row['Name'],
                'LEVEL' => $row['Level'],
                'ATTENDANCE_RATE' => $attendanceRate . '%',
                'CLASSES_ATTENDED' => $row['ClassesAttended'],
                'CLASSES_SCHEDULED' => $row['ClassesScheduled'],
                'AVERAGE_SCORE' => $row['AverageScore'] ?: 'N/A',
                'PERFORMANCE_STATUS' => $performanceStatus,
                'PAID_AMOUNT' => number_format($row['PaidAmount'], 2) . '€',
                'PENDING_AMOUNT' => number_format($row['PendingAmount'], 2) . '€'
            ];
        }
        
        return $data;
    }
    
    /**
     * Get attendance analysis for a student
     * 
     * USAGE: Use in complex query with handler "getAttendanceAnalysis"
     * PARAMS: {"student_name": "Student Name"}
     */
    public function getAttendanceAnalysis($params) {
        $studentName = $params['student_name'] ?? '';
        
        // Complex attendance pattern analysis
        $query = "
            SELECT 
                s.Name,
                COUNT(CASE WHEN a.Status = 'present' THEN 1 END) as PresentCount,
                COUNT(CASE WHEN a.Status = 'absent' THEN 1 END) as AbsentCount,
                COUNT(CASE WHEN a.Status = 'late' THEN 1 END) as LateCount,
                COUNT(*) as TotalClasses,
                GROUP_CONCAT(
                    CASE WHEN a.Status = 'absent' 
                    THEN DATE_FORMAT(a.AttendanceDate, '%d.%m.%Y') 
                    END 
                    ORDER BY a.AttendanceDate DESC 
                    SEPARATOR ', '
                ) as RecentAbsences
            FROM students s
            LEFT JOIN attendance a ON s.StudentID = a.StudentID
                AND a.AttendanceDate >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            WHERE s.Name = ?
            GROUP BY s.StudentID
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $studentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $attendanceRate = $row['TotalClasses'] > 0 
                ? round(($row['PresentCount'] / $row['TotalClasses']) * 100, 1)
                : 0;
                
            $attendanceStatus = $this->getAttendanceStatus($attendanceRate);
            
            return [
                'NAME' => $row['Name'],
                'ATTENDANCE_RATE' => $attendanceRate . '%',
                'ATTENDANCE_STATUS' => $attendanceStatus,
                'PRESENT_COUNT' => $row['PresentCount'],
                'ABSENT_COUNT' => $row['AbsentCount'],
                'LATE_COUNT' => $row['LateCount'],
                'RECENT_ABSENCES' => $row['RecentAbsences'] ?: 'Keine'
            ];
        }
        
        return [];
    }
    
    // ==========================================
    // HELPER METHODS
    // ==========================================
    
    /**
     * Determine performance status based on average score
     */
    private function getPerformanceStatus($score) {
        if ($score >= 90) return "Ausgezeichnet 🌟";
        if ($score >= 80) return "Sehr gut 👍";
        if ($score >= 70) return "Gut ✅";
        if ($score >= 60) return "Befriedigend 📈";
        return "Verbesserung nötig 📚";
    }
    
    /**
     * Determine attendance status based on attendance rate
     */
    private function getAttendanceStatus($rate) {
        if ($rate >= 95) return "Perfekt 🎯";
        if ($rate >= 85) return "Sehr gut 👏";
        if ($rate >= 75) return "Gut ✅";
        if ($rate >= 65) return "Verbesserung möglich 📊";
        return "Aufmerksamkeit erforderlich ⚠️";
    }
}

// ==========================================
// HANDLE INCOMING REQUEST
// ==========================================

$method = $_SERVER['REQUEST_METHOD'];
$input = null;

if ($method === 'GET') {
    $input = $_GET;
} elseif ($method === 'POST') {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $input = json_decode($raw_input, true);
    }
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
        'error' => 'No query configuration provided',
        'expected_format' => [
            'query' => [
                'type' => 'simple|dependent|complex|batch',
                'id' => 'unique_query_id',
                // ... other configuration based on type
            ],
            'trigger' => 'load|change|click|manual'
        ]
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
