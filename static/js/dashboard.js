class Event {
    constructor(id, title, description, event_date, location, capacity, category, status, created_by, created_at, updated_at, first_name = '', last_name = '') {
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
    }
}

const loadEventsFromAPI = () => {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'index.php?action=api&endpoint=events',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
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
                        eventData.last_name
                    )
                );
                resolve(events);
            },
            error: function() {
                console.error('Error loading events');
                resolve([]);
            }
        });
    });
};

const loadAllEvents = async () => {
    const events = await loadEventsFromAPI();
    
    if (events.length > 0) {
        $('.events-section').remove();
        
        const allEventsSection = $(`
            <div class="events-section">
                <h2>All Campus Events</h2>
                <div class="event-list" id="all-events"></div>
            </div>
        `);
        
        $('.dashboard-content').append(allEventsSection);
        
        events.forEach(event => {
            let titleCaseCategory = event.category.charAt(0).toUpperCase() + event.category.slice(1);
            const eventHtml = `
                <div class="event-item">
                    <div>
                        <h3>${event.title}</h3>
                        <p>${new Date(event.event_date).toLocaleDateString()} - ${event.location}</p>
                        <p><strong>Category:</strong> ${titleCaseCategory} | <strong>Capacity:</strong> ${event.capacity}</p>
                        ${event.organizer ? `<small>by ${event.organizer}</small>` : ''}
                    </div>
                </div>
            `;
            $('#all-events').append(eventHtml);
        });
    }
};

$(document).ready(async function() {
    await loadAllEvents();
    
    $('.event-item').hover(
        function() {
            $(this).css('background-color', 'azure');
        },
        function() {
            $(this).css('background-color', '#f9f9f9');
        }
    );
});
