<?php
include('inc/header.php');
include 'connect.php';

session_start();
//if (!isset($_SESSION['email_Admin'])) {
	//header('location:loginAdmin.php');
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
        header('location:user.php');
    } else {
        die(mysqli_error($con));
    }
}

?>

<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="css/dataTables.bootstrap.min.css" />
<?php include('inc/container.php'); ?>
<div class="container">
    <p>
    <?php //include("menus.php"); ?>
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
                        <a class="btn btn-danger" href="login.php">Kembali</a>
                        </form>
                    </div>
                <?php include('inc/footer.php'); ?>
            </div>
        </div>
    </div>
</div>