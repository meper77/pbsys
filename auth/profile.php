<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require $_SERVER['DOCUMENT_ROOT'].'/includes/lang_switch.php';

// ---- Identify the logged-in account ----
if (!empty($_SESSION['email_Admin'])) {
    $table = 'admin';
    $email = $_SESSION['email_Admin'];
    $role  = 'admin';
} elseif (!empty($_SESSION['email'])) {
    $table = 'user';
    $email = $_SESSION['email'];
    $role  = 'user';
} else {
    header('Location: /auth/role_selection.php');
    exit;
}

$messages = [];
$errors   = [];

// ---- POST: save changes ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $name  = trim($_POST['name']  ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $newPw = (string)($_POST['new_password'] ?? '');

    if ($name === '') {
        $errors[] = 'Name cannot be empty.';
    }

    // Optional image upload
    $imagePath = null;
    if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['profile_image'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed (code ' . $f['error'] . ').';
        } else {
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Image must be jpg / png / webp.';
            } elseif ($f['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image must be 5 MB or smaller.';
            } else {
                $dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profiles';
                if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $errors[] = 'Could not create upload directory.';
                } else {
                    // Look up user id for filename
                    $idStmt = $con->prepare("SELECT userid FROM `$table` WHERE email = ? LIMIT 1");
                    $idStmt->bind_param('s', $email);
                    $idStmt->execute();
                    $row = $idStmt->get_result()->fetch_assoc();
                    $uid = $row['userid'] ?? 0;
                    $fileName = sprintf('%s_%d_%s.%s', $role, $uid, date('YmdHis'), $ext);
                    $dest = $dir . '/' . $fileName;
                    if (!is_uploaded_file($f['tmp_name']) || !move_uploaded_file($f['tmp_name'], $dest)) {
                        $errors[] = 'Could not store uploaded image.';
                    } else {
                        $imagePath = '/uploads/profiles/' . $fileName;
                    }
                }
            }
        }
    }

    if (empty($errors)) {
        // Build the UPDATE dynamically (bound params, no string concat).
        $fields = ['name = ?', 'phone = ?'];
        $types  = 'ss';
        $vals   = [$name, $phone];

        if ($imagePath !== null) {
            $fields[] = 'profile_image = ?';
            $types  .= 's';
            $vals[]  = $imagePath;
        }
        if ($newPw !== '') {
            if (strlen($newPw) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } else {
                // System authenticates against plaintext passwords (login compares directly),
                // so store plaintext here too — a bcrypt hash would lock the account out.
                $fields[] = 'password = ?';
                $types  .= 's';
                $vals[]  = $newPw;
            }
        }

        if (empty($errors)) {
            $types .= 's';
            $vals[]  = $email;
            $sql = "UPDATE `$table` SET " . implode(', ', $fields) . " WHERE email = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param($types, ...$vals);
            if ($stmt->execute()) {
                $messages[] = 'Profile updated.';
            } else {
                $errors[] = 'Database error: ' . htmlspecialchars($con->error);
            }
        }
    }
}

// ---- Fetch current profile ----
// SELECT * (not specific columns) so schema drift between deployments — e.g. the prod
// admin/user tables missing last_login / profile_image / updated_at — can't make prepare()
// fail and 500 the page. The view below null-coalesces every optional field.
$stmt = $con->prepare("SELECT * FROM `$table` WHERE email = ? LIMIT 1");
if ($stmt === false) {
    $profile = [];
} else {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc() ?: [];
}

$lang = $_SESSION['language'] ?? 'bm';
$L = $lang === 'bm' ? [
    'title' => 'Profil Saya',
    'role_admin' => 'Administrator',
    'role_user'  => 'Pengguna',
    'name' => 'Nama',
    'email' => 'E-mel',
    'phone' => 'No. Telefon',
    'photo' => 'Foto Profil',
    'change_photo' => 'Tukar Foto',
    'change_password' => 'Tukar Kata Laluan',
    'new_password_ph' => 'Biarkan kosong untuk kekal',
    'last_login' => 'Log Masuk Terakhir',
    'updated_at' => 'Kemaskini Terakhir',
    'save' => 'Simpan',
    'back' => 'Kembali',
    'account' => 'Akaun',
] : [
    'title' => 'My Profile',
    'role_admin' => 'Administrator',
    'role_user'  => 'User',
    'name' => 'Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'photo' => 'Profile Photo',
    'change_photo' => 'Change Photo',
    'change_password' => 'Change Password',
    'new_password_ph' => 'Leave blank to keep current',
    'last_login' => 'Last Login',
    'updated_at' => 'Last Updated',
    'save' => 'Save',
    'back' => 'Back',
    'account' => 'Account',
];

$displayImg = !empty($profile['profile_image'])
    ? htmlspecialchars($profile['profile_image'])
    : 'https://www.gravatar.com/avatar/' . md5(strtolower($email)) . '?d=mp&s=200';
?>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/header.php'; ?>
<body>
<div class="nv-shell">
<?php $nv_active = ''; include $_SERVER['DOCUMENT_ROOT'].'/includes/nv_chrome.php'; ?>
<main class="page">
  <div class="page-head">
    <div>
      <span class="eyebrow"><?= htmlspecialchars($L['account']) ?></span>
      <h1><?= htmlspecialchars($L['title']) ?></h1>
    </div>
    <div class="actions">
      <a class="btn btn-ghost" href="<?= $role === 'admin' ? '/admin/admins.php' : '/search/car_user.php' ?>"><i data-lucide="arrow-left"></i> <?= htmlspecialchars($L['back']) ?></a>
    </div>
  </div>
  <div class="card">

    <?php foreach ($messages as $m): ?>
        <div class="flash ok"><?php echo htmlspecialchars($m); ?></div>
    <?php endforeach; foreach ($errors as $e): ?>
        <div class="flash bad"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <div style="text-align:center;display:flex;flex-direction:column;align-items:center;gap:10px;" class="mb-4">
        <img src="<?php echo $displayImg; ?>" alt="Profile" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:var(--shadow-2);">
        <h3 class="text-display" style="margin:0;"><?php echo htmlspecialchars($profile['name'] ?? ''); ?></h3>
        <span class="pill info"><span class="dot"></span><?php echo htmlspecialchars($role === 'admin' ? $L['role_admin'] : $L['role_user']); ?></span>
        <div class="text-mono text-muted" style="font-size:12px;">
            <?php if (!empty($profile['last_login'])): ?>
                <?php echo htmlspecialchars($L['last_login']); ?>: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($profile['last_login']))); ?>
            <?php endif; ?>
            <?php if (!empty($profile['updated_at'])): ?>
                &middot; <?php echo htmlspecialchars($L['updated_at']); ?>: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($profile['updated_at']))); ?>
            <?php endif; ?>
        </div>
    </div>

  </div>
  <form method="POST" enctype="multipart/form-data" novalidate class="card nv-stack gap-6">
        <input type="hidden" name="action" value="update_profile">

        <div class="field"><label class="field-label"><?php echo htmlspecialchars($L['name']); ?></label>
            <input type="text" class="input" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
        </div>

        <div class="field"><label class="field-label"><?php echo htmlspecialchars($L['email']); ?></label>
            <input type="email" class="input" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" readonly>
        </div>

        <div class="field"><label class="field-label"><?php echo htmlspecialchars($L['phone']); ?></label>
            <input type="text" class="input" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>" placeholder="012-3456789">
        </div>

        <div class="field"><label class="field-label"><?php echo htmlspecialchars($L['change_photo']); ?></label>
            <input type="file" class="input" name="profile_image" accept="image/jpeg,image/png,image/webp">
            <small class="text-muted">JPG / PNG / WEBP, max 5 MB.</small>
        </div>

        <div class="field"><label class="field-label"><?php echo htmlspecialchars($L['change_password']); ?></label>
            <input type="password" class="input" name="new_password" placeholder="<?php echo htmlspecialchars($L['new_password_ph']); ?>" autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
            <i data-lucide="save"></i><?php echo htmlspecialchars($L['save']); ?>
        </button>
  </form>
</main>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
