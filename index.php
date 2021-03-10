<?php
/*
 * Wordpress.com Media Library Export
 * https://homebrewgr.info/en/projects/script-download-your-entire-wordpresscom-media-library/
 * Author Kraken

 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.

 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. 
*/

//You have to put this file into a temporary folder (eg "temp")

########################################################################################
header("Content-type: text/html; charset=UTF-8");

/* Set internal character encoding to UTF-8 */
mb_internal_encoding("UTF-8");

//Set some config
ini_set('memory_limit', '128M');
set_time_limit(0);
########################################################################################
//
//Configuration
//
########################################################################################
//Enter your filename here
$xml_file = '';

//You can change this, to your timezone or leave it as it is
if (function_exists('date_default_timezone_set') && !ini_get('date.timezone') )
{
	date_default_timezone_set("America/Los_Angeles");
}

//Unset it if you want to see the errors
//error_reporting(E_ALL);

##############################################
//Configuration END
//
//DO NOT EDIT BELOW THIS LINE
##############################################

$dir = getcwd() . '/';

if ( !file_exists( $dir . $xml_file ) || empty( $xml_file ) )
	die("Can't find the XML file...");

//Uploads Folder
$uploads_folder = $dir . 'uploads/';


if (!is_dir($uploads_folder))
	mkdir($uploads_folder, 0755, true) or die ('Could not create folder ' . $uploads_folder);


$xml = simplexml_load_string(file_get_contents($xml_file));

if ($xml === false)
	die("Error loading the XML data...");

foreach($xml->channel->item as $item) 
{
		
	$date = $item->xpath('wp:post_date');
	$date = (string) $date['0'];
	
	$p_id = $item->xpath('wp:post_id');
	$p_id = (int) $p_id['0'];
	
	$attachment = $item->xpath('wp:attachment_url');
	$attachment = (string) $attachment['0'];
	
	if (empty ($attachment) )
		continue;
		
	$title = (string) $item->title;
	
	$unix_date = strtotime($date);
	
	$year_folder = $uploads_folder . date('Y', $unix_date);
	$month_folder = $year_folder . '/' . date('m', $unix_date) . '/';
	
	if (!is_dir($year_folder))
		mkdir($year_folder, 0755, true) or die ('Could not create folder ' . $year_folder);
	
	if (!is_dir($month_folder))
		mkdir($month_folder, 0755, true) or die ('Could not create folder ' . $month_folder);
	
	$filename = pathinfo($attachment, PATHINFO_FILENAME) . '.' . pathinfo($attachment, PATHINFO_EXTENSION);
	
	if (create_image($attachment, $month_folder, $filename))
		echo $title . ' copied<br />';
	
}

//We can now tell that it's done
echo '<br />All done. Your library has been downloaded.<br />';
########################################################################################
//
//
//
########################################################################################

function create_image($img_link, $upload_path, $name)
{

	$ext = pathinfo($img_link, PATHINFO_EXTENSION);
		
	if (is_file($upload_path . $name)) {
		echo 'File "' . $name . '" already exists<br />';
		return;
	}

	// try copying it... if it fails, go to backup method.
	if ( !@copy($img_link, $upload_path . $name) ) {
		
		//	create a new image
		list($img_width, $img_height, $img_type, $img_attr) = @getimagesize($img_link);

		$image = '';

		switch ($img_type) {
			case 1:
				//GIF
				$image = imagecreatefromgif($img_link);
				$ext = ".gif";
				break;
			case 2:
				//JPG
				$image = imagecreatefromjpeg($img_link);
				$ext = ".jpg";
				break;
			case 3:
				//PNG
				$image = imagecreatefrompng($img_link);
				$ext = ".png";
				break;
		}
			
		$newwidth = $img_width;
		$newheightt = $img_height;

		$resource = @imagecreatetruecolor($newwidth, $newheight);
		
		if (function_exists('imageantialias'))
			@imageantialias($resource, true);
			
		@imagecopyresampled($resource, $image, 0, 0, 0, 0, $newwidth, $newheight, $img_width, $img_height);
		
		@imagedestroy($image);
		

		switch ($img_type) {
			default:
			case 1:
				//GIF
				@imagegif($resource, $upload_path . $name);
				break;
			case 2:
				//JPG
				@imagejpeg($resource, $upload_path . $name);
				break;
			case 3:
				//PNG
				@imagepng($resource, $upload_path . $name);
				break;
		}
		

			if ($resource === '')
				return false;

		}

		return true;
	}
?>
