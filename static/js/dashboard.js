class Event {
    constructor(
        id,
        title,
        description,
        event_date,
        location,
        capacity,
        category,
        status,
        created_by,
        created_at,
        updated_at,
        first_name = '',
        last_name = '',
        user_registered = false,
        is_creator = false
    ) {
        this.id = id;
        this.title = title;
        this.description = description;
        this.event_date = event_date;
        this.location = location;
        this.capacity = capacity;
        this.category = category;
        this.status = status;
        this.created_by = created_by;
        this.created_at = created_at;
        this.updated_at = updated_at;
        this.organizer = `${first_name} ${last_name}`.trim();
        this.user_registered = Boolean(user_registered);
        this.is_creator = Boolean(is_creator);
    }
}

let cachedEvents = [];
const loadEventsFromAPI = () => {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'index.php?action=api&endpoint=events',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (!data || !data.success || !Array.isArray(data.events)) {
                    console.error('Unexpected events API response', data);
                    resolve([]);
                    return;
                }

                const events = data.events.map(eventData =>
                    new Event(
                        eventData.id,
                        eventData.title,
                        eventData.description,
                        eventData.event_date,
                        eventData.location,
                        eventData.capacity,
                        eventData.category,
                        eventData.status,
                        eventData.created_by,
                        eventData.created_at,
                        eventData.updated_at,
                        eventData.first_name,
                        eventData.last_name,
                        eventData.user_registered,
                        eventData.is_creator
                    )
                );

                cachedEvents = events;
                resolve(events);
            },
            error: function (xhr, status, error) {
                console.error('Error loading events:', status, error);
                reject(error);
            }
        });
    });
};

const renderAllEvents = (events) => {
    const $container = $('#all-events');
    if (!$container.length) return;

    $container.empty();

    if (!events.length) {
        $container.append('<p>No events found.</p>');
        return;
    }

    events.forEach(event => {
        const titleCaseCategory =
            event.category && event.category.length > 0
                ? event.category.charAt(0).toUpperCase() + event.category.slice(1)
                : 'General';

        let actionHtml = '';
        if (event.is_creator) {
            actionHtml = `<span class="badge-owner">You created this event</span>`;
        } else if (event.user_registered) {
            actionHtml = `<span class="badge-registered">Registered</span>`;
        } else {
            actionHtml = `<button class="btn-register" data-event-id="${event.id}">Register</button>`;
        }

        const eventHtml = `
            <div class="event-item">
                <div>
                    <h3>${event.title}</h3>
                    <p>${new Date(event.event_date).toLocaleDateString()} - ${event.location}</p>
                    <p><strong>Category:</strong> ${titleCaseCategory} | <strong>Capacity:</strong> ${event.capacity}</p>
                    ${event.organizer ? `<small>by ${event.organizer}</small>` : ''}
                    <div class="event-actions">
                        ${actionHtml}
                    </div>
                </div>
            </div>
        `;
        $container.append(eventHtml);
    });
};

const renderRegisteredEvents = (events) => {
    const $container = $('#registered-events');
    if (!$container.length) return;

    $container.empty();

    const registered = events.filter(ev => ev.user_registered);
    if (!registered.length) {
        $container.append('<p>You have not registered for any events yet.</p>');
        return;
    }

    registered.forEach(event => {
        const eventHtml = `
            <div class="event-item">
                <div>
                    <h3>${event.title}</h3>
                    <p>${new Date(event.event_date).toLocaleDateString()} - ${event.location}</p>
                    <p><strong>Status:</strong> Registered</p>
                </div>
            </div>
        `;
        $container.append(eventHtml);
    });
};

const loadDashboardEvents = async () => {
    try {
        const events = await loadEventsFromAPI();
        renderAllEvents(events);
        renderRegisteredEvents(events);
    } catch (e) {
        console.error(e);
        const $all = $('#all-events');
        if ($all.length) {
            $all.empty().append('<p class="error-message">Unable to load events right now.</p>');
        }
        const $reg = $('#registered-events');
        if ($reg.length) {
            $reg.empty().append('<p class="error-message">Unable to load your registered events right now.</p>');
        }
    }
};

const wireDashboardRegistration = () => {
    $(document).on('click', '.btn-register', function (e) {
        e.preventDefault();
        const button = $(this);
        const eventId = button.data('event-id');

        $.ajax({
            url: 'index.php?action=api&endpoint=register_event',
            type: 'POST',
            dataType: 'json',
            data: { event_id: eventId },
            success: function (data) {
                if (data && data.success) {
                    loadDashboardEvents();
                } else {
                    alert((data && data.message) || 'Unable to register for this event.');
                }
            },
            error: function () {
                alert('Error contacting server.');
            }
        });
    });
};

$(document).ready(async function () {
    wireDashboardRegistration();
    await loadDashboardEvents();

    $(document).on('mouseenter', '.event-item', function () {
        $(this).css('background-color', 'azure');
    });
    $(document).on('mouseleave', '.event-item', function () {
        $(this).css('background-color', '#f9f9f9');
    });
});
