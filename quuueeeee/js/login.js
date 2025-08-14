// Form toggle functionality
document.querySelectorAll('.toggle-btn').forEach(button => {
    button.addEventListener('click', function() {
        // Remove active class from all buttons and forms
        document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.form-section').forEach(form => form.classList.remove('active'));
        
        // Add active class to clicked button and corresponding form
        this.classList.add('active');
        document.getElementById(this.dataset.form + 'Form').classList.add('active');
    });
});

// Handle both login and register forms
// Simplify the form handling
document.querySelectorAll('.auth-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formType = this.closest('.form-section').id.replace('Form', '');
        const button = this.querySelector('.modern-button');
        const errorDiv = document.getElementById(formType + '-error');
        
        try {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Processing...';
            
            const formData = new FormData(this);
            const endpoint = formType === 'login' ? 'api/auth/login.php' : 'api/auth/register.php';

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                button.innerHTML = '<i class="fas fa-check"></i> Success!';
                button.style.background = 'linear-gradient(135deg, #28a745 0%, #218838 100%)';
                
                if (formType === 'register') {
                    // Switch to login form after successful registration
                    setTimeout(() => {
                        document.querySelector('[data-form="login"]').click();
                        const loginError = document.getElementById('login-error');
                        loginError.textContent = data.message;
                        loginError.style.display = 'block';
                        loginError.style.backgroundColor = '#d4edda';
                        loginError.style.color = '#155724';
                    }, 1000);
                } else {
                    // Regular login success
                    setTimeout(() => {
                        window.location.href = 'services.php';
                    }, 1000);
                }
            } else {
                throw new Error(data.message || 'Login failed');
            }
        } catch (error) {
            button.disabled = false;
            button.innerHTML = `<span>${formType === 'login' ? 'Login' : 'Register'}</span>
                              <i class="fas fa-${formType === 'login' ? 'arrow-right' : 'user-plus'}"></i>`;
            errorDiv.textContent = error.message || 'An error occurred. Please try again.';
            errorDiv.style.display = 'block';
            console.error('Error:', error);
        }
    });
});

// Input formatting for student number
document.querySelectorAll('[name="student_number"]').forEach(input => {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9);
        this.style.borderColor = this.value.length === 9 ? '#28a745' : '#e1e1e1';
    });
});