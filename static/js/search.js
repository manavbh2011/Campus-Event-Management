const showSearchError = (message) => {
    const existing = document.querySelector('.search-error');
    if (existing) existing.remove();

    const errorDiv = document.createElement('div');
    errorDiv.className = 'search-error';
    errorDiv.style.cssText =
        'color:red;background:#ffebee;padding:10px;margin:10px 0;border-radius:4px;';
    errorDiv.textContent = message;

    const form = document.querySelector('form[action="search.php"]');
    if (form && form.parentNode) {
        form.parentNode.insertBefore(errorDiv, form);
        setTimeout(() => {
            if (errorDiv.parentNode) errorDiv.remove();
        }, 3000);
    }
};

const validateSearchForm = () => {
    const form = document.querySelector('form[action="search.php"]');

    if (!form) return;

    form.addEventListener('submit', (e) => {
        const searchValue =
            form.querySelector('input[name="q"]')?.value.trim() || '';
        const locationValue =
            form.querySelector('input[name="location"]')?.value.trim() || '';

        if (!searchValue && !locationValue) {
            e.preventDefault();
            showSearchError('Please enter a search term or location.');
        } else if (searchValue && searchValue.length === 1) {
            e.preventDefault();
            showSearchError('Search term must be at least 2 characters.');
        }
    });
};

const highlightSearchTerms = () => {
    const params = new URLSearchParams(window.location.search);
    const searchTerm = params.get('q');

    if (!searchTerm) return;

    const elements = document.querySelectorAll(
        '.event-card h3, .event-card p'
    );

    elements.forEach((elem) => {
        const text = elem.textContent;
        if (!text) return;

        const regex = new RegExp(`(${searchTerm})`, 'gi');
        if (regex.test(text)) {
            elem.innerHTML = text.replace(
                regex,
                '<mark style="background:yellow;">$1</mark>'
            );
        }
    });
};

const wireRegistrationButtons = () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-register');
        if (!btn) return;

        e.preventDefault();
        const eventId = btn.getAttribute('data-event-id');

        fetch('index.php?action=api&endpoint=register_event', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'event_id=' + encodeURIComponent(eventId),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data && data.success) {
                    const span = document.createElement('span');
                    span.className = 'badge-registered';
                    span.textContent = 'Registered';
                    btn.replaceWith(span);
                } else {
                    alert(
                        (data && data.message) ||
                            'Unable to register for this event.'
                    );
                }
            })
            .catch(() => {
                alert('Error contacting server.');
            });
    });
};

const displayResultCount = () => {
    const container = document.querySelector('.search-results .event-list');
    if (!container) return;

    const count = container.querySelectorAll('.event-card').length;
    if (!count) return;

    const counter = document.createElement('div');
    counter.style.cssText =
        'font-weight:bold;color:#1d4ed8;background:#e3f2fd;padding:8px 12px;border-radius:4px;margin-bottom:15px;';
    counter.textContent = `Found ${count} event${count !== 1 ? 's' : ''}`;

    container.parentNode.insertBefore(counter, container);
};

document.addEventListener('DOMContentLoaded', () => {
    validateSearchForm();
    highlightSearchTerms();
    displayResultCount();
    wireRegistrationButtons();
});
