<?php
// $_POST contains the info passed to the script.
$filename = "./".$_POST['filename'];
$data = $_POST['filedata'];

// write to disk
file_put_contents($filename, $data, FILE_APPEND);
?>
