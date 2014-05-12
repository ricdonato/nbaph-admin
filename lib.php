<?php

//truncate
function myTruncate($string, $limit, $break=" ", $pad="...", $url=null)
{   
	if(strlen($string) <= $limit) return $string;
	if(false !== ($breakpoint = strpos($string, $break, $limit)))
	{
		if($breakpoint < strlen($string) - 1)
		{
			if(isset($url)){
				$string = substr($string, 0, $breakpoint) . "...<a href='". $url . "'>". $pad . "</a>";
			}else{
				$string = substr($string, 0, $breakpoint) . $pad;
			}
		}
    } 
	return $string;
}

// function to parse a video <entry>
function parseVideoEntry($entry) {
  $obj= new stdClass;

  // get nodes in media: namespace for media information
  $media = $entry->children('http://search.yahoo.com/mrss/');
  $obj->title = $media->group->title;
  $obj->description = $media->group->description;

  // get video player URL
  $attrs = $media->group->player->attributes();
  $obj->watchURL = $attrs['url'];

  // get video thumbnail
  $attrs = $media->group->thumbnail[0]->attributes();
  $obj->thumbnailURL = $attrs['url'];

  // get <yt:duration> node for video length
  $yt = $media->children('http://gdata.youtube.com/schemas/2007');
  $attrs = $yt->duration->attributes();
  $obj->length = $attrs['seconds'];

  // get <yt:stats> node for viewer statistics
  $yt = $entry->children('http://gdata.youtube.com/schemas/2007');
  $attrs = $yt->statistics->attributes();
  $obj->viewCount = $attrs['viewCount'];

  // get <gd:rating> node for video ratings
  $gd = $entry->children('http://schemas.google.com/g/2005');
  if ($gd->rating) {
   $attrs = $gd->rating->attributes();
   $obj->rating = $attrs['average'];
  } else {
   $obj->rating = 0;
  }

  // get <gd:comments> node for video comments
  $gd = $entry->children('http://schemas.google.com/g/2005');
  if ($gd->comments->feedLink) {
   $attrs = $gd->comments->feedLink->attributes();
   $obj->commentsURL = $attrs['href'];
   $obj->commentsCount = $attrs['countHint'];
  }

  //Get the author
  $obj->author = $entry->author->name;
  $obj->authorURL = $entry->author->uri;

  // get feed URL for video responses
  $entry->registerXPathNamespace('feed', 'http://www.w3.org/2005/Atom');
  $nodeset = $entry->xpath("feed:link[@rel='http://gdata.youtube.com/schemas/
  2007#video.responses']");
  if (count($nodeset) > 0) {
   $obj->responsesURL = $nodeset[0]['href'];
  }

  // get feed URL for related videos
  $entry->registerXPathNamespace('feed', 'http://www.w3.org/2005/Atom');
  $nodeset = $entry->xpath("feed:link[@rel='http://gdata.youtube.com/schemas/
  2007#video.related']");
  if (count($nodeset) > 0) {
   $obj->relatedURL = $nodeset[0]['href'];
  }

  // return object to caller
  return $obj;
}function resizeCrop($filename, $width, $height, $mode) {
//for caching..
$newimage = $filename;
$dimention = $width."x".$height."_";
$path_parts = pathinfo($newimage);
$cache_imgfile = "scache/".$path_parts['dirname']."/".$dimention.$path_parts['basename'];

if(file_exists($cache_imgfile))
 {

   $new_image = $cache_imgfile;

/*   header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($newimage));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($newimage));
    ob_clean();
    flush();
    readfile($newimage);
   exit;
 */

 }
else
 {

   if (!$mode) { $mode = 'crop'; } else { $mode = 'fit'; }

   $output = explode(".", $filename);

   header('Content-type: image/jpeg');

   list($width_orig, $height_orig) = getimagesize($filename);

   $ratio_orig = $width_orig/$height_orig;

   if ($width/$height < $ratio_orig) {
      $width_aspect = $height*$ratio_orig;
      $height_aspect = $height;
   } else {
      $height_aspect = $width/$ratio_orig;
      $width_aspect = $width;
   }

   $cropStartX = 0;
   $cropStartY = 0;

   $image = @ImageCreateFromJPEG ($filename) or // Read JPEG Image
   $image = @ImageCreateFromPNG ($filename) or // or PNG Image
   $image = @ImageCreateFromGIF ($filename) or // or GIF Image
   $image = false; // If image is not JPEG, PNG, or GIF

   if (!$image) {      // We get errors from PHP's ImageCreate functions...
   // So let's echo back the contents of the actual image.
   readfile ($filename);

   }
   else {

      if ($mode == 'crop') {
         $image_p = imagecreatetruecolor($width, $height);

      }

      if ($mode == 'fit') {
         $image_p = imagecreatetruecolor($width_aspect, $height_aspect);
      }

      imagecopyresampled($image_p, $image, 0, 0, $cropStartX, $cropStartY, $width_aspect, $height_aspect, $width_orig, $height_orig);

      // Output
      //imagejpeg($image_p, null, 100);

         // Create cache file
         $cache = "scache/";

         $path_parts = pathinfo($filename);

         $directory = $path_parts['dirname'];
         $imgfile = $path_parts['basename'];

         $new_imgfile = $width."x".$height."_".$imgfile;

         if(!is_dir($cache))
          {
             mkdir($cache, 0777);
          }

         if((!is_dir($cache.$directory)) && ($cache != ""))
          {
             mkdir($cache.$directory, 0777);
          }

       //CREATING DIRECTORIES...
       $directories = split('/', trim($directory,'/'));
       if(count($directories) > 0)
        {
          for($i=0; $i<count($directories); $i++)
           {

              $created_dir = $cache;

              for($z=0; $z<=$i; $z++)
               {
                  if($directories[$z] != "")
                  {
                     $created_dir = $created_dir."/".$directories[$z];
                     //echo $created_dir;
                     if(($created_dir!="") && (!is_dir($created_dir)))
                        {
                           mkdir($created_dir, 0777);
                            //chmod($created_dir, 0777);
                        }

                  }
               }

           }

        }

      $cache_path = $cache.$directory."/".$new_imgfile;

      imagejpeg($image_p, $cache_path, 100);

      $new_image = $cache_path;

   }

 }//end else

return $new_image;

}//end function

function resizePic($filename, $width, $height, $mode) {

$width_user = $width;
$height_user = $height;
$mode_user = $mode;

//for caching..
$newimage = $filename;
$dimention = $width."x".$height."_";
$path_parts = pathinfo($newimage);
$cache_imgfile = "scache/".$path_parts['dirname']."/".$dimention.$path_parts['basename'];

if(file_exists($cache_imgfile))
 {

   $new_image = $cache_imgfile;

 }
else
 {

   $output = explode(".", $filename);

   header('Content-type: image/jpeg');

   list($width_orig, $height_orig) = getimagesize($filename);
   $ratio_orig = $width_orig/$height_orig;

      if($width_orig > $height_orig){
                         $width = $width;
                         $height = $height/$ratio_orig;
                     }
                     else{
                        $height = $height;
                       $width = $widt*$ratio_orig;

                     }

                     if ($width/$height < $ratio_orig)
                     {
                      $width = $height*$ratio_orig;
                     }
                     else
                     {
                     $height = $width/$ratio_orig;
                     }

   $image = imagecreatefromjpeg($filename);

   if (!$image)
   {      // We get errors from PHP's ImageCreate functions...
   // So let's echo back the contents of the actual image.
   readfile ($filename);

   }
   else
   {

      $image_p = imagecreatetruecolor($width, $height);

   imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

   // Output
   //imagejpeg($image_p, null, 100);

      // Create cache file
      $cache = "scache/";

      $path_parts = pathinfo($filename);

      $directory = trim($path_parts['dirname']);
      $imgfile = trim($path_parts['basename']);

      $new_imgfile = $width_user."x".$height_user."_".$imgfile;

      if(!is_dir($cache))
       {
          mkdir($cache, 0777);
          chmod($cache, 0777);
        }

      /*if(!is_dir($cache.$directory))
       {
          mkdir($cache.$directory, 0777);
          //mkdir -p($cache.$directory, 0777);
          //mkdir -p Logs/UnitTest/foo;
       }*/

       //CREATING DIRECTORIES...
       $directories = split('/', trim($directory,'/'));
       if(count($directories) > 0)
        {
          for($i=0; $i<count($directories); $i++)
           {

              $created_dir = $cache;

              for($z=0; $z<=$i; $z++)
               {
                  if($directories[$z] != "")
                  {
                     $created_dir = $created_dir."/".$directories[$z];
                     //echo $created_dir;
                     if(($created_dir!="") && (!is_dir($created_dir)))
                        {
                           mkdir($created_dir, 0777);
                            //chmod($created_dir, 0777);
                        }

                  }
               }

           }

        }

      $cache_path = $cache.$directory."/".$new_imgfile;

      imagejpeg($image_p, $cache_path, 100);

      $new_image = $cache_path;

   }

 }//end else

return $new_image;

}//end function


function pauResizePic($filename, $width, $height) {

     $width_user = $width;
     $height_user = $height;

     //for caching..
     $newimage = $filename;
     $dimention = $width."x".$height."_";
     $path_parts = pathinfo($newimage);
     $cache_imgfile = "ftp-web/images/cheerdancers/cached/".$dimention.$path_parts['basename'];

     if(file_exists($cache_imgfile)){
          $new_image = $cache_imgfile;
     }else{
          $output = explode(".", $filename);
          header('Content-type: image/jpeg');
          list($width_orig, $height_orig) = getimagesize($filename);
          $ratio_orig = $width_orig/$height_orig;
          if($width_orig > $height_orig){
               $width = $width;
               $height = $height/$ratio_orig;
          }else{
               $height = $height;
               $width = $widt*$ratio_orig;
          }

          if ($width/$height < $ratio_orig){
               $width = $height*$ratio_orig;
          }else{
               $height = $width/$ratio_orig;
          }

          $image = imagecreatefromjpeg($filename);
          $image_p = imagecreatetruecolor($width, $height);
          imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
          // Output
          //imagejpeg($image_p, null, 100);
          // Create cache file          
        
          imagejpeg($image_p, $cache_imgfile, 100);
          $new_image = $cache_imgfile;
     
     }//end else
     return $new_image;

}//end function
?>