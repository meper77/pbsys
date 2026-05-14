<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

session_start();
//if (!isset($_SESSION['email_Admin'])) {
	//header('location:/auth/login_admin.php');
//}

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];

    $sql = "insert into `user` (`email`, `password`, `name`)
    values('$email','$password','$name')";

    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "Pengguna berjaya didaftar!";
        header('location:/admin/users.php');
    } else {
        die(mysqli_error($con));
    }
}

?>

<script src="/assets/js/jquery.dataTables.min.js"></script>
<script src="/assets/js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="/assets/css/dataTables.bootstrap.min.css" />
<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/container.php'); ?>
<div class="container">
    <p>
    <?php //include($_SERVER['DOCUMENT_ROOT'].'/includes/menus.php'); ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-default rounded-0 shadow">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-9">
                            <h3 class="card-title">Tambah Pengguna</h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <H5 style="color: black;">Emel : </H5>
                        <input type="text" id="email" name="email" placeholder="Isi emel" class="form-control mb-2">

                        <H5 style="color: black;">Kata Laluan : </H5>
                        <input type="text" id="password" name="password" placeholder="Isi kata laluan" class="form-control mb-2">

                        <H5 style="color: black;">Nama : </H5>
                        <input type="text" id="name" name="name" placeholder="Isi nama penuh" class="form-control mb-2">

                        <br>
                        <button class="btn btn-success" name="submit">Simpan</button>
                        <a class="btn btn-danger" href="/auth/login.php">Kembali</a>
                        </form>
                    </div>
                <?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>
            </div>
        </div>
    </div>
</div>