const showSearchError = (message) => {
    const existing = document.querySelector('.search-error');
    if (existing) existing.remove();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'search-error';
    errorDiv.style.cssText = `color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 4px;`;
    errorDiv.textContent = message;
    
    const form = document.querySelector('form[action="search.php"]');
    if (form) {
        form.parentNode.insertBefore(errorDiv, form);
        setTimeout(() => errorDiv.remove(), 3000);
    }
};

const validateSearchForm = () => {
    const form = document.querySelector('form[action="search.php"]');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const searchValue = form.querySelector('input[name="q"]')?.value.trim() || '';
            const locationValue = form.querySelector('input[name="location"]')?.value.trim() || '';
            
            if (!searchValue && !locationValue) {
                e.preventDefault();
                showSearchError('Please enter a search term or location.');
            } else if (searchValue.length === 1) {
                e.preventDefault();
                showSearchError('Search term must be at least 2 characters.');
            }
        });
    }
};

const highlightSearchTerms = () => {
    const searchTerm = new URLSearchParams(window.location.search).get('q');
    
    if (searchTerm) {
        document.querySelectorAll('.event-card h3, .event-card p:last-child').forEach(elem => {
            const text = elem.textContent;
            const regex = new RegExp(`(${searchTerm})`, 'gi');
            if (regex.test(text)) {
                elem.innerHTML = text.replace(regex, '<mark style="background: yellow;">$1</mark>');
            }
        });
    }
};

const displayResultCount = () => {
    const container = document.querySelector('.search-results');
    if (container) {
        const count = container.querySelectorAll('.event-card').length;
        if (count > 0) {
            const counter = document.createElement('div');
            counter.style.cssText = `font-weight: bold; color: #1976d2; padding: 10px; background: #e3f2fd; border-radius: 4px; margin-bottom: 15px;`;
            counter.textContent = `Found ${count} event${count !== 1 ? 's' : ''}`;
            container.insertBefore(counter, container.firstChild);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    validateSearchForm();
    highlightSearchTerms();
    displayResultCount();
});
