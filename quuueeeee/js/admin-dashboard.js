// Sidebar Toggle
document.getElementById('sidebar-toggle').addEventListener('click', function() {
    document.querySelector('.admin-sidebar').classList.toggle('active');
});

// Initialize Charts
const ctx = document.getElementById('serviceChart').getContext('2d');
const serviceChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Transcript', 'Enrollment', 'Certification', 'Grade Verification'],
        datasets: [{
            data: [12, 19, 8, 15],
            backgroundColor: [
                '#4e73df',
                '#1cc88a',
                '#36b9cc',
                '#f6c23e'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Load Queue Data
function loadQueueData() {
    fetch('../api/admin/queue.php')
        .then(response => response.json())
        .then(data => {
            updateQueueTable(data.queue);
            updateStats(data.stats);
        })
        .catch(error => console.error('Error loading queue data:', error));
}

function updateQueueTable(queue) {
    const tbody = document.querySelector('#queueTable tbody');
    tbody.innerHTML = queue.map(item => `
        <tr>
            <td>${item.ticket_number}</td>
            <td>${item.student_name}</td>
            <td>${item.service}</td>
            <td>
                <span class="status-badge ${item.status.toLowerCase()}">
                    ${item.status}
                </span>
            </td>
            <td>
                <button class="action-btn serve" onclick="serveTicket('${item.ticket_number}')">
                    <i class="fas fa-play"></i>
                </button>
                <button class="action-btn complete" onclick="completeTicket('${item.ticket_number}')">
                    <i class="fas fa-check"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function updateStats(stats) {
    document.getElementById('currentQueue').textContent = stats.current_queue;
    document.getElementById('servedToday').textContent = stats.served_today;
    document.getElementById('avgWaitTime').textContent = stats.avg_wait_time + ' min';
    document.getElementById('totalStudents').textContent = stats.total_students;
}

// Initial load
loadQueueData();

// Refresh every 30 seconds
setInterval(loadQueueData, 30000);