<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>photocap</title>
<!--
	Runnable via 
	  php -S localhost:8000
	or similar
-->
<style>
img, textarea {
	width:500px;
}
textarea {
	height:100px;
}
.remove{
	opacity: .25;
}
label {
	font-size: 40px;
	text-decoration: underline;
}
input[type='submit'] {
	width: 135px;
	height: 120px;
}
figure{
	margin:20px 0px;
}
</style>
<script>
function toggleImage(checkboxElem){
	var image = checkboxElem.parentNode.previousElementSibling;
	var text =  checkboxElem.parentNode.nextElementSibling.nextElementSibling;
	if(checkboxElem.checked){
		addClass(image,'remove');
		addClass(text,'remove');
	} else {
		removeClass(image,'remove');
		removeClass(text,'remove');
	}
}
function addClass(el,className){
	if (el.classList) el.classList.add(className);
	else el.className += ' ' + className;
}
function removeClass(el,className){
	if (el.classList) el.classList.remove(className);
    else el.className = el.className.replace(new RegExp('(^|\\b)' + 
    	className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
}
</script>
</head>
<body>
<form method="POST">

<?php

	$dir = ".";
	$captions = array();

	if(file_exists("CAPTIONS.txt")){
		$raw = file_get_contents("CAPTIONS.txt");
		$lines = explode("\n",$raw);
		foreach($lines as $line){
			#print "GOT $line\n";
			$parts = explode("\t",$line);
			$captions[$parts[0]] = $parts[1];
		}
	}

	if($_GET['display'] == 'fb' || $_GET['display'] == 'web'){
		displayDump($_GET['display'] == 'web');
	} else {
		print '<a href="?">edit</a> | <a href="?display=fb">fb</a> | <a href="?display=web">web</a><br><br>';

		print '<input type="submit" value="save all"><br><br>';
		$files = array_diff(scandir($dir), array('..', '.'));

		foreach($files as $file){
			$keytext = "text_".base64_encode($file);
			$keykill = "kill_".base64_encode($file);
			if( isset($_POST[$keykill])){
				#print "KILL $file\n";
				if (!file_exists('killem')) {
	    			mkdir('killem', 0777, true);
				}			 
				rename($file,'killem/'.$file);
			} else {
				if( isset($_POST[$keytext])){
					#print "GOT $file $keytext : ".$_POST[$keytext]."<br>";
					$captions[$file] = $_POST[$keytext];
				}
			}
		}

		$files = array_diff(scandir($dir), array('..', '.')); #in case files got removed

		$buf = "";
		foreach($captions as $file=>$caption){
			if($file != '' && $caption != ''){
				$caption = str_replace("\n"," ",$caption);
				$caption = str_replace("\r","",$caption);
				$buf .= "$file\t$caption\n";
			}
		}
		file_put_contents("CAPTIONS.txt", $buf);

		foreach($files as $file){
			#only deal with things in arr
			if(isImageFilename($file)){
				$oldcaption = htmlspecialchars($captions[$file]);
				$filecode = base64_encode($file); ##php doesn't allow filenames with . :-( 
				print <<<__EOQ__
<hr>
$file<br><br>			
<img src='$file'>
<label><input name="kill_$filecode" type='checkbox' onclick="toggleImage(this)">delete</label><br>
<textarea name="text_$filecode">$oldcaption</textarea>
<hr>
__EOQ__;
		} 

	}
	print '<br><input type="submit" value="save all"><br>';
}


function displayDump($isWeb){
	global $dir, $captions;

	if($isWeb) print "<pre>";
	$files = array_diff(scandir($dir), array('..', '.')); 
	foreach($files as $file){
		if(isImageFilename($file)){
			$url_prefix = "";
			$thumbnail = $file;
			if($isWeb){
				$url_prefix = "[[urlprefix]]";
				#my blogs goofball renaming of thumbnails...
				$thumbnail = preg_replace('/([^\.]*)\.([^\.]?)/','$1_560.$2',$file);
			}
			$oldcaption = htmlspecialchars($captions[$file]);
			$display = "<figure>\n<a href='$url_prefix$file'><img src='$url_prefix$thumbnail'></a>\n";
			if($oldcaption) $display .= "<figcaption>$oldcaption</figcaption>\n";
			$display .= "</figure>\n\n";
			if($isWeb) $display = htmlspecialchars($display);

			print $display;
		}
	}
	if($isWeb) print "</pre>";
}

function isImageFilename($file){
	return in_array(strtolower(substr($file, -4)),array(".jpg",".png"));
}
?>

</form>
</body>
</html>