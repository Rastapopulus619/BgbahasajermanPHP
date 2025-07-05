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
    .template-list { max-width: 300px; margin-bottom: 20px; }
    .template-list li { cursor: pointer; padding: 6px 10px; border-radius: 4px; }
    .template-list li.selected { background: #e0e0e0; font-weight: bold; }
    .template-actions { margin-top: 10px; display: flex; gap: 10px; }
    .template-actions button { padding: 4px 10px; }
    #templateContent { width: 100%; min-height: 120px; margin-top: 10px; }
    .flex-row { display: flex; gap: 40px; align-items: flex-start; }
    .side-panel { flex: 1 1 0; min-width: 0; }
    .main-panel { flex: 2 1 0; min-width: 0; }
  </style>
</head>
<body>
  <h1>Manage WhatsApp Templates</h1>
  
  <!-- Template selection with "Create New" option -->
  <div style="margin-bottom: 20px;">
    <?php 
    $templateSelectId = 'templateManagerSelect';
    $templateSelectLabel = 'Choose Template to Edit';
    $includeNewOption = true;
    $onChangeCallback = "'handleTemplateSelection'";
    include '../assets/components/TemplateSelector.php'; 
    ?>
  </div>
  
  <div class="flex-row">
    <div class="side-panel">
      <strong>Templates</strong>
      <ul id="templateList" class="template-list"></ul>
      <div class="template-actions">
        <button id="addBtn">Add New</button>
        <button id="duplicateBtn" disabled>Duplicate</button>
        <button id="renameBtn" disabled>Rename</button>
        <button id="deleteBtn" disabled>Delete</button>
      </div>
    </div>
    <div class="main-panel">
      <label for="templateSelect"><strong>Template Name:</strong></label><br>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
        <select id="templateSelect" style="width: 50%;">
          <option value="">-- Choose Template --</option>
        </select>
        <span style="color: #888;">or</span>
        <input type="text" id="templateName" style="width: 45%;" placeholder="Type new template name..." disabled>
      </div>
      <label for="templateContent"><strong>Template Content:</strong></label><br>
      <textarea id="templateContent" rows="8" placeholder="Template text..."></textarea><br>
      <button id="saveBtn" disabled>Save</button>
      <span id="statusMsg" style="margin-left: 20px; color: green;"></span>
    </div>
  </div>
  <br>
  <a href="WATemplateCreator.php">&larr; Back to Template Creator</a>
  <script>
    let selected = '';
    let templates = [];
    const templateList = document.getElementById('templateList');
    const templateSelect = document.getElementById('templateSelect');
    const templateName = document.getElementById('templateName');
    const templateContent = document.getElementById('templateContent');
    const saveBtn = document.getElementById('saveBtn');
    const addBtn = document.getElementById('addBtn');
    const renameBtn = document.getElementById('renameBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const duplicateBtn = document.getElementById('duplicateBtn');
    const statusMsg = document.getElementById('statusMsg');

    // Handle template selection from the dropdown
    function handleTemplateSelection(value) {
      if (value === '__NEW__') {
        addNewTemplate();
      } else if (value) {
        selectTemplate(value);
      } else {
        clearSelection();
      }
    }

    function addNewTemplate() {
      const base = 'new_template';
      let name = base;
      let i = 1;
      while (templates.includes(name)) { name = base + i; i++; }
      
      // Clear dropdown and prepare for new template
      templateSelect.value = '';
      templateName.value = name;
      templateContent.value = '';
      templateName.disabled = false;
      saveBtn.disabled = false;
      renameBtn.disabled = true;
      deleteBtn.disabled = true;
      duplicateBtn.disabled = true;
      selected = '';
      statusMsg.textContent = '';
      renderList();
      
      // Reset existing dropdown component if it exists
      if (document.getElementById('templateManagerSelect')) {
        document.getElementById('templateManagerSelect').value = '';
      }
    }

    function clearSelection() {
      selected = '';
      templateName.value = '';
      templateContent.value = '';
      templateName.disabled = true;
      saveBtn.disabled = true;
      renameBtn.disabled = true;
      deleteBtn.disabled = true;
      duplicateBtn.disabled = true;
      statusMsg.textContent = '';
      renderList();
    }

    function fetchTemplates() {
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'list'}) })
        .then(r => r.json())
        .then(data => {
          templates = data.templates || [];
          renderList();
          populateDropdown();
        });
    }

    function populateDropdown() {
      // Clear existing options except the first one
      templateSelect.innerHTML = '<option value="">-- Choose Template --</option>';
      
      // Add templates to dropdown
      templates.forEach(name => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name.charAt(0).toUpperCase() + name.slice(1);
        templateSelect.appendChild(option);
      });
    }
    function renderList() {
      templateList.innerHTML = '';
      templates.forEach(name => {
        const li = document.createElement('li');
        li.textContent = name;
        if (name === selected) li.classList.add('selected');
        li.onclick = () => selectTemplate(name);
        templateList.appendChild(li);
      });
    }
    function selectTemplate(name) {
      selected = name;
      templateSelect.value = name;
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
      renderList();
    }
    addBtn.onclick = () => {
      addNewTemplate();
    };
    saveBtn.onclick = () => {
      const name = templateName.value.trim();
      const content = templateContent.value;
      if (!name.match(/^[a-zA-Z0-9_\- ]+$/)) {
        statusMsg.textContent = 'Invalid name.';
        statusMsg.style.color = 'red';
        return;
      }
      fetch('templateManager.php', { method: 'POST', body: new URLSearchParams({action: 'save', name, content}) })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            statusMsg.textContent = 'Saved!';
            statusMsg.style.color = 'green';
            if (!templates.includes(name)) {
              templates.push(name);
              // Reload the dropdown to include the new template
              populateDropdown();
              if (window.templateManagerSelect_reload) {
                window.templateManagerSelect_reload();
              }
            }
            // Update dropdown selection and selected template
            selected = name;
            templateSelect.value = name;
            renderList();
          } else {
            statusMsg.textContent = data.error || 'Error.';
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
            templateSelect.value = newName;
            templateName.value = '';
            populateDropdown();
            renderList();
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
            templateSelect.value = '';
            templateName.value = '';
            templateContent.value = '';
            saveBtn.disabled = true;
            renameBtn.disabled = true;
            deleteBtn.disabled = true;
            duplicateBtn.disabled = true;
            // Reload the dropdown to remove the deleted template
            populateDropdown();
            if (window.templateManagerSelect_reload) {
              window.templateManagerSelect_reload();
            }
            renderList();
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
            populateDropdown();
            renderList();
          } else {
            statusMsg.textContent = data.error || 'Error.';
            statusMsg.style.color = 'red';
          }
        });
    };

    // Handle dropdown selection
    templateSelect.addEventListener('change', function() {
      const selectedTemplate = this.value;
      if (selectedTemplate) {
        selectTemplate(selectedTemplate);
      } else {
        // Clear everything when "-- Choose Template --" is selected
        selected = '';
        templateName.value = '';
        templateContent.value = '';
        templateName.disabled = false;
        saveBtn.disabled = false;
        renameBtn.disabled = true;
        deleteBtn.disabled = true;
        duplicateBtn.disabled = true;
        statusMsg.textContent = '';
        renderList();
      }
    });

    // Handle manual input in the text field
    templateName.addEventListener('input', function() {
      const inputValue = this.value.trim();
      if (inputValue && templates.includes(inputValue)) {
        // If user typed an existing template name, load it
        selectTemplate(inputValue);
      } else {
        // User is typing a new name
        templateSelect.value = '';
        selected = '';
        saveBtn.disabled = false;
        renameBtn.disabled = true;
        deleteBtn.disabled = true;
        duplicateBtn.disabled = true;
        statusMsg.textContent = '';
        renderList();
      }
    });

    fetchTemplates();
  </script>
</body>
</html>
