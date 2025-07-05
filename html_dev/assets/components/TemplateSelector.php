<?php
// TemplateSelector.php - Reusable template selection component

$templateSelectId = $templateSelectId ?? 'templateSelect';
$templateSelectLabel = $templateSelectLabel ?? 'Select Template';
$includeNewOption = $includeNewOption ?? false;
$onChangeCallback = $onChangeCallback ?? null;
?>

<div class="template-selector-wrapper">
  <label for="<?= $templateSelectId ?>"><strong><?= $templateSelectLabel ?>:</strong></label>
  <select id="<?= $templateSelectId ?>" class="template-selector">
    <option value="">-- Choose Template --</option>
    <?php if ($includeNewOption): ?>
    <option value="__NEW__">+ Create New Template</option>
    <?php endif; ?>
  </select>
</div>

<script>
// Template Selector Component Logic
(function() {
  const selectId = '<?= $templateSelectId ?>';
  const includeNew = <?= $includeNewOption ? 'true' : 'false' ?>;
  const onChangeCallback = <?= $onChangeCallback ? $onChangeCallback : 'null' ?>;
  
  // Load templates into the selector
  async function loadTemplateOptions() {
    try {
      const res = await fetch('../handlers/listTemplates.php');
      const templates = await res.json();
      
      const select = document.getElementById(selectId);
      
      // Clear existing options except first one (and "New" if included)
      const optionsToKeep = includeNew ? 2 : 1;
      while (select.options.length > optionsToKeep) {
        select.removeChild(select.lastChild);
      }
      
      // Add template options
      templates.forEach(name => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name.charAt(0).toUpperCase() + name.slice(1);
        select.appendChild(option);
      });
      
      // Add change event listener if callback provided
      if (onChangeCallback && typeof window[onChangeCallback] === 'function') {
        select.addEventListener('change', () => {
          window[onChangeCallback](select.value);
        });
      }
      
    } catch (err) {
      console.error('Error loading template options:', err);
    }
  }
  
  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadTemplateOptions);
  } else {
    loadTemplateOptions();
  }
  
  // Expose reload function globally
  window[selectId + '_reload'] = loadTemplateOptions;
})();
</script>
