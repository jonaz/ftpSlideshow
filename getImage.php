<?php

require_once('config.php');

if(!isset($paths[$_GET['folder']]))
	return false;

$localTempDir = $localTempDir.'/'.$paths[$_GET['folder']];
if(!is_dir($localTempDir))
	mkdir($localTempDir,0777,true);

if( $files = getFiles($localTempDir)){
	foreach($files as $key=>$file){
		$path = $localTempDir.'/'.$file;
		$fileAge = time()-filemtime($path);
		if($fileAge > 86400){
			$doFetch = true;
			break;
		}
		//echo "$path  $fileAge<br>";
	}

}else
	$doFetch = true;


if($doFetch){
	$conn_id = ftp_connect($host);
	$login_result = ftp_login($conn_id, $user, $password);
	$filesToDownload = ftp_nlist($conn_id, "./".$paths[$_GET['folder']]);

	foreach($filesToDownload as $file){
		$path = $paths[$_GET['folder']].'/'.$file;
		$localPath = $localTempDir.'/'.$file;
		if (ftp_get($conn_id, $localPath, $path, FTP_BINARY)) {
			//echo "Successfully written to $localPath<br>";
		} else {
			//echo "There was a problem<br>";
		}
	}

	$files = getFiles($localTempDir);
}


if(isset($_GET['count'])){
	$count = count(getFiles($localTempDir));
	echo '{"count":'.$count.'}';
	return;
}

if(isset($files[$_GET['image']])){
	displayImage($localTempDir.'/'.$files[$_GET['image']]);
}


function getFiles($path){
	$files = array();
	if($files = scandir($path)){
		foreach($files as $key=>$file){
			if($file == '.' || $file == '..' || $file == 'Thumbs.db'  || $file == 'thumbs'){
				unset($files[$key]);
			}
		}
	}
	$files = array_values($files);
	return $files;
}

function displayImage($path){
	$type = image_type_to_mime_type(exif_imagetype($path));

	if(isset($_GET['s']) ){

		$new_width = $_GET['s'];
		$new_height = $_GET['s'];

		if($type=='image/png'){ $src_img = imagecreatefrompng($path); }
		if($type=='image/jpg'){ $src_img = imagecreatefromjpeg($path); }
		if($type=='image/jpeg'){ $src_img = imagecreatefromjpeg($path); }
		if($type=='image/pjpeg'){ $src_img = imagecreatefromjpeg($path); }

		$old_x          =   imageSX($src_img);
		$old_y          =   imageSY($src_img);

		if($old_x > $old_y) {
			$thumb_w    =   $new_width;
			$thumb_h    =   $old_y*($new_height/$old_x);
		}

		if($old_x < $old_y) {
			$thumb_w    =   $old_x*($new_width/$old_y);
			$thumb_h    =   $new_height;
		}

		if($old_x == $old_y) {
			$thumb_w    =   $new_width;
			$thumb_h    =   $new_height;
		}

		$pathArray = explode('/',$path);
		$file = $pathArray[count($pathArray)-1];
		array_pop($pathArray);
		$pathArray[] = 'thumbs';
		$path = implode('/',$pathArray);
		if(!is_dir($path))
			mkdir($path);
		$new_thumb_loc = $path.'/'.$file.$thumb_w.$thumb_h;
		if(is_file($new_thumb_loc)){
			header('Content-Type:'.$type);
			header('Content-Length: ' . filesize($new_thumb_loc));
			readfile($new_thumb_loc);
			return;
		}

		$dst_img        =   ImageCreateTrueColor($thumb_w,$thumb_h);

		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y); 


		// New save location
		//$new_thumb_loc = $moveToDir . $image_name;

		if($type=='image/png'){ $result = imagepng($dst_img,$new_thumb_loc,8); }
		if($type=='image/jpg'){ $result = imagejpeg($dst_img,$new_thumb_loc,80); }
		if($type=='image/jpeg'){ $result = imagejpeg($dst_img,$new_thumb_loc,80); }
		if($type=='image/pjpeg'){ $result = imagejpeg($dst_img,$new_thumb_loc,80); }

		imagedestroy($dst_img); 
		imagedestroy($src_img);
		header('Content-Type:'.$type);
		header('Content-Length: ' . filesize($new_thumb_loc));
		readfile($new_thumb_loc);
		return;
	}

	header('Content-Type:'.$type);
	header('Content-Length: ' . filesize($path));
	readfile($path);

}
