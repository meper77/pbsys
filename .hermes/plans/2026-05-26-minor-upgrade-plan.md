# NEO V-TRACK Minor Upgrade Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Modernize pbsys with responsive design, cross-page autocomplete search, multi-select bulk delete, M:M user-vehicle logic, status tracking, XLSX import, and permission-based views.

**Architecture:** 
- Phase 1: Database schema changes (status table, remove IC as PK)
- Phase 2: Frontend foundation (responsive design, bulk delete UI)
- Phase 3: Backend APIs (autocomplete search, M:M across pages, bulk delete endpoint)
- Phase 4: Integration (update all vehicle pages, reports, admin pages)
- Phase 5: Permissions & validation (role-based views, SMTP password reset)

**Tech Stack:** PHP 8, MySQL, HTML5, CSS3 (media queries), JavaScript (fetch API), Bootstrap/Tailwind (responsive)

---

## Phase 1: Database Schema Changes

### Task 1: Create vehicle_status table

**Objective:** Add status tracking table to track active/inactive vehicles per type

**Files:**
- Create: `database/migrations/2026_05_26_create_vehicle_status_table.sql`

**Step 1: Write migration SQL**

```sql
-- Migration: 2026_05_26_create_vehicle_status_table.sql
-- Creates status table to track vehicle active/inactive status across types

CREATE TABLE IF NOT EXISTS `vehicle_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` int(11) NOT NULL,
  `vehicle_type` enum('visitor','staff','student','contractor') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `status_changed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status_changed_by` int(11),
  `auto_inactive_date` date,
  `reactivated_at` timestamp NULL,
  
  -- Prevent duplicates
  UNIQUE KEY `unique_vehicle_status` (`vehicle_id`, `vehicle_type`),
  
  -- Indexes
  KEY `idx_vehicle_type` (`vehicle_type`, `status`),
  KEY `idx_auto_inactive_date` (`auto_inactive_date`),
  
  -- Foreign keys
  CONSTRAINT `fk_vehicle_status_admin` FOREIGN KEY (`status_changed_by`) 
    REFERENCES `admin`(`adminid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Verify migration file created**

```bash
cd /c/Users/User.J1-ALPHA-PENS/pbsys
test -f database/migrations/2026_05_26_create_vehicle_status_table.sql && echo "✓ Migration file exists"
```

**Step 3: Commit**

```bash
git add database/migrations/2026_05_26_create_vehicle_status_table.sql
git commit -m "db: add vehicle_status table migration for active/inactive tracking"
```

---

### Task 2: Create vehicle_search_cache table

**Objective:** Add search cache for autocomplete performance

**Files:**
- Create: `database/migrations/2026_05_26_create_vehicle_search_cache.sql`

**Step 1: Write migration SQL**

```sql
-- Migration: 2026_05_26_create_vehicle_search_cache.sql
-- Cache table for vehicle search across all types

CREATE TABLE IF NOT EXISTS `vehicle_search_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` int(11) NOT NULL,
  `vehicle_type` enum('visitor','staff','student','contractor') NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `brand` varchar(100),
  `color` varchar(50),
  `phone` varchar(20),
  `staff_number` varchar(20),
  `matric_number` varchar(20),
  `owner_name` varchar(255),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_updated` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Full-text search index
  FULLTEXT INDEX `ft_search` (`plate_number`, `brand`, `owner_name`),
  
  -- Regular indexes
  UNIQUE KEY `unique_cache` (`vehicle_id`, `vehicle_type`),
  KEY `idx_status` (`status`),
  KEY `idx_vehicle_type` (`vehicle_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Verify migration file created**

```bash
test -f database/migrations/2026_05_26_create_vehicle_search_cache.sql && echo "✓ Migration file exists"
```

**Step 3: Commit**

```bash
git add database/migrations/2026_05_26_create_vehicle_search_cache.sql
git commit -m "db: add vehicle_search_cache table for autocomplete performance"
```

---

### Task 3: Add status column to database schema file

**Objective:** Update neovtrack_db.sql to include new tables

**Files:**
- Modify: `database/neovtrack_db.sql` (before closing COMMIT)

**Step 1: Read schema file tail**

```bash
tail -50 database/neovtrack_db.sql | head -20
```

**Step 2: Add vehicle_status table definition before COMMIT**

Search for the last `/*!40000 ALTER TABLE` and add vehicle_status table definition before the final `COMMIT;`

**Step 3: Commit**

```bash
git add database/neovtrack_db.sql
git commit -m "db: add vehicle_status and vehicle_search_cache tables to schema"
```

---

## Phase 2: Frontend Foundation

### Task 4: Update CSS for responsive design

**Objective:** Add media queries and fluid grid support to all pages

**Files:**
- Modify: `assets/css/styles.css`
- Modify: `assets/css/responsive.css` (create if needed)

**Step 1: Add responsive CSS media queries**

```css
/* File: assets/css/responsive.css (create new) */

/* Mobile-first approach */

/* Tablets (768px+) */
@media (min-width: 768px) {
  .container {
    width: 750px;
  }
  
  .grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  
  table {
    font-size: 14px;
  }
}

/* Desktops (1024px+) */
@media (min-width: 1024px) {
  .container {
    width: 960px;
  }
  
  .grid-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
  }
}

/* Large screens (1200px+) */
@media (min-width: 1200px) {
  .container {
    width: 1140px;
  }
}

/* Mobile (< 768px) */
@media (max-width: 767px) {
  body {
    font-size: 14px;
  }
  
  table {
    font-size: 12px;
    overflow-x: auto;
    display: block;
  }
  
  .btn {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
}

/* Flexible typography */
body {
  font-size: clamp(14px, 2vw, 16px);
}

h1 {
  font-size: clamp(24px, 5vw, 32px);
}

h2 {
  font-size: clamp(20px, 4vw, 28px);
}

/* Fluid images */
img {
  max-width: 100%;
  height: auto;
}
```

**Step 2: Update header includes responsive meta tag**

Verify all PHP header files include:
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

**Step 3: Commit**

```bash
git add assets/css/responsive.css
git commit -m "style: add responsive design with media queries and fluid grids"
```

---

### Task 5: Create bulk delete UI component

**Objective:** Add reusable bulk delete button and checkbox selection

**Files:**
- Create: `assets/js/bulk-delete.js`
- Create: `includes/bulk_delete_component.php`

**Step 1: Create JavaScript bulk delete handler**

```javascript
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
    
    // Handle bulk delete
    if (this.form) {
      this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    } else if (this.button) {
      this.button.addEventListener('click', (e) => this.handleClick(e));
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
```

**Step 2: Create PHP component**

```php
<!-- File: includes/bulk_delete_component.php -->

<?php
/**
 * Bulk Delete UI Component
 * Usage in table:
 *   <thead>
 *     <th><input type="checkbox" id="selectAllCheckbox"></th>
 *     ...
 *   </thead>
 * 
 * Usage in form:
 *   <form id="bulkDeleteForm" method="POST">
 *     {{ bulk_delete_button() }}
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
```

**Step 3: Commit**

```bash
git add assets/js/bulk-delete.js includes/bulk_delete_component.php
git commit -m "feat: add bulk delete UI component with checkboxes and JS handler"
```

---

## Phase 3: Backend APIs

### Task 6: Create autocomplete search API

**Objective:** Create cross-page vehicle search API for autocomplete

**Files:**
- Create: `api/vehicle_search_api.php`

**Step 1: Write API**

```php
<?php
/**
 * API: Vehicle Autocomplete Search
 * GET/POST /api/vehicle_search_api.php
 * 
 * Actions:
 *   - search&q=term&type=staff (search vehicles by plate/brand/owner)
 *   - get_by_plate&plate=ABC123&type=staff (get single vehicle)
 *   - get_by_id&id=5&type=staff (get by ID)
 *   - update_cache (admin only - rebuild search cache)
 */

header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$q = $_GET['q'] ?? $_POST['q'] ?? '';
$vehicle_type = $_GET['type'] ?? $_POST['type'] ?? '';
$limit = (int)($_GET['limit'] ?? $_POST['limit'] ?? 20);
$limit = min($limit, 100); // Max 100

if (!$action) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Missing action']));
}

try {
  switch ($action) {
    case 'search':
      search_vehicles($con, $q, $vehicle_type, $limit);
      break;
    
    case 'get_by_plate':
      get_vehicle_by_plate($con, $_GET['plate'] ?? $_POST['plate'] ?? '', $vehicle_type);
      break;
    
    case 'get_by_id':
      get_vehicle_by_id($con, (int)($_GET['id'] ?? $_POST['id'] ?? 0), $vehicle_type);
      break;
    
    case 'update_cache':
      if (!isset($_SESSION['email_Admin'])) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Admin only']));
      }
      update_search_cache($con);
      break;
    
    default:
      http_response_code(400);
      die(json_encode(['success' => false, 'message' => 'Invalid action']));
  }
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function search_vehicles($con, $q, $vehicle_type, $limit) {
  if (strlen($q) < 2) {
    die(json_encode(['success' => true, 'data' => []]));
  }
  
  $q = $con->real_escape_string($q);
  $vehicle_type = $con->real_escape_string($vehicle_type);
  
  // Search across cache table
  $where = "status = 'active'";
  if ($vehicle_type) {
    $where .= " AND vehicle_type = '$vehicle_type'";
  }
  
  $query = "
    SELECT vehicle_id, vehicle_type, plate_number, brand, color, owner_name, phone
    FROM vehicle_search_cache
    WHERE $where AND (
      plate_number LIKE '%$q%' OR 
      brand LIKE '%$q%' OR 
      owner_name LIKE '%$q%' OR 
      phone LIKE '%$q%'
    )
    LIMIT $limit
  ";
  
  $result = $con->query($query);
  
  $data = [];
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      'id' => $row['vehicle_id'],
      'type' => $row['vehicle_type'],
      'label' => $row['plate_number'] . ' - ' . $row['brand'] . ' (' . $row['owner_name'] . ')',
      'plate' => $row['plate_number'],
      'brand' => $row['brand'],
      'color' => $row['color'],
      'owner' => $row['owner_name'],
      'phone' => $row['phone']
    ];
  }
  
  echo json_encode(['success' => true, 'data' => $data]);
}

function get_vehicle_by_plate($con, $plate, $vehicle_type) {
  $plate = $con->real_escape_string($plate);
  $vehicle_type = $con->real_escape_string($vehicle_type);
  
  $query = "
    SELECT vehicle_id, vehicle_type, plate_number, brand, color, owner_name, phone
    FROM vehicle_search_cache
    WHERE plate_number = '$plate' AND vehicle_type = '$vehicle_type'
  ";
  
  $result = $con->query($query);
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Vehicle not found']));
  }
  
  $row = $result->fetch_assoc();
  
  // Get assigned users via M:M
  $users = get_vehicle_users($con, $row['vehicle_id'], $row['vehicle_type']);
  
  echo json_encode([
    'success' => true,
    'data' => [
      'id' => $row['vehicle_id'],
      'type' => $row['vehicle_type'],
      'plate' => $row['plate_number'],
      'brand' => $row['brand'],
      'color' => $row['color'],
      'owner' => $row['owner_name'],
      'phone' => $row['phone'],
      'users' => $users
    ]
  ]);
}

function get_vehicle_by_id($con, $id, $vehicle_type) {
  $id = (int)$id;
  $vehicle_type = $con->real_escape_string($vehicle_type);
  
  $query = "
    SELECT vehicle_id, vehicle_type, plate_number, brand, color, owner_name, phone
    FROM vehicle_search_cache
    WHERE vehicle_id = $id AND vehicle_type = '$vehicle_type'
  ";
  
  $result = $con->query($query);
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Vehicle not found']));
  }
  
  $row = $result->fetch_assoc();
  
  // Get assigned users
  $users = get_vehicle_users($con, $row['vehicle_id'], $row['vehicle_type']);
  
  echo json_encode([
    'success' => true,
    'data' => [
      'id' => $row['vehicle_id'],
      'type' => $row['vehicle_type'],
      'plate' => $row['plate_number'],
      'brand' => $row['brand'],
      'color' => $row['color'],
      'owner' => $row['owner_name'],
      'phone' => $row['phone'],
      'users' => $users
    ]
  ]);
}

function update_search_cache($con) {
  // Clear existing cache
  $con->query("TRUNCATE TABLE vehicle_search_cache");
  
  $types = ['visitor', 'staff', 'student', 'contractor'];
  $total = 0;
  
  foreach ($types as $type) {
    $table = $type . 'car';
    $id_col = $type === 'visitor' ? 'visitorid' : ($type === 'staff' ? 'staffid' : ($type === 'student' ? 'studentid' : 'contractorid'));
    $phone_col = $type === 'visitor' ? 'phone' : ($type === 'contractor' ? 'phone' : '');
    $staff_col = $type === 'staff' ? 'staffnumber' : '';
    $matric_col = $type === 'student' ? 'matricnumber' : '';
    
    // Get status for each vehicle
    $query = "
      SELECT 
        v.$id_col as vehicle_id,
        '$type' as vehicle_type,
        v.plate_number,
        v.brand,
        v.color,
        COALESCE(u.name, 'Unknown') as owner_name,
        COALESCE(u.phone, '') as phone,
        COALESCE(vs.status, 'active') as status
      FROM $table v
      LEFT JOIN user u ON v.userid = u.userid
      LEFT JOIN vehicle_status vs ON vs.vehicle_id = v.$id_col AND vs.vehicle_type = '$type'
    ";
    
    $result = $con->query($query);
    
    while ($row = $result->fetch_assoc()) {
      $ins_query = "
        INSERT INTO vehicle_search_cache 
        (vehicle_id, vehicle_type, plate_number, brand, color, phone, staff_number, matric_number, owner_name, status)
        VALUES (
          {$row['vehicle_id']},
          '{$row['vehicle_type']}',
          '{$con->real_escape_string($row['plate_number'])}',
          '{$con->real_escape_string($row['brand'])}',
          '{$con->real_escape_string($row['color'])}',
          '{$con->real_escape_string($row['phone'])}',
          '',
          '',
          '{$con->real_escape_string($row['owner_name'])}',
          '{$row['status']}'
        )
      ";
      
      if ($con->query($ins_query)) {
        $total++;
      }
    }
  }
  
  echo json_encode(['success' => true, 'message' => "Cache updated: $total vehicles"]);
}
?>
```

**Step 2: Commit**

```bash
git add api/vehicle_search_api.php
git commit -m "api: add vehicle search and autocomplete API"
```

---

### Task 7: Create bulk delete API

**Objective:** Backend endpoint for bulk delete operations

**Files:**
- Create: `api/bulk_delete_api.php`

**Step 1: Write API**

```php
<?php
/**
 * API: Bulk Delete Vehicles
 * POST /api/bulk_delete_api.php
 * 
 * Parameters:
 *   - action=bulk_delete (required)
 *   - vehicle_type=staff|visitor|student|contractor (required)
 *   - ids[]=1,2,3 (required - array of vehicle IDs)
 */

header('Content-Type: application/json');

session_start();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

// Require admin session
if (!isset($_SESSION['email_Admin'])) {
  http_response_code(403);
  die(json_encode(['success' => false, 'message' => 'Admin access required']));
}

$action = $_POST['action'] ?? null;
$vehicle_type = $_POST['vehicle_type'] ?? null;
$ids = $_POST['ids'] ?? [];

if ($action !== 'bulk_delete') {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Invalid action']));
}

if (!$vehicle_type || !in_array($vehicle_type, ['visitor', 'staff', 'student', 'contractor'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Invalid vehicle type']));
}

if (!is_array($ids) || empty($ids)) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'No IDs provided']));
}

try {
  bulk_delete_vehicles($con, $vehicle_type, $ids, $_SESSION['email_Admin']);
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function bulk_delete_vehicles($con, $vehicle_type, $ids, $admin_email) {
  $table = $vehicle_type . 'car';
  $id_col = $vehicle_type === 'visitor' ? 'visitorid' : 
            ($vehicle_type === 'staff' ? 'staffid' : 
             ($vehicle_type === 'student' ? 'studentid' : 'contractorid'));
  
  // Sanitize IDs
  $safe_ids = array_map(function($id) {
    return (int)$id;
  }, $ids);
  
  // Begin transaction
  $con->begin_transaction();
  
  try {
    $id_list = implode(',', $safe_ids);
    
    // Delete from M:M table first (foreign key)
    $con->query("
      DELETE FROM user_vehicle 
      WHERE vehicle_id IN ($id_list) 
        AND vehicle_type = '{$vehicle_type}'
    ");
    
    // Delete from vehicle table
    $delete_query = "DELETE FROM $table WHERE $id_col IN ($id_list)";
    $con->query($delete_query);
    
    if ($con->affected_rows === 0) {
      throw new Exception('No vehicles deleted');
    }
    
    $count = $con->affected_rows;
    
    // Delete from status table
    $con->query("
      DELETE FROM vehicle_status 
      WHERE vehicle_id IN ($id_list) 
        AND vehicle_type = '$vehicle_type'
    ");
    
    // Delete from cache
    $con->query("
      DELETE FROM vehicle_search_cache 
      WHERE vehicle_id IN ($id_list) 
        AND vehicle_type = '$vehicle_type'
    ");
    
    $con->commit();
    
    // Log action
    $admin_query = "SELECT adminid FROM admin WHERE email = '{$con->real_escape_string($admin_email)}' LIMIT 1";
    $admin_result = $con->query($admin_query);
    if ($admin_result && $admin_result->num_rows > 0) {
      $admin = $admin_result->fetch_assoc();
      $con->query("
        INSERT INTO admin_action_logs (admin_id, action, details, created_at)
        VALUES ({$admin['adminid']}, 'bulk_delete', 'Deleted $count $vehicle_type vehicles', NOW())
      ");
    }
    
    echo json_encode([
      'success' => true,
      'message' => "Deleted $count vehicle(s)",
      'count' => $count
    ]);
    
  } catch (Exception $e) {
    $con->rollback();
    throw $e;
  }
}
?>
```

**Step 2: Commit**

```bash
git add api/bulk_delete_api.php
git commit -m "api: add bulk delete endpoint for vehicles"
```

---

### Task 8: Create XLSX import API

**Objective:** Replace CSV with XLSX import supporting new schema

**Files:**
- Create: `api/bulk_import_xlsx_api.php`

**Step 1: Write API**

```php
<?php
/**
 * API: Bulk Import Vehicles from XLSX
 * POST /api/bulk_import_xlsx_api.php
 * 
 * Expects:
 *   - file: XLSX file upload
 *   - vehicle_type: staff|visitor|student|contractor
 *   - assume_owner: email of user to assign as owner
 */

header('Content-Type: application/json');

session_start();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';

// Require admin
if (!isset($_SESSION['email_Admin'])) {
  http_response_code(403);
  die(json_encode(['success' => false, 'message' => 'Admin access required']));
}

$vehicle_type = $_POST['vehicle_type'] ?? null;
$assume_owner = $_POST['assume_owner'] ?? null;

if (!$vehicle_type || !in_array($vehicle_type, ['visitor', 'staff', 'student', 'contractor'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'Invalid vehicle type']));
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'File upload failed']));
}

// Verify XLSX file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['file']['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])) {
  http_response_code(400);
  die(json_encode(['success' => false, 'message' => 'File must be XLSX']));
}

try {
  // Require PhpSpreadsheet
  require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
  
  $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
  $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
  $worksheet = $spreadsheet->getActiveSheet();
  
  $result = import_vehicles_from_xlsx($con, $vehicle_type, $worksheet, $assume_owner, $_SESSION['email_Admin']);
  
  echo json_encode($result);
  
} catch (Exception $e) {
  http_response_code(500);
  die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}

function import_vehicles_from_xlsx($con, $vehicle_type, $worksheet, $assume_owner, $admin_email) {
  $inserted = 0;
  $skipped = 0;
  $errors = [];
  
  $table = $vehicle_type . 'car';
  $id_col = $vehicle_type === 'visitor' ? 'visitorid' : 
            ($vehicle_type === 'staff' ? 'staffid' : 
             ($vehicle_type === 'student' ? 'studentid' : 'contractorid'));
  
  // Row 1 is header, start from row 2
  foreach ($worksheet->getRowIterator(2) as $row) {
    $cells = $row->getCellIterator();
    $cells->setIterateOnlyExistingCells(false);
    
    $data = [];
    $col_index = 0;
    foreach ($cells as $cell) {
      $data[$col_index++] = $cell->getValue();
    }
    
    // Skip empty rows
    if (empty($data[0])) continue;
    
    try {
      // Parse row based on vehicle type
      $vehicle = parse_vehicle_row($data, $vehicle_type);
      
      // Check uniqueness: plate + status=active
      $check_query = "
        SELECT $id_col FROM $table 
        WHERE plate_number = '{$vehicle['plate_number']}' 
        LIMIT 1
      ";
      $check = $con->query($check_query);
      
      if ($check && $check->num_rows > 0) {
        $skipped++;
        $errors[] = "Row " . ($row->getRowIndex()) . ": Plate {$vehicle['plate_number']} already exists";
        continue;
      }
      
      // Insert vehicle
      $columns = implode(',', array_keys($vehicle));
      $values = implode(',', array_map(function($v) use ($con) {
        return "'{$con->real_escape_string($v)}'";
      }, array_values($vehicle)));
      
      $insert_query = "INSERT INTO $table ($columns) VALUES ($values)";
      
      if (!$con->query($insert_query)) {
        throw new Exception($con->error);
      }
      
      $vehicle_id = $con->insert_id;
      
      // Assign owner if provided
      if ($assume_owner) {
        $owner_query = "SELECT userid FROM user WHERE email = '{$con->real_escape_string($assume_owner)}' LIMIT 1";
        $owner_result = $con->query($owner_query);
        
        if ($owner_result && $owner_result->num_rows > 0) {
          $owner = $owner_result->fetch_assoc();
          assign_user_to_vehicle($con, $owner['userid'], $vehicle_id, $vehicle_type, 'owner', 0);
        }
      }
      
      // Create status record
      $con->query("
        INSERT INTO vehicle_status (vehicle_id, vehicle_type, status)
        VALUES ($vehicle_id, '$vehicle_type', 'active')
      ");
      
      $inserted++;
      
    } catch (Exception $e) {
      $skipped++;
      $errors[] = "Row " . ($row->getRowIndex()) . ": " . $e->getMessage();
    }
  }
  
  return [
    'success' => true,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'errors' => array_slice($errors, 0, 10) // Return first 10 errors
  ];
}

function parse_vehicle_row($data, $vehicle_type) {
  // Expected columns: plate, brand, color, year, [owner_name], [owner_phone]
  return [
    'plate_number' => $data[0] ?? '',
    'brand' => $data[1] ?? '',
    'color' => $data[2] ?? '',
    'year' => $data[3] ?? date('Y')
  ];
}
?>
```

**Step 2: Commit**

```bash
git add api/bulk_import_xlsx_api.php
git commit -m "api: add XLSX bulk import endpoint (replaces CSV)"
```

---

## Phase 4: Integration

### Task 9: Update vehicle list page with bulk delete

**Objective:** Add multi-select checkboxes and bulk delete to staff vehicle list

**Files:**
- Modify: `vehicles/staff/list.php`

**Step 1: Add form wrapper**

Wrap the table in a form and add bulk delete button before it.

**Step 2: Update table header**

```php
<thead>
  <tr>
    <?php echo bulk_delete_checkbox_header(); ?>
    <th>Plate Number</th>
    <th>Brand</th>
    <th>Color</th>
    <th>Owner</th>
    <th>Actions</th>
  </tr>
</thead>
```

**Step 3: Update table rows**

Replace delete link with checkbox:

```php
<tbody>
  <?php while ($row = $result->fetch_assoc()): ?>
  <tr>
    <?php echo bulk_delete_checkbox($row['staffid']); ?>
    <td><?php echo htmlspecialchars($row['plate_number']); ?></td>
    <td><?php echo htmlspecialchars($row['brand']); ?></td>
    <td><?php echo htmlspecialchars($row['color']); ?></td>
    <td><?php echo htmlspecialchars($row['owner_name'] ?? 'N/A'); ?></td>
    <td>
      <a href="/vehicles/staff/view.php?id=<?php echo $row['staffid']; ?>" class="btn btn-sm">View</a>
      <a href="/vehicles/staff/update.php?id=<?php echo $row['staffid']; ?>" class="btn btn-sm">Edit</a>
    </td>
  </tr>
  <?php endwhile; ?>
</tbody>
```

**Step 4: Add bulk delete button**

```php
<?php echo bulk_delete_button([
  'endpoint' => '/api/bulk_delete_api.php',
  'confirm_message' => 'Delete selected staff vehicles? This cannot be undone.'
]); ?>
```

**Step 5: Commit**

```bash
git add vehicles/staff/list.php
git commit -m "feat: add bulk delete to staff vehicle list"
```

---

### Task 10: Add responsive CSS to all pages

**Objective:** Link responsive.css in all PHP header includes

**Files:**
- Modify: `includes/header.php`

**Step 1: Add responsive CSS link**

```html
<link rel="stylesheet" href="/assets/css/responsive.css">
```

**Step 2: Commit**

```bash
git add includes/header.php
git commit -m "style: link responsive CSS in page headers"
```

---

## Phase 5: Permissions & Security

### Task 11: Implement SMTP-only password reset

**Objective:** Remove local password reset, force SMTP

**Files:**
- Create: `auth/forgot_password_smtp.php`
- Modify: `auth/login.php`

**Step 1: Create SMTP forgot password page**

```php
<?php
/**
 * Forgot Password: SMTP Reset Link
 * /auth/forgot_password_smtp.php
 */

session_start();

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  
  if (empty($email)) {
    $error = 'Email is required';
  } else {
    // Check if user exists
    $check_query = "SELECT userid, name, email FROM user WHERE email = '{$con->real_escape_string($email)}' LIMIT 1";
    $check = $con->query($check_query);
    
    if (!$check || $check->num_rows === 0) {
      // Don't reveal if email exists (security)
      $success = 'If an account exists, a reset link has been sent to ' . htmlspecialchars($email);
    } else {
      $user = $check->fetch_assoc();
      
      // Generate token
      $token = bin2hex(random_bytes(32));
      $token_hash = hash('sha256', $token);
      $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
      
      // Store token hash
      $con->query("
        INSERT INTO password_reset_tokens (user_id, token_hash, email, expires_at)
        VALUES ({$user['userid']}, '$token_hash', '{$user['email']}', '$expires')
        ON DUPLICATE KEY UPDATE token_hash = '$token_hash', expires_at = '$expires'
      ");
      
      // Send email
      $reset_link = "https://neovtrack.uitm.edu.my/auth/reset_password.php?token=" . urlencode($token);
      
      // Use PHPMailer
      require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/PHPMailer.php';
      require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/SMTP.php';
      require $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/PHPMailer/src/Exception.php';
      
      $mail = new \PHPMailer\PHPMailer\PHPMailer();
      $mail->IsSMTP();
      $mail->Host = 'mail.uitm.edu.my'; // Configure SMTP host
      $mail->SMTPAuth = true;
      $mail->Username = 'noreply@uitm.edu.my';
      $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
      $mail->SMTPSecure = 'tls';
      $mail->Port = 587;
      
      $mail->From = 'noreply@uitm.edu.my';
      $mail->FromName = 'NEO V-TRACK';
      $mail->addAddress($user['email'], $user['name']);
      
      $mail->Subject = 'Reset Your NEO V-TRACK Password';
      $mail->Body = "
Hello {$user['name']},

Click the link below to reset your password:
$reset_link

This link expires in 1 hour.

If you did not request this, ignore this email.

-- NEO V-TRACK
";
      
      if ($mail->send()) {
        $success = 'Reset link sent to ' . htmlspecialchars($email);
      } else {
        $error = 'Failed to send email. Please contact admin.';
      }
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password - NEO V-TRACK</title>
  <link rel="stylesheet" href="/assets/css/styles.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
  <div class="container">
    <h1>Forgot Password</h1>
    
    <?php if (isset($success)): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif (isset($error)): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required>
      </div>
      <button type="submit" class="btn btn-primary">Send Reset Link</button>
      <a href="/auth/login.php" class="btn btn-secondary">Back to Login</a>
    </form>
  </div>
</body>
</html>
```

**Step 2: Commit**

```bash
git add auth/forgot_password_smtp.php
git commit -m "feat: implement SMTP-only password reset"
```

---

### Task 12: Add permission checks to pages

**Objective:** Restrict user access to certain admin pages

**Files:**
- Create: `includes/permission_check.php`
- Modify: `admin/users.php`
- Modify: `admin/admin.php`
- Modify: `admin/reports.php`
- Modify: `admin/bulk_import.php`

**Step 1: Create permission helper**

```php
<?php
// File: includes/permission_check.php

function require_admin() {
  if (!isset($_SESSION['email_Admin'])) {
    http_response_code(403);
    die('Access denied. Admin access required.');
  }
}

function is_admin() {
  return isset($_SESSION['email_Admin']);
}

function is_user() {
  return isset($_SESSION['email']) && !isset($_SESSION['email_Admin']);
}

function can_view_admin_list() {
  return is_admin();
}

function can_view_user_list() {
  return is_admin();
}

function can_view_reports() {
  return is_admin();
}

function can_view_import() {
  return is_admin();
}

function redirect_unauthorized() {
  header('Location: /dashboard.php');
  exit;
}
?>
```

**Step 2: Update admin pages**

Add to top of `admin/users.php`, `admin/admin.php`, `admin/reports.php`, `admin/bulk_import.php`:

```php
<?php
include $_SERVER['DOCUMENT_ROOT'] . '/includes/permission_check.php';
require_admin();

if (!can_view_user_list()) {
  redirect_unauthorized();
}
?>
```

**Step 3: Update navigation to hide restricted links for users**

**Step 4: Commit**

```bash
git add includes/permission_check.php admin/users.php admin/admin.php admin/reports.php admin/bulk_import.php
git commit -m "feat: add permission-based access control for admin pages"
```

---

## Deployment Steps

### Pre-deployment Checklist

- [ ] All migrations created in `database/migrations/`
- [ ] Run migrations against staging database
- [ ] Test each Phase locally
- [ ] All files committed to git
- [ ] Code review completed

### Deployment

1. **Sync to Hestia:**
   ```bash
   git push origin main
   git workflow run deploy-to-hestia.yml
   ```

2. **Run migrations:**
   ```bash
   mysql -u neovtrack_app -p neovtrack_db < database/migrations/2026_05_26_create_vehicle_status_table.sql
   mysql -u neovtrack_app -p neovtrack_db < database/migrations/2026_05_26_create_vehicle_search_cache.sql
   ```

3. **Rebuild search cache:**
   ```bash
   curl -X POST https://neovtrack.uitm.edu.my/api/vehicle_search_api.php \
     -H "Cookie: email_Admin=..." \
     -d "action=update_cache"
   ```

4. **Verify pages:**
   - [ ] Vehicle list shows bulk delete
   - [ ] Autocomplete search works
   - [ ] User can't access admin/users
   - [ ] Admin can access all pages
   - [ ] Password reset sends email

---

## Testing

Each phase should be tested locally:

```bash
# Phase 1: Database
mysql -u root neovtrack_db < database/migrations/2026_05_26_create_vehicle_status_table.sql
mysql -u root neovtrack_db < database/migrations/2026_05_26_create_vehicle_search_cache.sql

# Phase 2: Frontend
# Open http://localhost:8000/vehicles/staff/list.php
# Test: checkboxes appear, delete button toggles disabled state

# Phase 3: APIs
curl http://localhost:8000/api/vehicle_search_api.php?action=search&q=plate&type=staff
curl -X POST http://localhost:8000/api/bulk_import_xlsx_api.php \
  -F "file=@test.xlsx" \
  -F "vehicle_type=staff"

# Phase 4: Integration
# Test all vehicle pages have bulk delete

# Phase 5: Permissions
# Login as user, verify can't access /admin/users.php
# Login as admin, verify can access all pages
```

---

## Git Commit Summary

```
db: add vehicle_status table migration for active/inactive tracking
db: add vehicle_search_cache table for autocomplete performance
db: add vehicle_status and vehicle_search_cache tables to schema
style: add responsive design with media queries and fluid grids
feat: add bulk delete UI component with checkboxes and JS handler
api: add vehicle search and autocomplete API
api: add bulk delete endpoint for vehicles
api: add XLSX bulk import endpoint (replaces CSV)
feat: add bulk delete to staff vehicle list
style: link responsive CSS in page headers
feat: implement SMTP-only password reset
feat: add permission-based access control for admin pages
```

---

