<?php
function geocode($address){
    $address = urlencode($address);
    $url = "http://maps.google.com/maps/api/geocode/json?address={$address}";
    $resp_json = file_get_contents($url);
    $resp = json_decode($resp_json, true);
    if($resp['status']=='OK'){
        $lati = $resp['results'][0]['geometry']['location']['lat'];
        $longi = $resp['results'][0]['geometry']['location']['lng'];
        $formatted_address = $resp['results'][0]['address_components'][0]['long_name'];
        if($lati && $longi && $formatted_address){
            $data_arr = array();
            array_push(
                $data_arr,
                    $lati,
                    $longi,
                    $formatted_address
                );
            return $data_arr;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function get_nearest_timezone($latitude, $longitude, $country_code = '') {
    $timezone_ids = ($country_code) ? DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $country_code) : DateTimeZone::listIdentifiers();
    if($timezone_ids && is_array($timezone_ids) && isset($timezone_ids[0])) {
        $time_zone = '';
        $tz_distance = 0;
        if (count($timezone_ids) == 1) {
            $time_zone = $timezone_ids[0];
        } else {
            foreach($timezone_ids as $timezone_id) {
                $timezone = new DateTimeZone($timezone_id);
                $location = $timezone->getLocation();
                $tz_lat   = $location['latitude'];
                $tz_long  = $location['longitude'];
                $theta    = $longitude - $tz_long;
                $distance = (sin(deg2rad($latitude)) * sin(deg2rad($tz_lat)))
                + (cos(deg2rad($latitude)) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
                $distance = acos($distance);
                $distance = abs(rad2deg($distance));
                if (!$time_zone || $tz_distance > $distance) {
                    $time_zone   = $timezone_id;
                    $tz_distance = $distance;
                }
            }
        }
        return  $time_zone;
    }
    return 'unknown';
}

?>
<!DOCTYPE html>
<html id="isitdarkinthepark" lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Is it Dark in the Park?</title>
    <link rel="stylesheet" href="style.min.css" />
  </head>

  <?php
  if($_POST){
      $ld = '';

      $data_arr = geocode(htmlspecialchars($_POST['address']));

      if($data_arr){

          $latitude = $data_arr[0];
          $longitude = $data_arr[1];
          $formatted_address = $data_arr[2];
          $timezone = get_nearest_timezone($latitude, $longitude);
          date_default_timezone_set($timezone);

          $sunrise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, 0);
          $sunset = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90, 0);
          $formatted_sunrise = date('g:i a', $sunrise);
          $formatted_sunset = date('g:i a', $sunset);

          if ((time() > $sunset) || (time() < $sunrise)) {
            $message = "It's currently dark in " . $formatted_address . ". Stick to well-lit paths, stay out of closed areas, and enjoy the stargazing. The sun will rise at " . $formatted_sunrise . ".";
            $ld = 'dark';
  } else {
    $message = "It's currently daylight in " . $formatted_address . ". The sun will set at " . $formatted_sunset . ".";
  }

}else{
  $message = "Sorry, we couldn't find that park.";
}
echo '<body id="body" class=' . $ld . '><div class="starrynight"></div><header>
  <h1>Is it Dark in the Park?</h1>
</header><main id="main"><div id="innerdiv"><p>' . $message . '</p></div>';
} else {
  echo '<body id="body"><header>
    <h1>Is it Dark in the Park?</h1>
  </header><main id="main">';
}
?>
        <form action="" method="post">
          <label for="address">What park would you like to check?</label>
          <input id="address" type="text" name="address" placeholder="Washington Square Park New York">
               <input id="submit" type="submit" value="Submit">
        </form>

</main>
</body>
</html>
