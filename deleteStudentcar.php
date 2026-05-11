<?php
include 'connect.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "delete from `owner` where id=$id";
    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "Kenderaan pelajar berjaya dibuang!";
        header('location:studentcar.php');
    } else {
        die(mysqli_error($con));
    }
}
?>