const showLoginError = (message) => {
    const errorDiv = document.getElementById('login-error');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
};

const handleLoginSubmission = () => {
    const form = document.querySelector('.login-form');
    
    form.addEventListener('submit', function(e) {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        
        if (!email) {
            e.preventDefault();
            showLoginError('Email is required');
            return;
        }
        else if (!validateEmail(email)) {
            e.preventDefault();
            showLoginError('Please enter a valid email.');
            return;
        }
        else if (!password) {
            e.preventDefault();
            showLoginError('Password is required');
            return;
        }
        else if (!validatePassword(password)) {
            e.preventDefault();
            showLoginError('Password must be 8+ chars and include upper, lower, and a number.');
            return;
        }
        document.getElementById('login-error').style.display = 'none';
    });
};

document.addEventListener('DOMContentLoaded', () => {
    handleLoginSubmission();
});