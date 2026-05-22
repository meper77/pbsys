<?php
/**
 * Vehicle Registration: Autocomplete user selection with add-new support
 * URL: /vehicles/daftar_kenderaan.php
 * Route: /register_vehicle.php (legacy, via .htaccess)
 */

session_start();

if (empty($_SESSION['email_Admin'])) {
    header('Location: /auth/login_admin.php');
    exit;
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/includes/user_vehicle_helper.php';

// Language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'bm';
}

$lang = $_SESSION['language'];
$t = $lang === 'bm' ? [
    'title'           => 'Daftar Kenderaan',
    'user_details'    => 'Butiran Pengguna',
    'search_user'     => 'Cari pengguna',
    'user_name'       => 'Nama pengguna',
    'user_id'         => 'ID pengguna',
    'email'           => 'Emel',
    'phone'           => 'Telefon',
    'idnumber'        => 'Nombor ID',
    'or_new_user'     => 'atau tambah pengguna baru',
    'vehicle_details' => 'Butiran Kenderaan',
    'vehicle_type'    => 'Jenis kenderaan',
    'plate_number'    => 'Nombor plat',
    'brand'           => 'Jenama',
    'color'           => 'Warna',
    'year'            => 'Tahun',
    'status'          => 'Status',
    'role'            => 'Peranan',
    'register'        => 'Daftar',
    'new_user_form'   => 'Borang Pengguna Baru',
    'existing_user'   => 'Pengguna Sedia Ada',
    'cancel'          => 'Batal',
    'success'         => 'Kenderaan berjaya didaftarkan',
    'error'           => 'Ralat: ',
] : [
    'title'           => 'Vehicle Registration',
    'user_details'    => 'User Details',
    'search_user'     => 'Search user',
    'user_name'       => 'User name',
    'user_id'         => 'User ID',
    'email'           => 'Email',
    'phone'           => 'Phone',
    'idnumber'        => 'ID Number',
    'or_new_user'     => 'or add new user',
    'vehicle_details' => 'Vehicle Details',
    'vehicle_type'    => 'Vehicle Type',
    'plate_number'    => 'Plate Number',
    'brand'           => 'Brand',
    'color'           => 'Color',
    'year'            => 'Year',
    'status'          => 'Status',
    'role'            => 'Role',
    'register'        => 'Register',
    'new_user_form'   => 'New User Form',
    'existing_user'   => 'Existing User',
    'cancel'          => 'Cancel',
    'success'         => 'Vehicle registered successfully',
    'error'           => 'Error: ',
];

$result_message = null;
$result_type = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $vehicle_type = $_POST['vehicle_type'] ?? '';
        $plate_number = trim($_POST['plate_number'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $year = (int)($_POST['year'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $role = $_POST['role'] ?? 'owner';

        // Validate required fields
        if (!$user_id || !$vehicle_type || !$plate_number) {
            throw new Exception('Missing required fields');
        }

        // Insert into appropriate vehicle table based on type
        $table = match($vehicle_type) {
            'visitor' => 'visitorcar',
            'staff' => 'staffcar',
            'student' => 'studentcar',
            'contractor' => 'contractorcar',
            default => null
        };

        if (!$table) {
            throw new Exception('Invalid vehicle type');
        }

        // Get table structure to determine correct column names
        $columns_result = $con->query("SHOW COLUMNS FROM `$table`");
        $columns = [];
        while ($col = $columns_result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }

        // Build insert based on available columns
        $insert_fields = ['platenum'];
        $insert_values = [$plate_number];

        if (in_array('brand', $columns)) {
            $insert_fields[] = 'brand';
            $insert_values[] = $brand;
        }
        if (in_array('color', $columns)) {
            $insert_fields[] = 'color';
            $insert_values[] = $color;
        }
        if (in_array('year', $columns)) {
            $insert_fields[] = 'year';
            $insert_values[] = $year;
        }
        if (in_array('status', $columns)) {
            $insert_fields[] = 'status';
            $insert_values[] = $status;
        }

        // Build parameterized query
        $placeholders = implode(',', array_fill(0, count($insert_fields), '?'));
        $fields_str = '`' . implode('`,`', $insert_fields) . '`';
        $types = str_repeat('s', count($insert_values));
        $types = preg_replace('/(?<=^.{1})s/', 'i', $types, 1); // First s to i for year
        if (in_array('year', $insert_fields)) {
            $year_pos = array_search('year', $insert_fields);
            $types[$year_pos] = 'i';
        }

        $query = "INSERT INTO `$table` ($fields_str) VALUES ($placeholders)";
        $stmt = $con->prepare($query);

        if (!$stmt) {
            throw new Exception('Database error: ' . $con->error);
        }

        $stmt->bind_param($types, ...$insert_values);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert vehicle: ' . $stmt->error);
        }

        $vehicle_id = $stmt->insert_id;

        // Assign user to vehicle
        if (!assign_user_to_vehicle($con, $user_id, $vehicle_id, $vehicle_type, $role, 
            (int)($_SESSION['admin_id'] ?? 0))) {
            throw new Exception('Vehicle registered but user assignment failed');
        }

        $result_message = $t['success'];
        $result_type = 'success';

        // Clear form
        $_POST = [];

    } catch (Exception $e) {
        $result_message = $t['error'] . htmlspecialchars($e->getMessage());
        $result_type = 'error';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($t['title']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/neo-vtrack-tokens.css">
    <link rel="stylesheet" href="/assets/css/neo-vtrack-components.css">
    <style>
        body { background-color: var(--neutral-50); }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .form-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--neutral-900); }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid var(--neutral-300); 
            border-radius: 6px; 
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--brand-blue);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }
        .autocomplete-wrapper { position: relative; }
        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--neutral-300);
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .autocomplete-list.show { display: block; }
        .autocomplete-item {
            padding: 12px;
            border-bottom: 1px solid var(--neutral-200);
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .autocomplete-item:hover {
            background-color: var(--neutral-100);
        }
        .autocomplete-item.selected {
            background-color: var(--brand-blue);
            color: white;
        }
        .autocomplete-item-name { font-weight: 600; }
        .autocomplete-item-email { font-size: 12px; color: var(--neutral-600); }
        .autocomplete-item.selected .autocomplete-item-email { color: rgba(255,255,255,0.7); }
        .input-hint { font-size: 12px; color: var(--neutral-600); margin-top: 4px; }
        .section-title { font-size: 16px; font-weight: 700; color: var(--neutral-900); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--neutral-200); }
        .alert { padding: 16px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background-color: var(--success-50); color: var(--success-900); border: 1px solid var(--success-300); }
        .alert-error { background-color: var(--danger-50); color: var(--danger-900); border: 1px solid var(--danger-300); }
        .user-selection { 
            padding: 15px; 
            background-color: var(--neutral-50); 
            border-radius: 6px; 
            margin-top: 15px;
            border-left: 4px solid var(--brand-blue);
        }
        .user-selection-content { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 14px; }
        .user-selection-label { color: var(--neutral-600); font-weight: 600; }
        .user-selection-value { color: var(--neutral-900); }
        .btn-group { display: flex; gap: 10px; margin-top: 30px; }
        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: var(--brand-blue);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--brand-blue-hover);
        }
        .btn-secondary {
            background-color: var(--neutral-200);
            color: var(--neutral-900);
        }
        .btn-secondary:hover {
            background-color: var(--neutral-300);
        }
        .form-hint { font-size: 12px; color: var(--neutral-600); }
        @media (max-width: 600px) {
            .container { padding: 15px; margin: 20px auto; }
            .form-card { padding: 20px; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    
    <div class="container">
        <div class="form-card">
            <?php if ($result_message): ?>
                <div class="alert alert-<?php echo $result_type; ?>">
                    <?php echo htmlspecialchars($result_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registrationForm">
                <!-- USER DETAILS SECTION -->
                <div style="margin-bottom: 40px;">
                    <div class="section-title"><?php echo htmlspecialchars($t['user_details']); ?></div>
                    
                    <div class="form-group">
                        <label for="search_user"><?php echo htmlspecialchars($t['search_user']); ?></label>
                        <div class="autocomplete-wrapper">
                            <input 
                                type="text" 
                                id="search_user" 
                                placeholder="<?php echo htmlspecialchars($t['user_name']); ?>"
                                autocomplete="off"
                            >
                            <div class="autocomplete-list" id="userList"></div>
                        </div>
                        <div class="input-hint"><?php echo htmlspecialchars($t['or_new_user']); ?></div>
                    </div>

                    <!-- Hidden field for selected user -->
                    <input type="hidden" id="user_id" name="user_id" value="">

                    <!-- Selected User Display -->
                    <div id="userSelection" class="user-selection" style="display: none;">
                        <div class="user-selection-content">
                            <div>
                                <div class="user-selection-label"><?php echo htmlspecialchars($t['user_name']); ?></div>
                                <div class="user-selection-value" id="displayName"></div>
                            </div>
                            <div>
                                <div class="user-selection-label"><?php echo htmlspecialchars($t['email']); ?></div>
                                <div class="user-selection-value" id="displayEmail"></div>
                            </div>
                            <div>
                                <div class="user-selection-label"><?php echo htmlspecialchars($t['phone']); ?></div>
                                <div class="user-selection-value" id="displayPhone"></div>
                            </div>
                            <div>
                                <div class="user-selection-label"><?php echo htmlspecialchars($t['idnumber']); ?></div>
                                <div class="user-selection-value" id="displayIdNumber"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VEHICLE DETAILS SECTION -->
                <div style="margin-bottom: 40px;">
                    <div class="section-title"><?php echo htmlspecialchars($t['vehicle_details']); ?></div>
                    
                    <div class="form-group">
                        <label for="vehicle_type"><?php echo htmlspecialchars($t['vehicle_type']); ?></label>
                        <select id="vehicle_type" name="vehicle_type" required>
                            <option value="">-- <?php echo htmlspecialchars($t['vehicle_type']); ?> --</option>
                            <option value="visitor">Visitor / Pelawat</option>
                            <option value="staff">Staff / Staf</option>
                            <option value="student">Student / Pelajar</option>
                            <option value="contractor">Contractor / Kontraktor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="plate_number"><?php echo htmlspecialchars($t['plate_number']); ?></label>
                        <input 
                            type="text" 
                            id="plate_number" 
                            name="plate_number" 
                            placeholder="e.g., ABC1234"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="brand"><?php echo htmlspecialchars($t['brand']); ?></label>
                        <input 
                            type="text" 
                            id="brand" 
                            name="brand" 
                            placeholder="e.g., Honda Civic"
                        >
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="color"><?php echo htmlspecialchars($t['color']); ?></label>
                            <input 
                                type="text" 
                                id="color" 
                                name="color" 
                                placeholder="e.g., Silver"
                            >
                        </div>

                        <div class="form-group">
                            <label for="year"><?php echo htmlspecialchars($t['year']); ?></label>
                            <input 
                                type="number" 
                                id="year" 
                                name="year" 
                                placeholder="e.g., 2023"
                                min="1980"
                                max="<?php echo date('Y') + 1; ?>"
                            >
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="status"><?php echo htmlspecialchars($t['status']); ?></label>
                            <select id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="role"><?php echo htmlspecialchars($t['role']); ?></label>
                            <select id="role" name="role">
                                <option value="owner">Owner</option>
                                <option value="co-owner">Co-owner</option>
                                <option value="driver">Driver</option>
                                <option value="passenger">Passenger</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($t['register']); ?></button>
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()"><?php echo htmlspecialchars($t['cancel']); ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>

    <script>
        const searchInput = document.getElementById('search_user');
        const userList = document.getElementById('userList');
        const userIdInput = document.getElementById('user_id');
        const userSelection = document.getElementById('userSelection');
        const form = document.getElementById('registrationForm');

        let users = [];
        let selectedUserIndex = -1;

        // Fetch users on page load
        async function loadUsers() {
            try {
                const response = await fetch('/api/user_search_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=list_all'
                });
                const data = await response.json();
                users = data.data || [];
            } catch (e) {
                console.error('Failed to load users:', e);
            }
        }

        function filterUsers(query) {
            if (!query.trim()) {
                userList.classList.remove('show');
                return;
            }

            const filtered = users.filter(u => 
                u.name.toLowerCase().includes(query.toLowerCase()) ||
                u.email.toLowerCase().includes(query.toLowerCase())
            );

            if (filtered.length === 0) {
                userList.innerHTML = '<div class="autocomplete-item" style="color: var(--neutral-600);">No users found. Keep typing to register a new one.</div>';
                userList.classList.add('show');
                return;
            }

            userList.innerHTML = filtered.map((user, idx) => `
                <div class="autocomplete-item" data-idx="${idx}" data-userid="${user.userid}">
                    <div class="autocomplete-item-name">${escapeHtml(user.name)}</div>
                    <div class="autocomplete-item-email">${escapeHtml(user.email)}</div>
                </div>
            `).join('');

            userList.classList.add('show');
            selectedUserIndex = -1;

            // Add click handlers
            document.querySelectorAll('.autocomplete-item').forEach(item => {
                item.addEventListener('click', function() {
                    const userid = this.dataset.userid;
                    const user = users.find(u => u.userid == userid);
                    if (user) {
                        selectUser(user);
                    }
                });
            });
        }

        function selectUser(user) {
            searchInput.value = user.name;
            userIdInput.value = user.userid;
            
            document.getElementById('displayName').textContent = escapeHtml(user.name);
            document.getElementById('displayEmail').textContent = escapeHtml(user.email);
            document.getElementById('displayPhone').textContent = escapeHtml(user.phone || '-');
            document.getElementById('displayIdNumber').textContent = escapeHtml(user.idnumber || '-');
            
            userSelection.style.display = 'block';
            userList.classList.remove('show');
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        searchInput.addEventListener('input', function() {
            filterUsers(this.value);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (userList.classList.contains('show')) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedUserIndex++;
                    updateSelection();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedUserIndex--;
                    updateSelection();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const selected = document.querySelector('.autocomplete-item.selected');
                    if (selected && selected.dataset.userid) {
                        const user = users.find(u => u.userid == selected.dataset.userid);
                        if (user) {
                            selectUser(user);
                        }
                    }
                }
            }
        });

        function updateSelection() {
            const items = document.querySelectorAll('.autocomplete-item');
            items.forEach((item, idx) => {
                item.classList.toggle('selected', idx === selectedUserIndex);
            });
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.autocomplete-wrapper')) {
                userList.classList.remove('show');
            }
        });

        // Validate form before submit
        form.addEventListener('submit', function(e) {
            if (!userIdInput.value) {
                e.preventDefault();
                alert('Please select a user first');
            }
        });

        // Load users on page load
        loadUsers();
    </script>
</body>
</html>
