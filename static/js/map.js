
const ICONS = {
    UP: L.icon({ iconUrl: '/static/images/icon_up.png', iconSize: [38, 38] }),
    DOWN: L.icon({ iconUrl: '/static/images/icon_down.png', iconSize: [38, 38] })
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
        <img src="${item.available === "0" ? '/static/images/icon_down.png' : '/static/images/icon_up.png'}" style="width: 38px; height: 38px;">
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
    marker.bindPopup(createPopup(item, [lat, lon]), { minWidth: 166, maxWidth: 250, minHeight: 300, maxHeight: 836 });
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


async function loadDynamicContent(hostid) {
    const scriptsPlaceholder = document.getElementById(`scripts-placeholder-${hostid}`);
    const linksPlaceholder = document.getElementById(`links-placeholder-${hostid}`);

    try {
        // Fetch links and scripts data concurrently
        const [links, scripts] = await Promise.all([
            fetch('/links', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hostid })
            }).then(res => res.json()),
            fetch('/scripts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hostid })
            }).then(res => res.json())
        ]);

        // Handle Scripts
        if (scriptsPlaceholder) {
            // Remove existing <li> elements after the placeholder
            let next = scriptsPlaceholder.nextSibling;
            while (next && next.tagName === 'LI' && !next.querySelector('h3')) {
                const toRemove = next;
                next = next.nextSibling;
                toRemove.remove();
            }

            if (Array.isArray(scripts) && scripts.length) {
                scripts.forEach(script => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.className = 'popup-content-item';
                    a.tabIndex = -1;
                    a.textContent = script.name;
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

        // Handle Links
        if (linksPlaceholder) {
            // Remove existing <li> elements after the placeholder
            let next = linksPlaceholder.nextSibling;
            while (next && next.tagName === 'LI' && !next.querySelector('h3')) {
                const toRemove = next;
                next = next.nextSibling;
                toRemove.remove();
            }

            if (Array.isArray(links) && links.length) {
                links.forEach(link => {
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
    } catch (error) {
        console.error('Error loading dynamic content:', error);
        // Display error message for scripts
        if (scriptsPlaceholder) {
            const li = document.createElement('li');
            li.innerHTML = '<span style="color:#888">Failed to load scripts.</span>';
            scriptsPlaceholder.parentNode.insertBefore(li, scriptsPlaceholder.nextSibling);
        }
        // Display error message for links
        if (linksPlaceholder) {
            const li = document.createElement('li');
            li.innerHTML = '<span style="color:#888">Failed to load links.</span>';
            linksPlaceholder.parentNode.insertBefore(li, linksPlaceholder.nextSibling);
        }
    }
}


async function executeScript(scriptid, hostid, scriptName) {
    try {
        const response = await fetch('/execute', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ scriptid, hostid })
        });
        const result = await response.json();
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

    map.setMaxBounds(null);

    if (bounds.isValid()) {
        const southWest = bounds.getSouthWest();
        const northEast = bounds.getNorthEast();
        const expandedBounds = L.latLngBounds(
            [southWest.lat - 0.1, southWest.lng - 0.1],
            [northEast.lat + 0.1, northEast.lng + 0.1]
        );

        if (!mapCentered) {
            if (focusLatLng) {
                map.setView(focusLatLng, 16);
            } else {
                map.fitBounds(expandedBounds, { padding: [50, 50] });
            }
            mapCentered = true;
        }

        map.setMaxBounds(expandedBounds);
        map.options.maxBoundsViscosity = 1.0;
        map.setMinZoom(13);
    }
}

async function fetchData() {
    try {
        const id = getIdFromUrl();
        const url = id ? `/data?id=${encodeURIComponent(id)}` : '/data';
        const response = await fetch(url, { method: 'GET', mode: 'cors' });
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