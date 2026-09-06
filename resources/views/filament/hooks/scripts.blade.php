<script src="{{ asset('vendor/bootstrap/5.3.0/js/bootstrap.bundle.min.js') }}" defer></script>
<script src="{{ asset('vendor/leaflet/1.9.4/leaflet.js') }}"></script>
<script>
(function () {
    function initOrdersMapWidget() {
        var mapEl = document.getElementById('ordersMap');
        if (!mapEl || typeof L === 'undefined') {
            return;
        }

        if (mapEl._leafletMap) {
            setTimeout(function () { mapEl._leafletMap.invalidateSize(); }, 200);
            return;
        }

        var points = [];
        try {
            points = JSON.parse(mapEl.dataset.points || '[]');
        } catch (e) {
            points = [];
        }

        var map = L.map(mapEl, {
            center: [32.4279, 53.6880],
            zoom: 5,
            zoomControl: true,
        });

        mapEl._leafletMap = map;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap',
        }).addTo(map);

        map.getContainer().style.background = '#e8eef4';

        points.forEach(function (point) {
            var radius = Math.min(25, Math.max(8, (point.count || 1) * 2));
            L.circleMarker([point.lat, point.lng], {
                radius: radius,
                fillColor: point.color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.85,
            }).addTo(map).bindPopup(
                '<strong>' + point.name + '</strong><br>' + (point.count || 0) + ' سفارش'
            );
        });

        setTimeout(function () { map.invalidateSize(); }, 300);
        setTimeout(function () { map.invalidateSize(); }, 800);
    }

    function boot() {
        if (typeof L === 'undefined') {
            setTimeout(boot, 100);
            return;
        }
        initOrdersMapWidget();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    document.addEventListener('livewire:navigated', boot);
    window.addEventListener('load', boot);
})();
</script>
<script src="{{ asset('vendor/sweetalert2/11/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('adminpanel/sweetalert-notifications.js') }}"></script>
<script src="{{ asset('adminpanel/script.js') }}" defer></script>
