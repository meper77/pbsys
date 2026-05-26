<?php
/**
 * Bulk Delete UI Component
 * Usage in table:
 *   <thead>
 *     <th><?php echo bulk_delete_checkbox_header(); ?></th>
 *     ...
 *   </thead>
 * 
 * Usage in form:
 *   <form id="bulkDeleteForm" method="POST">
 *     <?php echo bulk_delete_button(); ?>
 *   </form>
 */

function bulk_delete_button($options = []) {
  $opts = array_merge([
    'endpoint' => '/api/bulk_delete_api.php',
    'confirm_message' => 'Delete selected items? This cannot be undone.',
    'button_class' => 'btn btn-ghost text-danger'
  ], $options);
  
  return <<<HTML
<button type="button" 
        class="{$opts['button_class']}" 
        id="bulkDeleteBtn" 
        disabled=""
        data-endpoint="{$opts['endpoint']}"
        data-confirm-message="{$opts['confirm_message']}">
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" 
       fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" 
       stroke-linejoin="round" data-lucide="trash-2" aria-hidden="true" 
       class="lucide lucide-trash-2">
    <path d="M10 11v6"></path>
    <path d="M14 11v6"></path>
    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
    <path d="M3 6h18"></path>
    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
  </svg> Delete selected
</button>
<script src="/assets/js/bulk-delete.js"></script>
HTML;
}

function bulk_delete_checkbox_header() {
  return <<<HTML
<th style="width: 40px;">
  <input type="checkbox" id="selectAllCheckbox" title="Select all on this page">
</th>
HTML;
}

function bulk_delete_checkbox($id) {
  return <<<HTML
<td>
  <input type="checkbox" name="selected_ids[]" value="$id">
</td>
HTML;
}

function bulk_delete_select_all_script() {
  return <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectAll = document.getElementById('selectAllCheckbox');
  if (!selectAll) return;
  
  selectAll.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
    
    // Update button state
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) {
      const checked = document.querySelectorAll('input[name="selected_ids[]"]:checked').length;
      bulkDeleteBtn.disabled = checked === 0;
      bulkDeleteBtn.textContent = checked > 0 ? `Delete selected (\${checked})` : 'Delete selected';
    }
  });
});
</script>
HTML;
}
?>
