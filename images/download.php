<?php
$conn = mysql_connect("localhost", "root", "") or die(mysql_error());
mysql_selectdb("illinidumps", $conn) or die(mysql_error());

$sql = "SELECT * FROM location";
$result = mysql_query($sql);
// while($row = mysql_fetch_assoc($result)) {
	// $location = $row['image'];
	// $id = $row['id'];
	
	// if($img = file_get_contents("$location")) {
		// file_put_contents("building_$id.jpg",$img);
		// echo "done...<br>";
	// } else {
		// echo "error";
	// }
// }

echo "script is commented out";
?>