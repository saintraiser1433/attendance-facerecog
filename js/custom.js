let currentFormat = Fingerprint.SampleFormat.Intermediate;

let FingerprintSdkTest = (function () {
  function FingerprintSdkTest() {
    let _instance = this;
    this.operationToRestart = null;
    this.acquisitionStarted = false;
    // instantiating the fingerprint sdk here
    this.sdk = new Fingerprint.WebApi();
    this.sdk.onDeviceConnected = function (e) {
      // Detects if the device is connected for which acquisition started
      showMessage("Scan Appropriate Finger on the Reader", "success");
    };
    this.sdk.onDeviceDisconnected = function (e) {
      // Detects if device gets disconnected - provides deviceUid of disconnected device
      showMessage("Device is Disconnected. Please Connect Back");
    };
    this.sdk.onCommunicationFailed = function (e) {
      // Detects if there is a failure in communicating with U.R.U web SDK
      showMessage("Communication Failed. Please Reconnect Device");
    };
    this.sdk.onSamplesAcquired = function (s) {
      // Sample acquired event triggers this function
      storeSample(s);
    };
    this.sdk.onQualityReported = function (e) {
      // Quality of sample acquired - Function triggered on every sample acquired
      //document.getElementById("qualityInputBox").value = Fingerprint.QualityCode[(e.quality)];
    };
  }

  // this is were finger print capture takes place
  FingerprintSdkTest.prototype.startCapture = function () {
    if (this.acquisitionStarted)
      // Monitoring if already started capturing
      return;
    let _instance = this;
    showMessage("");
    this.operationToRestart = this.startCapture;
    this.sdk.startAcquisition(currentFormat, "").then(
      function () {
        _instance.acquisitionStarted = true;

        //Disabling start once started
        //disableEnableStartStop();
      },
      function (error) {
        showMessage(error.message);
      }
    );
  };

  FingerprintSdkTest.prototype.stopCapture = function () {
    if (!this.acquisitionStarted)
      //Monitor if already stopped capturing
      return;
    let _instance = this;
    showMessage("");
    this.sdk.stopAcquisition().then(
      function () {
        _instance.acquisitionStarted = false;

        //Disabling stop once stopped
        //disableEnableStartStop();
      },
      function (error) {
        showMessage(error.message);
      }
    );
  };

  FingerprintSdkTest.prototype.getInfo = function () {
    let _instance = this;
    return this.sdk.enumerateDevices();
  };

  FingerprintSdkTest.prototype.getDeviceInfoWithID = function (uid) {
    let _instance = this;
    return this.sdk.getDeviceInfo(uid);
  };

  return FingerprintSdkTest;
})();

class Reader {
  constructor() {
    this.reader = new FingerprintSdkTest();
    this.selectFieldID = null;
    this.currentStatusField = null;
    /**
     * @type {Hand}
     */
    this.currentHand = null;
  }

  readerSelectField(selectFieldID) {
    this.selectFieldID = selectFieldID;
  }

  setStatusField(statusFieldID) {
    this.currentStatusField = statusFieldID;
  }

  displayReader() {
    let readers = this.reader.getInfo(); // grab available readers here
    let id = this.selectFieldID;
    let selectField = document.getElementById(id);
    selectField.innerHTML = `<option>Select Fingerprint Reader</option>`;
    readers.then(function (availableReaders) {
      // when promise is fulfilled
      if (availableReaders.length > 0) {
        showMessage("");
        for (let reader of availableReaders) {
          selectField.innerHTML += `<option value="${reader}" selected>${reader}</option>`;
        }
      } else {
        showMessage("Please Connect the Fingerprint Reader");
      }
    });
  }
}

class Hand {
  constructor() {
    this.id = 0;
    this.index_finger = [];
    this.middle_finger = [];
  }

  addIndexFingerSample(sample) {
    this.index_finger.push(sample);
  }

  addMiddleFingerSample(sample) {
    this.middle_finger.push(sample);
  }

  uploadImage() {
    var fileInput = document.getElementById("customFile");
    var file = fileInput.files[0];
    var formData = new FormData();
    formData.append("file", file);
    formData.append("name", this.id);
    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function () {
      if (this.readyState === 4 && this.status === 200) {
        if (this.responseText === "success") {
          showMessage(successMessage, "success");
        }
      }
    };
    xhttp.open("POST", "../ajax/upload.php", true);
    xhttp.send(formData);
  }

  generateFullHand() {
    let id = this.id;
    let index_finger = this.index_finger;
    let middle_finger = this.middle_finger;
    
    // Safely get form field values (for student registration page)
    let regfnameEl = document.getElementById("regfname");
    let reglnameEl = document.getElementById("reglname");
    let regmnameEl = document.getElementById("regmname");
    let regyearlevelEl = document.getElementById("regyearlevel");
    let regdepartmentEl = document.getElementById("regdepartment");
    let regcourseEl = document.getElementById("regcourse");
    let regblockEl = document.getElementById("regblock");
    let isUpdateEl = document.getElementById("isUpdate");
    
    let regfname = regfnameEl ? regfnameEl.value : "";
    let reglname = reglnameEl ? reglnameEl.value : "";
    let regmname = regmnameEl ? regmnameEl.value : "";
    let regyearlevel = regyearlevelEl ? regyearlevelEl.value : "";
    let regdepartment = regdepartmentEl ? regdepartmentEl.value : "";
    let regcourse = regcourseEl ? regcourseEl.value : "";
    let regblock = regblockEl ? regblockEl.value : "";
    let img_path = this.id + ".png";
    let isUpdate = isUpdateEl ? isUpdateEl.value : "0";

    return JSON.stringify({
      id,
      regfname,
      reglname,
      regmname,
      regyearlevel,
      regdepartment,
      regcourse,
      regblock,
      index_finger,
      middle_finger,
      img_path,
      isUpdate,
    });
  }

  generateHandLogin() {
    let id = this.id;
    let schedEl = document.getElementById("sched");
    let sched = schedEl ? schedEl.value : "";
    let index_finger = this.index_finger;
    let middle_finger = this.middle_finger;
    return JSON.stringify({ id, index_finger, middle_finger, sched });
  }
}

let myReader = new Reader();

function beginEnrollment() {
  setReaderSelectField("enrollReaderSelect");
  myReader.setStatusField("enrollmentStatusField");
}

function beginIdentification() {
  setReaderSelectField("verifyReaderSelect");
  myReader.setStatusField("verifyIdentityStatusField");
}

function setReaderSelectField(fieldName) {
  myReader.readerSelectField(fieldName);
  myReader.displayReader();
}

function showMessage(message, message_type = "error") {
  let types = new Map();
  types.set("success", "my-text7 my-pri-color text-bold");
  types.set("error", "text-danger");
  if (message == "") {
  } else if (message == "Scan Appropriate Finger on the Reader") {
  } else if (message == "success") {
  } else {
    swal(message, {
      icon: message_type,
    }).then(() => {
      location.reload();
    });
  }

  // let statusFieldID = myReader.currentStatusField;
  // if (statusFieldID) {
  //   let statusField = document.getElementById(statusFieldID);
  //   statusField.innerHTML = `<p class="my-text7 my-pri-color my-3 ${types.get(
  //     message_type
  //   )} font-weight-bold">${message}</p>`;
  // }
}

function beginCapture() {
  if (!readyForEnroll()) {
    return;
  }
  myReader.currentHand = new Hand();
  storeUserID(); // for current user in Hand instance
  myReader.reader.startCapture();
  showNextNotEnrolledItem();
}

function captureForIdentifyIn() {
  let typesEl = document.getElementById("types");
  if (typesEl) {
    typesEl.value = "IN";
  }
  
  // Reduce opacity of the "out" button
  var inButton = document.querySelector(".in");
  var outButton = document.querySelector(".out");
  if (outButton) outButton.style.opacity = "0.5";
  if (inButton) inButton.style.opacity = "1";
  
  if (!readyForIdentify()) {
    toastr.warning("Please select a fingerprint reader first");
    return;
  }
  
  // Create new Hand instance for this verification attempt
  myReader.currentHand = new Hand();
  // For verification, we don't have a userID yet - it will be found from fingerprint match
  myReader.currentHand.id = 0;

  // Clear previous verification icons
  clearPrints();
  
  // Start capture
  myReader.reader.startCapture();
  showNextVerifyNotEnrolledItem();
  
  // Update status
  var statusEl = document.getElementById('fp-status');
  if (statusEl) {
    statusEl.className = 'status-message status-info';
    statusEl.innerHTML = '<i class="fas fa-fingerprint"></i> Please place your finger on the reader...';
  }
}

function captureForIdentifyOut() {
  // Reduce opacity of the "in" button
  var inButton = document.querySelector(".in");
  var outButton = document.querySelector(".out");
  if (inButton) inButton.style.opacity = "0.5";
  if (outButton) outButton.style.opacity = "1";

  let typesEl = document.getElementById("types");
  if (typesEl) {
    typesEl.value = "OUT";
  }
  
  if (!readyForIdentify()) {
    toastr.warning("Please select a fingerprint reader first");
    return;
  }
  
  // Create new Hand instance for this verification attempt
  myReader.currentHand = new Hand();
  // For verification, we don't have a userID yet - it will be found from fingerprint match
  myReader.currentHand.id = 0;

  // Clear previous verification icons
  clearPrints();
  
  // Start capture
  myReader.reader.startCapture();
  showNextVerifyNotEnrolledItem();
  
  // Update status
  var statusEl = document.getElementById('fp-status');
  if (statusEl) {
    statusEl.className = 'status-message status-info';
    statusEl.innerHTML = '<i class="fas fa-fingerprint"></i> Please place your finger on the reader...';
  }
}

/**
 * @returns {boolean}
 */
function readyForEnroll() {
  var userId = document.getElementById("userID");
  var enrollReaderSelect = document.getElementById("enrollReaderSelect");
  if (userId !== null && enrollReaderSelect !== null) {
    return (
      userId.value !== "" &&
      enrollReaderSelect.value !== "Select Fingerprint Reader"
    );
  }
  return false;
}

/**
 * @returns {boolean}
 */
function readyForIdentify() {
  let verifyReaderSelect = document.getElementById("verifyReaderSelect");
  return verifyReaderSelect && verifyReaderSelect.value !== "Select Fingerprint Reader";
}

function clearCapture() {
  clearInputs();
  clearPrints();
  clearHand();
  // myReader.reader.stopCapture();
  var userId = document.getElementById("userDetails");
  if (userId !== null) {
    document.getElementById("userDetails").innerHTML = "";
  }
}

function clearInputs() {
  var userId = document.getElementById("userID");
  if (userId !== null) {
    myReader.currentHand.id = document.getElementById("userID").value = "";
  }

  //let id = myReader.selectFieldID;
  //let selectField = document.getElementById(id);
  //selectField.innerHTML = `<option>Select Fingerprint Reader</option>`;
}

function clearPrints() {
  let indexFingers = document.getElementById("indexFingers");
  let middleFingers = document.getElementById("middleFingers");
  let verifyFingers = document.getElementById("verificationFingers");

  if (indexFingers) {
    for (let indexfingerElement of indexFingers.children) {
      indexfingerElement.innerHTML = `<span class="verifyicon icon-indexfinger-not-enrolled" title="not_enrolled"></span>`;
    }
  }

  if (middleFingers) {
    for (let middlefingerElement of middleFingers.children) {
      middlefingerElement.innerHTML = `<span class="verifyicon icon-middlefinger-not-enrolled" title="not_enrolled"></span>`;
    }
  }

  if (verifyFingers) {
    for (let finger of verifyFingers.children) {
      finger.innerHTML = `<span class="verifyicon icon-indexfinger-not-enrolled" title="not_enrolled"></span>`;
    }
  }
}

function clearHand() {
  myReader.currentHand = null;
}

function showSampleCaptured() {
  let nextElementID = getNextNotEnrolledID();
  let markup = null;
  if (
    nextElementID.startsWith("index") ||
    nextElementID.startsWith("verification")
  ) {
    markup = `<span class="myicon icon-indexfinger-enrolled" title="enrolled"></span>`;
  }

  if (nextElementID.startsWith("middle")) {
    markup = `<span class="myicon icon-middlefinger-enrolled" title="enrolled"></span>`;
  }

  if (nextElementID !== "" && markup) {
    let nextElement = document.getElementById(nextElementID);
    nextElement.innerHTML = markup;
  }
}

function showNextNotEnrolledItem() {
  let nextElementID = getNextNotEnrolledID();
  let markup = null;
  if (
    nextElementID.startsWith("index") ||
    nextElementID.startsWith("verification")
  ) {
    markup = `<span class="myicon capture-indexfinger" title="not_enrolled"></span>`;
  }

  if (nextElementID.startsWith("middle")) {
    markup = `<span class="myicon capture-middlefinger" title="not_enrolled"></span>`;
  }

  if (nextElementID !== "" && markup) {
    let nextElement = document.getElementById(nextElementID);
    nextElement.innerHTML = markup;
  }
  // Only call serverIdentify if we're on a verification page (has verifyReaderSelect)
  // Don't call it during enrollment (has enrollReaderSelect)
  if (nextElementID == "") {
    let verifyReaderSelect = document.getElementById("verifyReaderSelect");
    if (verifyReaderSelect) {
      serverIdentify();
    }
    // For enrollment, all fingers are captured, ready to enroll
    // The enrollment button will call serverEnroll()
  }
}

/**
 * @returns {string}
 */
function getNextNotEnrolledID() {
  let eUserIdval = null;
  let indexFingers = document.getElementById("indexFingers");
  let middleFingers = document.getElementById("middleFingers");
  let verifyFingers = document.getElementById("verificationFingers");

  let indexFingerElement = findElementNotEnrolled(indexFingers);
  let middleFingerElement = findElementNotEnrolled(middleFingers);
  let verifyFingerElement = findElementNotEnrolled(verifyFingers);
  let eUserID = document.getElementById("userID");
  
  // Safely get userID value (may not exist on verification pages)
  eUserIdval = eUserID ? eUserID.value : "";
  
  // For enrollment pages (has userID and enrollReaderSelect)
  if (indexFingerElement !== null && eUserIdval !== "") {
    return indexFingerElement.id;
  }
  if (middleFingerElement !== null && eUserIdval !== "") {
    return middleFingerElement.id;
  }
  
  // For verification pages (has verifyReaderSelect, no userID)
  if (verifyFingerElement !== null) {
    return verifyFingerElement.id;
  }

  return "";
}

function showNextVerifyNotEnrolledItem() {
  let nextElementID = getNextNotEnrolledID();
  let markup = null;
  if (
    nextElementID.startsWith("index") ||
    nextElementID.startsWith("verification")
  ) {
    markup = `<span class="verifyicon capture-indexfinger" title="not_enrolled"></span>`;
  }

  if (nextElementID.startsWith("middle")) {
    markup = `<span class="verifyicon capture-middlefinger" title="not_enrolled"></span>`;
  }

  if (nextElementID !== "" && markup) {
    let nextElement = document.getElementById(nextElementID);
    nextElement.innerHTML = markup;
  }
  // Only call serverIdentify if we're on a verification page
  if (nextElementID == "") {
    let verifyReaderSelect = document.getElementById("verifyReaderSelect");
    if (verifyReaderSelect) {
      serverIdentify();
    }
  }
}

/**
 * @returns {string}
 */
function getNextVerifyNotEnrolledID() {
  let eUserIdval = null;
  let indexFingers = document.getElementById("indexFingers");
  let middleFingers = document.getElementById("middleFingers");
  let verifyFingers = document.getElementById("verificationFingers");

  let indexFingerElement = findElementNotEnrolled(indexFingers);
  let middleFingerElement = findElementNotEnrolled(middleFingers);
  let verifyFingerElement = findElementNotEnrolled(verifyFingers);

  let eUserID = document.getElementById("userID");

  if (eUserID !== null) {
    eUserIdval = document.getElementById("userID").value;
    if (indexFingerElement !== null && eUserIdval !== "") {
      return indexFingerElement.id;
    }

    if (middleFingerElement !== null && eUserIdval !== "") {
      return middleFingerElement.id;
    }
  } else {
    if (verifyFingerElement !== null) {
      return verifyFingerElement.id;
    }
  }
  //assumption is that we will always start with
  //indexfinger and run down to middlefinger

  return "";
}

/**
 *
 * @param {Element} element
 * @returns {Element}
 */
function findElementNotEnrolled(element) {
  if (element) {
    for (let fingerElement of element.children) {
      if (fingerElement.firstElementChild.title === "not_enrolled") {
        return fingerElement;
      }
    }
  }

  return null;
}

function storeUserID() {
  var userId = document.getElementById("userID");
  if (userId !== null) {
    myReader.currentHand.id = document.getElementById("userID").value;
  }
}

function storeSample(sample) {
  // Convert acquired sample into FMD if SDK supports it.
  // The gRPC engine expects DigitalPersona FMD (pre-enrollment features), not raw/intermediate sample blobs.
  storeSampleAsync(sample);
}

async function storeSampleAsync(sample) {
  let samples = JSON.parse(sample.samples);
  let sampleData = samples[0].Data;
  let fmdData = sampleData;

  try {
    if (typeof Fingerprint !== "undefined" && typeof Fingerprint.createFmd === "function") {
      // WebSDK returns a promise that resolves to an object with Data (base64-encoded FMD)
      let fmd = await Fingerprint.createFmd(sampleData, currentFormat);
      if (fmd && fmd.Data) {
        fmdData = fmd.Data;
      }
    }
  } catch (e) {
    // If conversion fails, fall back to the raw sampleData
    console.warn("createFmd failed; using raw sample data", e);
  }

  let nextElementID = getNextNotEnrolledID();
  let isVerification = document.getElementById("verifyReaderSelect") !== null;

  // For verification: only capture 2 index finger samples
  // For enrollment: capture index and middle fingers
  if (
    nextElementID.startsWith("index") ||
    nextElementID.startsWith("verification")
  ) {
    myReader.currentHand.addIndexFingerSample(fmdData);
    showSampleCaptured();
    
    // For verification: after 2 samples, verify immediately
    if (isVerification && myReader.currentHand.index_finger.length >= 2) {
      // Stop capture and verify
      myReader.reader.stopCapture();
      setTimeout(() => {
        serverIdentify();
      }, 500);
      return;
    }
    
    showNextNotEnrolledItem();
    return;
  }

  if (nextElementID.startsWith("middle")) {
    myReader.currentHand.addMiddleFingerSample(fmdData);
    showSampleCaptured();
    showNextNotEnrolledItem();
  }
}

function serverEnroll() {
  if (!readyForEnroll()) {
    return;
  }

  // Check if currentHand exists and has captured samples
  if (!myReader.currentHand) {
    toastr.error('Please capture fingerprints first');
    return;
  }

  if (myReader.currentHand.index_finger.length === 0 && myReader.currentHand.middle_finger.length === 0) {
    toastr.error('Please capture at least one fingerprint sample');
    return;
  }

  // Get user ID and user type
  let userIDEl = document.getElementById("userID");
  let userTypeEl = document.querySelector('input[name="user_type"]');
  
  if (!userIDEl || !userIDEl.value) {
    toastr.error('User ID not found');
    return;
  }
  
  let user_id = userIDEl.value;
  let user_type = userTypeEl ? userTypeEl.value : 'staff'; // Default to staff if not found
  
  // Create fingerprint data in the format expected by enroll_staff.php
  let fingerprintData = {
    index_finger: myReader.currentHand.index_finger,
    middle_finger: myReader.currentHand.middle_finger
  };
  
  let data = JSON.stringify(fingerprintData);
  let successMessage = "Enrollment Successful!";
  let failedMessage = "Enrollment Failed!";
  
  // Build payload with all required fields
  let payload = `user_id=${encodeURIComponent(user_id)}&user_type=${encodeURIComponent(user_type)}&data=${encodeURIComponent(data)}`;

  let xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      try {
        // Try to parse as JSON first
        let response = JSON.parse(this.responseText);
        if (response.success === true) {
          toastr.success(response.message || successMessage);
          if (myReader.currentHand && myReader.currentHand.uploadImage) {
            myReader.currentHand.uploadImage();
          }
          // Reload page after 2 seconds to show updated status
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          toastr.error(response.error || failedMessage);
        }
      } catch(e) {
        // If not JSON, check for string "success" (backward compatibility)
        if (this.responseText.trim() === "success") {
          toastr.success(successMessage);
          if (myReader.currentHand && myReader.currentHand.uploadImage) {
            myReader.currentHand.uploadImage();
          }
        } else {
          toastr.error(failedMessage + ': ' + this.responseText);
        }
      }
    }
  };
  xhttp.open("POST", "../core/enroll_staff.php", true);
  xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhttp.send(payload);
}

// Helper function to get base path for API calls
function getBasePath() {
  let pathname = window.location.pathname;
  // If in admin folder, go up one level
  if (pathname.includes('/admin/')) {
    return "../";
  }
  // Otherwise, we're in root
  return "";
}

function serverIdentify() {
  if (!readyForIdentify()) {
    toastr.warning("Please select a fingerprint reader first");
    return;
  }

  // Stop capture if still running
  if (myReader.reader.acquisitionStarted) {
    myReader.reader.stopCapture();
  }

  // Check if we have samples
  if (!myReader.currentHand || 
      (myReader.currentHand.index_finger.length === 0 && myReader.currentHand.middle_finger.length === 0)) {
    toastr.error("No fingerprint samples captured. Please try again.");
    return;
  }

  let data = myReader.currentHand.generateHandLogin();
  let payload = `data=${data}`;
  let typeEl = document.getElementById("types");
  let schedEl = document.getElementById("sched");
  let type = typeEl ? typeEl.value : "IN";
  let schedid = schedEl ? schedEl.value : "";
  
  // Update status
  var statusEl = document.getElementById('fp-status');
  if (statusEl) {
    statusEl.className = 'status-message status-info';
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying fingerprint...';
  }
  
  let xhttp = new XMLHttpRequest();

  xhttp.onreadystatechange = function () {
    if (this.readyState === 4 && this.status === 200) {
      if (this.responseText !== null && this.responseText !== "") {
        try {
          let verifyResponse = JSON.parse(this.responseText);
          
          // Check if fingerprint match was found
          if (verifyResponse.match === true) {
            // Get the user ID - prefer user_id if it's valid, otherwise use student_id
            // For students, student_id is the student number (e.g., "2025-01")
            // For attendance, we need the numeric user_id or student_id
            let userIdForAttendance = verifyResponse.user_id;
            
            // If user_id is 0 or missing, try to use student_id
            // Note: student_id might be a string, so we'll let the backend handle it
            if (!userIdForAttendance || userIdForAttendance === 0) {
              userIdForAttendance = verifyResponse.student_id;
            }
            
            // Update status
            if (statusEl) {
              statusEl.className = 'status-message status-success';
              statusEl.innerHTML = '<i class="fas fa-check-circle"></i> Fingerprint matched! Recording attendance...';
            }
            
            // Fingerprint matched, now insert attendance
            let attendancePath = getBasePath() + "ajax/insertAttendance.php";
            
            $.ajax({
              method: "POST",
              url: attendancePath,
              data: {
                userId: userIdForAttendance,
                userType: verifyResponse.user_type || 'student',
                type: type,
                sched: schedid,
                matchScore: 95.0, // You can get this from verify response if available
                studentId: verifyResponse.student_id // Also pass student_id separately if needed
              },
              dataType: "json",
              success: function (attendanceResult) {
                // Update UI elements if they exist
                if ($(".stud").length && attendanceResult.studentID) {
                  $(".stud").empty().html(attendanceResult.studentID);
                }
                if ($(".name").length && attendanceResult.fullname) {
                  $(".name").empty().html(attendanceResult.fullname);
                }
                if ($(".yearlvl").length && attendanceResult.year) {
                  $(".yearlvl").empty().html(attendanceResult.year);
                }
                if ($(".coursedtl").length && attendanceResult.course) {
                  $(".coursedtl").empty().html(attendanceResult.course);
                }
                if ($(".res").length && attendanceResult.response) {
                  $(".res").empty().html(attendanceResult.response);
                }
                if ($("#studimg").length && attendanceResult.img) {
                  $("#studimg").attr("src", "../lib/studentimage/" + attendanceResult.img);
                }
                
                // Show attendance card if it exists
                if ($("#attendance-card").length) {
                  $("#attendance-card").fadeIn();
                  // Hide after 5 seconds
                  setTimeout(function() {
                    $("#attendance-card").fadeOut();
                  }, 5000);
                }
                
                // Show toast notification
                if (attendanceResult.type && attendanceResult.response) {
                  toastr[attendanceResult.type](attendanceResult.response);
                }
                
                // Update status
                if (statusEl) {
                  statusEl.className = 'status-message status-success';
                  statusEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + (attendanceResult.response || 'Attendance recorded successfully');
                }
              },
              error: function(xhr, status, error) {
                console.error("Attendance insertion error:", error);
                toastr.error("Failed to record attendance. Please try again.");
                if (statusEl) {
                  statusEl.className = 'status-message status-error';
                  statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed to record attendance';
                }
              }
            });
          } else {
            // No match found
            toastr.error(
              verifyResponse.error || "NO FINGERPRINT FOUND OR NOT REGISTERED"
            );
            if (statusEl) {
              statusEl.className = 'status-message status-error';
              statusEl.innerHTML = '<i class="fas fa-times-circle"></i> ' + (verifyResponse.error || "Fingerprint not found");
            }
          }
        } catch (e) {
          console.error("Error parsing verification response:", e);
          console.error("Response text:", this.responseText);
          toastr.error("Error processing fingerprint verification");
          if (statusEl) {
            statusEl.className = 'status-message status-error';
            statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error processing verification';
          }
        }
      } else {
        toastr.error("NO RESPONSE FROM SERVER");
        if (statusEl) {
          statusEl.className = 'status-message status-error';
          statusEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No response from server';
        }
      }
    }

    clearCapture();

    // Restart capture after a delay
    if (type == "IN") {
      setTimeout(() => {
        captureForIdentifyIn();
      }, 2000);
    } else {
      setTimeout(() => {
        captureForIdentifyOut();
      }, 2000);
    }
  };

  // Determine correct path based on current location
  let verifyPath = getBasePath() + "core/verify.php";
  
  xhttp.open("POST", verifyPath, true);
  xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhttp.send(payload);
}
