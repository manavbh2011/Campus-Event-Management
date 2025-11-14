const getTimeBasedGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return "Good Morning!";
    if (hour < 18) return "Good Afternoon!";
    return "Good Night!";
};

const showTimeGreeting = () => {
    const mainContent = document.querySelector('.main-content h1');
    const greeting = getTimeBasedGreeting();
    
    const greetingElem = document.createElement('div');
    greetingElem.textContent = greeting;
    greetingElem.style.cssText = `
        color: blue;
        font-size: 20px;
        margin-bottom: 10px;
    `;

    mainContent.parentNode.insertBefore(greetingElem, mainContent);
};

document.addEventListener('DOMContentLoaded', () => {
    showTimeGreeting();
});