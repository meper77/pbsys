<?php
include 'connect.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "delete from `owner` where id=$id";
    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "Kenderaan staf berjaya dibuang!";
        header('location:staffcar.php');
    } else {
        die(mysqli_error($con));
    }
}
?>