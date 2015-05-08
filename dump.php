<?php
// dump.php
// Dumps metadata from hackaday.io global feed items to database.  Uses Hackaday.io API.
//
// Author: Bob Blake
// Date: May 5, 2015
// TODO: Make it so this code adds new post items if they appear; also old post items if they're not yet
// in the database.
//    Start at page 1, read in new posts until a duplicate is found
//    Then, count the number of entires in the database and divide by 50
//    This will give the next page where un-dumped old data has appeared

require("../../php/had_feed/vars.php");

$finished = 0;
$pagenum = 1;

// This is deprecated, I know I know
$server = mysql_connect(DB_HOST,DB_USER,DB_PASS);
$dbcnx = @mysql_select_db(DB_NAME);

// echo "<html>
//       <head></head>
//       <body>
//         <p>";

    $num_pages = 2;
    $page = 1;

    while($page <= $num_pages){
      // get json data from api
      $json = @file_get_contents("https://api.hackaday.io/v1/feeds/global?api_key=$api_key&page=$page");  // Suppress errors that will stop execution
      $data = json_decode($json,true);
      if(!$data){ // Probably means I hit the API limit
        $file = 'errors.txt';
        $message = "Error occurred at " . gmdate("Y-m-d H:i:s",time()) . ", page $page not processed.\r";
        //echo $message;
        file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
        return;
      }
     
      // Cycle through feed items on each page
      foreach($data["feeds"] as $feed_item){
         
         // Put array data into variables for legibility
         $item_id = $feed_item["id"];
         $user_id = $feed_item["user_id"];
         $project_id = $feed_item["project_id"];
         $user2_id = $feed_item["user2_id"];
         $post_id = $feed_item["post_id"];
         $type = $feed_item["type"];
         $activity = $feed_item["activity"];
         $date_time = gmdate("Y-m-d H:i:s", $feed_item["created"]);
         $is_duplicate = 0;
      
         // Mark duplicates
         $query = "SELECT * FROM feed_items_api WHERE item_id='$item_id'";
         $result = mysql_query($query);
         if($result){
            $num_rows = mysql_num_rows($result);
            if($num_rows > 0)
               $is_duplicate = 1;
         }
         else
            echo mysql_error();
         
        // Output to screen for testing
        // echo "Item ID: $item_id <br />";
        // echo "User ID: $user_id <br />";
        // echo "Project ID: $project_id <br />";
        // echo "User2 ID: $user2_id <br />";
        // echo "Post ID: $post_id <br />";
        // echo "Post Type: $type <br />";
        // echo "Activity: $activity <br />";
        // echo "Date/Time: $date_time <br />";
        // echo "Duplicate?: $is_duplicate <br />";
        // echo "<br />";
         
         // Put data into database
          $query = "INSERT INTO feed_items_api (item_id, user_id, project_id, user2_id, post_id, post_type, activity, date_time, is_duplicate) 
                      VALUES ('$item_id', '$user_id', '$project_id', '$user2_id', '$post_id', '$type', '$activity', '$date_time', '$is_duplicate')";

          $result = mysql_query($query);

          $err = mysql_error();
          if($err){
             $file = 'errors.txt';
             file_put_contents($file, $err, FILE_APPEND | LOCK_EX);
          }
      }
      
      // Increment page
      $page++;
      // Update num_pages to account for any new posts
      $num_pages = $data["last_page"];
   }
   
// echo "      </p>
//       </body>
//       </html>";
?>