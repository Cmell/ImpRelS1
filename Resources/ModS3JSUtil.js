// Functions for saving data uniformly across tasks.

var saveSuccessCode = -1;
var numSaveAttempts = 0;
var maxSaveAttempts = 10;

function sendData(flData, flName) {
  // AJAX stuff to actually send data.
  $.ajax({
    type: 'POST',
    cache: false,
    url: '../Resources/SaveData.php',
    error: onSaveError,
    success: onSaveSuccess,
    data: {
      filename: flName,
      filedata: flData
    }
  });
};

function onSaveSuccess(data, textStatus, jqXHR) {
  saveSuccessCode = 0;
  console.log(textStatus);
  numSaveAttempts++;
};

function onSaveError(data, textStatus, jqXHR) {
  console.log(textStatus);
  if (numSaveAttempts < maxSaveAttempts) {
    sendData();
    numSaveAttempts++;
    console.log(textStatus);
  } else {
    saveSuccessCode = 1;
    console.log(textStatus);
    console.log('Maximum number of save attempts exceeded.')
  }
};
