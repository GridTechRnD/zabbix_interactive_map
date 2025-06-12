
const searchInput = document.getElementById('search-input');
const searchButton = document.getElementById('search-button');
const suggestionsDiv = createSuggestionsDiv();


searchInput.parentNode.appendChild(suggestionsDiv);


function createSuggestionsDiv() {
    const div = document.createElement('div');
    div.id = 'suggestions';
    div.style.display = 'none';
    return div;
}


function updateSuggestions(query) {
    suggestionsDiv.innerHTML = '';

    // if (query.length < 2) {
    //     hideSuggestions();
    //     return;
    // }

    const matches = getMatchingItems(query);

    if (matches.length > 0) {
        matches.forEach(match => {
            const item = createSuggestionItem(match);
            suggestionsDiv.appendChild(item);
        });
        showSuggestions();
    } else {
        hideSuggestions();
    }
}


function getMatchingItems(query) {
    const lowerQuery = query.toLowerCase();
    return cachedData
        .filter(item => doesItemMatch(item, lowerQuery))
        .map(item => mapItemToSuggestion(item));
}


function doesItemMatch(item, lowerQuery) {
    const fieldsToSearch = [
        item.name,
        item.inventory.type,
        item.inventory.type_full,
        item.host,
        item.inventory.location,
        item.inventory.serialno_a,
        item.inventory.location_lat,
        item.inventory.location_lon,
        item.hostid
    ];
    return fieldsToSearch.some(field =>
        (field || '').toString().toLowerCase().includes(lowerQuery)
    );
}


function mapItemToSuggestion(item) {
    return {
        name: item.name || 'N/A',
        alias: item.host || 'N/A',
        type: item.inventory.type || 'N/A',
        location: item.inventory.location || 'N/A'
    };
}


function createSuggestionItem(match) {
    const suggestionItem = document.createElement('div');
    suggestionItem.innerHTML = `
        <strong>${match.alias}</strong> (${match.type})<br>
        Location: ${match.location}<br>
        Name: ${match.name}
    `.trim();
    suggestionItem.style.padding = '8px';
    suggestionItem.style.cursor = 'pointer';
    suggestionItem.addEventListener('click', () => handleSuggestionClick(match.name));
    return suggestionItem;
}


function handleSuggestionClick(match) {
    searchInput.value = match;
    hideSuggestions();
    search();
}


function showSuggestions() {
    suggestionsDiv.style.display = 'block';
}


function hideSuggestions() {
    suggestionsDiv.style.display = 'none';
}


function showPop(message, timeout = 3000, hasCloseButton = false) {
    const popup = document.getElementById('custom-pop');
    if (popup) {
        popup.textContent = message;
        popup.classList.remove('hidden');
        popup.classList.add('show');

        if (hasCloseButton) {
            const closeButton = document.createElement('button');
            closeButton.textContent = 'x';
            closeButton.style.position = 'absolute';
            closeButton.style.top = '5px';
            closeButton.style.right = '10px';
            closeButton.style.background = 'transparent';
            closeButton.style.border = 'none';
            closeButton.style.fontSize = '24px';
            closeButton.style.cursor = 'pointer';
            closeButton.addEventListener('click', () => {
                popup.classList.remove('show');
                popup.classList.add('hidden');
            });
            popup.appendChild(closeButton);
        } else {
            setTimeout(() => {
                popup.classList.remove('show');
                popup.classList.add('hidden');
            }, timeout);
        }
    }
}


function search() {
    const query = searchInput.value.toLowerCase();

    if (!query) {
        showPop('Please enter a location to search.');
        return;
    }

    if (!Array.isArray(cachedData)) {
        showPop('Data is not available.');
        return;
    }

    const result = cachedData.find(item =>
        (item.name || '').toLowerCase().includes(query)
    );

    if (result) {
        const lat = parseFloat(result.inventory.location_lat);
        const lon = parseFloat(result.inventory.location_lon);
        if (!isNaN(lat) && !isNaN(lon)) {
            map.setView([lat, lon], 25);
        } else {
            showPop('Invalid coordinates.');
        }
    } else {
        showPop('Not found.');
    }
}


searchInput.addEventListener('input', (event) => {
    const query = event.target.value;
    updateSuggestions(query);
});

searchInput.addEventListener('blur', () => {
    setTimeout(hideSuggestions, 200);
});

searchInput.addEventListener('focus', () => {
    const query = searchInput.value;
    updateSuggestions(query);
});

searchButton.addEventListener('click', search);

searchInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        search();
    }
});