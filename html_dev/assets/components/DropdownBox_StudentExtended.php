<?php
$inputId = $inputId ?? 'studentInput';
$dropdownId = $dropdownId ?? 'studentDropdown';
$statusId = $statusId ?? 'studentStatus';
$errorId = $errorId ?? 'studentError';
$buttonId = $buttonId ?? 'studentShowBtn';
$label = $label ?? 'Student auswählen';
$placeholder = $placeholder ?? 'Gib den Namen ein …';

// Auto-setup configuration
$autoSetup = true;
$dropdownConfig = [
  'dataSource' => 'dataFetcher',
  'dataFetcherQuery' => [
    'type' => 'simple',
    'sql' => 'SELECT StudentID, StudentNumber, Name, Title FROM students ORDER BY Name',
    'return_type' => 'multiple'
  ],
  'displayField' => 'Name',
  'valueField' => 'Name',
  'filterFields' => ['Name', 'StudentNumber'],
  'showAllOnFocus' => true,
  'autoHighlight' => isset($autoHighlight) ? $autoHighlight : false,
  'highlightFunction' => isset($highlightFunction) ? $highlightFunction : null
];

include 'DropdownBoxTemplate.php';
?>