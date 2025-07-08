<?php ?>
<div class="dropdownbox-wrapper" id="<?= $inputId ?>_wrapper">
  <label for="<?= $inputId ?>"><?= $label ?></label>
  <div class="input-wrapper">
    <input
      type="text"
      id="<?= $inputId ?>"
      autocomplete="off"
      placeholder="<?= $placeholder ?>"
      disabled
    />
    <div class="dropdown-button-area"></div>
  </div>
  <div id="<?= $dropdownId ?>" class="dropdownbox-list hidden"></div>
  <div class="status-line">
    <span id="<?= $statusId ?>" class="status"></span>
    <span id="<?= $errorId ?>" class="input-error"></span>
  </div>
  <?php if (isset($buttonId) && $buttonId): ?>
  <div id="<?= $buttonId ?>" style="display:none;"></div>
  <?php endif; ?>
</div>

<?php if (isset($autoSetup) && $autoSetup): ?>
<script type="module">
import { setupDataDropdown } from '/assets/js/dropdownBoxExtended.js';

document.addEventListener('DOMContentLoaded', () => {
  const config = <?= json_encode($dropdownConfig ?? []) ?>;
  config.inputId = '<?= $inputId ?>';
  config.dropdownId = '<?= $dropdownId ?>';
  config.statusId = '<?= $statusId ?>';
  config.errorId = '<?= $errorId ?>';
  <?php if (isset($buttonId)): ?>
  config.buttonId = '<?= $buttonId ?>';
  <?php endif; ?>
  
  setupDataDropdown(config);
});
</script>
<?php endif; ?>