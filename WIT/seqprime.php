<?php
require_once('../Resources/Util.php');
require_once('../Resources/pInfo.php');
session_start();
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Experiment</title>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
	<script src="../Resources/jspsych-6.0.5/jspsych.js"></script>
  <script src="../Resources/jspsych-6.0.5/plugins/jspsych-sequential-priming.js"></script>
  <script src="../Resources/jspsych-6.0.5/plugins/jspsych-instructions.js"></script>
  <script src="https://cdn.rawgit.com/Cmell/JavascriptUtilsV9-20-2017/master/Util.js"></script>
  <script src='../Resources/ModS3JSUtil.js'></script>
	<link href="../Resources/jspsych-6.0.5/css/jspsych.css" rel="stylesheet" type="text/css"></link>
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
  //var numTrials = 120;
  /*
  This task is constructed so that each person sees each combination of prime
  and target twice. That yields 288 trials (12 primes by 12 targets yields 144
  combinations; 144 * 2 = 288).
  */
  var timing_parameters = [200, 200, 200, 800];
  var primeSize = [240, 336];
  var targetSize = [380, 380]

  // The timing_parameters should correspond to the planned set of stimuli.
  // In this case, I'm leading with a mask, and then
  // the prime, and then the stimulus, and then the mask until the end of the
  // trial.

  // Key parameters:
  var prime1Label = "Black";
  var prime2Label = "White";
  var target1Label = "Gun";
  var target2Label = "Tool";

  // get the pid:
  <?php
  // Grab the condition
  if (mt_rand(0,1)) {
    $condition = 'HighVar';
  } else {
    $condition = 'LowVar';
  }
  // Get the pid. If it is passed in, then use that value. Otherwise, generate:
  if (isset($_GET['pid'])) {
    $pid = $_GET['pid'];
  } else {
    $pid = getNewPID("../Resources/PID.csv", Array($condition));
  }
  echo "pid = ".$pid.";";
  echo "condition = '".$condition."';";
  ?>

  // Load files

  // primes:
  var prime1Fls = <?php
    echo json_encode(glob("../Resources/".$condition."/Black/*.jpg"));
    ?>;
  var prime2Fls = <?php
    echo json_encode(glob("../Resources/".$condition."/White/*.jpg"));
    ?>;


  // targets:

  //var target1Fls = <?php echo json_encode(getWords('../Resources/good.csv')); ?>;
  //var target2Fls = <?php echo json_encode(getWords('../Resources/bad.csv')); ?>;
  var target1Fls = <?php echo json_encode(glob("../Resources/guns/*.png"));?>;
  var target2Fls = <?php echo json_encode(glob("../Resources/nonguns/*.png"));?>;

  var allTargets = target1Fls.concat(target2Fls);
  var allPrimes = prime1Fls.concat(prime2Fls);

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
    "replication",
    "rt",
    "time_elapsed",
    "rt_from_start",
    "correct"
  ]

  // Choose keys:
  leftKey = "e";
  rightKey = "i";
  leftTarget = rndSelect([target1Label, target2Label], 1)[0];
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
  expPrompt = '<table style="width:300px; text-align:center; margin:auto">'
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
    type: "instructions",
    pages: [instr1],
    key_forward: 32
  };

  // Make a countdown sequence to begin the task
  countdownNumbers = [
    '<div id="jspsych-countdown-numbers">3</div>',
    '<div id="jspsych-countdown-numbers">2</div>',
    '<div id="jspsych-countdown-numbers">1</div>'
  ];
  countdown = {
    type: "sequential-priming",
    stimuli: countdownNumbers,
    is_html: [true, true, true],
    choices: [],
    prompt: expPrompt,
    timing_stim: [1000, 1000, 1000],
    response_ends_trial: false,
    feedback: false,
    timing_post_trial: 0,
    iti: 0
  };

  // Load stimulus lists

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

  var makeWordObjs = function (words, condVar, condValue) {
    var tempLst = [];
    var tempObj;
    for (i=0; i<words.length; i++) {
      var w = words[i];
      var htmlStr = '<h2 style="text-align:center;font-size:90px;margin:0;">' + w + '</h2>';
      tempObj = {
        word: w,
        html: htmlStr
      };
      tempObj[condVar] = condValue;
      tempLst.push(tempObj);
    }
    return(tempLst);
  };

  // Add a "thank you trial"
  // TODO: Forward people to a survey.

  var prime1Lst = makeStimObjs(prime1Fls, "prime_type", prime1Label);
  var prime2Lst = makeStimObjs(prime2Fls, "prime_type", prime2Label);
  var target1Lst = makeStimObjs(target1Fls, "target_type", target1Label);
  var target2Lst = makeStimObjs(target2Fls, "target_type", target2Label);

  mask = "MaskReal.png";
  fixCross = "FixationCross380x380.png";
  redX = "XReal.png";
  check = "CheckReal.png";
  tooSlow = "TooSlow.png";
  blank = "Blank.png";

  // utility sum function
  var sum = function (a, b) {
    return a + b;
  };

  //  Each person should see each combination of prime and target twice.

  // Combinations:
  var allPrimeLst = prime1Lst.concat(prime2Lst);
  var allTargetLst = target1Lst.concat(target2Lst);
  var combos = [];
  for (var i = 0; i < allPrimeLst.length; i++) {
    for (var a=0; a < allTargetLst.length; a++) {
      var curP = allPrimeLst[i];
      var curT = allTargetLst[a];
      combos.push({
        prime: curP,
        target: curT
      });
    }
  }

  // Twice, assigning set A and set B:
  var combos2 = jQuery.extend(true, [], combos); // Copies the combos I think
  combos = combos.concat(combos2);
  for (var i=0; i < combos.length; i++) {
    var rep = '';
    if (i < combos.length / 2) {
      rep = 'A';
    } else if (i >= combos.length / 2) {
      rep = 'B';
    }
    combos[i].replication = rep;
  }

  /*
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
  */

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
    //key_to_advance: 32,
    feedback_duration: 1000, // Only activate these if the check should show.
    //correct_feedback: check,
    incorrect_feedback: redX,
    correct_feedback: 'AllAlpha.png',
    timeout_feedback: tooSlow,
    timing_post_trial: 0,
    iti: 800,
    on_finish: endTrial
  };

  feedbackTrials = {
    type: "instructions",
    key_forward: 32,
    allow_backward: false,
    on_start: function (trl) {
      var countRight = function (a, b) {
        return(a + (b.correct=='true'));
      };
      // Get sequential priming trials and calculate accuracy
      var d = jsPsych.data.get();

      d = d.filterCustom(function (t) {
        var TorF = t.trial_type == 'sequential-priming' && typeof t.target_id !== 'undefined';
        return(TorF);
      });
      var cor = d.select('correct');
      var rates = cor.frequencies();
      var nTrials = d.count();
      var nCorrect = rates['true'];
      if (typeof nCorrect === 'undefined') {
        nCorrect = 0;
      }

      accRate = Math.round(100 * nCorrect / nTrials);

      var txt = '<p>Your accuracy rate so far:</p><h2>' + accRate + '&#x25;\
      </h2><p>\
      Press the spacebar to continue.\
      </p>';
      trl.pages = [txt];
    }
  };

  // Randomize trial order here:
  combos = shuffle(combos);

  for (i=0; i<combos.length; i++){
    var curTrial = combos[i];
    var curPrime = curTrial.prime;
    var curTarget = curTrial.target;
    //correct_answer = targets[i].word_type == target1Label ? target1KeyCode : target2KeyCode;
    correct_answer = curTarget.target_type == target1Label ? target1KeyCode : target2KeyCode;
    tempTrial = {
      //stimuli: [fixCross, primes[i].file, targets[i].html, mask],
      stimuli: [fixCross, curPrime.file, curTarget.file, mask],
      data: {
        task_trial: 'yes', // This can be used to distinguish task trials
        // from other sequential priming trials.
        prime_type: curPrime.prime_type,
        target_type: curTarget.target_type,
        prime_id: curPrime.stId,
        target_id: curTarget.stId,
        replication: curTrial.replication,
        trial_num: i + 1
      },
      correct_choice: correct_answer
    };
    /*
    if (i == 3) {
      taskTrials.timeline.push(feedbackTrials);
    }
    */
    taskTrials.timeline.push(tempTrial);
  }

  // Push everything to the big timeline in order
  timeline.push(instructStim);
  timeline.push(countdown);
  timeline.push(taskTrials);
  //timeline.push(saveCall);
  //timeline.push(thankyouTrial);

  // try to set the background-color
  document.body.style.backgroundColor = '#ffffff';

  // Preload all images manually
  var imgNamesSizes = [];
  for (var i = 0; i < allPrimes.length; i++) {
    imgNamesSizes[i] = [allPrimes[i], primeSize];
  }

  imgNamesSizes = imgNamesSizes.concat([ //imgNamesArr = imgNamesArr.concat([
    ['./TooSlow.png', [201, 380]],
    ['./AllAlpha.pn', [380, 380]],
    ['./XReal.png', [272, 380]],
    ['./MaskReal.png', targetSize],
    ['./FixationCross380x380.png', targetSize]
  ]);
  var preLoadArray = allPrimes.concat([
    './TooSlow.png',
    './AllAlpha.png',
    './XReal.png',
    './MaskReal.png',
    './FixationCross380x380.png'
  ]);

  //window.allWITImages = preloadResizedImages(imgNamesSizes);

  var experimentEnd = function () {
    var params = new URLSearchParams({
      pid: pid,
      sonacode: <?php echo $_GET['sonacode'];?>
    });
    var queryString = params.toString();
    var url = "https://cuboulder.qualtrics.com/jfe/form/SV_38UTFIh2dhPDnz7?" +
      queryString;
    window.location = url;
  }

  var startExperiment = function () {
    jsPsych.init({
    	timeline: timeline,
      fullscreen: true,
      preload_images: preLoadArray,
      on_finish: experimentEnd
    });
  };
  startExperiment();

</script>
</html>
