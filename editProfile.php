<?php
include('inc/header.php');
include 'connect.php';

session_start();
if (!isset($_SESSION['email_Admin'])) {
	header('location:loginAdmin.php');
}


if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];

    $sql = "update `admin` set email_Admin ='$email', password_Admin ='$password', name_Admin='$name' where userid=$adminid";

    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "Pengguna berjaya dikemaskini!";
        header('location:admin.php');
    } else {
        die(mysqli_error($con));
    }
}
?>

<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="css/dataTables.bootstrap.min.css" />
<script src="js/brand.js"></script>
<script src="js/common.js"></script>
<?php include('inc/container.php'); ?>
<div class="container">
    <?php include("menus.php"); ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-default rounded-0 shadow">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-9">
                            <h3 class="card-title">Kemaskini Admin</h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php
                        $id = $_GET['id'];
                        $sql = "select * from `admin` where adminid=$id";
                        $result = mysqli_query($con, $sql);

                        if ($result) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $id = $row['adminid'];
                                $email = $row['email_Admin'];
                                $password = $row['password_Admin'];
                                $name = $row['name_Admin'];
                        ?>
                                <H5 style="color: black;">Email : </H5>
                                <input type="text" id="email_Admin" name="email_Admin" placeholder="Isi email" class="form-control mb-2" value="<?php echo $row['email_Admin']; ?>">

                                <H5 style="color: black;">Kata Laluan : </H5>
                                <input type="text" id="password_Admin" name="password_Admin" placeholder="Isi kata laluan" class="form-control mb-2" value="<?php echo $row['password_Admin']; ?>">

                                <H5 style="color: black;">Nama : </H5>
                                <input type="text" id="name_Admin" name="name_Admin" placeholder="Isi nama penuh" class="form-control mb-2" value="<?php echo $row['name_Admin']; ?>">
                        <?php
                            }
                        }
                        ?>
                        <br>
                        <button class="btn btn-primary" name="submit">Simpan</button>
                        <a href="admin.php" class="btn btn-danger">Kembali</a>
                        </form>
                <?php include('inc/footer.php'); ?>
                </div>
            </div>
        </div>
    </div>
</div>