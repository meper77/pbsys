<?php
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "delete from `owner` where id=$id";
    $result = mysqli_query($con, $sql);
    if ($result) {
        echo "Kenderaan staf berjaya dibuang!";
        header('location:/vehicles/visitor/list.php');
    } else {
        die(mysqli_error($con));
    }
}
?>