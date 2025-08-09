const video = document.getElementById('video');
const statusText = document.getElementById('status');
const alertSound = new Audio('assets/alert.mp3');

const EAR_THRESHOLD = 0.3;
const EAR_CONSEC_FRAMES = 3; // Number of consecutive frames eyes must be below threshold to trigger alert (0.3 seconds at 100ms interval)

let blinkCounter = 0;
let alertOn = false;

async function loadModels() {
  const MODEL_URL = 'models';
  try {
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL + '/tiny_face_detector_model/model.json'),
      faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL + '/face_landmark_68_model/model.json'),
      faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL + '/face_expression_model/model.json')
    ]);
    console.log('Models loaded successfully');
  } catch (error) {
    console.error('Error loading models:', error);
    statusText.innerText = 'Error loading models. Check console.';
  }
}

async function startVideo() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
    video.srcObject = stream;
  } catch (err) {
    statusText.innerText = 'Camera access denied or not available.';
    console.error(err);
  }
}

function calculateEAR(eye) {
  if (!eye || eye.length !== 6) {
    return NaN;
  }
  for (let point of eye) {
    if (typeof point._x !== 'number' || typeof point._y !== 'number' || isNaN(point._x) || isNaN(point._y)) {
      return NaN;
    }
  }
  function euclideanDistance(p1, p2) {
    const dx = p1._x - p2._x;
    const dy = p1._y - p2._y;
    return Math.sqrt(dx * dx + dy * dy);
  }
  const vertical1 = euclideanDistance(eye[1], eye[5]);
  const vertical2 = euclideanDistance(eye[2], eye[4]);
  const horizontal = euclideanDistance(eye[0], eye[3]);
  if (horizontal === 0) return NaN;
  return (vertical1 + vertical2) / (2.0 * horizontal);
}

let canvas, ctx, displaySize;

async function onPlay() {
  if (!canvas) {
    canvas = faceapi.createCanvasFromMedia(video);
    canvas.id = 'overlay';
    const videoWrapper = document.querySelector('.video-wrapper');
    videoWrapper.style.position = 'relative';
    canvas.style.position = 'absolute';
    canvas.style.top = '0px';
    canvas.style.left = '0px';
    canvas.style.width = videoWrapper.offsetWidth + 'px';
    canvas.style.height = videoWrapper.offsetHeight + 'px';
    videoWrapper.appendChild(canvas);

    ctx = canvas.getContext('2d', { willReadFrequently: true });
    displaySize = { width: videoWrapper.offsetWidth, height: videoWrapper.offsetHeight };
    faceapi.matchDimensions(canvas, displaySize);
  }

  setInterval(async () => {
    if (video.paused || video.ended) {
      if (alertOn) {
        alertSound.pause();
        alertSound.currentTime = 0;
        alertOn = false;
      }
      return;
    }

    const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

    const detections = await faceapi.detectAllFaces(video, options)
      .withFaceLandmarks()
      .withFaceExpressions();

    console.log('Detections:', detections.length);

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (detections.length > 0) {
      const resizedDetections = faceapi.resizeResults(detections, displaySize);

      resizedDetections.forEach(detection => {
        const box = detection.detection.box;
        ctx.strokeStyle = '#00FF00';
        ctx.lineWidth = 2;
        ctx.strokeRect(box.x, box.y, box.width, box.height);
        try {
          faceapi.draw.drawFaceLandmarks(canvas, detection);
          console.log('Landmarks drawn at', box);
        } catch (err) {
          console.error('Error drawing landmarks:', err);
        }
      });

      const landmarks = resizedDetections[0].landmarks;
      const leftEye = landmarks.getLeftEye();
      const rightEye = landmarks.getRightEye();

      console.log('Left eye landmarks:', JSON.stringify(leftEye));
      console.log('Right eye landmarks:', JSON.stringify(rightEye));

      const leftEAR = calculateEAR(leftEye);
      const rightEAR = calculateEAR(rightEye);
      const avgEAR = (leftEAR + rightEAR) / 2.0;

      if (isNaN(avgEAR)) {
        statusText.innerText = 'Detecting...';
        console.log('EAR: NaN');
        // Fallback: Use face expression sleepy confidence to trigger alert
        const expressions = resizedDetections[0].expressions;
        const sleepyConfidence = expressions['sleepy'] || 0;
        console.log(`Sleepy confidence (fallback): ${sleepyConfidence.toFixed(3)}`);
        if (sleepyConfidence > 0.6) {
          if (!alertOn) {
            console.log('Fallback: Triggering alert due to sleepy expression');
            alertSound.play().catch(err => console.error('Error playing alert sound:', err));
            alertOn = true;
            statusText.innerText = 'Wake Up! (Fallback)';
            statusText.classList.add('alert');
          }
        } else {
          if (alertOn) {
            alertSound.pause();
            alertSound.currentTime = 0;
            alertOn = false;
            statusText.innerText = 'Awake';
            statusText.classList.remove('alert');
          } else {
            statusText.innerText = 'Awake';
            statusText.classList.remove('alert');
          }
        }
        return;
      }

      const expressions = resizedDetections[0].expressions;
      const eyesClosed = expressions['closed'] || expressions['sleepy'] || 0;

      console.log(`EAR: ${avgEAR.toFixed(3)}, blinkCounter: ${blinkCounter}, eyesClosed: ${eyesClosed.toFixed(3)}`);

      if (avgEAR < EAR_THRESHOLD || eyesClosed > 0.5) {
        blinkCounter++;
        console.log(`blinkCounter incremented to ${blinkCounter}, alertOn: ${alertOn}, EAR: ${avgEAR.toFixed(3)}, eyesClosed: ${eyesClosed.toFixed(3)}`);
        if (blinkCounter >= EAR_CONSEC_FRAMES) {
          if (!alertOn) {
            console.log('Attempting to play alert sound...');
            if (alertSound.paused) {
              alertSound.play().then(() => {
                console.log('Alert sound played');
              }).catch(err => {
                console.error('Error playing alert sound:', err);
              });
            } else {
              console.log('Alert sound already playing');
            }
            alertOn = true;
            statusText.innerText = 'Wake Up!';
            statusText.classList.add('alert');
          }
        } else {
          // Show sleepy warning before alert triggers
          statusText.innerText = 'Sleepy...';
          statusText.classList.add('alert');
        }
      } else {
        blinkCounter = 0;
        if (alertOn) {
          console.log('Stopping alert sound...');
          alertSound.pause();
          alertSound.currentTime = 0;
          alertOn = false;
          statusText.innerText = 'Awake';
          statusText.classList.remove('alert');
        } else {
          statusText.innerText = 'Awake';
          statusText.classList.remove('alert');
        }
      }
    } else {
      blinkCounter = 0;
      if (alertOn) {
        alertSound.pause();
        alertSound.currentTime = 0;
        alertOn = false;
      }
      statusText.innerText = 'No face detected';
      statusText.classList.remove('alert');
    }
  }, 100);
}

async function init() {
  statusText.innerText = 'Loading models...';
  await loadModels();
  statusText.innerText = 'Starting video...';
  await startVideo();
  video.addEventListener('play', onPlay);

  // Add event listener for test alert button
  const testAlertBtn = document.getElementById('testAlertBtn');
  if (testAlertBtn) {
    testAlertBtn.addEventListener('click', () => {
      if (alertSound.paused) {
        alertSound.play().then(() => {
          console.log('Test alert sound played');
        }).catch(err => {
          console.error('Error playing test alert sound:', err);
        });
      }
      statusText.innerText = 'Wake Up! (Test Alert)';
      statusText.classList.add('alert');
      setTimeout(() => {
        statusText.innerText = 'Awake';
        statusText.classList.remove('alert');
        alertSound.pause();
        alertSound.currentTime = 0;
      }, 3000);
    });
  }
}

init();
