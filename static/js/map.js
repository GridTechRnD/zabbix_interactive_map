
const ICONS = {
    UP: L.icon({ iconUrl: '/static/images/icon_up.png', iconSize: [38, 38] }),
    DOWN: L.icon({ iconUrl: '/static/images/icon_down.png', iconSize: [38, 38] })
};

const DEFAULT_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const MAP_OPTIONS = { zoomControl: false };
const DEFAULT_VIEW = [0, 0];
const DEFAULT_ZOOM = 1;


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

    const popup = L.popup({ closeButton: true }).setContent(popContent);
    popup.on('add', () => loadDynamicContent(item.hostid));
    return popup;
}


async function loadDynamicContent(hostid) {
    const scriptsContainer = document.getElementById(`scripts-container-${hostid}`);
    if (!scriptsContainer) return;

    try {
        const [links, scripts] = await Promise.all([
            fetch('/links', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ hostid }) }).then(res => res.json()),
            fetch('/scripts', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ hostid }) }).then(res => res.json())
        ]);

        const linksContainer = document.createElement('div');
        linksContainer.innerHTML = '<h4>Links:</h4>';
        if (Array.isArray(links) && links.length) {
            links.forEach(link => {
                const button = document.createElement('button');
                button.className = 'custom-button';
                button.textContent = link.name;
                button.onclick = async (e) => {
                    button.disabled = true;
                    const original = button.innerHTML;
                    button.innerHTML = `<span class="spinner"></span> Abrindo...`;
                    setTimeout(() => {
                        window.open(link.url, '_blank');
                        button.innerHTML = original;
                        button.disabled = false;
                    }, 500);
                };
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
                button.onclick = async () => {
                    button.disabled = true;
                    const original = button.innerHTML;
                    button.innerHTML = `<span class="spinner"></span> Executando...`;
                    await executeScript(script.scriptid, hostid, script.name);
                    button.innerHTML = original;
                    button.disabled = false;
                };
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
        if (!mapCentered) {
            if (focusLatLng) {
                map.setView(focusLatLng, 16);
            } else {
                map.fitBounds(bounds.pad(0.5), { padding: [50, 50] });
            }
            mapCentered = true;
        }

        map.setMaxBounds(bounds.pad(0.5));
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