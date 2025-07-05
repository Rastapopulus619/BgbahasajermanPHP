<?php
// templateManager.php - Manage WhatsApp Templates (add, edit, rename, delete, duplicate)

// Fix directory handling with better error reporting
$templateDir = __DIR__ . '/../templates/';
if (!is_dir($templateDir)) {
    if (!mkdir($templateDir, 0777, true)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Could not create templates directory']);
        exit;
    }
}
$templateDir = realpath($templateDir); // Now it will work since directory exists

// Ensure directory is writable
if (!is_writable($templateDir)) {
    chmod($templateDir, 0777);
}

function listTemplates($dir) {
    $files = glob($dir . '/*.txt');
    $names = [];
    foreach ($files as $file) {
        $names[] = basename($file, '.txt');
    }
    return $names;
}

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $content = $_POST['content'] ?? '';
    $newName = trim($_POST['newName'] ?? '');
    $response = ['success' => false];

    // Skip name validation for 'list' action
    if ($action !== 'list' && !preg_match('/^[a-zA-Z0-9_\- ]+$/', $name)) {
        $response['error'] = 'Invalid template name.';
        echo json_encode($response);
        exit;
    }
    
    $file = $templateDir . '/' . $name . '.txt';

    switch ($action) {
        case 'save':
            // Add error handling for file writing
            if (!is_writable($templateDir)) {
                $response['error'] = 'Templates directory is not writable.';
                break;
            }
            $result = @file_put_contents($file, $content);
            if ($result === false) {
                $response['error'] = 'Failed to save file. Check permissions.';
            } else {
                $response['success'] = true;
            }
            break;
        case 'delete':
            if (file_exists($file)) unlink($file);
            $response['success'] = true;
            break;
        case 'rename':
            if (!preg_match('/^[a-zA-Z0-9_\- ]+$/', $newName)) {
                $response['error'] = 'Invalid new name.';
                break;
            }
            $newFile = $templateDir . '/' . $newName . '.txt';
            if (file_exists($file)) rename($file, $newFile);
            $response['success'] = true;
            break;
        case 'duplicate':
            $copyName = $name . '_copy';
            $copyFile = $templateDir . '/' . $copyName . '.txt';
            $i = 2;
            while (file_exists($copyFile)) {
                $copyName = $name . '_copy' . $i;
                $copyFile = $templateDir . '/' . $copyName . '.txt';
                $i++;
            }
            if (file_exists($file)) copy($file, $copyFile);
            $response['success'] = true;
            $response['newName'] = $copyName;
            break;
        case 'load':
            if (file_exists($file)) {
                $response['success'] = true;
                $response['content'] = file_get_contents($file);
            }
            break;
        case 'list':
            $response['success'] = true;
            $response['templates'] = listTemplates($templateDir);
            break;
    }
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage WhatsApp Templates</title>
  <link rel="stylesheet" href="../assets/entryform.css">
  <link rel="stylesheet" href="../assets/dropdownbox.css">
  <style>
    .template-actions { margin-top: 10px; display: flex; gap: 10px; }
    .template-actions button { padding: 4px 10px; }
    #templateContent { width: 100%; min-height: 120px; margin-top: 10px; }
    .flex-row { display: flex; gap: 40px; align-items: flex-start; }
    .side-panel { flex: 0 0 auto; min-width: 200px; }
    .main-panel { flex: 1 1 0; min-width: 0; }
  </style>
</head>
<body>
  <h1>Manage WhatsApp Templates</h1>
  
  
  <div class="flex-row">
    <div class="side-panel">
      <div class="template-actions">
        <button id="resetBtn">Reset</button>
        <button id="duplicateBtn" disabled>Duplicate</button>
        <button id="renameBtn" disabled>Rename</button>
        <button id="deleteBtn" disabled>Delete</button>
      </div>
    </div>
    <div class="main-panel">
      <label for="templateManagerSelect"><strong>Template Name:</strong></label><br>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
        <div style="width: 50%;">
          <?php 
          $templateSelectId = 'templateManagerSelect';
          $templateSelectLabel = '';
          $includeNewOption = false;
          $onChangeCallback = "'handleTemplateSelection'";
          include '../assets/components/TemplateSelector.php'; 
          ?>
        </div>
        <span style="color: #888;">or</span>
        <input type="text" id="templateName" style="width: 45%;" placeholder="Type new template name...">
      </div>
      <label for="templateContent"><strong>Template Content:</strong></label><br>
      <textarea id="templateContent" rows="8" placeholder="Template text..."></textarea><br>
      <button id="saveBtn" style="padding: 4px 10px;">Save</button>
      <span id="statusMsg" style="margin-left: 20px; color: green;"></span>
    </div>
  </div>
  <br>
  <a href="WATemplateCreator.php">&larr; Back to Template Creator</a>
  <script>
    let selected = '';
    let templates = [];
    const templateName = document.getElementById('templateName');
    const templateContent = document.getElementById('templateContent');
    const saveBtn = document.getElementById('saveBtn');
    const resetBtn = document.getElementById('resetBtn');
    const renameBtn = document.getElementById('renameBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const duplicateBtn = document.getElementById('duplicateBtn');
    const statusMsg = document.getElementById('statusMsg');

    // Handle template selection from the dropdown
    function handleTemplateSelection(value) {
      if (value) {
        selectTemplate(value);
      } else {
        clearSelection();
      }
    }

    function resetFields() {
      // Clear all fields
      templateName.value = '';
      templateContent.value = '';
      templateName.disabled = false;
      saveBtn.disabled = false;
      renameBtn.disabled = true;
      deleteBtn.disabled = true;
      duplicateBtn.disabled = true;
      selected = '';
      statusMsg.textContent = '';
      
      // Reset dropdown selection
      if (document.getElementById('templateManagerSelect')) {
        document.getElementById('templateManagerSelect').value = '';
      }
    }

    function clearSelection() {
      selected = '';
      templateName.value = '';
      templateContent.value = '';
      templateName.disabled = false;
      saveBtn.disabled = false;
      renameBtn.disabled = true;
      deleteBtn.disabled = true;
      duplicateBtn.disabled = true;
      statusMsg.textContent = '';
    }

    function fetchTemplates() {
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'list'}) })
        .then(r => r.json())
        .then(data => {
          templates = data.templates || [];
          // Reload the dropdown component
          if (window.templateManagerSelect_reload) {
            window.templateManagerSelect_reload();
          }
        });
    }
    function selectTemplate(name) {
      selected = name;
      templateName.value = '';
      templateName.disabled = false;
      saveBtn.disabled = false;
      renameBtn.disabled = false;
      deleteBtn.disabled = false;
      duplicateBtn.disabled = false;
      statusMsg.textContent = '';
      
      // Load template content automatically
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'load', name}) })
        .then(r => r.json())
        .then(data => {
          templateContent.value = data.content || '';
        });
    }
    resetBtn.onclick = () => {
      resetFields();
    };
    saveBtn.onclick = () => {
      const name = templateName.value.trim();
      const content = templateContent.value;
      
      // If templateName is empty, use the selected template name
      const finalName = name || selected;
      
      if (!finalName) {
        statusMsg.textContent = 'Please enter a template name or select a template.';
        statusMsg.style.color = 'red';
        return;
      }
      
      if (!finalName.match(/^[a-zA-Z0-9_\- ]+$/)) {
        statusMsg.textContent = 'Invalid name. Use only letters, numbers, spaces, hyphens, and underscores.';
        statusMsg.style.color = 'red';
        return;
      }
      
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'save', name: finalName, content}) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            statusMsg.textContent = 'Saved!';
            statusMsg.style.color = 'green';
            
            // If this is a new template, add it to the list and reload dropdown
            if (!templates.includes(finalName)) {
              templates.push(finalName);
              if (window.templateManagerSelect_reload) {
                window.templateManagerSelect_reload();
              }
            }
            
            // Update selection state
            selected = finalName;
            templateName.value = '';
            
            // Update dropdown selection
            if (document.getElementById('templateManagerSelect')) {
              document.getElementById('templateManagerSelect').value = finalName;
            }
          } else {
            statusMsg.textContent = data.error || 'Error saving template.';
            statusMsg.style.color = 'red';
          }
        });
    };
    renameBtn.onclick = () => {
      const oldName = selected;
      const newName = prompt('Rename template to:', oldName);
      if (!newName || !newName.match(/^[a-zA-Z0-9_\- ]+$/)) return;
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'rename', name: oldName, newName}) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            statusMsg.textContent = 'Renamed!';
            statusMsg.style.color = 'green';
            templates = templates.map(n => n === oldName ? newName : n);
            selected = newName;
            templateName.value = '';
            
            // Reload dropdown
            if (window.templateManagerSelect_reload) {
              window.templateManagerSelect_reload();
            }
            
            // Update dropdown selection
            setTimeout(() => {
              if (document.getElementById('templateManagerSelect')) {
                document.getElementById('templateManagerSelect').value = newName;
              }
            }, 100);
          } else {
            statusMsg.textContent = data.error || 'Error.';
            statusMsg.style.color = 'red';
          }
        });
    };
    deleteBtn.onclick = () => {
      if (!selected) return;
      if (!confirm('Delete template "' + selected + '"?')) return;
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'delete', name: selected}) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            statusMsg.textContent = 'Deleted!';
            statusMsg.style.color = 'green';
            templates = templates.filter(n => n !== selected);
            selected = '';
            templateName.value = '';
            templateContent.value = '';
            saveBtn.disabled = false;
            renameBtn.disabled = true;
            deleteBtn.disabled = true;
            duplicateBtn.disabled = true;
            
            // Reload the dropdown to remove the deleted template
            if (window.templateManagerSelect_reload) {
              window.templateManagerSelect_reload();
            }
          } else {
            statusMsg.textContent = data.error || 'Error.';
            statusMsg.style.color = 'red';
          }
        });
    };
    duplicateBtn.onclick = () => {
      if (!selected) return;
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'duplicate', name: selected}) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            statusMsg.textContent = 'Duplicated!';
            statusMsg.style.color = 'green';
            templates.push(data.newName);
            
            // Reload dropdown
            if (window.templateManagerSelect_reload) {
              window.templateManagerSelect_reload();
            }
          } else {
            statusMsg.textContent = data.error || 'Error.';
            statusMsg.style.color = 'red';
          }
        });
    };

    // Handle manual input in the text field
    templateName.addEventListener('input', function() {
      const inputValue = this.value.trim();
      if (inputValue && templates.includes(inputValue)) {
        // If user typed an existing template name, load it
        selectTemplate(inputValue);
      } else {
        // User is typing a new name - allow saving as new template
        if (document.getElementById('templateManagerSelect')) {
          document.getElementById('templateManagerSelect').value = '';
        }
        selected = '';
        saveBtn.disabled = false;
        renameBtn.disabled = true;
        deleteBtn.disabled = true;
        duplicateBtn.disabled = true;
        statusMsg.textContent = '';
      }
    });

    fetchTemplates();
  </script>
</body>
</html>
