<?php
if (isset($match) && $match['name'] == 'pkmnroute')
{
    $preload = true;
    $pkmnID = $PoGO->getPokemonIDbyName($match['params']['pokemon']);
} else {
    $pkmnID = null;
    $preload = false;
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Pokémon Map Braunschweig</title>
        <link rel="stylesheet" href="/static/css/bootstrap.min.css" id="basecss">
        <link rel="stylesheet" href="/static/css/select2.css" id="basecss">
        <link rel="stylesheet" href="/static/css/main.css">
    </head>
    <body>
        <div class="loader">
            <img src="/static/icons/pokeball.png" alt="loading" width="120" height="120"/>
        </div>
        <div class="menue-container">
            <div class="menue-icon menue-icon-open glyphicon glyphicon-menu-hamburger"></div>
            <div class="menue-icon menue-icon-close glyphicon glyphicon-remove"></div>
            <div class="menue">
                <div class="select-container">
                    <?php echo $PoGO->getJSONPKMN('select', $pkmnID ); ?>
                </div>
            </div>
        </div>
        <div id="map" style="width: 100vw; height: 100vh;"></div>
        <script src="/static/js/vendor/jquery.js"></script>
        <script src="/static/js/vendor/bootstrap.min.js"></script>
        <script src="/static/js/vendor/jquery.select2.js"></script>
        <script src="/static/js/gmaps.js"></script>
        <script src="/static/js/main.js"></script>
        <?php
        if ($preload)
        {
            echo '<script>window.onload=function(){
                var markers = '.$PoGO->getPokemon($pkmnID).';
                jQuery(".loader").fadeIn();
                addMarkers(markers, "'.$PoGO->getPokemonNamebyID($pkmnID).'");

            };
            </script>';
        }
        ?>
   </body>
</html>
