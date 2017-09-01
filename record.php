<?php
//http://owntracks.org/booklet/tech/http/
# Obtain the JSON payload from an OwnTracks app POSTed via HTTP
# and insert into database table.

header("Content-type: application/json");
require_once('./config.inc.php');

$payload = file_get_contents("php://input");
$data =  @json_decode($payload, true);

if ($data['_type'] == 'location') {

    # CREATE TABLE locations (dt TIMESTAMP, tid CHAR(2), lat DECIMAL(9,6), lon DECIMAL(9,6));
    $mysqli = new mysqli($_config['sql_host'], $_config['sql_user'], $_config['sql_pass'], $_config['sql_db']);
	
	//http://owntracks.org/booklet/tech/json/
	//iiiissddissiiidsiis
    if (array_key_exists('acc', $data)) $accuracy = intval($data['acc']);
    if (array_key_exists('alt', $data)) $altitude = intval($data['alt']);
    if (array_key_exists('batt', $data)) $battery_level = intval($data['batt']);
	if (array_key_exists('cog', $data)) $heading = intval($data['cog']);
	if (array_key_exists('desc', $data)) $description = strval($data['desc']);
	if (array_key_exists('event', $data)) $event = strval($data['event']);
	if (array_key_exists('lat', $data)) $latitude = floatval($data['lat']);
	if (array_key_exists('lon', $data)) $longitude = floatval($data['lon']);
	if (array_key_exists('rad', $data)) $radius = intval($data['rad']);
	if (array_key_exists('t', $data)) $trig = strval($data['t']);
	if (array_key_exists('tid', $data)) $tracker_id = strval($data['tid']);
	if (array_key_exists('tst', $data)) $epoch = intval($data['tst']);
	if (array_key_exists('vac', $data)) $vertical_accuracy = intval($data['vac']);
	if (array_key_exists('vel', $data)) $velocity = intval($data['vel']);
	if (array_key_exists('p', $data)) $pressure = floatval($data['p']);
	if (array_key_exists('conn', $data)) $connection = strval($data['conn']);
	
	/*
	if($_config['enable_geo_reverse']){
		
		$geo_decode_url = $_config['geo_reverse_lookup_url'] . 'lat=' .$latitude. '&lon='.$longitude;

		$geo_decode_json = file_get_contents($geo_decode_url);		

		$geo_decode = @json_decode($geo_decode_json, true);

	
		$place_id = intval($geo_decode['place_id']);
		$osm_id = intval($geo_decode['osm_id']);
		$display_name = strval($geo_decode['display_name']);
		
		if($display_name == '') { $display_name = @json_encode($geo_decode); }

	}
	*/
	
	$sql = "SELECT epoch FROM ".$_config['sql_prefix']."locations WHERE tracker_id = ? AND epoch = ?";

	if ($stmt = $mysqli->prepare($sql)){
    	
    	$stmt->bind_param('si', $tracker_id, $epoch);
    	$stmt->execute();
		$stmt->store_result();


	    //record only if same data found at same epoch / tracker_id
	    if($stmt->num_rows == 0) {

			$sql = "INSERT INTO ".$_config['sql_prefix']."locations (accuracy, altitude, battery_level, heading, description, event, latitude, longitude, radius, trig, tracker_id, epoch, vertical_accuracy, velocity, pressure, connection, place_id, osm_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		    
		    if ($stmt = $mysqli->prepare($sql)){
		    	
		    	# bind parameters (s = string, i = integer, d = double,  b = blob)
			    $stmt->bind_param('iiiissddissiiidsii', $accuracy, $altitude, $battery_level, $heading, $description, $event, $latitude, $longitude, $radius, $trig, $tracker_id, $epoch, $vertical_accuracy, $velocity, $pressure, $connection, $place_id, $osm_id);
			    $stmt->execute();
			    http_response_code(200);
				$response['msg'] = "OK record saved";
			
		    }else{
				http_response_code(500);
				die("Can't write to database");
				$response['msg'] = "Can't write to database";
			}

	    }
	    $stmt->close();
	
    }else{
		http_response_code(500);
		die("Can't read from database");
		$response['msg'] = "Can't read from database";
	}


    

    

}else{
	http_response_code(204);
	$response['msg'] = "OK type is not location";
}

$response = array();
# optionally add objects to return to the app (e.g.
# friends or cards)
print json_encode($response);
?>
