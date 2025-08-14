document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('queueModal');
    const closeBtn = document.querySelector('.close-modal');
    const queueBtns = document.querySelectorAll('.queue-btn');
    const printBtn = document.querySelector('.print-btn');

    // Handle queue button clicks
    queueBtns.forEach(button => {
        button.addEventListener('click', function() {
            const service = this.dataset.service;
            getQueueNumber(service);
        });
    });

    // Close modal when clicking X or outside
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    // Get queue number from server
    function getQueueNumber(service) {
        fetch('api/queue/generate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ service: service })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showQueueNumber(data.queueNumber, data.estimatedTime, service);
            } else {
                alert(data.message || 'Failed to get queue number');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Failed to connect to the server');
        });
    }

    // Display queue number in modal
    function showQueueNumber(number, estimatedTime, service) {
        const queueNumberDiv = document.querySelector('.queue-number');
        const queueInfoP = document.querySelector('.queue-info');
        
        queueNumberDiv.textContent = number;
        queueInfoP.innerHTML = `
            Service: ${getServiceName(service)}<br>
            Estimated Wait Time: ${estimatedTime} minutes
        `;
        
        modal.style.display = 'block';
    }

    // Print queue ticket
    printBtn.addEventListener('click', function() {
        const printWindow = window.open('', '', 'width=600,height=600');
        const queueNumber = document.querySelector('.queue-number').textContent;
        const queueInfo = document.querySelector('.queue-info').innerHTML;
        
        printWindow.document.write(`
            <html>
            <head>
                <title>Queue Ticket</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .ticket { text-align: center; }
                    .queue-number { font-size: 36px; font-weight: bold; margin: 20px 0; }
                    .footer { margin-top: 20px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="ticket">
                    <h2>NORSU Registrar Queue</h2>
                    <div class="queue-number">${queueNumber}</div>
                    <div class="queue-info">${queueInfo}</div>
                    <div class="footer">
                        <p>Date: ${new Date().toLocaleDateString()}</p>
                        <p>Time: ${new Date().toLocaleTimeString()}</p>
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    });

    // Helper function to get full service name
    function getServiceName(code) {
        const services = {
            'TOR': 'Transcript of Records',
            'CERT': 'Certification',
            'AUTH': 'Authentication',
            'CLR': 'Clearance'
        };
        return services[code] || code;
    }

    // Category filtering
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            const category = this.dataset.category;
            const cards = document.querySelectorAll('.service-card');
            
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'flex';
                    card.style.animation = 'fadeIn 0.5s ease';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Queue button functionality
    document.querySelectorAll('.queue-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const service = this.dataset.service;
            // Add your queue generation logic here
            const modal = document.getElementById('queueModal');
            modal.style.display = 'flex';
        });
    });

    // Close modal functionality
    document.querySelector('.close-modal').addEventListener('click', function() {
        document.getElementById('queueModal').style.display = 'none';
    });
});