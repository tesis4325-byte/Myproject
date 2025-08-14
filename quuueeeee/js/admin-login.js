document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const button = this.querySelector('.admin-button');
    const errorDiv = document.getElementById('admin-error');
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Authenticating...';
    
    const formData = {
        username: this.querySelector('[name="username"]').value,
        password: this.querySelector('[name="password"]').value
    };

    fetch('../api/admin/auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '<i class="fas fa-check"></i> Success!';
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1000);
        } else {
            button.disabled = false;
            button.innerHTML = '<span>Login to Dashboard</span><i class="fas fa-arrow-right"></i>';
            errorDiv.textContent = data.message || 'Invalid credentials';
        }
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = '<span>Login to Dashboard</span><i class="fas fa-arrow-right"></i>';
        errorDiv.textContent = 'An error occurred. Please try again.';
    });
});

// Password visibility toggle
document.querySelector('.toggle-password').addEventListener('click', function() {
    const passwordInput = document.querySelector('[name="password"]');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.classList.remove('fa-eye');
        this.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        this.classList.remove('fa-eye-slash');
        this.classList.add('fa-eye');
    }
});