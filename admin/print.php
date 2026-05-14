<?php
include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php');
include $_SERVER['DOCUMENT_ROOT'].'/includes/connect.php';

session_start();
if (!isset($_SESSION['email'])) {
    header('location:/auth/login.php');
}
?>

<script src="/assets/js/jquery.dataTables.min.js"></script>
<script src="/assets/js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="/assets/css/dataTables.bootstrap.min.css" />
<head>

    <link rel="stylesheet" type="text/css" href="print.css" media="print">

    <style>
        body {
            font-family: "Calibri";
        }

        *:disabled {
            background-color: transparent;
        }

        ::-webkit-scrollbar {
            display: none;
        }

        @media print {
            .pagebreak {
                page-break-before: always;
            }

            /* page-break-after works, as well */
        }
    </style>

</head>
<body>
    <center><button onclick="window.print();" class="btn btn-primary" id="print-btn">Cetak</button></center><br>

    

    <center><button onclick="window.print();" class="btn btn-primary" id="print-btn">Cetak</button></center>
</body>

</html>