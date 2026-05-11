<?php
include('../inc/header.php');
include '../connect.php';

session_start();
if (!isset($_SESSION['email'])) {
	header('location:login.php');
}
?>

<?php

$success = 0;
$fail = 0;

if(isset($_POST["submit"]))
{
 $path = 'upload/' . $_FILES["attachment"]["name"];
 move_uploaded_file($_FILES["attachment"]["tmp_name"], $path);
 
 require 'PHPMailer/src/PHPMailer.php';
 require 'PHPMailer/src/SMTP.php';
 require 'PHPMailer/src/Exception.php';
 
 $staffname = ($_POST['staffname']);
 $staffno = ($_POST['staffno']);
 $staffmail = ($_POST['staffmail']);
 $subject = ($_POST['subject']);
 $message = ($_POST['message']);
 
 $mail = new PHPMailer\PHPMailer\PHPMailer();
 $mail->IsSMTP();        //Sets Mailer to send message using SMTP
 $mail->Host = 'smtp.gmail.com';  //Sets the SMTP hosts of your Email hosting
 $mail->Port = 587;        //Sets the default SMTP server port
 $mail->SMTPAuth = true;       //Sets SMTP authentication. Utilizes the Username and Password variables
 $mail->Username = 'polisbuitm@gmail.com';     //Sets SMTP username
 $mail->Password = 'ebozxuhjixzjwwxs';     //Sets SMTP password "ebozxuhjixzjwwxs" "radonsquamvcfgib"
 $mail->SMTPSecure = 'tls';       //Sets connection prefix. Options are "", "ssl" or "tls"
 $mail->AddAddress($staffmail, $staffname);  //Adds a "To" address
 $mail->IsHTML(true);       //Sets message type to HTML
 $mail->setFrom('polisbuitm@gmail.com', 'Polis Bantuan UiTM'); // Sender email and name
 $mail->AddAttachment($path);     //Adds an attachment from a path on the filesystem
 $mail->Subject = $subject;    //Sets the Subject of the message
 $mail->Body = $message;       //An HTML or plain text message body
 if($mail->Send())        //Send an Email. Return true on success or false on error
 {
	$success = 1;
 }
 else
 {
	$fail = 1;
 }
 }
?>

<script src="js/jquery.dataTables.min.js"></script>
<script src="js/dataTables.bootstrap.min.js"></script>
<link rel="stylesheet" href="css/dataTables.bootstrap.min.css" />

<?php include('../inc/container.php'); ?>
<?php
    if ($success) {
        echo '<div class="alert alert-primary alert-dismissible fade show" role="alert">
  		Mail has been sent succesfully
  		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
    }
?>

<?php
    if ($fail) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
  		Mail cannot be sent
  		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
    }
?>
<div class="container">
	<?php include("../MenuMail.php"); ?>
	<div class="row">
		<div class="col-lg-12">
			<div class="card card-default rounded-0 shadow">
				<div class="card-header">
					<div class="row">
						<div class="col-lg-8 col-md-8 col-sm-8 col-xs-6">
							<h3 class="card-title">Sistem Mail Staf</h3>
						</div>
						<body>
							<form method="post" action="emailstaf.php" enctype="multipart/form-data">
							
							<H5 style="color: black;">Nama : </H5>
							<input type="text" id="staffname" name="staffname" class="form-control mb-2" value="" required>
							
							<H5 style="color: black;">No. Staf : </H5>
							<input type="text" id="staffno" name="staffno" value="" class="form-control mb-2" required>
							
							<H5 style="color: black;">Email Staf : </H5>
							<input type="text" id="staffmail" name="staffmail" class="form-control mb-2" required>
							
							<H5 style="color: black;">No. Plate Kenderaan : </H5>
							<input type="text" id="staffplate" name="staffplate" placeholder="Isi no. kenderaan" onkeyup="GetDetail(this.value)" value="" class="form-control mb-2" required>
							
							<H5 style="color: black;">Jenis Kesalahan : </H5>
							<select type="text" id="subject" name="subject" value="kesalahan" placeholder="Pilih Jenis Kesalahan" class="form-control mb-2" required>
							<option>Pilih Jenis Kesalahan</option>
							<option value="Beroperasi tanpa lesen">Beroperasi tanpa lesen</option>		
							<option value="Tidak mematuhi kelajuan yang ditetapkan">Tidak mematuhi kelajuan yang ditetapkan</option>
							<option value="Ubahsuai kenderaan tanpa kebenaran">Ubahsuai kenderaan tanpa kebenaran</option>
							<option value="Tidak mematuhi larangan pengunaan kenderaan">Tidak mematuhi larangan pengunaan kenderaan</option>
							</select>
							
							<H5 style="color: black;">Mesej : </H5>
							<textarea name="message" id="message" placeholder="Berikan mesej" class="form-control mb-2 rounded-0" required></textarea>
							
							<H5 style="color: black;">Lampiran email : </H5>
							<input type="file" name="attachment" class="form-control mb-2" accept=".doc,.docx, .pdf, .png, .jpg, .mp4">
							
							<button class="btn btn-success" type="submit" name="submit">Submit</button>
							</form>
						</div>
					<div style="clear:both"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.1/xlsx.full.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
<script>
        function GetDetail(str) {
            if (str.length == 0) {
                document.getElementById("staffname").value = "";
                document.getElementById("staffmail").value = "";
				document.getElementById("staffno").value ="";
                return;
            }
            else {
  
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function () {
  
                    if (this.readyState == 4 && 
                            this.status == 200) {
                          
                        var myObj = JSON.parse(this.responseText);
  
                        document.getElementById
                            ("staffname").value = myObj[0];
                          
                        document.getElementById
						    ("staffmail").value = myObj[1];
							
						document.getElementById
						    ("staffno").value = myObj[2];
                    }
                };
  
                xmlhttp.open("GET", "../autofillstaf.php?staffplate=" + str, true);
                xmlhttp.send();
            }
        }
</script>
<?php include('../inc/footer.php'); ?>