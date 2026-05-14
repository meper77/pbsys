<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

session_start();
if (!isset($_SESSION['email_Admin'])) {
	header('location:/auth/login_admin.php');
}
?>

<script src="/assets/js/jquery.dataTables.min.js"></script>
<script src="/assets/js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="/assets/css/dataTables.bootstrap.min.css" />
<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/container.php'); ?>
<div class="container">

    <?php include($_SERVER['DOCUMENT_ROOT'].'/includes/menus.php'); ?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-default rounded-0 shadow">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-9">
                            <h3 class="card-title">Lihat Maklumat Kenderaan Pelawat</h3>
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
                        <a href="/vehicles/visitor/list.php" class="btn btn-danger">Kembali</a>
                        </form>
                    <?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>
                </div>
            </div>
        </div>
    </div>
</div>