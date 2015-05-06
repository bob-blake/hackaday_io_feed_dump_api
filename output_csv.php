<?
// output_csv.php
// Loads all hackaday.io feed items from my database and outputs them to a .csv file for download.
// http://www.bobblake.me/had_feed/output_csv.php
// Author: Bob Blake
// Date: May 5, 2015

// TODO: rewrite so the script doesn't load the ENTIRE database into an array, that requires far too much memory

	require("../../php/had_feed/vars.php");

	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=file.csv");
	header("Pragma: no-cache");
	header("Expires: 0");

	// This is deprecated, I know I know
	$server = mysql_connect(DB_HOST,DB_USER,DB_PASS);
	$dbcnx = @mysql_select_db(DB_NAME);

	$query = "SELECT *
				FROM feed_items_api 
				ORDER BY date_time;";

	$mysql_array = mysql_query($query);
   if(!$mysql_array)
      echo mysql_error();
   
	$array[] = array("Item ID", "User ID", "Project ID", "User2 ID", "Post ID", "Post Type", "Activity", "Date/Time", "Duplicate?");

	while($data_row = mysql_fetch_array($mysql_array, MYSQL_BOTH)){
		// This will need to change
		$array[] = array($data_row['item_id'],$data_row['user_id'],$data_row['project_id'],$data_row['user2_id'],$data_row['post_id'],$data_row['post_type'],$data_row['activity'],$data_row['date_time'],$data_row['is_duplicate']);
	}

	outputCSV($array);

	function outputCSV($data) {
		$outstream = fopen("php://output", "w");
		function __outputCSV(&$vals, $key, $filehandler) {
			fputcsv($filehandler, $vals); // add parameters if you want
		}
		array_walk($data, "__outputCSV", $outstream);
		fclose($outstream);
	}
?>