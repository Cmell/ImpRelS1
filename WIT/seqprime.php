<?php
require_once('../Resources/Util.php');
require_once('../Resources/pInfo.php');
session_start();
?>

<!doctype html>
<html>
<head>
  <title>Experiment</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
	<script src="../Resources/jspsych-5.0.3/jspsych.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-sequential-priming.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-text.js"></script>
  <script src="../Resources/jspsych-5.0.3/plugins/jspsych-call-function.js"></script>
  <script src="https://cdn.rawgit.com/Cmell/JavascriptUtilsV9-20-2017/master/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
	<link href="../Resources/jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<body>

</body>
<script>
  // Define vars
  var seed, pid, condition, taskTimeline;
  var leftKeyCode, rightKeyCode, correct_answer, target1KeyCode, target2KeyCode;
  var mask, redX, check, expPrompt;
  var instr1, instructStim, countdown, countdownNumbers;
  var timeline = [];
  var numTrials = 80;
  var timing_parameters = [200, 200, 200, 800];
  var primeSize = [240, 336];
  var targetSize = [380, 380]

  // The timing_parameters should correspond to the planned set of stimuli.
  // In this case, I'm leading with a mask (following Ito et al.), and then
  // the prime, and then the stimulus, and then the mask until the end of the
  // trial.

  // Key parameters:
  prime1Label = "Black";
  prime2Label = "White";
  target1Label = "Gun";
  target2Label = "Non-Gun";

  // get the pid:
  <?php
  // Grab the condition
  if (mt_rand(0,1)) {
    $condition = 'HighVar';
  } else {
    $condition = 'LowVar';
  }
  // Get the pid:
  $pid = getNewPID("../Resources/PID.csv", Array($condition));
  echo "pid = ".$pid.";";
  echo "condition = '".$condition."';";
  ?>

  d = new Date();
  seed = d.getTime();
  Math.seedrandom(seed);

  // Some utility variables
  var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s

  var flPrefix = "Data/WIT_"

  var filename = flPrefix + pidStr + "_" + seed + ".csv";

  var fields = [
    "pid",
    "condition",
    "target1_key",
    "target2_key",
    "internal_node_id",
    "key_press",
    "left_target",
    "right_target",
    "seed",
    "trial_index",
    "trial_type",
    "trial_num",
    "target_id",
    "target_type",
    "prime_type",
    "prime_id",
    "rt",
    "time_elapsed",
    "rt_from_start",
    "correct"
  ]

  // Choose keys:
  leftKey = "e";
  rightKey = "i";
  leftTarget = rndSelect([target1Label, target2Label], 1);
  rightTarget = leftTarget == target1Label ? target2Label : target1Label;
  target1Key = rightTarget == target1Label ? rightKey : leftKey;
  target2Key = rightTarget == target2Label ? rightKey : leftKey;
  leftKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(leftKey);
  rightKeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(rightKey);
  target1KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(target1Key);
  target2KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(target2Key);

  // Append pid and condition information to all trials, including my
  // trialNum tracking variable (dynamically updated).
  jsPsych.data.addProperties({
    pid: pid,
    seed: seed,
    condition: condition,
    target1_key: target1Key,
    target2_key: target2Key,
    left_target: leftTarget,
    right_target: rightTarget
  });

  // Save data function
  var saveAllData = function () {
    var filedata = jsPsych.data.dataAsCSV;
    // send it!
  	sendData(filedata, filename);
  };

  var endTrial = function (trialObj) {
    // Extract trial information from the trial object adding data to the trial
    var trialCSV = trialObjToCSV(trialObj);
    sendData(trialCSV, filename);
  };

  var generateHeader = function () {
    var line = '';
    var f;
    var fL = fields.length;
    for (i=0; i < fL; i++) {
      f = fields[i];
      if (i < fL - 1) {
        line += f + ',';
      } else {
        // don't include the comma on the last one.
        line += f;
      }
    }

    // Add an eol character or two
    line += '\r\n';
    return(line);
  };

  var sendHeader = function () {
    sendData(generateHeader(), filename);
  }

  var trialObjToCSV = function (t, extras) {
    // t is the trial object
    var f;
    var line = '';
    var fL = fields.length;
    var thing;

    for (i=0; i < fL; i++) {
      f = fields[i];
      thing = typeof t[f] === 'undefined' ? 'NA' : t[f];
      if (i < fL - 1) {
        line += thing + ',';
      } else {
        // Don't include the comma on the last one.
        line += thing;
      }
    }
    // Add an eol character or two
    line += '\r\n';
    return(line);
  };

  // Initialize the data file
  sendHeader();

  // Load instruction strings
  if (target1KeyCode == 69) {
    instr1 = <?php
    $flName = "./Texts/InstructionsScreen1e-gun.txt";
    $myfile = fopen($flName, "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize($flName)));
    fclose($myfile);
    ?>

  } else {
    instr1 = <?php
    $flName = "./Texts/InstructionsScreen1e-nogun.txt";
    $myfile = fopen($flName, "r") or die("Unable to open file!");
    echo json_encode(fread($myfile,filesize($flName)));
    fclose($myfile);
    ?>

  }

  // Make the expPrompt
  expPrompt = '<table style="width:100%; text-align:center">'
  + '<tr"> \
  <th style="width:50%">"' +
  leftKey + '":' +
  '</th> \
  <th style="width:50%">"' + rightKey + '":' +
  '</th>\
  </tr>' +
  '<tr>\
  <th style="width:50%">' +
  leftTarget +
  '</th> \
  <th style="width:50%">' + rightTarget +
  '</th>\
  </tr>'
  '</table>';

  // Make the instruction stimulus.
  instructStim = {
    type: "text",
    text: instr1,
    cont_key: [32]
  };

  // Make a countdown sequence to begin the task
  countdownNumbers = [
    '<div id="jspsych-countdown-numbers">3</div>',
    '<div id="jspsych-countdown-numbers">2</div>',
    '<div id="jspsych-countdown-numbers">1</div>'
  ]
  countdown = {
    type: "sequential-priming",
    stimuli: countdownNumbers,
    is_html: [true, true, true],
    choices: [],
    prompt: expPrompt,
    timing: [1000, 1000, 1000],
    response_ends_trial: false,
    feedback: false,
    timing_post_trial: 0,
    iti: 0
  };

  // Load stimulus lists

  // primes:
  prime1Fls = <?php
    echo json_encode(glob("../Resources/".$condition."/Black/*.jpg"));
    ?>;
  prime2Fls = <?php
    echo json_encode(glob("../Resources/".$condition."/White/*.jpg"));
    ?>;

  // targets:
  target1Fls = <?php echo json_encode(glob('../Resources/guns/*.png')); ?>;
  target2Fls = <?php echo json_encode(glob('../Resources/nonguns/*.png')); ?>;
  // TODO: Change the background of the target objects to alpha channel

  // Put the stimuli in lists with the relevant information.

  var makeStimObjs = function (fls, condVar, condValue) {
    var tempLst = [];
    var tempObj;
    for (i=0; i<fls.length; i++) {
      fl = fls[i];
      flVec = fl.split("/");
      tempObj = {
        file: fl,
        stId: flVec[flVec.length-1]
      };
      tempObj[condVar] = condValue;
      tempLst.push(tempObj);
    }
    return(tempLst);
  };

  /*
  // Don't need this one right now because everything is images.
  var makeWordObjs = function (words, condValue) {
    var tempLst = [];
    var tempObj;
    for (i=0; i<words.length; i++) {
      var w = words[i];
      var htmlStr = '<h2 style="text-align:center;font-size:90px;margin:0;">' + w + '</h2>';
      tempObj = {
        valence: condValue,
        word: w,
        html: htmlStr
      };
      tempLst.push(tempObj);
    }
    return(tempLst);
  };
  */

  // Add a "thank you trial"
  var thankyouTrial = {
    type: "text",
    text: 'Thank you! Please let the experimenter know you are finished.',
    cont_key: [32]
  };

  var prime1Lst = makeStimObjs(prime1Fls, "prime_type", prime1Label);
  var prime2Lst = makeStimObjs(prime2Fls, "prime_type", prime2Label);
  var target1Lst = makeStimObjs(target1Fls, "target_type", target1Label);
  var target2Lst = makeStimObjs(target2Fls, "target_type", target2Label);

  mask = "MaskReal.png";
  fixCross = "FixationCross380x380.png";
  redX = "XwithSpacebarMsg.png";
  check = "CheckReal.png";
  tooSlow = "TooSlow.png";
  blank = "Blank.png"

  // utility sum function
  var sum = function (a, b) {
    return a + b;
  };

  // Recycle each list to the length of each trial subset. Then shuffle each
  // subset list's primes. Then, pairs are randomized, and trials can be
  // randomized later.

  var rndmTarget1 = randomRecycle(target1Lst, numTrials/2);
  var rndmTarget2 = randomRecycle(target2Lst, numTrials/2);
  var rndmPrime1 = shuffle(randomRecycle(prime1Lst, numTrials/2));
  var rndmPrime2 = shuffle(randomRecycle(prime2Lst, numTrials/2));
  targets = rndmTarget1.concat(rndmTarget2);
  primes = rndmPrime1.slice(0,rndmPrime1.length/2).concat(
    rndmPrime2.slice(0,rndmPrime2.length/2),
    rndmPrime1.slice(rndmPrime1.length/2, rndmPrime1.length),
    rndmPrime2.slice(rndmPrime2.length/2, rndmPrime2.length)
  );

  // Make all the trials and timelines.
  taskTrials = {
    type: "sequential-priming",
    choices: [leftKeyCode, rightKeyCode],
    prompt: expPrompt,
    timing_stim: timing_parameters,
    is_html: [false,false,false,false],
    response_ends_trial: true,
    timeline: [],
    timing_response: timing_parameters[2] + timing_parameters[3],
    response_window: [timing_parameters[0] + timing_parameters[1], Infinity],
    feedback: true,
    key_to_advance: 32,
    //feedback_duration: 1000, // Only activate these if the check should show.
    //correct_feedback: check,
    incorrect_feedback: redX,
    timeout_feedback: tooSlow,
    timing_post_trial: 0,
    iti: 800,
    on_finish: endTrial
  };

  for (i=0; i<numTrials; i++){
    correct_answer = targets[i].target_type == target1Label ? target1KeyCode : target2KeyCode;
    tempTrial = {
      stimuli: [fixCross, primes[i].file, targets[i].file, mask],
      data: {
        prime_cat: primes[i].prime_type,
        target_type: targets[i].target_type,
        prime_id: primes[i].stId,
        target_id: targets[i].stId,
        trial_num: i + 1
      },
      correct_choice: correct_answer
    };
    taskTrials.timeline.push(tempTrial);
  }
  // Randomize trial order here:
  taskTrials.timeline = shuffle(taskTrials.timeline);

  // Push everything to the big timeline in order
  timeline.push(instructStim);
  timeline.push(countdown);
  timeline.push(taskTrials);
  //timeline.push(saveCall);
  timeline.push(thankyouTrial);

  // try to set the background-color
  document.body.style.backgroundColor = '#ffffff';

  // Preload all stimuli
  var allTargets = target1Fls.concat(target2Fls);
  var allPrimes = prime1Fls.concat(prime2Fls);
  var imgNamesSizes = [];
  for (var i = 0; i < allPrimes.length; i++) {
    imgNamesSizes[i] = [allPrimes[i], primeSize];
  }
  var tempLength = imgNamesSizes.length;
  for (var i = 0; i < allTargets.length; i++) {
    imgNamesSizes[i + tempLength] = [allTargets[i], targetSize];
  }
  imgNamesSizes = imgNamesSizes.concat([ //imgNamesArr = imgNamesArr.concat([
    ['./TooSlow.png', [201, 380]],
    ['./XwithSpacebarMsg.png', [285, 380]],
    ['./MaskReal.png', targetSize],
    ['./FixationCross380x380.png', targetSize]
  ]);
  window.allWITImages = preloadResizedImages(imgNamesSizes);

  var startExperiment = function () {
    jsPsych.init({
    	timeline: timeline,
      fullscreen: true
    });
  };
  startExperiment();

</script>
</html>
