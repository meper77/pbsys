<?php
  
// Get the user id 
$staffplate = $_REQUEST['staffplate'];
  
// Database connection
$con = mysqli_connect("localhost", "root", "", "pbantuan_db");
  
if ($staffplate !== "") {
      
    // Get corresponding name and 
    // email for that matric    
    $query = mysqli_query($con, "SELECT staffname, staffmail, staffno FROM staffcar WHERE staffplate='$staffplate'");
    $row = mysqli_fetch_array($query);
  
    // Get the name
    $staffname = $row["staffname"];
  
    // Get the email
    $staffmail = $row["staffmail"];
	
	//Get the plate number
	$staffno = $row["staffno"];
}

// Store it in a array
$result = array("$staffname", "$staffmail", "$staffno");
  
// Send in JSON encoded form
$myJSON = json_encode($result);
echo $myJSON;
?>