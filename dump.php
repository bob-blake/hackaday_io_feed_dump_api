<?php
// dump.php
// Dumps metadata from hackaday.io global feed items to database.  Uses Hackaday.io API.
// 
// Author: Bob Blake
// Date: May 5, 2015

require("vars.php");

class feedItem
{
  public $item_id;
  public $user_id;
  public $project_id;
  public $user2_id;
  public $post_id;
  public $type;
  public $activity;
  public $date_time;

  private $table_name;

  public function __construct($table){
    $this->table_name = $table;
  }

  public function get_current($feed_item_arr){
    $this->item_id = $feed_item_arr["id"];
    $this->user_id = $feed_item_arr["user_id"];
    $this->project_id = $feed_item_arr["project_id"];
    $this->user2_id = $feed_item_arr["user2_id"];
    $this->post_id = $feed_item_arr["post_id"];
    $this->type = $feed_item_arr["type"];
    $this->activity = $feed_item_arr["activity"];
    $this->date_time = gmdate("Y-m-d H:i:s", $feed_item_arr["created"]);
    $this->is_duplicate = 0;
  }
  
  public function is_duplicate(){
    $query = "SELECT * FROM $this->table_name WHERE item_id='$this->item_id'";
    $result = mysql_query($query);
    if(!$result){
      //echo mysql_error();  // Or handle otherwise, return an error code
      die();
    }
    $num_rows = mysql_num_rows($result);
    if($num_rows > 0)
      return 1;
    else
      return 0;
  }

  public function print_to_screen(){
    echo "Item ID: $this->item_id <br />";
    echo "User ID: $this->user_id <br />";
    echo "Project ID: $this->project_id <br />";
    echo "User2 ID: $this->user2_id <br />";
    echo "Post ID: $this->post_id <br />";
    echo "Post Type: $this->type <br />";
    echo "Activity: $this->activity <br />";
    echo "Date/Time: $this->date_time <br />";
    echo "<br />";
  }

  public function insert_into_table(){
    $query = "INSERT INTO $this->table_name (item_id, user_id, project_id, user2_id, post_id, post_type, activity, date_time) 
                 VALUES ('$this->item_id', '$this->user_id', '$this->project_id', '$this->user2_id', '$this->post_id', '$this->type', '$this->activity', '$this->date_time')";
    $result = mysql_query($query);

    $err = mysql_error();
    if($err){
        $file = 'errors.txt';
        file_put_contents($file, $err, FILE_APPEND | LOCK_EX);
    }
  }
}

// Reads new page of feed items from Hackaday API
// Returns array of items upon success, null if failed
function getNewPage($key,$pagenum){
  // get json data from api
  $json = @file_get_contents("https://api.hackaday.io/v1/feeds/global?api_key=$key&page=$pagenum");  // Suppress errors that would stop execution
  return json_decode($json,true);
}

$db_table = "feed_items_api";
$api_safety_limit = 1000;    // Limit number of hits to API so my key doesn't get locked out
$first_page = 1;          // Set this to values other than 1 for debug
$page_num = $first_page;
$num_pages = $page_num;   // Ensure we always get at least one page

$api_hit_cntr = 0;
$dupl_cntr = 0;

$item = new feedItem($db_table);

// This is deprecated, I know I know
$server = mysql_connect(DB_HOST,DB_USER,DB_PASS);
$dbcnx = @mysql_select_db(DB_NAME);

// echo "<html>
//       <head></head>
//       <body>
//         <p>";

    while($page_num <= $num_pages){

      // Make sure we're being polite before hitting the api again
      if($api_hit_cntr >= $api_safety_limit){
        //echo "Reached safety limit!<br />";
        return;
      }
      $api_hit_cntr++;

      // Get a new page
      $data = getNewPage($api_key_test,$page_num);  
      if(!data){  // Probably means we hit the hackaday API limit
        $file = 'errors.txt';
        $message = "Error occurred at " . gmdate("Y-m-d H:i:s",time()) . ", page $page_num not processed.  Hit counter: $api_hit_cntr\r";
        //echo $message;
        file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
        return;
      }

      //echo "API hit# $api_hit_cntr!<br />";
      //echo "Page: $page_num<br />";
     
      // Cycle through feed items on each page
      foreach($data["feeds"] as $feed_item){
         
        $item->get_current($feed_item);

        if($item->is_duplicate()){

          if(++$dupl_cntr < 50){
            //echo "Duplicate #$dupl_cntr<br />";
            continue; // Move on to next post
          }
          else{   // 50 in a row probably means we've already gotten these items, so try to get more data from the end
            $dupl_cntr = 0;     // Reset counter

            // Count all items in table
            $query = "SELECT COUNT(id) FROM $db_table";
            $result = mysql_query($query);
            if(!$result){
              //echo mysql_error();
              return;
            }
            $count = mysql_result($result,0);   
            //echo "Count: $count<br />";
            
            // Continue getting posts from the end
            $page_num = floor(($count) / 50); // This gets incremented before being used
            break;  // Move on to next page
          }
        }
        else{
          $dupl_cntr = 0; // If we get here, reset counter

          $item->print_to_screen();
          $item->insert_into_table();
        }
      }
      
      // Increment page
      $page_num++;
      // Update num_pages to account for any new posts
      $num_pages = $data["last_page"];
   }
   
// echo "      </p>
//       </body>
//       </html>";
?>