const showProfileError = (message) => {
    const existing = document.querySelector('.profile-error');
    if (existing) existing.remove();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'profile-error';
    errorDiv.style.cssText = `color: red; background: #ffebee; padding: 15px; margin: 15px 0; border-radius: 4px; font-weight: bold;`;
    errorDiv.textContent = message;
    
    const form = document.querySelector('form');
    if (form) {
        form.parentNode.insertBefore(errorDiv, form);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const validateProfileForm = () => {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            
            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    showProfileError('Passwords do not match.');
                } else if (newPassword.length < 8) {
                    e.preventDefault();
                    showProfileError('Password must be at least 8 characters.');
                } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(newPassword)) {
                    e.preventDefault();
                    showProfileError('Password must include uppercase, lowercase, and number.');
                }
            }
        });
    }
};

const updateMatchStatus = (message, color) => {
    let status = document.getElementById('match-status');
    if (!status) {
        status = document.createElement('div');
        status.id = 'match-status';
        status.style.cssText = `margin-top: 5px; font-size: 14px; font-weight: bold;`;
        document.getElementById('confirm_password')?.parentNode.appendChild(status);
    }
    status.textContent = message;
    if (color) {
        status.style.color = color;
    }
};

const checkPasswordMatch = () => {
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    
    if (newPass && confirmPass) {
        const check = () => {
            if (confirmPass.value.length > 0) {
                if (newPass.value === confirmPass.value) {
                    updateMatchStatus('Passwords match ✓', 'green');
                } else {
                    updateMatchStatus('Passwords do not match', 'red');
                }
            } else {
                updateMatchStatus('', '');
            }
        };
        newPass.addEventListener('input', check);
        confirmPass.addEventListener('input', check);
    }
};

const updateStrengthMeter = (password) => {
    let meter = document.getElementById('strength-meter');
    if (!meter) {
        meter = document.createElement('div');
        meter.id = 'strength-meter';
        meter.style.cssText = `margin-top: 5px; font-size: 14px; font-weight: bold;`;
        document.getElementById('new_password')?.parentNode.appendChild(meter);
    }
    
    if (!password) {
        meter.textContent = '';
        return;
    }
    
    let score = 0;
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/\d/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    if (score <= 2) {
        meter.textContent = 'Strength: Weak';
        meter.style.color = '#d32f2f';
    } else if (score <= 4) {
        meter.textContent = 'Strength: Medium';
        meter.style.color = '#f57c00';
    } else {
        meter.textContent = 'Strength: Strong';
        meter.style.color = '#388e3c';
    }
};

const addPasswordStrengthMeter = () => {
    const newPass = document.getElementById('new_password');
    if (newPass) {
        newPass.addEventListener('input', (e) => updateStrengthMeter(e.target.value));
    }
};

document.addEventListener('DOMContentLoaded', () => {
    checkPasswordMatch();
    addPasswordStrengthMeter();
    validateProfileForm();
});
