<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display - NORSU Queue</title>
    <link rel="stylesheet" href="css/display.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="display-container">
        <div class="header">
            <img src="images/norsu-logo.png" alt="NORSU Logo">
            <h1>Now Serving</h1>
        </div>
        
        <div class="window-container">
            <div class="window-box">
                <h2>Window 1</h2>
                <div class="ticket" id="window1">-</div>
            </div>
            <div class="window-box">
                <h2>Window 2</h2>
                <div class="ticket" id="window2">-</div>
            </div>
        </div>

        <div id="callout" class="callout hidden">
            <div class="callout-content">
                <h2>Now Calling</h2>
                <p id="calloutMessage"></p>
            </div>
        </div>

        <div class="next-queue">
            <h3>Next in Queue</h3>
            <div class="waiting-list" id="waitingList"></div>
        </div>
    </div>

    <!-- Clean audio element -->
    <!-- Add autoplay attribute to audio element -->
    <!-- Update audio elements -->
    <!-- Add these audio elements at the bottom of the body tag -->
    <audio id="notificationSound" preload="auto">
        <source src="sounds/notification.mp3" type="audio/mpeg">
    </audio>
    <audio id="window1Sound" preload="auto">
        <source src="sounds/window1.mp3" type="audio/mpeg">
    </audio>
    <audio id="window2Sound" preload="auto">
        <source src="sounds/window2.mp3" type="audio/mpeg">
    </audio>

    <script>
        let audioInitialized = false;

        // Initialize audio on page load
        window.addEventListener('load', function() {
            const notificationSound = document.getElementById('notificationSound');
            const window1Sound = document.getElementById('window1Sound');
            const window2Sound = document.getElementById('window2Sound');
            
            // Force load the audio files
            notificationSound.load();
            window1Sound.load();
            window2Sound.load();
            
            // Set volume
            notificationSound.volume = 1.0;
            window1Sound.volume = 1.0;
            window2Sound.volume = 1.0;
            
            audioInitialized = true;
        });

        // Also initialize on user interaction
        document.addEventListener('click', function() {
            if (!audioInitialized) {
                const notificationSound = document.getElementById('notificationSound');
                const window1Sound = document.getElementById('window1Sound');
                const window2Sound = document.getElementById('window2Sound');
                
                // Try to play and immediately pause to initialize
                Promise.all([
                    notificationSound.play().then(() => notificationSound.pause()),
                    window1Sound.play().then(() => window1Sound.pause()),
                    window2Sound.play().then(() => window2Sound.pause())
                ]).catch(console.error);
                
                audioInitialized = true;
            }
        });

        function playAnnouncement(ticket, window) {
            const notificationSound = document.getElementById('notificationSound');
            const windowSound = document.getElementById(`window${window}Sound`);
            
            // Reset audio state
            notificationSound.pause();
            windowSound.pause();
            notificationSound.currentTime = 0;
            windowSound.currentTime = 0;

            // Play notification sound first
            notificationSound.play()
                .then(() => {
                    // Wait for notification sound to end
                    notificationSound.addEventListener('ended', () => {
                        // Then play window sound
                        windowSound.play().catch(error => {
                            console.error('Window sound playback failed:', error);
                        });
                    }, { once: true });
                })
                .catch(error => {
                    console.error('Notification sound playback failed:', error);
                });
        }

        function showCallout(ticket, window) {
            const callout = document.getElementById('callout');
            const message = document.getElementById('calloutMessage');
            message.textContent = `${ticket}, PROCEED TO WINDOW ${window}`;
            callout.classList.remove('hidden');
            callout.classList.add('show');
            
            setTimeout(() => {
                callout.classList.remove('show');
                callout.classList.add('hidden');
            }, 5000);
        }

        function updateDisplay() {
            fetch('api/get-current-queue.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Received data:', data); // Debug log
                    
                    // Update windows display
                    data.windows.forEach(window => {
                        const element = document.getElementById('window' + window.number);
                        if (element) {
                            element.textContent = window.ticket || '-';
                            if (window.isNew) {
                                playAnnouncement(window.ticket, window.number);
                                showCallout(window.ticket, window.number);
                            }
                        }
                    });

                    // Update waiting list
                    const waitingList = document.getElementById('waitingList');
                    if (data.waiting && data.waiting.length > 0) {
                        waitingList.innerHTML = data.waiting
                            .map(ticket => `<div class="waiting-ticket">${ticket}</div>`)
                            .join('');
                    } else {
                        waitingList.innerHTML = '<div class="waiting-ticket">No waiting tickets</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching queue data:', error);
                });
        }

        // Initial update and set interval
        updateDisplay();
        setInterval(updateDisplay, 5000);
    </script>
</body>
</html>