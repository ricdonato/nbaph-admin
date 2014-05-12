<?php
image_resize($_GET['filename'], $_GET['width'], $_GET['height']);

function image_resize($filename, $width, $height) {
   $ext = explode(".", $filename);

   list($width_orig, $height_orig) = getimagesize($filename);

   $ratio_orig = $width_orig/$height_orig;

   if ($width/$height > $ratio_orig) {
      $width = $height*$ratio_orig;
   } else {
      $height = $width/$ratio_orig;
   }


   if ($ext[count($ext) - 1] == "jpg"){
	try{
      $image = imagecreatefromjpeg($filename);
	}catch(Exception $e){}
   }	
   else if ($ext[count($ext) - 1] == "png")
      $image = imagecreatefrompng($filename);
   else if ($ext[count($ext) - 1] == "gif")
      $image = imagecreatefromgif($filename);

   $image_p = imagecreatetruecolor($width, $height);

   if ($ext[count($ext) - 1] == "gif") {
      $bgc = imagecolorallocate ($image_p, 255, 255, 255);
      $tc = imagecolorallocate ($image_p, 0, 0, 0);

      imagefilledrectangle ($image_p, 0, 0, $width, $height, $bgc);
   }
   else {
      if ($ext[count($ext) - 1] == "png")
         imagealphablending($image_p, false);
	try{
      imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	}catch(Exception $e){}
      if ($ext[count($ext) - 1] == "png")
         imagesavealpha($image_p, true);
   }

   // Output
   if ($ext[count($ext) - 1] == "jpg") {
      header('Content-Type: image/jpg');
      imagejpeg($image_p, null, 100);
   }
   else if ($ext[count($ext) - 1] == "png") {
      header('Content-Type: image/png');
      imagepng($image_p, null, 9);
   }
   else if ($ext[count($ext) - 1] == "gif") {
      header('Content-Type: image/gif');
      imagegif($image_p, null);
   }
   imagedestroy($image_p);
}
?> 

