

    <div>
<x-filament-panels::page>
    <div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

        {{-- Add some basic styling for the labels --}}
        <style>
            .asset-label {
                background-color: transparent;
                border: none;
                box-shadow: none;
                font-weight: bold;
                font-size: 12px;
                color: #333;
                text-shadow: 1px 1px 2px white;
            }
        </style>

        <div id="map" style="height: 600px; width: 100%; border-radius: 0.5rem;"></div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const assets = @json($this->getAssetsWithCoordinates());

                if (assets.length === 0) {
                    document.getElementById('map').innerHTML = '<div style="text-align:center; padding-top: 50px;">No assets with coordinates to display.</div>';
                    return;
                }

                const map = L.map('map').setView([assets[0].latitude, assets[0].longitude], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);

                assets.forEach(asset => {
                    const marker = L.marker([asset.latitude, asset.longitude]).addTo(map);
                    
                    // Keep the popup for when a user clicks the marker
                    marker.bindPopup(`<b>${asset.name}</b>`);

                    // --- THIS IS THE NEW PART ---
                    // Add a permanent tooltip that acts as a label
                    marker.bindTooltip(asset.name, {
                        permanent: true,      // Makes the tooltip always visible
                        direction: 'right',   // Position the label to the right of the pin
                        offset: [2, 6],      // Adjust position away from the pin center
                        className: 'asset-label' // Apply our custom CSS class
                    }).openTooltip();
                });
            });
        </script>
    </div>
</x-filament-panels::page>
    </div>