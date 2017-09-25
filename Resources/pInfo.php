<?php
// This script includes functions to generate new pids and
// save condition information to the pid file.

function getNewPID($pidFile, $pInfo = null) {
  // Get default pinfo
  if ($pInfo === null) {
    $pInfo = array();
  }
  
  if (($fp = fopen($pidFile, "r+")) === FALSE) {
    throw new Exception("Couldn't open pid file!");
  }

  // Get the lock.
  $maxTries = 10;
  $numTries = 0;
  $gotLock = FALSE;
  while (!$gotLock && $numTries < $maxTries) {
    $numTries++;
    // figure out the new id and write it.
    $gotLock = flock($fp, LOCK_EX);
    if ($gotLock) {break;}
    sleep(1);
  }

  // Find the appropriate pid
  if ($gotLock) {
    $largestPid = 0;
    // Figure out the largest id and add one to it.
    while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
      if ($largestPid < (int)$data[0]) {
        $largestPid = (int)$data[0];
      }
    }
    $pid = $largestPid + 1;
    date_default_timezone_set('America/Denver');
    $date = date ('m-d-Y H:i:s');

    // Write the id and condition information to the pid file.
    $newFields = array($pid, $date);
    $allFields = array_merge($newFields, $pInfo);
    fputcsv($fp, $allFields);

    // close the file connection and lock
    flock($fp, LOCK_UN);
    fclose($fp);

    // return it
    return($pid);
  } else {
    throw new Exception("No lock on pid file!");
  }
}
?>
