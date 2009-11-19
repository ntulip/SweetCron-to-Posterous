<html>
	<head>
		<title>SweetCron to Posterous Batch Job</title>
		<style>
		body {font-size:1em;font-face:Georgia;}h1{font-size:2em;}h3{font-size:1.7em;}
		</style>
	</head>
	<body>

	<?php 

	set_time_limit(60);

	include('db.php');
	
	function ob_handler($string, $flags) {
			static $input = array();
			if ( $flags & PHP_OUTPUT_HANDLER_START )
					$flags_sent[] = "PHP_OUTPUT_HANDLER_START";
			if ( $flags & PHP_OUTPUT_HANDLER_CONT )
					$flags_sent[] = "PHP_OUTPUT_HANDLER_CONT";
			if ( $flags & PHP_OUTPUT_HANDLER_END )
					$flags_sent[] = "PHP_OUTPUT_HANDLER_END";
			$input[] = implode(' | ', $flags_sent) . " ($flags): $string<br />";
			$output  = "$string<br />";
			if ( $flags & PHP_OUTPUT_HANDLER_END ) {
					$output .= '<br />';
					foreach($input as $k => $v) $output .= "$k: $v";
			}
			return $output;
	}

	ob_start('ob_handler');
	
	

	define('IMPORT_SITE_ID',       -1);                       // The id of one of your Posterous sites ...
	define('IMPORT_SITE_EMAIL',    'USER@DOMAIN.com');       // The email address assigned to your Posterous account ...
	define('IMPORT_SITE_PASSWORD', 'PASSWORD');               	  // The password assigned to your Posterous account ...
	define('DB_NAME', 'Database Name');
	define('DB_URL', 'Database URL or IP Address');
	define('DB_USER', 'Database username');
	define('DB_PASSWORD', 'Database password');


	$batchSize = 100;
	$totalRecsProcessed = 0;
	$currentRecord = 0;

	// SHOW ITEM BY DEFAULT on Posterous Website
	$private_item = 1;
	
	echo "Welcome. This process will attempt to move your items from SweetCron to Posterous<br/><br/>";
	
	$db = new DataAccessLayer(DB_URL,DB_USER,DB_PASSWORD,DB_NAME);
	$db->debug=false;

	$totalRows = $db->nonQuery("select * FROM items where item_status = 'publish' limit 0,10");
	
	while($totalRows > 0){

		$currentRecord = 0;
		if($totalRecsProcessed > 100){
			break;
		}
		
		echo "<h1>Processing " . $totalRecordsProcessed . " through " . $totalRecordsProcessed + $batchSize . "</h1>";

		$sql = "SELECT f.feed_title, f.feed_url, f.feed_domain, f.feed_icon, f.feed_data, i.* FROM items i " .
		" inner join feeds f on f.feed_id = i.item_feed_id " .
		" where i.item_status = 'publish' " .
		" limit 0," . $batchSize;

		$items = $db->query($sql);
		

		while($obj = $items->fetch_object()){ 
		
			$dmp = unserialize($obj->item_data); // load up the serialized item_data
		
			echo "<h3>Importing " . $obj->ID . " - " . $obj->item_title . "</h3>"; 
			echo "Original Date: " . $obj->item_date . "<br/>";
			echo "Item Title: " . $dmp["title"] . "<br/>";

			$itemContent = "From: " . $obj->feed_domain . "<br/>" . $dmp["content"] . "<br/>Permalink: <a href='" . $obj->item_permalink . "'>" . $obj->item_permalink . "</a><br/>";

			// Posterous values to go into curl request
			$values = array
			(
				'site_id' => IMPORT_SITE_ID,
				'title'   => $obj->item_title,
				'body'    => $itemContent,
				'date'    => date("M d Y H:i:s", $obj->item_date),
				'source'  => $obj->feed_title,
				'sourceLink' => $obj->item_permalink,
				'private' => $private_item,
			);

			// get tags from the database.
			// i concatenate them here as per API spec
			$itemTags = "";
			$tags = $db->query("SELECT GROUP_CONCAT(DISTINCT name SEPARATOR ',') as tags FROM tags where tag_id in (select tag_id from tag_relationships where item_id = " . $obj->ID. ")");

			// there has to be a better way to pull a single item from a result set than doing a loop
			// TO BE RESOLVED later
			while($tag = $tags->fetch_object()){
				$itemTags .= $tag->tags;
			}
			// release handle
			$tags->close();

			// add tags to posterous item post values
			$values["tags"] = $itemTags;

			// now lets post this up
			$curl = curl_init('posterous.com/api/newpost');

			curl_setopt($curl, CURLOPT_USERPWD,        IMPORT_SITE_EMAIL . ':' . IMPORT_SITE_PASSWORD);
			curl_setopt($curl, CURLOPT_POST,           true);
			curl_setopt($curl, CURLOPT_POSTFIELDS,     $values);
			curl_setopt($curl, CURLOPT_HEADER,         false);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPAUTH,       CURLAUTH_ANY);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			curl_exec($curl);

			curl_close($curl);

			// now update the item in the database and set it's status to 'moved' so that it doesn't appear in our queue again
			$update = array
			(
				"item_status" => "moved",
			);

			$db->updateById("items", $update, "ID", $obj->ID);
			
			$currentRecord++;
			$totalRecsProcessed++;
			
			echo "Finished batch. Total Records: " . $totalRecsProcessed . " Waiting 3 seconds until next batch.<br/>";
			
			ob_flush();
			sleep(3);
			
		} 
		
		$items->close();
		ob_clean();
		
	}
	
	if($totalRecsProcessed == 0){
		echo "Apparently you don't interact with the web much so how exactly did you come up on this script?";
	}
	
	ob_end_flush();
	?>
	</body>
</html>