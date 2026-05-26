/**
 * Vehicle Plate Autocomplete Helper
 * Integrates vehicle search API with autocomplete dropdown
 * Usage: vehicleAutocomplete(inputId, apiEndpoint, onSelectCallback)
 */

window.vehicleAutocomplete = function(inputId, apiEndpoint = '/api/vehicle_search_api.php', onSelectCallback = null) {
  const input = document.getElementById(inputId);
  if (!input) return;

  let dropdownContainer = null;
  let debounceTimeout = null;

  // Create dropdown container
  function createDropdown() {
    const container = document.createElement('div');
    container.id = inputId + '_dropdown';
    container.style.cssText = `
      position: absolute;
      top: ${input.offsetTop + input.offsetHeight}px;
      left: ${input.offsetLeft}px;
      width: ${input.offsetWidth}px;
      background: white;
      border: 1px solid #e0e0e0;
      border-top: none;
      border-radius: 0 0 4px 4px;
      max-height: 300px;
      overflow-y: auto;
      z-index: 1000;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
      display: none;
    `;
    input.parentNode.insertBefore(container, input.nextSibling);
    return container;
  }

  dropdownContainer = createDropdown();

  // Fetch suggestions from API
  function fetchSuggestions(query) {
    if (query.length < 2) {
      dropdownContainer.innerHTML = '';
      dropdownContainer.style.display = 'none';
      return;
    }

    fetch(`${apiEndpoint}?action=search&q=${encodeURIComponent(query)}`)
      .then(response => response.json())
      .then(data => {
        if (data.success && data.count > 0) {
          displayResults(data.data);
        } else {
          dropdownContainer.innerHTML = '<div style="padding:10px;color:#999;">No results found</div>';
          dropdownContainer.style.display = 'block';
        }
      })
      .catch(error => {
        console.error('Autocomplete error:', error);
        dropdownContainer.innerHTML = '<div style="padding:10px;color:#999;">Error loading results</div>';
        dropdownContainer.style.display = 'block';
      });
  }

  // Display results in dropdown
  function displayResults(results) {
    dropdownContainer.innerHTML = '';
    results.forEach(vehicle => {
      const item = document.createElement('div');
      item.style.cssText = `
        padding: 10px 12px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
      `;
      item.innerHTML = `
        <div style="font-weight: 500; color: #333;">${escapeHtml(vehicle.plate)}</div>
        <div style="font-size: 0.85em; color: #666;">
          ${escapeHtml(vehicle.brand || 'N/A')} • ${escapeHtml(vehicle.name || '')}
        </div>
      `;
      item.onmouseover = () => { item.style.backgroundColor = '#f5f5f5'; };
      item.onmouseout = () => { item.style.backgroundColor = 'white'; };
      item.onclick = () => {
        input.value = vehicle.plate;
        dropdownContainer.style.display = 'none';
        if (typeof onSelectCallback === 'function') {
          onSelectCallback(vehicle);
        }
      };
      dropdownContainer.appendChild(item);
    });
    dropdownContainer.style.display = 'block';
  }

  // Input event listener with debounce
  input.addEventListener('input', (e) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      fetchSuggestions(e.target.value);
    }, 300);
  });

  // Hide dropdown on blur
  input.addEventListener('blur', () => {
    setTimeout(() => {
      dropdownContainer.style.display = 'none';
    }, 200);
  });

  // Show dropdown on focus if has content
  input.addEventListener('focus', () => {
    if (input.value.length >= 2) {
      dropdownContainer.style.display = 'block';
    }
  });
};

// Helper to escape HTML
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
}

// Auto-initialize on page load if data-autocomplete attribute present
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('input[data-autocomplete]').forEach(input => {
    const endpoint = input.getAttribute('data-api-endpoint') || '/api/vehicle_search_api.php';
    vehicleAutocomplete(input.id, endpoint);
  });
});
