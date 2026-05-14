<?php
include('inc/header.php');
include 'connect.php';

session_start();
if (!isset($_SESSION['email_Admin'])) {
	header('location:loginAdmin.php');
}
?>

<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="css/dataTables.bootstrap.min.css" />
<?php include('inc/container.php'); ?>
<div class="container">

    <?php include("menus.php"); ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-default rounded-0 shadow">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-9">
                            <h3 class="card-title">Lihat Maklumat Kenderaan Kontraktor</h3>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php
                        $id = $_GET['id'];
                        $sql = "select * from `owner` where id=$id";
                        $result = mysqli_query($con, $sql);

                        if ($result) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $type = $row['type'];
                                $contractplate = $row['platenum'];
                        ?>

                                <H5 style="color: black;">Jenis Kenderaan : </H5>
                                <input disabled type="text" id="type" name="type" placeholder="" class="form-control mb-2" value="<?php echo $row['type']; ?>">

                                <H5 style="color: black;">Nombor Plat : </H5>
                                <input disabled type="text" id="platenum" name="platenum" placeholder="" class="form-control mb-2" value="<?php echo $row['platenum']; ?>">
                        <?php
                            }
                        }
                        ?>
                        <br>
                        <a href="contractorcar.php" class="btn btn-danger">Kembali</a>
                    </form>
                    <?php include('inc/footer.php'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
