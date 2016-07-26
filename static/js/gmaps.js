jQuery(function($) {
    // Asynchronously Load the map API
    var script = document.createElement('script');
    script.src = "//maps.googleapis.com/maps/api/js?callback=initialize&key=AIzaSyDkFoLRWcxWl5cWsTXpSwAYiqa39Sa6UM0";
    document.body.appendChild(script);
});

var map, bounds;
var markers = [];

function initialize() {
    bounds = new google.maps.LatLngBounds();
    var mapOptions = {
        zoom: 14,
        center: new google.maps.LatLng(52.258032, 10.527706),
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: false,
        streetViewControl: false,
        styles: [{"featureType": "administrative.locality", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "poi", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "poi.business", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "poi.government", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "transit","elementType":"all", "stylers": [{visibility: "off"}]}]
        // styles: [{"featureType": "administrative.locality", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "landscape", "elementType": "all", "stylers": [{"color": "#AFFFA0"}]},{"featureType": "poi", "elementType": "all", "stylers": [{"color": "#EAFFE5"},{"visibility": "off"}]},{"featureType": "poi.business", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "poi.government", "elementType": "all", "stylers": [{"visibility": "off"}]},{"featureType": "road", "elementType": "geometry", "stylers": [{"color": "#59A499"}]},{"featureType": "road", "elementType": "geometry.stroke", "stylers": [{"color": "#F0FF8D"},{"weight": 2.2}]},{"featureType": "water", "elementType": "all", "stylers": [{"visibility": "on"},{"color": "#1A87D6"}]},{"featureType": "transit","elementType":"all", "stylers": [{visibility: "off"}]}]
    };

    map = new google.maps.Map(document.getElementById("map"), mapOptions);
    map.setTilt(45);
}

function addMarkers(pkmns, name) {

    var infoWindow = new google.maps.InfoWindow(), marker, i;

    var gmapsLink = 'https://www.google.com/maps/dir/Current+Location/';
    var pokedexLink = 'http://www.pokemon.com/de/pokedex/';
    var iconBase = '/static/icons/';
    for( i = 0; i < pkmns.length; i++ ) {
        var position = new google.maps.LatLng(pkmns[i].lat, pkmns[i].lon);
        bounds.extend(position);
        var jsDate = new Date(pkmns[i].normalized_timestamp*1000);
        marker = new google.maps.Marker({
            position: position,
            map: map,
            title: name,
            icon: iconBase + pkmns[i].pokemon_id + '.png'
        });

        google.maps.event.addListener(marker, 'click', (function(marker, i) {
            return function() {
                var id_long = pkmns[i].pokemon_id.toString();
                while (id_long.length < 3) {
                    id_long = '0'+id_long;
                }
                infoWindow.setContent('<div style="max-width: 130px;"><h4 class="noma pkmn_title">'+name+' <a target="_blank" class="pokedexLink" href="'+pokedexLink+pkmns[i].pokemon_id+'">#'+id_long+'</a></h4><div><img src="'+iconBase+'xl/'+pkmns[i].pokemon_id+'.png" alt="'+name+'" class="pkmn_xl_icon"/><a href="'+gmapsLink+pkmns[i].lat+','+pkmns[i].lon+'" target="_blank" class="route_link">Route zum Standort</a></div><div><strong>Spawn Zeiten:</strong><br>'+pkmns[i].spawn_times+pkmns[i].spawn_timer_text+'</div></div>');
                infoWindow.open(map, marker);
            }
        })(marker, i));

        map.fitBounds(bounds);
        markers.push(marker);
    }
    console.log("Added "+i+" Markers");
    jQuery(".loader").fadeOut();
    // var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
    //     this.setZoom(14);
    //     google.maps.event.removeListener(boundsListener);
    // });
}

function deleteMarkers() {
    for (var i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers = [];
};
