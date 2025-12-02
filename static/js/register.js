const validateName = (name) => {
    return name.length >= 2 && /^[\p{L}\p{M}\s'\-\.]{2,}$/u.test(name);
};

const validateEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

const validatePassword = (password) => {
    return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(password);
};

const showError = (message) => {
    const existingError = document.querySelector('.js-error');
    if (existingError) {
        existingError.remove();
    }
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'js-error';
    errorDiv.style.cssText = `
        color: red;
        margin-bottom: 15px;
    `;
    errorDiv.textContent = message;
    
    const form = document.querySelector('.login-form');
    form.parentNode.insertBefore(errorDiv, form);
};

const handleFormSubmission = () => {
    const form = document.querySelector('.login-form');
    
    form.addEventListener('submit', function(e) {
        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (!validateName(firstName)) {
            e.preventDefault();
            showError('First name is invalid.');
            return;
        }
        
        if (!validateName(lastName)) {
            e.preventDefault();
            showError('Last name is invalid.');
            return;
        }
        
        if (!validateEmail(email)) {
            e.preventDefault();
            showError('Please enter a valid email.');
            return;
        }
        
        if (!validatePassword(password)) {
            e.preventDefault();
            showError('Password must be 8+ chars and include upper, lower, and a number.');
            return;
        }
        
        if (password !== confirmPassword) {
            e.preventDefault();
            showError('Passwords do not match.');
            return;
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    handleFormSubmission();
});
