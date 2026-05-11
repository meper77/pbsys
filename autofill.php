<?php
  
// Get the user id 	
$platenum = $_REQUEST['platenum'];
  
// Database connection
$con = mysqli_connect("localhost", "root", "", "pbantuan_db");

/*//Get search term
$searchTerm = $_GET["term"];

// Fetch matched data from the database
$query = $db->query("SELECT * FROM studentcar WHERE matric LIKE '%".$searchTerm."%' ORDER BY matric ASC");

// Generate array with skills data
$skillData = array();
if ($query->num_rows > 0){
	while ($row = $query->fetch_assoc()){
		$data['id'] = $row['studentid'];
		$data['value'] = $row['matric'];
		array_push($skillData, $data);
	}
}

// Return results as json encoded array
echo json_encode($skillData);*/

if ($platenum !== "") {
      
    // Get corresponding name and 
    // email for that matric    
    $query = mysqli_query($con, "SELECT name, idnumber, email FROM owner WHERE platenum='$platenum'");
    $row = mysqli_fetch_array($query);
	
    // Get the name
    $name = $row["name"];
  
    // Get the ID number
    $idnumber = $row["idnumber"];
	
	//Get the email
	$email = $row["email"];
}

// Store it in a array
$result = array("$name", "$idnumber", "$email");
  
// Send in JSON encoded form
$myJSON = json_encode($result);
echo $myJSON;			
?>
