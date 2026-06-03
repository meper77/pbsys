// File: assets/js/bulk-delete.js

class BulkDelete {
  constructor(options = {}) {
    this.checkboxSelector = options.checkboxSelector || 'input[name="selected_ids[]"]';
    this.buttonSelector = options.buttonSelector || '#bulkDeleteBtn';
    this.formSelector = options.formSelector || '#bulkDeleteForm';
    this.confirmMessage = options.confirmMessage || 'Delete selected items? This cannot be undone.';
    this.endpoint = options.endpoint || '/api/bulk_delete_api.php';
    
    this.init();
  }
  
  init() {
    this.form = document.querySelector(this.formSelector);
    this.button = document.querySelector(this.buttonSelector);
    this.checkboxes = document.querySelectorAll(this.checkboxSelector);
    
    if (!this.button) return;
    
    // Toggle button disabled state on checkbox change
    this.checkboxes.forEach(cb => {
      cb.addEventListener('change', () => this.updateButtonState());
    });
    
    // Handle bulk delete. The component renders a type="button" trigger, so a
    // wrapping <form>'s submit event never fires from it — always bind the click.
    this.button.addEventListener('click', (e) => this.handleClick(e));
    if (this.form) {
      this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
  }
  
  updateButtonState() {
    const checked = document.querySelectorAll(this.checkboxSelector + ':checked').length;
    this.button.disabled = checked === 0;
    this.button.textContent = checked > 0 ? `Delete selected (${checked})` : 'Delete selected';
  }
  
  handleClick(e) {
    e.preventDefault();
    
    const selected = this.getSelectedIds();
    if (selected.length === 0) {
      alert('Please select items to delete');
      return;
    }
    
    if (!confirm(this.confirmMessage)) return;
    
    this.submit(selected);
  }
  
  handleSubmit(e) {
    e.preventDefault();
    
    const selected = this.getSelectedIds();
    if (selected.length === 0) {
      alert('Please select items to delete');
      return;
    }
    
    if (!confirm(this.confirmMessage)) return;
    
    this.submit(selected);
  }
  
  getSelectedIds() {
    return Array.from(document.querySelectorAll(this.checkboxSelector + ':checked'))
      .map(cb => cb.value);
  }
  
  submit(ids) {
    const formData = new URLSearchParams();
    formData.append('action', 'bulk_delete');
    formData.append('vehicle_type', document.querySelector('[name="vehicle_type"]')?.value || '');
    ids.forEach(id => formData.append('ids[]', id));
    
    fetch(this.endpoint, {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(`Deleted ${data.count} item(s)`);
        location.reload();
      } else {
        alert('Error: ' + (data.message || 'Unknown error'));
      }
    })
    .catch(err => alert('Error: ' + err.message));
  }
}

// Auto-init if data attributes present
document.addEventListener('DOMContentLoaded', () => {
  const button = document.getElementById('bulkDeleteBtn');
  if (button && button.dataset.autoinit !== 'false') {
    new BulkDelete({
      confirmMessage: button.dataset.confirmMessage || 'Delete selected items? This cannot be undone.',
      endpoint: button.dataset.endpoint || '/api/bulk_delete_api.php'
    });
  }
});
