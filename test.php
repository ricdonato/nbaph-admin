<?php
//	exec('aws s3 ls s3://nbaphfiles/images/');
	define('USERNAME','cronuser');
        define('PASSWORD','nb@p@$$');
        define('SERVER','nbadb.cgvo8mpef8im.ap-southeast-1.rds.amazonaws.com');
        define('DATABASE','db48516_nba');


	$connect = mysqli_connect(SERVER,USERNAME,PASSWORD,DATABASE);
	if(!$connect){
 		die('Connect Error: ' . mysqli_connect_error());
	}
	//$q = "SELECT * FROM personalitiesorder;";
	$q = "SELECT * FROM wall_videos;";
	$result = mysqli_query($connect,$q);
	while($row = mysqli_fetch_assoc($result)){
		$person[] = $row;
	}
	var_dump($person);
?>
