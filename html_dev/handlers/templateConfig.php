<?php
/**
 * Template Configuration System
 * 
 * This file defines auto-fetch configurations for different WhatsApp templates.
 * Each template can have specific rules for automatically filling placeholders
 * from database data when a student is selected.
 * 
 * HOW TO ADD NEW TEMPLATES:
 * 1. Add a new key to the $configs array
 * 2. Define 'auto_fetch' rules for each placeholder
 * 3. Specify the data source, field, and optional custom query
 * 
 * HOW TO MODIFY EXISTING TEMPLATES:
 * 1. Find the template key in $configs
 * 2. Add/modify placeholders in the 'auto_fetch' section
 * 3. Use 'source' for simple field mapping or 'query' for complex data
 */

/**
 * Get auto-fetch configuration for a specific template
 * 
 * @param string $templateKey - The template identifier (filename without .txt)
 * @return array - Configuration array with auto-fetch rules
 */
function getTemplateConfig($templateKey) {
    $configs = [
        
        // ==========================================
        // STUDENT REMINDER TEMPLATE
        // Simple auto-fetch for basic student data
        // ==========================================
        'student_reminder' => [
            'auto_fetch' => [
                // Basic student information - direct field mapping
                'NAME' => [
                    'source' => 'student',           // Data comes from students table
                    'field' => 'Name',               // Database field name
                    'trigger' => 'student_selected'  // When to fetch this data
                ],
                'NUMBER' => [
                    'source' => 'student',
                    'field' => 'StudentNumber',
                    'trigger' => 'student_selected'
                ],
                'LEVEL' => [
                    'source' => 'student',
                    'field' => 'Level',
                    'trigger' => 'student_selected'
                ],
                // Complex query example - next class date with formatting
                'NEXT_CLASS' => [
                    'source' => 'schedule',
                    'field' => 'NextClass',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT DATE_FORMAT(MIN(ScheduleDate), "%d.%m.%Y um %H:%i") as NextClass FROM schedules WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND ScheduleDate > NOW()'
                ]
            ]
        ],
        
        // ==========================================
        // PAYMENT REMINDER TEMPLATE
        // Complex auto-fetch with calculations
        // ==========================================
        'payment_reminder' => [
            'auto_fetch' => [
                'NAME' => [
                    'source' => 'student',
                    'field' => 'Name',
                    'trigger' => 'student_selected'
                ],
                'NUMBER' => [
                    'source' => 'student',
                    'field' => 'StudentNumber',
                    'trigger' => 'student_selected'
                ],
                // Next pending payment amount with formatting
                'AMOUNT' => [
                    'source' => 'payment',
                    'field' => 'TotalOutstanding',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT CONCAT(FORMAT(COALESCE(SUM(Amount), 0), 2), "€") as TotalOutstanding FROM payments WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND Status = "pending"'
                ],
                // Due date of next payment
                'DUE_DATE' => [
                    'source' => 'payment',
                    'field' => 'DueDate',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT DATE_FORMAT(DueDate, "%d.%m.%Y") as DueDate FROM payments WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND Status = "pending" ORDER BY DueDate ASC LIMIT 1'
                ]
            ]
        ],
        
        // ==========================================
        // SCHEDULE UPDATE TEMPLATE
        // Schedule and attendance related data
        // ==========================================
        'schedule_update' => [
            'auto_fetch' => [
                'NAME' => [
                    'source' => 'student',
                    'field' => 'Name',
                    'trigger' => 'student_selected'
                ],
                'LEVEL' => [
                    'source' => 'student',
                    'field' => 'Level',
                    'trigger' => 'student_selected'
                ],
                // Next scheduled class with day and time
                'NEXT_CLASS' => [
                    'source' => 'schedule',
                    'field' => 'NextClass',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT DATE_FORMAT(MIN(ScheduleDate), "%W, %d.%m.%Y um %H:%i") as NextClass FROM schedules WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND ScheduleDate > NOW()'
                ],
                // Time of next class
                'TIME' => [
                    'source' => 'schedule',
                    'field' => 'Time',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT DATE_FORMAT(MIN(ScheduleDate), "%H:%i") as Time FROM schedules WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND ScheduleDate > NOW()'
                ],
                // Count of classes this month
                'CLASSES_THIS_MONTH' => [
                    'source' => 'schedule',
                    'field' => 'ClassesThisMonth',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT COUNT(*) as ClassesThisMonth FROM schedules WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND MONTH(ScheduleDate) = MONTH(NOW()) AND YEAR(ScheduleDate) = YEAR(NOW())'
                ]
            ]
        ],
        
        // ==========================================
        // PROGRESS REPORT TEMPLATE
        // Academic progress and performance data
        // ==========================================
        'progress_report' => [
            'auto_fetch' => [
                'NAME' => [
                    'source' => 'student',
                    'field' => 'Name',
                    'trigger' => 'student_selected'
                ],
                'NUMBER' => [
                    'source' => 'student',
                    'field' => 'StudentNumber',
                    'trigger' => 'student_selected'
                ],
                'LEVEL' => [
                    'source' => 'student',
                    'field' => 'Level',
                    'trigger' => 'student_selected'
                ],
                // Performance status based on recent test scores
                'PROGRESS_STATUS' => [
                    'source' => 'progress',
                    'field' => 'ProgressStatus',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT CASE 
                                  WHEN AVG(Score) >= 90 THEN "Ausgezeichnet 🌟"
                                  WHEN AVG(Score) >= 80 THEN "Sehr gut 👍"
                                  WHEN AVG(Score) >= 70 THEN "Gut ✅"
                                  WHEN AVG(Score) >= 60 THEN "Befriedigend 📈"
                                  ELSE "Verbesserung nötig 📚"
                                END as ProgressStatus
                                FROM test_results 
                                WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) 
                                AND TestDate >= DATE_SUB(NOW(), INTERVAL 3 MONTH)'
                ],
                // Average score over last 3 months
                'AVERAGE_SCORE' => [
                    'source' => 'progress',
                    'field' => 'AverageScore',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT ROUND(AVG(Score), 1) as AverageScore FROM test_results WHERE StudentID = (SELECT StudentID FROM students WHERE Name = ?) AND TestDate >= DATE_SUB(NOW(), INTERVAL 3 MONTH)'
                ],
                // Next milestone based on current level
                'NEXT_MILESTONE' => [
                    'source' => 'student',
                    'field' => 'NextMilestone',
                    'trigger' => 'student_selected',
                    'query' => 'SELECT CASE 
                                    WHEN Level = "A1" THEN "A2 Prüfung 🎯"
                                    WHEN Level = "A2" THEN "B1 Prüfung 📈"
                                    WHEN Level = "B1" THEN "B2 Prüfung 🚀"
                                    WHEN Level = "B2" THEN "C1 Prüfung 🎓"
                                    ELSE "Fortgeschrittene Kurse 🚀"
                                  END as NextMilestone
                                FROM students 
                                WHERE Name = ?'
                ]
            ]
        ]
    ];
    
    // Return the configuration for the specified template, or empty array if not found
    return $configs[$templateKey] ?? [];
}

/**
 * Get list of all available template configurations
 * 
 * @return array - Array of template keys
 */
function getAvailableTemplates() {
    $configs = [
        'student_reminder' => 'Student Reminder',
        'payment_reminder' => 'Payment Reminder', 
        'schedule_update' => 'Schedule Update',
        'progress_report' => 'Progress Report'
    ];
    
    return $configs;
}

/**
 * Validate template configuration
 * 
 * @param array $config - Template configuration array
 * @return array - Validation result with success status and any errors
 */
function validateTemplateConfig($config) {
    $errors = [];
    
    if (!isset($config['auto_fetch'])) {
        $errors[] = "Missing 'auto_fetch' configuration";
    } else {
        foreach ($config['auto_fetch'] as $placeholder => $fetchConfig) {
            if (!isset($fetchConfig['source'])) {
                $errors[] = "Missing 'source' for placeholder '{$placeholder}'";
            }
            if (!isset($fetchConfig['field'])) {
                $errors[] = "Missing 'field' for placeholder '{$placeholder}'";
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}
?>
