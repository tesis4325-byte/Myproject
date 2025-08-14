<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Queue Number - NORSU Queue</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 2rem;
            text-align: center;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .header img {
            width: 150px;
            margin-bottom: 1rem;
        }

        .ticket-button {
            background: #1a237e;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            cursor: pointer;
            margin: 2rem 0;
            transition: background 0.3s ease;
        }

        .ticket-button:hover {
            background: #283593;
        }

        .ticket-info {
            background: #f5f5f5;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
            display: none;
        }

        .ticket-number {
            font-size: 3rem;
            font-weight: bold;
            color: #1a237e;
            margin: 1rem 0;
        }

        .show {
            display: block !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="images/norsu-logo.png" alt="NORSU Logo">
            <h1>NORSU Queue System</h1>
        </div>

        <div class="ticket-section">
            <h2>Get Your Queue Number</h2>
            <button id="getTicket" class="ticket-button">Get Enrollment Ticket</button>
            <div id="ticketDisplay" class="ticket-info"></div>
        </div>
    </div>

    <script>
        document.getElementById('getTicket').addEventListener('click', function() {
            // Disable button while processing
            const button = document.getElementById('getTicket');
            button.disabled = true;
            
            fetch('api/create-ticket.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    const ticketDisplay = document.getElementById('ticketDisplay');
                    ticketDisplay.innerHTML = `
                        <h3>Your Ticket Number</h3>
                        <p class="ticket-number">${data.ticket}</p>
                        <p>Please wait for your number to be called</p>
                        <p>You can view the queue status on the display screen</p>
                    `;
                    ticketDisplay.classList.add('show');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error generating ticket. Please try again.');
                })
                .finally(() => {
                    button.disabled = false;
                });
        });
    </script>
</body>
</html>