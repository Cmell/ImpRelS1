<?php

function RedirectToURL($url)
{
    header("Location: $url");
    exit;
}

function SetWebsiteName($sitename)
{
    $this->sitename = $sitename;
}

function getWords($flName) {
  // Get the list of words and return it as an array.
  if (($fp = fopen($flName, "r")) === FALSE) {
    throw new Exception("Couldn't open file!");
  }
  $words = array();
  while (($data = fgetcsv($fp, 1000, ",")) !== FALSE) {
    $words[] = $data[0];
  }
  return($words);
}

function rndmCode($codeForm = null) {
  // This function will generate a random code.
  // The codeForm argument should be comprised of  the letters "n" and "l".
  // For each "n", a random integer (0-9) is chosen. For each "l" a random
  // letter is chosen (A-X). Only uppercase is currently supported.
  // Default is "nnnlll", which is 3 numbers followed by 3 letters.
  if ($codeForm === null) {
    $codeForm = "nnnlll";
  }
  $codeForm = str_split($codeForm);
  $code = "";

  foreach ($codeForm as $i) {
    if ($i == 'n') {
      $code = $code.chr(mt_rand(48,57));
    } elseif ($i == 'l'){
      $code = $code.chr(mt_rand(65,90));
    }
  }

  return($code);
}
?>
