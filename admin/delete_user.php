<?php
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "delete from `user` where userid=$id";
    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "Pengguna berjaya dibuang!";
        header('location:/admin/users.php');
    } else {
        die(mysqli_error($con));
    }
}
?>