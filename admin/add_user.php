<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
require $_SERVER['DOCUMENT_ROOT'].'/includes/lang_switch.php';

if (isset($_POST['submit'])) {
    $email    = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $name     = mysqli_real_escape_string($con, $_POST['name']);

    $sql = "INSERT INTO `user` (`email`, `password`, `name`) VALUES ('$email','$password','$name')";
    if (mysqli_query($con, $sql)) {
        header('location:/admin/users.php');
        exit();
    } else {
        $add_error = mysqli_error($con);
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>
<body>
<div class="nv-shell">
<?php $nv_active = 'users'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/nv_chrome.php'; ?>
<main class="page">
    <div class="page-head">
        <div>
            <span class="eyebrow">Pengguna</span>
            <h1>Add user</h1>
            <p class="sub">Create a new portal account.</p>
        </div>
    </div>
    <?php if (!empty($add_error)): ?><div class="flash bad"><?= htmlspecialchars($add_error) ?></div><?php endif; ?>
    <form class="card nv-stack gap-6" method="POST">
        <div class="nv-grid cols-2">
            <div class="field"><label class="field-label" for="email">Email</label>
                <input class="input" id="email" name="email" type="email" required placeholder="name@uitm.edu.my"></div>
            <div class="field"><label class="field-label" for="password">Password</label>
                <input class="input" id="password" name="password" type="password" required></div>
            <div class="field" style="grid-column:1 / -1;"><label class="field-label" for="name">Full name</label>
                <input class="input" id="name" name="name" type="text" required></div>
        </div>
        <div class="nv-row end gap-2">
            <a class="btn btn-ghost" href="/admin/users.php"><i data-lucide="arrow-left"></i> Cancel</a>
            <button class="btn btn-primary" type="submit" name="submit"><i data-lucide="check"></i> Save</button>
        </div>
    </form>
</main>
</div>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>
