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
        UP: L.icon({ iconUrl: 'modules/zabbix-module-geomap/views/images/icon_up.png', iconSize: [38, 38] }),
        DOWN: L.icon({ iconUrl: 'modules/zabbix-module-geomap/views/images/icon_down.png', iconSize: [38, 38] })
    };

    const DEFAULT_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
    const MAP_OPTIONS = { zoomControl: false };
    const DEFAULT_VIEW = [0, 0];
    const DEFAULT_ZOOM = 13;


    let map, currentTileLayer, markersCluster;
    let mapCentered = false;
    let cachedData = [];


    function initMap() {
        map = L.map('map', MAP_OPTIONS).setView(DEFAULT_VIEW, DEFAULT_ZOOM);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

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
            if (marker.options.icon && marker.options.icon.options.iconUrl && marker.options.icon.options.iconUrl.includes('icon_down.png')) {
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
        <img src="${item.available === "0" ? 'modules/zabbix-module-geomap/views/images/icon_down.png' : 'modules/zabbix-module-geomap/views/images/icon_up.png'}" style="width: 38px; height: 38px;">
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

        const marker = L.marker([lat, lon], { icon: customIcon });
        marker.bindPopup(createPopup(item, [lat, lon]));
        return marker;
    }


    function createPopup(item, latLng) {
        const popContent = `
    <ul class="popup-content" tabindex="0">
            <li>
                <h3>View</h3>
            </li>
            <li><a tabindex="-1" aria-label="View, Dashboards" class="popup-content-item"
                href="zabbix.php?action=host.dashboard.view&amp;hostid=${item.hostid}">Dashboards</a></li>
            <li><a tabindex="-1" aria-label="View, Problems" class="popup-content-item"
                    href="zabbix.php?action=problem.view&amp;hostids%5B%5D=${item.hostid}&amp;filter_set=1">Problems</a></li>
            <li><a tabindex="-1" aria-label="View, Latest data" class="popup-content-item"
                    href="zabbix.php?action=latest.view&amp;hostids%5B%5D=${item.hostid}&amp;filter_set=1">Latest data</a></li>
            <li><a tabindex="-1" aria-label="View, Graphs" class="popup-content-item"
                    href="zabbix.php?action=charts.view&amp;filter_hostids%5B%5D=${item.hostid}&amp;filter_set=1">Graphs</a></li>
            <li><a tabindex="-1" aria-label="View, Inventory" class="popup-content-item"
                    href="hostinventories.php?hostid=${item.hostid}">Inventory</a></li>
            <li>
                <div></div>
            </li>
            <li>
                <h3>Configuration</h3>
            </li>
            <li><a tabindex="-1" aria-label="Configuration, Host" class="popup-content-item"
                href="zabbix.php?action=host.edit&amp;hostid=${item.hostid}">Host</a></li>
            <li><a tabindex="-1" aria-label="Configuration, Items" class="popup-content-item"
                    href="zabbix.php?action=item.list&amp;filter_set=1&amp;filter_hostids%5B%5D=${item.hostid}&amp;context=host">Items</a>
            </li>
            <li><a tabindex="-1" aria-label="Configuration, Triggers" class="popup-content-item"
                    href="zabbix.php?action=trigger.list&amp;filter_set=1&amp;filter_hostids%5B%5D=${item.hostid}&amp;context=host">Triggers</a>
            </li>
            <li><a tabindex="-1" aria-label="Configuration, Graphs" class="popup-content-item"
                    href="graphs.php?filter_set=1&amp;filter_hostids%5B%5D=${item.hostid}&amp;context=host">Graphs</a></li>
            <li>
                <div></div>
            </li>
            <li>
                <h3>Scripts</h3>
            </li>
            <!-- marcador para scripts -->
            <li id="scripts-placeholder-${item.hostid}"></li>
            <li>
                <div></div>
            </li>
            <li>
                <h3>Links</h3>
            </li>
            <!-- marcador para links -->
            <li id="links-placeholder-${item.hostid}"></li>
            <li>
                <div></div>
            </li>
        </ul>
  `;

        const popup = L.popup({ closeButton: true }).setContent(popContent);
        popup.on('add', () => loadDynamicContent(item.hostid));
        return popup;
    }


    function loadDynamicContent(hostid) {
        // Scripts
        const scriptsPlaceholder = document.getElementById(`scripts-placeholder-${hostid}`);
        if (scriptsPlaceholder) {
            // Remove possíveis <li> já inseridos após o placeholder
            let next = scriptsPlaceholder.nextSibling;
            while (next && next.tagName === 'LI' && !next.querySelector('h3')) {
                const toRemove = next;
                next = next.nextSibling;
                toRemove.remove();
            }

            const item = cachedData.find(h => h.hostid == hostid);
            if (item && Array.isArray(item.scripts) && item.scripts.length) {
                item.scripts.forEach(script => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.className = 'popup-content-item';
                    a.tabIndex = -1;
                    a.textContent = script.name;
                    // a.href = '#';
                    a.onclick = async (e) => {
                        e.preventDefault();
                        a.textContent = 'Executando...';
                        a.classList.add('disabled');
                        await executeScript(script.scriptid, hostid, script.name);
                        a.textContent = script.name;
                        a.classList.remove('disabled');
                    };
                    li.appendChild(a);
                    scriptsPlaceholder.parentNode.insertBefore(li, scriptsPlaceholder.nextSibling);
                });
            } else {
                const li = document.createElement('li');
                li.innerHTML = '<span style="color:#888">No scripts available.</span>';
                scriptsPlaceholder.parentNode.insertBefore(li, scriptsPlaceholder.nextSibling);
            }
        }

        // Links
        const linksPlaceholder = document.getElementById(`links-placeholder-${hostid}`);
        if (linksPlaceholder) {
            let next = linksPlaceholder.nextSibling;
            while (next && next.tagName === 'LI' && !next.querySelector('h3')) {
                const toRemove = next;
                next = next.nextSibling;
                toRemove.remove();
            }

            const item = cachedData.find(h => h.hostid == hostid);
            if (item && Array.isArray(item.links) && item.links.length) {
                item.links.forEach(link => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.className = 'popup-content-item';
                    a.tabIndex = -1;
                    a.textContent = link.name;
                    a.href = link.url;
                    a.target = '_blank';
                    li.appendChild(a);
                    linksPlaceholder.parentNode.insertBefore(li, linksPlaceholder.nextSibling);
                });
            } else {
                const li = document.createElement('li');
                li.innerHTML = '<span style="color:#888">No links available.</span>';
                linksPlaceholder.parentNode.insertBefore(li, linksPlaceholder.nextSibling);
            }
        }
    }


    async function executeScript(scriptid, hostid, scriptName) {
        try {
            const formData = new URLSearchParams();
            formData.append('scriptid', scriptid);
            formData.append('hostid', hostid);

            const response = await fetch('/zabbix.php?action=executescript.action', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
                credentials: 'include'
            });

            const result = await response.json();
            console.log(result);
            showPop(`Script "${scriptName}" executed successfully. Result: ${result.value}`, 10000, true);
        } catch (error) {
            console.error('Error executing script:', error);
            showPop(`Failed to execute script "${scriptName}".`, 5000, true);
        }
    }


    function loadData() {
        markersCluster.clearLayers();
        const bounds = L.latLngBounds();
        const statusFilter = document.getElementById('status-filter')?.value || '';
        const id = getIdFromUrl();
        let focusLatLng = null;

        const uniqueCoords = new Set();
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

            if (statusFilter && ((statusFilter === 'Up' && item.available === '0') || (statusFilter === 'Down' && item.available === '1'))) return;

            const lat = parseFloat(item.inventory.location_lat);
            const lon = parseFloat(item.inventory.location_lon);
            const coordKey = `${lat},${lon}`;

            if (uniqueCoords.has(coordKey)) return;
            uniqueCoords.add(coordKey);

            const marker = createMarker(item);
            markersCluster.addLayer(marker);
            const latLng = [lat, lon];
            bounds.extend(latLng);

            if (id && item.hostid === id) {
                focusLatLng = latLng;
            }
        });

        if (!mapCentered) {
            if (focusLatLng) {
                map.setView(focusLatLng, 16);
            } else if (bounds.isValid()) {
                map.fitBounds(bounds);
            }
            mapCentered = true;
        }
    }

    function fetchData() {
        try {
            <?php if (isset($data['hosts'])): ?>
                const data = <?= json_encode($data['hosts']) ?>;
                if (Array.isArray(data)) {
                    cachedData = data;
                    loadData();
                }
            <?php else: ?>
            <?php endif; ?>
        } catch (error) {
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

        if (statusFilter || statusFilter.value === "") statusFilter.addEventListener('change', loadData);

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
                closeButton.style.top = '2px';
                closeButton.style.right = '2px';
                closeButton.style.background = 'transparent';
                closeButton.style.border = 'none';
                closeButton.style.fontSize = '18px';
                closeButton.style.color = '#757575';
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

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            search();
        }
    });

</script>