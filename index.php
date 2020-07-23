<?php



//Input image here (can do multiple)

$images = array (

    'IMG_OF_CAR',

);

foreach($images as $image) {

// settings

    $api_key = 'API_KEY';

    $url = "https://vision.googleapis.com/v1/images:annotate?key=" . $api_key;

    $detection_type = "TEXT_DETECTION";

    $image64 = file_get_contents($image);

    $image_base64 = base64_encode($image64);

    $json_request = '{

				  	"requests": [

						{

						  "image": {

						    "content":"' . $image_base64 . '"

						  },

						  "features": [

						      {

						      	"type": "' . $detection_type . '",

								"maxResults": 200

						      }

						  ]

						}

					]

				}';

//    Curl stuff for G API

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));

    curl_setopt($curl, CURLOPT_POST, true);

    curl_setopt($curl, CURLOPT_POSTFIELDS, $json_request);

    $json_response = curl_exec($curl);

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($status != 200) {

        die("Oh dear... Status code: $status");

    }

    $path_info = pathinfo($image);

    switch ($path_info['extension']) {

        case 'jpg':

            $im = imagecreatefromjpeg($image);

            break;

        case 'png':

            $im = imagecreatefrompng($image);

            break;

        case 'gif':

            $im = imagecreatefromgif($image);

            break;

    }

    //Set all varibles

    $response = json_decode($json_response, true);

    $red = 0;

    $green = 0;

    $blue = 0;

    $i = 0;

    $x = 0;

    $n = 0;

    $y = 0;

    $word = "";

    $points = array();





//    Now we get a little complicated



    foreach ($response['responses'][0]['fullTextAnnotation']['pages'] as $box) {

        foreach ($box['blocks'] as $block) {

            foreach ($block['paragraphs'] as $paragraph) {

                $points = array();

                foreach ($paragraph['words'] as $word) {

                    foreach ($word['boundingBox']['vertices'] as $vertex) {

//                        We find each "word" e.g  "J953" AND "CCL" (plate J953CCL)

//                        Then grab the first point, and last point of word 1

//                        Then grab the first point, and last point of word 2

                        echo count($vertex);

                        if ($i == 0 || $i == 3 || $i == 6) {

                            array_push($points, $vertex['x'], $vertex['y']);

                        }

                        if ($i == 5) {

                            $x = $vertex['x'];

                            $y = $vertex['y'];

                        }

                        if ($i == 7) {

                            array_push($points, $x, $y);

                        }

                        if ($i == 0) {

                            $x0 = $vertex['x'];

                            $y0 = $vertex['y'];

                        }

                        if ($i == 7) {

                            $x6 = $vertex['x'];

                            $y6 = $vertex['y'];

                        }

                        $i++;

                    }



                }

                if ($i != 8) {

                    $i = 0;

                    foreach ($paragraph['words'] as $word) {

                        foreach ($word['boundingBox']['vertices'] as $vertex) {

                            array_push($points, $vertex['x'], $vertex['y']);

                            if ($i == 0) {

                                $x0 = $vertex['x'];

                                $y0 = $vertex['y'];

                            }

                            if ($i == 3) {

                                $x6 = $vertex['x'];

                                $y6 = $vertex['y'];

                            }

                            $i++;

                        }

                    }

                }

            }

        }

    }

//    My favorite bit:

//    here we grab the frist point of word one and the last point of word 2, creating us a rectangle

//    We then grab $randit amount of random points in the square if the points are black (Letters) we exclude them

//    If the poitns are white we keep them (or near yellow for rear of car

//    We then grab all those random points and get the RGB of that points $randit amount of times

//    Then we get and average of the points giving us a "avrage color"

//    Then use that color as the background to draw a square to cover the points 1 though to 7

//    :)

//    Then, save and display on a page





    $min = array($x0, $y0);

    $max = array($x6, $y6);



    $xrand = rand($x0, $x6);

    $yrand = rand($y0, $y6);

    $randcoord = array();

    $randit = 1000;

    $actualrand = 0;

    $wy = "";

    $retries = 0;

    array_push($randcoord, $xrand, $yrand);

    for ($p = 0; $p <= $randit; $p++) {

        $rgb = imagecolorat($im, rand($x0, $x6), rand($y0, $y6));

        $colors = imagecolorsforindex($im, $rgb);

//        echo "<div style='background-color: rgb(".$colors['red'].",".$colors['green'].",".$colors['blue'].")'>RGB(".$colors['red'].",".$colors['green'].",".$colors['blue'].")</div>";

        if ($colors['blue'] <= 100 && $colors['red'] >= 100 && $colors['green'] >= 100) {

            $red += $colors['red'];

            $green += $colors['green'];

            $blue += $colors['blue'];

            $actualrand++;



        }

        if ($colors['blue'] >= 130 && $colors['red'] >= 130 && $colors['green'] >= 130) {

            $red += $colors['red'];

            $green += $colors['green'];

            $blue += $colors['blue'];

            $actualrand++;

//            echo "<div style='background-color: rgb(".$colors['red'].",".$colors['green'].",".$colors['blue'].")'>RGB(".$colors['red'].",".$colors['green'].",".$colors['blue'].")</div>";

        }



    }



    $red = $red / $actualrand;

    $green = $green / $actualrand;

    $blue = $blue / $actualrand;





//                echo $actualrand;

//    echo "RGB(" . round($red) . "," . round($green) . "," . round($blue) . ")";

//                echo"<pre>";

//                var_dump_p($points);

//                echo "</pre>";

    $color = imagecolorallocate($im, round($red), round($green), round($blue));

    imagefilledpolygon($im, $points, count($points) / 2, $color);





    $fileName = time() . '.jpg';



    $prefix = 'C:\xampp\htdocs\ImageGen\uploads';



    $sourceImage = "uploads/" .$fileName;



    imagejpeg($im, $sourceImage);

    imagedestroy($im);





    echo '<div style="width:100%;"><img src="'.$sourceImage.'" style="width:100%;"/></div>';

    echo '<div style="width:50%;float:right;">';

// display the first text annotation



    foreach ($response['responses'][0]['fullTextAnnotation']['pages'] as $box) {

        foreach ($box['blocks'] as $block) {

            foreach ($block['paragraphs'] as $paragraph) {

                foreach ($paragraph['words'] as $word) {

                    foreach ($word['symbols'] as $text) {

                        echo "<h1>".$text['text']."</h1>";

                    }

                }

            }

        }

    }



    echo '</div>';





}

