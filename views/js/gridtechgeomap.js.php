<?php

/**
 * @var CView $this
 */

?>

<script type="text/javascript">
    // Leaflet JS and CSS imports
    $('head').append('<script type="text/javascript" src="modules/zabbix-module-geomap/views/Leaflet/js/leaflet.js"/>');
    $('head').append('<script type="text/javascript" src="modules/zabbix-module-geomap/views/Leaflet/js/leaflet.markercluster.js"/>');
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/Leaflet/css/leaflet.css"/>');
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/Leaflet/css/MarkerCluster.css"/>');
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/Leaflet/css/MarkerCluster.Default.css"/>');

    //All JS and CSS import
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/css/marker.css"/>');
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/css/menu.css"/>');
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/css/search.css"/>');
    $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/css/global.css"/>');


    const ICONS = {
        UP: L.icon({
            iconUrl: 'http://localhost:8081/static/images/icon_up.png',
            iconSize: [38, 38]
        }),
        DOWN: L.icon({
            iconUrl: 'http://localhost:8081/static/images/icon_down.png',
            iconSize: [38, 38]
        })
    };

    const DEFAULT_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    const MAP_OPTIONS = {
        zoomControl: false
    };
    const DEFAULT_VIEW = [0, 0];
    const DEFAULT_ZOOM = 1;


    let map, currentTileLayer, markersCluster;
    let cachedData = [];


    function initMap() {
        map = L.map('map', MAP_OPTIONS).setView(DEFAULT_VIEW, DEFAULT_ZOOM);
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        currentTileLayer = L.tileLayer(DEFAULT_TILE_URL, {
            maxZoom: 19,
            attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        markersCluster = L.markerClusterGroup({
            iconCreateFunction: createClusterIcon
        });
        map.addLayer(markersCluster);
    }


    function createClusterIcon(cluster) {
        var hasDown = null;
        cluster.getAllChildMarkers().forEach(marker => {
            if (marker.options.icon && marker.options.icon.options.iconUrl && marker.options.icon.options.iconUrl
                .includes('icon_down.png')) {
                hasDown = true;
            }
        });
        let sizeClass = 'large';
        const count = cluster.getChildCount();
        if (hasDown) sizeClass = 'down';
        else if (count < 10) sizeClass = 'small';
        else if (count < 100) sizeClass = 'medium';

        return L.divIcon({
            html: `<div><span>${count}</span></div>`,
            className: `marker-cluster marker-cluster-${sizeClass}`,
            iconSize: [40, 40]
        });
    }


    function createMarkerHTML(item) {
        return `
    <div class="marker-box">
      <div class="marker-item alias"><b>${item.host || 'N/A'}</b></div>
      <div class="marker-item image">
        <img src="${item.available === "0" ? 'http://localhost:8081/static/images/icon_down.png' : 'http://localhost:8081/static/images/icon_up.png'}" style="width: 38px; height: 38px;">
      </div>
      <div class="marker-item grouped">
        <div><b>${item.name || 'N/A'}</b></div>
        <div><b>${item.interfaces[0].ip || 'N/A'}</b></div>
        <div>Provider: <b>${item.inventory.provider || 'N/A'}</b></div>
        <div>CSQ: <b>${item.inventory.csq || 'N/A'}</b></div>
        <div>RPL: <b>${item.inventory.rpl || 'N/A'}</b></div>
      </div>
    </div>
  `;
    }


    function createMarker(item) {
        const lat = parseFloat(item.inventory.location_lat);
        const lon = parseFloat(item.inventory.location_lon);

        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: createMarkerHTML(item),
            iconSize: [38, 58]
        });

        const marker = L.marker([lat, lon], {
            icon: customIcon
        });
        marker.bindPopup(createPopup(item, [lat, lon]));
        return marker;
    }


    function createPopup(item, latLng) {
        const popContent = `
    <div class="popup-content">
      <b>Details</b><br>
      Name: <b>${item.name || 'N/A'}</b><br>
      Host: <b>${item.host || 'N/A'}</b><br>
      IP: <b>${item.interfaces[0].ip || 'N/A'}</b><br>
      Serial Number: <b>${item.inventory.serialno_a || 'N/A'}</b><br>
      Type: <b>${item.inventory.type || 'N/A'}</b><br>
      Type (Full Details): <b>${item.inventory.type_full || 'N/A'}</b><br>
      Location: <b>${item.inventory.location || 'N/A'}</b><br>
      Latitude: <b>${latLng[0]}</b><br>
      Longitude: <b>${latLng[1]}</b><br>
      <div id="scripts-container-${item.hostid}">Loading scripts...</div>
    </div>
  `;

        const popup = L.popup({
            closeButton: true
        }).setContent(popContent);
        popup.on('add', () => loadDynamicContent(item.hostid));
        return popup;
    }


    async function loadDynamicContent(hostid) {
        const scriptsContainer = document.getElementById(`scripts-container-${hostid}`);
        if (!scriptsContainer) return;

        try {
            const [links, scripts] = await Promise.all([
                fetch('http://localhost:8081/links', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hostid
                    })
                }).then(res => res.json()),
                fetch('http://localhost:8081/scripts', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hostid
                    })
                }).then(res => res.json())
            ]);


            const linksContainer = document.createElement('div');
            linksContainer.innerHTML = '<h4>Links:</h4>';
            if (Array.isArray(links) && links.length) {
                links.forEach(link => {
                    const button = document.createElement('button');
                    button.className = 'custom-button';
                    button.textContent = link.name;
                    button.onclick = () => window.open(link.url, '_blank');
                    linksContainer.appendChild(button);
                });
            } else {
                linksContainer.textContent = 'No links available.';
            }
            scriptsContainer.before(linksContainer);


            scriptsContainer.innerHTML = '<h4>Scripts:</h4>';
            if (Array.isArray(scripts) && scripts.length) {
                scripts.forEach(script => {
                    const button = document.createElement('button');
                    button.className = 'custom-button';
                    button.textContent = script.name;
                    button.onclick = () => executeScript(script.scriptid, hostid, script.name);
                    scriptsContainer.appendChild(button);
                });
            } else {
                scriptsContainer.textContent = 'No scripts available.';
            }
        } catch (error) {
            console.error('Error loading dynamic content:', error);
            scriptsContainer.textContent = 'Failed to load content.';
        }
    }


    async function executeScript(scriptid, hostid, scriptName) {
        try {
            const response = await fetch('http://localhost:8081/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    scriptid,
                    hostid
                })
            });
            const result = await response.json();
            showPop(`Script "${scriptName}" executed successfully. Result: ${result.value}`, 10000);
        } catch (error) {
            console.error('Error executing script:', error);
            showPop(`Failed to execute script "${scriptName}".`, 5000);
        }
    }


    function loadData() {
        markersCluster.clearLayers();
        const bounds = L.latLngBounds();
        const statusFilter = document.getElementById('status-filter')?.value || '';

        cachedData.forEach(item => {
            if (
                !item.inventory ||
                !item.inventory.location_lat ||
                !item.inventory.location_lon ||
                isNaN(parseFloat(item.inventory.location_lat)) ||
                isNaN(parseFloat(item.inventory.location_lon))
            ) {
                return;
            }

            if (statusFilter && ((statusFilter === 'Up' && item.available === '0') || (statusFilter === 'Down' &&
                item.available === '1'))) return;

            const marker = createMarker(item);
            markersCluster.addLayer(marker);
            bounds.extend([parseFloat(item.inventory.location_lat), parseFloat(item.inventory.location_lon)]);
        });

        if (bounds.isValid()) map.fitBounds(bounds);
    }

    async function fetchData() {
        try {
            const id = getIdFromUrl();
            const url = id ? `http://localhost:8081/data?id=${encodeURIComponent(id)}` :
                'http://localhost:8081/data?id=28742';
            const response = await fetch(url, {
                method: 'GET',
                mode: 'cors'
            });
            if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);
            const data = await response.json();
            if (Array.isArray(data)) {
                cachedData = data;
                loadData();
            }
        } catch (error) {
            console.error('Error fetching data:', error);
        }
    }

    function getIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }

    function setupEventListeners() {
        const statusFilter = document.getElementById('status-filter');
        const mapThemeFilter = document.getElementById('map-theme-filter');
        const savedThemeUrl = localStorage.getItem('mapTheme');

        if (statusFilter) statusFilter.addEventListener('change', loadData);

        if (mapThemeFilter) {
            if (savedThemeUrl) {
                mapThemeFilter.value = savedThemeUrl;
                updateMapTheme(savedThemeUrl);
            }
            mapThemeFilter.addEventListener('change', (e) => {
                const themeUrl = e.target.value || DEFAULT_TILE_URL;
                localStorage.setItem('mapTheme', themeUrl);
                updateMapTheme(themeUrl);
            });
        }
    }

    function updateMapTheme(themeUrl) {
        map.removeLayer(currentTileLayer);
        currentTileLayer = L.tileLayer(themeUrl, {
            maxZoom: 19,
            attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initMap();
        fetchData();
        setupEventListeners();
    });

    const searchInput = document.getElementById('search-input');
    const suggestionsDiv = document.getElementById('suggestions');

    searchInput.parentNode.appendChild(suggestionsDiv);

    function updateSuggestions(query) {
        suggestionsDiv.innerHTML = '';
            if (query.length < 1) {
                hideSuggestions();
                return;
            }
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

    function showPop(message, timeout = 3000) {
        const popup = document.getElementById('custom-pop');
        if (popup) {
            popup.textContent = message;
            popup.classList.remove('hidden');
            popup.classList.add('show');
            setTimeout(() => {
                popup.classList.remove('show');
                popup.classList.add('hidden');
            }, timeout);
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

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            search();
        }
    });

</script>