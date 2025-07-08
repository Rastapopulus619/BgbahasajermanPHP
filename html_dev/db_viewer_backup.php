<?php
// filepath: \srv\projects\docker\bgbahasajerman-php-app\html_dev\db_viewer.php
session_start();
require_once 'config/db.php'; // Use your existing database connection

// Get database schema information
function getTableInfo($conn, $database) {
    $tables = [];
    
    // Get all table names
    $tablesQuery = "SHOW TABLES";
    $tablesResult = $conn->query($tablesQuery);
    
    if ($tablesResult) {
        while ($row = $tablesResult->fetch_array()) {
            $tableName = $row[0];
            $tables[$tableName] = [
                'name' => $tableName,
                'columns' => [],
                'foreign_keys' => [],
                'indexes' => [],
                'row_count' => 0
            ];
        }
    }
    
    // Get detailed information for each table
    foreach ($tables as $tableName => &$tableInfo) {
        // Get column information
        try {
            $columnsQuery = "DESCRIBE `$tableName`";
            $columnsResult = $conn->query($columnsQuery);
            if ($columnsResult) {
                while ($row = $columnsResult->fetch_assoc()) {
                    $tableInfo['columns'][] = $row;
                }
            }
        } catch (Exception $e) {
            // Skip if there's an error
            $tableInfo['columns'] = [];
        }
        
        // Get foreign key information using prepared statement
        try {
            $fkQuery = "SELECT 
                            COLUMN_NAME,
                            REFERENCED_TABLE_NAME,
                            REFERENCED_COLUMN_NAME,
                            CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = ? 
                        AND TABLE_NAME = ? 
                        AND REFERENCED_TABLE_NAME IS NOT NULL";
            
            $fkStmt = $conn->prepare($fkQuery);
            if ($fkStmt) {
                $fkStmt->bind_param('ss', $database, $tableName);
                $fkStmt->execute();
                $fkResult = $fkStmt->get_result();
                while ($row = $fkResult->fetch_assoc()) {
                    $tableInfo['foreign_keys'][] = $row;
                }
                $fkStmt->close();
            }
        } catch (Exception $e) {
            // Skip if there's an error
            $tableInfo['foreign_keys'] = [];
        }
        
        // Get row count using SHOW TABLE STATUS (avoids GROUP BY issues)
        try {
            $statusQuery = "SHOW TABLE STATUS LIKE ?";
            $statusStmt = $conn->prepare($statusQuery);
            if ($statusStmt) {
                $statusStmt->bind_param('s', $tableName);
                $statusStmt->execute();
                $statusResult = $statusStmt->get_result();
                if ($statusRow = $statusResult->fetch_assoc()) {
                    $tableInfo['row_count'] = $statusRow['Rows'] ?? 0;
                }
                $statusStmt->close();
            }
        } catch (Exception $e) {
            // Fallback: try simple count only if table status fails
            try {
                $simpleCountQuery = "SELECT COUNT(*) as total FROM `$tableName`";
                $countResult = $conn->query($simpleCountQuery);
                if ($countResult) {
                    $countRow = $countResult->fetch_assoc();
                    $tableInfo['row_count'] = $countRow['total'] ?? 0;
                }
            } catch (Exception $e2) {
                // If both fail, just set to 0
                $tableInfo['row_count'] = 0;
            }
        }
    }
    
    return $tables;
}

$selectedTable = $_GET['table'] ?? null;
$page = $_GET['page'] ?? 1;
$recordsPerPage = 25;
$offset = ($page - 1) * $recordsPerPage;

// Pass the database variable to the function
$tables = getTableInfo($conn, $database);
$tableData = [];
$totalRecords = 0;

if ($selectedTable && isset($tables[$selectedTable])) {
    $totalRecords = $tables[$selectedTable]['row_count'];
    
    // Get table data with pagination
    try {
        $dataQuery = "SELECT * FROM `$selectedTable` LIMIT $recordsPerPage OFFSET $offset";
        $dataResult = $conn->query($dataQuery);
        if ($dataResult) {
            while ($row = $dataResult->fetch_assoc()) {
                $tableData[] = $row;
            }
        }
    } catch (Exception $e) {
        // Handle any pagination errors
        $tableData = [];
    }
}

$totalPages = ceil($totalRecords / $recordsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Database Viewer - BG Bahasa Jerman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .schema-diagram {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            min-height: 400px;
        }
        .table-box {
            background: white;
            border: 2px solid #007bff;
            border-radius: 8px;
            margin: 10px;
            padding: 10px;
            min-width: 250px;
            display: inline-block;
            vertical-align: top;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-header {
            background: #007bff;
            color: white;
            padding: 8px;
            margin: -10px -10px 10px -10px;
            border-radius: 6px 6px 0 0;
            font-weight: bold;
        }
        .column-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .column-item {
            padding: 3px 5px;
            border-bottom: 1px solid #eee;
            font-size: 0.9em;
        }
        .column-item:last-child {
            border-bottom: none;
        }
        .pk-column {
            background: #fff3cd;
            font-weight: bold;
        }
        .fk-column {
            background: #d1ecf1;
            color: #0c5460;
        }
        .data-view {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .stats-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #28a745;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .table th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }
        .error-notice {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-database me-2"></i>
                <span class="live-indicator"></span>
                Live Database Schema Viewer
            </a>
            <div>
                <button class="btn btn-outline-light me-2" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i>Back to Main
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <!-- Database Overview Stats -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-primary"><?= count($tables) ?></h3>
                    <p class="mb-0">Total Tables</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-success"><?= number_format(array_sum(array_column($tables, 'row_count'))) ?></h3>
                    <p class="mb-0">Total Records</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-info"><?= htmlspecialchars($database) ?></h3>
                    <p class="mb-0">Database Name</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <h3 class="text-warning">
                        <span class="live-indicator"></span>LIVE
                    </h3>
                    <p class="mb-0">Connection Status</p>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mt-4" id="viewTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="schema-tab" data-bs-toggle="tab" data-bs-target="#schema" type="button">
                    <i class="fas fa-project-diagram me-2"></i>Schema Diagram
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button">
                    <i class="fas fa-table me-2"></i>Data Explorer
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="relationships-tab" data-bs-toggle="tab" data-bs-target="#relationships" type="button">
                    <i class="fas fa-link me-2"></i>Relationships
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="viewTabsContent">
            <!-- Schema Diagram Tab -->
            <div class="tab-pane fade show active" id="schema" role="tabpanel">
                <div class="schema-diagram">
                    <h4><i class="fas fa-project-diagram me-2"></i>Database Schema Overview</h4>
                    <div class="row">
                        <?php foreach ($tables as $tableName => $tableInfo): ?>
                            <div class="col-md-4 col-lg-3">
                                <div class="table-box">
                                    <div class="table-header">
                                        <i class="fas fa-table me-2"></i><?= htmlspecialchars($tableName) ?>
                                        <span class="badge bg-light text-dark ms-2"><?= number_format($tableInfo['row_count']) ?></span>
                                    </div>
                                    <ul class="column-list">
                                        <?php if (!empty($tableInfo['columns'])): ?>
                                            <?php foreach ($tableInfo['columns'] as $column): ?>
                                                <li class="column-item <?= $column['Key'] === 'PRI' ? 'pk-column' : ($column['Key'] === 'MUL' ? 'fk-column' : '') ?>">
                                                    <?php if ($column['Key'] === 'PRI'): ?>
                                                        <i class="fas fa-key me-1" title="Primary Key"></i>
                                                    <?php elseif ($column['Key'] === 'MUL'): ?>
                                                        <i class="fas fa-link me-1" title="Foreign Key"></i>
                                                    <?php endif; ?>
                                                    <strong><?= htmlspecialchars($column['Field']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($column['Type']) ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="column-item text-muted">
                                                <small>Unable to load columns</small>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Data Explorer Tab -->
            <div class="tab-pane fade" id="data" role="tabpanel">
                <div class="data-view">
                    <div class="row">
                        <div class="col-md-3">
                            <h5>Select Table</h5>
                            <div class="list-group">
                                <?php foreach ($tables as $tableName => $tableInfo): ?>
                                    <a href="?table=<?= urlencode($tableName) ?>#data" 
                                       class="list-group-item list-group-item-action <?= $selectedTable === $tableName ? 'active' : '' ?>">
                                        <i class="fas fa-table me-2"></i>
                                        <?= htmlspecialchars($tableName) ?>
                                        <span class="badge bg-secondary ms-2"><?= number_format($tableInfo['row_count']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <?php if ($selectedTable): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5>
                                        <i class="fas fa-table me-2"></i><?= htmlspecialchars($selectedTable) ?>
                                        <span class="badge bg-info ms-2"><?= number_format($totalRecords) ?> records</span>
                                    </h5>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Table pagination">
                                        <ul class="pagination">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?table=<?= urlencode($selectedTable) ?>&page=<?= $page - 1 ?>#data">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?table=<?= urlencode($selectedTable) ?>&page=<?= $i ?>#data"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?table=<?= urlencode($selectedTable) ?>&page=<?= $page + 1 ?>#data">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>

                                <!-- Table Data -->
                                <div class="table-container">
                                    <?php if (!empty($tables[$selectedTable]['columns'])): ?>
                                        <table class="table table-striped table-hover table-sm">
                                            <thead>
                                                <tr>
                                                    <?php foreach ($tables[$selectedTable]['columns'] as $column): ?>
                                                        <th>
                                                            <?php if ($column['Key'] === 'PRI'): ?>
                                                                <i class="fas fa-key me-1 text-warning" title="Primary Key"></i>
                                                            <?php elseif ($column['Key'] === 'MUL'): ?>
                                                                <i class="fas fa-link me-1 text-info" title="Foreign Key"></i>
                                                            <?php endif; ?>
                                                            <?= htmlspecialchars($column['Field']) ?>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($column['Type']) ?></small>
                                                        </th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($tableData)): ?>
                                                    <tr>
                                                        <td colspan="<?= count($tables[$selectedTable]['columns']) ?>" class="text-center text-muted">
                                                            No data found in this table
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($tableData as $row): ?>
                                                        <tr>
                                                            <?php foreach ($tables[$selectedTable]['columns'] as $column): ?>
                                                                <td>
                                                                    <?php 
                                                                    $value = $row[$column['Field']] ?? '';
                                                                    if (is_null($value)) {
                                                                        echo '<span class="text-muted">NULL</span>';
                                                                    } elseif (strlen($value) > 50) {
                                                                        echo '<span title="' . htmlspecialchars($value) . '">' . htmlspecialchars(substr($value, 0, 50)) . '...</span>';
                                                                    } else {
                                                                        echo htmlspecialchars($value);
                                                                    }
                                                                    ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <div class="error-notice">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Unable to load table structure for "<?= htmlspecialchars($selectedTable) ?>"
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center mt-5">
                                    <i class="fas fa-table fa-3x text-muted mb-3"></i>
                                    <h4>Select a table to view its data</h4>
                                    <p class="text-muted">Choose a table from the left sidebar to explore your database</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Relationships Tab -->
            <div class="tab-pane fade" id="relationships" role="tabpanel">
                <div class="data-view">
                    <h4><i class="fas fa-link me-2"></i>Foreign Key Relationships</h4>
                    <div class="row">
                        <?php 
                        $hasRelationships = false;
                        foreach ($tables as $tableName => $tableInfo): 
                            if (!empty($tableInfo['foreign_keys'])): 
                                $hasRelationships = true;
                        ?>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <strong><i class="fas fa-table me-2"></i><?= htmlspecialchars($tableName) ?></strong>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                <?php foreach ($tableInfo['foreign_keys'] as $fk): ?>
                                                    <li class="mb-2">
                                                        <i class="fas fa-arrow-right me-2 text-primary"></i>
                                                        <code><?= htmlspecialchars($fk['COLUMN_NAME']) ?></code>
                                                        <i class="fas fa-long-arrow-alt-right mx-2"></i>
                                                        <strong><?= htmlspecialchars($fk['REFERENCED_TABLE_NAME']) ?></strong>
                                                        (<code><?= htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) ?></code>)
                                                        <br><small class="text-muted"><?= htmlspecialchars($fk['CONSTRAINT_NAME']) ?></small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                        <?php 
                            endif; 
                        endforeach; 
                        
                        if (!$hasRelationships): 
                        ?>
                            <div class="col-12">
                                <div class="text-center mt-5">
                                    <i class="fas fa-link fa-3x text-muted mb-3"></i>
                                    <h4>No Foreign Key Relationships Found</h4>
                                    <p class="text-muted">This database doesn't have any visible foreign key constraints</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 60 seconds
        setInterval(() => {
            location.reload();
        }, 60000);

        // Handle tab switching with URL hash
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash === '#data') {
                const dataTab = document.getElementById('data-tab');
                const dataTabInstance = new bootstrap.Tab(dataTab);
                dataTabInstance.show();
            } else if (hash === '#relationships') {
                const relationshipsTab = document.getElementById('relationships-tab');
                const relationshipsTabInstance = new bootstrap.Tab(relationshipsTab);
                relationshipsTabInstance.show();
            }
        });
    </script>
</body>
</html> 