/**
* jspsych-muli-stim-multi-response
* Josh de Leeuw
*
* plugin for displaying a set of stimuli and collecting a set of responses
* via the keyboard
*
* documentation: docs.jspsych.org
*
**/


jsPsych.plugins["sequential-priming"] = (function() {

  var plugin = {};

  //jsPsych.pluginAPI.registerPreload('sequential-priming', 'stimuli', 'image');
  /**
  Note that this plugin relies on the css named for the plugin. In that css,
  the height is specified. Change this to match the stimuli so that the
  prompt knows where to be during ITIs or other blank spaces.
  **/

  // recycle function for arrays
  function recycle (arr, num) {
    // arr should be an array to recycle.
    // num can be either an integer number to recycle to, or an array whose
    // length will be matched.
    if (typeof num === 'object') {
      num = num.length;
    }

    rep = Math.floor(num / arr.length);
    rem = num % arr.length;

    var tempArr = [];
    for (i=0; i < rep; i++) {
      tempArr.concat(arr);
    }
    if (rem > 0) {
      tempArr.concat(arr.slice(0, rem));
    }
    return(tempArr);
  }

  plugin.info = {
    name: 'sequential-priming',
    description: '',
    parameters: {
      iti: {
        type: jsPsych.plugins.parameterType.INT,
        pretty_name: "ITI",
        default: 1000,
        description: "Inter-trial interval"
      },
      response_ends_trial: {
        type: jsPsych.plugins.parameterType.BOOL,
        default: true
      },
      response_ends_stimulus: {
        default: true
      },
      timing_stim: {
        default: undefined
      },
      timing_response: {
        default: -1
      },
      is_html: {
        default: undefined
      },
      prompt: {
        type: jsPsych.plugins.parameterType.STRING,
        default: ""
      },
      response_window: {
        default: [0, Infinity]
      },
      key_to_advance: {
        default: 'none'
      },
      feedback_duration: {
        default: 0
      },
      correct_feedback: {
        default: ""
      }
    }
  };

  plugin.trial = function(display_element, trial) {

    // expand the response_ends_stimulus variable for each trial if necessary.
    if (trial.response_ends_stimulus.length < trial.choices.length) {
      trial.response_ends_stimulus = recycle(trial.response_ends_stimulus, trial.stimuli);
    }
    // timing parameters
    var default_timing_array = [];
    for (var j = 0; j < trial.stimuli.length; j++) {
      default_timing_array.push(1000);
    }
    trial.timing_stim = trial.timing_stim || default_timing_array;
    //For timing_response, if -1, then wait for response forever
    // optional parameters
    trial.is_html = (typeof trial.is_html === 'undefined') ? false : trial.is_html;
    if (trial.is_html.length < trial.choices.length) {
      trial.is_html = recycle(trial.is_html, trial.stimuli);
    }

    // The trial.key_to_advance can be set a keycode. If set to 'none',
    // then no key is needed to advance the trial after an incorrect response
    // or a timeout. If it is set, then that key must be pressed.

    // if any trial variables are functions
    // this evaluates the function and replaces
    // it with the output of the function
    //trial = jsPsych.pluginAPI.evaluateFunctionParameters(trial);

    // this array holds handlers from setTimeout calls
    // that need to be cleared if the trial ends early
    var setTimeoutHandlers = [];

    // an indicator for whether or not we are correct
    var correct;
    var rt;
    var rtFromStart;
    var timeout = false;

    // array to store if we have gotten a valid response for
    // each of the responses

    var validResponses = [];
    for (var i = 0; i < trial.choices.length; i++) {
      validResponses[i] = trial.choices[i].length > 0 ? false : true;
    }


    // array for response times for each of the different response types
    var responseTimes = [];
    for (var i = 0; i < trial.choices.length; i++) {
      responseTimes[i] = -1;
    }

    var responseKey;

    // function to check if all of the valid responses are received
    /*
    function checkAllResponsesAreValid() {
    for (var i = 0; i < validResponses.length; i++) {
    if (validResponses[i] == false) {
    return false;
  }
}
return true;
}
*/
var clearTimeoutHandlers = function () {
  for (var i = 0; i < setTimeoutHandlers.length; i++) {
    clearTimeout(setTimeoutHandlers[i]);
  }
};

var show_feedback = function() {
  if (trial.feedback) {
    // clear timeout handlers and the display
    clearTimeoutHandlers();
    display_element.innerHTML = '';

    if (rt > trial.timing_response || typeof rt === 'undefined') {
      feedback_stimulus = trial.timeout_feedback;
    } else {
      feedback_stimulus = correct ? trial.correct_feedback : trial.incorrect_feedback;
    }
    var html = '<img src="'+feedback_stimulus+'" id="jspsych-sequential-priming-stimulus"></img>';


    // add the prompt
    if (trial.prompt != '') {
      html += trial.prompt;
    }
    display_element.innerHTML = html;

    // If a keypress to advance on incorrect is set, AND we are wrong or
    // timed out, then wait for a key. Otherwise, just advance after the
    // feedback time limit.
    var needKeyPress = trial.key_to_advance != 'none' && ! (correct == true);

    if (needKeyPress) {
      jsPsych.pluginAPI.cancelKeyboardResponse(keyboardListener);
      keyboardListener = jsPsych.pluginAPI.getKeyboardResponse({
        callback_function: advance_trial,
        valid_responses: [trial.key_to_advance],
        rt_method: 'date',
        persist: true,
        allow_held_key: false
      });
    } else {
      var t3 = setTimeout(end_trial, trial.feedback_duration);
      setTimeoutHandlers.push(t3);
    }
  } else {
    end_trial();
  }
};

var advance_trial = function (info) {
  // keyboard listener specifically for advancing the trial during
  // feedback after an incorrect or timeout response.
  end_trial();
};

var endStimulus = function() {
  // clear the display
  display_element.innerHTML = '';
  // truth conditions for deciding whether to end the trial
  lastStim = typeof trial.stimuli[whichStimulus + 1] === 'undefined';
  correctSet = typeof correct !== 'undefined';
  if (lastStim || (trial.response_ends_trial && correctSet)) {
    show_feedback();
  } else {
    // show the next stimulus
    whichStimulus++;
    showNextStimulus();
  }

};

// function to end trial when it is time
var end_trial = function() {

  // kill any remaining setTimeout handlers
  clearTimeoutHandlers();

  // clear the display, but leave the prompt up
  display_element.innerHTML = '';
  var html = '<div id="jspsych-sequential-priming-stimulus"></div>';
  if (trial.prompt != '') {
    html += trial.prompt;
  }
  display_element.innerHTML = html;
  // kill keyboard listeners

  jsPsych.pluginAPI.cancelKeyboardResponse(keyboardListener);

  // gather the data to store for the trial
  var trial_data = {
    "rt": JSON.stringify(rt),
    "rt_from_start": JSON.stringify(rtFromStart),
    "stimulus": JSON.stringify(trial.stimuli),
    "key_press": JSON.stringify(responseKey),
    "correct": JSON.stringify(correct)
  };

  // move on to the next trial after the iti
  var f1 = setTimeout(
    function () {
      display_element.innerHTML = '';
      jsPsych.finishTrial(trial_data);
    }, trial.iti
  );
};

// function to handle responses by the subject
var after_response = function(info) {
  // If we are waiting for the participant to advance the trial after
  // an incorrect response or timeout, just send us to the endTrial function
  // if they press the right thing.

  // For now, only allow one response per stimulus
  var response_in_window = (info.rt >= trial.response_window[0] && info.rt <= trial.response_window[1]);
  if (typeof rt === 'undefined' && response_in_window) {

    for (var j = 0; j < trial.choices.length; j++) {
      keycode = (typeof trial.choices[j] == 'string') ? jsPsych.pluginAPI.convertKeyCharacterToKeyCode(trial.choices[j]) : trial.choices[j];
      if (info.key == keycode) {
        validResponses[whichStimulus] = true;
        rt = info.rt - trial.response_window[0];
        rtFromStart = info.rt;
        responseKey = info.key;

        // for now, a single correct response implies correct
        correct = info.key == trial.correct_choice;

        if (trial.response_ends_stimulus[whichStimulus] || trial.response_ends_trial) {
          endStimulus();
        }
        break;
      }
    }
  }
};

var whichStimulus = 0;

function showNextStimulus() {
  // display stimulus
  var html = '';
  if (!trial.is_html[whichStimulus]) {
    html += '<img src="'+trial.stimuli[whichStimulus]+'" id="jspsych-sequential-priming-stimulus"></img>';
  } else {
    html += '<div id="jspsych-sequential-priming-stimulus">'+trial.stimuli[whichStimulus]+'</div>';
    //debugger;
  }

  //show prompt if there is one
  if (trial.prompt !== "") {
    html += trial.prompt;
  }
  display_element.innerHTML = html;

  if (typeof trial.timing_stim[whichStimulus] !== 'undefined' && trial.timing_stim[whichStimulus] > 0) {
    var t1 = setTimeout(endStimulus, trial.timing_stim[whichStimulus]);

    setTimeoutHandlers.push(t1);
  }

}

// show first stimulus
showNextStimulus();

// start the response listener
var keyboardListener = jsPsych.pluginAPI.getKeyboardResponse({
  callback_function: after_response,
  valid_responses: trial.choices,
  rt_method: 'date',
  persist: true,
  allow_held_key: false
});
};

return plugin;
})();
