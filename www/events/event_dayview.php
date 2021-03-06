<?php
require_once ("Carbon/Carbon.php");
use Carbon\Carbon;

$loc_back_state=0;
$color_toggle=0;
$event_arr = array_fill(0, 24, array_fill(0, 2, ''));

// --------------------------------------------------------------------- EVENTS
$result=mysqli_query($conn,"SELECT * FROM events WHERE ts>=$start_day_utc AND ts<$end_day_utc AND group_id=$id order by ts");
while($row = mysqli_fetch_array($result)) {
  $event_ts = Carbon::createFromTimeStamp($row['ts']);
  $curr_hour = $event_ts->format('G');
  $event_arr[$curr_hour][0] = $row['name'];
}
// Find events before today
$result=mysqli_query($conn,"SELECT name FROM events WHERE ts=".
                            "(SELECT max(ts) FROM events WHERE ts<$start_day_utc AND group_id=$id) AND group_id=$id");
$row = mysqli_fetch_array($result);
if(isset($row['name'])) {
  if(!is_null($event_arr[0][0]))  $event_arr[0][0]=$row['name']." (previous)";
}
cell_colors($event_arr);

$loc_arr = get_day_locations();
cell_colors($loc_arr);
echo draw_timetable(false);

// --------------------------------------------------------------------- FUNCTIONS
function draw_timetable($is_location) {
  global $date;
  global $size;
  $width_time=40;
  $width_cell=150;
  $font_size="11px";
  $num_cols=6;
  $num_rows=4;
  if($size==2) { // Large
    $width_cell=320;
    $font_size="16px";
    $num_cols=3;
    $num_rows=8;
  }
  if($is_location) $title="Location";
    else $title="Events";
  $html="";
  $html.="<div class='container' style='padding:0px 0px 10px 0px;clear:left;'>";  
  $html.="<table border=0>";
  
  $html.="<tr>";
  $html.="<td colspan=8><h3>$title</h3></td>";
  $html.="</tr>";   
  $html.="<tr>";
  for($i=0;$i<$num_cols;$i++) {
    $html.="<td><img src='images/transparent.gif' width='$width_time' height='1'></td>";
    $html.="<td><img src='images/transparent.gif' width='$width_cell' height='1'></td>";
  }
  $html.="</tr>";  
  
  $hour=0;
  for($inner_loop=0;$inner_loop<$num_rows;$inner_loop++)
  {
    $html.="<tr>";
    for($column_loop=0;$column_loop<$num_cols;$column_loop++) 
    {
      $curr_hour=$hour;
      if($size==2) { // Large
	$curr_hour=$inner_loop+($column_loop*$num_rows);
      }
      $curr_ts_utc=$date->copy()->startOfDay()->addHours($curr_hour)->format('U');

      $html.="<td style='text-align:right;font-size:$font_size;' nowrap>";
      $html.=sprintf("%1$02d&nbsp;",$curr_hour);
      $html.="</td>";
      
      if($is_location)
        $html.=location_cell($curr_hour,$curr_ts_utc,$start_date_param,$size);
      else $html.=event_cell($curr_hour,$curr_ts_utc,$start_date_param,$size);
      $hour++;
    } // $column_loop
    $html.="</tr>";
  } // $inner_loop

  $html.="</table>";
  $html.="</div>";  
  return $html;
}

function location_cell($curr_hour,$curr_ts_utc,$start_date_param,$size) {
  global $loc_arr;  
  global $id;
  global $start_date_param;
  global $size;
  $font_size="14px";
  if($size==2) { // Large
    $font_size="20px";
  }

  $background='background-color:#FFFCEB;';
  if($loc_arr[$curr_hour][1]==1) {
    $background='background-color:#F9F1B0;';
  } else if($loc_arr[$curr_hour][1]==2) {
    $background='background-color:#FFEB44;';
  }    
  $html ="";
  $html.="<td style='text-align:left;cursor:pointer;$background;font-size:$font_size;' ";
  $html.="onclick='location.href=\"events/manage_location.php?id=$id&ts=$curr_ts_utc"."&start_date=".$start_date_param."&size=".$size."\"'>";        
  $html.=$loc_arr[$curr_hour][0];
  $html.="&nbsp;";  
  $html.="</td>";  
  return $html;
}

function event_cell($curr_hour,$curr_ts_utc,$start_date_param,$size) {
  global $event_arr;  
  global $id;
  global $start_date_param;
  global $size;  
  $font_size="14px";
  if($size==2) { // Large
    $font_size="20px";
  }  
  
  $background='background-color:#F0F7FD;';
  if($event_arr[$curr_hour][1]==1) {
    $background='background-color:#A9D0F8;';
  } else if($event_arr[$curr_hour][1]==2) {
    $background='background-color:#2FB3F8;';
  }    
  $html ="";
  $html.="<td style='text-align:left;cursor:pointer;$background;font-size:$font_size;' ";
  $html.="onclick='location.href=\"events/manage_location.php?id=$id&ts=$curr_ts_utc"."&start_date=".$start_date_param."&size=".$size."\"'>";        
  $html.=$event_arr[$curr_hour][0];
  $html.="&nbsp;";
  //$html.="&nbsp;".$curr_hour."||ts=".$curr_ts_utc."||ce=".$curr_event."||ea=".$event_arr[$curr_hour][0]."||pe=".$prev_event."||in=".is_null($curr_event)."||sl=".strlen($curr_event);  
  $html.="</td>";  
  return $html;
}

function cell_colors(&$event_arr) {
  for($curr_hour=0;$curr_hour<24;$curr_hour++) {
    
    $prev_event=$event_arr[$curr_hour-1][0];
    $curr_event=$event_arr[$curr_hour][0];    
    if(is_null($curr_event)) { // Current event is NULL = Close Event 
      $event_arr[$curr_hour][1]=0;
    } else {
      if(strlen($curr_event)==0) {  
        $event_arr[$curr_hour][1]=$event_arr[$curr_hour-1][1];
      } else {
        if(is_null($prev_event)) {
          $event_arr[$curr_hour][1]=1;
        } else {
          if($event_arr[$curr_hour-1][1]==1) {
  	    $event_arr[$curr_hour][1]=2;
          } else {
	    $event_arr[$curr_hour][1]=1;
          }
        }
      }
    }   
    //error_log("event_arr[$curr_hour][0]=".$event_arr[$curr_hour][0]." event_arr[$curr_hour][1]=".$event_arr[$curr_hour][1]); 
  }
}
?>
