<?php
//test array concatination
$groovyArray = array("me","oh","my");
print_r($groovyArray);
echo "<br/>";
$groovyArray[]="whaaa";
print_r($groovyArray);
echo "<br/>";
$groovyArray = "oh boy";
print_r($groovyArray);
echo "<br/>";

//testing preg_split
$header = "0-524287/2000000";
$content_range = preg_split('/[^0-9]+/', $header);

print_r($content_range);

?>
