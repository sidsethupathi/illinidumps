<?php
Header("content-type: application/x-javascript");	// Set's type as javascript so we can dynamically create jQuery based on id
include("connect.php");

$EFFECT = 'slideDown'; //sets the effect of the animation

function findNear($id, $option) { // Returns arrays of information based on the option passed
	$near = array();
	$lat = array();
	$lng = array();
	$near_id = array();
	$score = array();
	$name = array();
    $slug = array();

	$result = mysql_query("SELECT dest_id FROM distance WHERE src_id=$id ORDER BY distance ASC LIMIT 0,5");
	
	while($row = mysql_fetch_assoc($result)) {
		array_push($near,$row['dest_id']);
	}
	$near_max = sizeof($near);
	for($i=0; $i<$near_max; $i++) {
		$latlng_result = mysql_query("SELECT latitude, longitude, name, score, slug FROM locations WHERE id=$near[$i]");
		$latlng_row = mysql_fetch_assoc($latlng_result);
			
		array_push($lat,$latlng_row['latitude']); 		
		array_push($lng,$latlng_row['longitude']);
		array_push($name, mysql_real_escape_string($latlng_row['name']));
		array_push($near_id,$latlng_row['slug']);
		array_push($score, $latlng_row['score']);
	} 		
	
	if($option == 0) {
		return $lat;
	}
	if($option == 1) {
		return $lng;
	}
	if($option == 2) {
		return $name;
	}
	if($option == 3) {
		return $near_id;
	}
	if($option == 4) {
		return $score;
	}
}

function placeMarker($lat, $lng, $name, $id, $score, $i) { // Add's the relevant nearby markers onto the map
	if(is_null($score)) {
		$score = "0";
	}
	echo "
		var latlng$i = new google.maps.LatLng($lat, $lng);
		var content$i = '<h2><a href=\'/locations/$id\'><span class=\'blue\'>$name</span></a></h2><h3 class=\'orange\'>SCORE: $score</h3>';

		var marker$i = new google.maps.Marker({
			position: latlng$i,
			map: map, 
			title: \"$name\"
		});

		google.maps.event.addListener(marker$i, 'click', function() {
			infowindow.setContent(content$i);
			infowindow.open(map,marker$i);
		});
	";
}
if(isset($_GET['id'])) {
	$id = $_GET['id'];
	
	$near_lat = findNear($id,0);
	$near_lng = findNear($id,1);
	$near_name = findNear($id,2);
	$near_id = findNear($id,3);
	$near_score = findNear($id,4);

	$sql = "SELECT latitude, longitude, name FROM locations WHERE id=$id";
	$result = mysql_query($sql);
	$row = mysql_fetch_assoc($result);
	
		

	$lat = $row['latitude'];
	$long = $row['longitude'];
	$name = $row['name'];
	
echo "
	var lat = ",$lat,";
	var lng = ",$long,";
	
	function initialize() {
    var latlng = new google.maps.LatLng(lat, lng);
    var myOptions = {
      zoom: 16,
      center: latlng,
      mapTypeId: google.maps.MapTypeId.HYBRID
    };
    var map = new google.maps.Map(document.getElementById(\"map_canvas\"),
        myOptions);
	var centerIcon = new google.maps.MarkerImage(\"http://www.illinidumps.com/images/blue-dot.png\");

	var marker = new google.maps.Marker({
		position: latlng,
		icon: centerIcon,
		map: map, 
		title: \"$name\"
	});
	var infowindow = new google.maps.InfoWindow();
	";
	
	$near_lat_max = sizeof($near_lat);
	for($i=0; $i<$near_lat_max; $i++) {
		placeMarker($near_lat[$i], $near_lng[$i], $near_name[$i], $near_id[$i], $near_score[$i], $i);
	}
	echo "
	
  }
  ";
}
?>
