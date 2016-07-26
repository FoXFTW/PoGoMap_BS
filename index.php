<?php
define( 'basedir', __DIR__ );

require_once basedir.'/inc/AltoRouter.php';
require_once basedir.'/inc/PoGO-BS.class.php';
$PoGO = new PoGOBS();
$PoGO->setJsonFile(basedir.'/inc/pokemon.de.json');
$PoGO->setDBType('SQLite');
$PoGO->setSQLiteDB(basedir.'/inc/db.sqlite');
$PoGO->connectSQLite();

$routerPath = '';
$Router = new AltoRouter();
$Router -> setBasePath( $routerPath );
$Router -> map( 'GET', '/', basedir.'/content/index.php', 'index' );
$Router -> map( 'GET', '/pokemon/[a:pokemon]', basedir.'/content/index.php', 'pkmnroute' );
$Router -> map( 'POST', '/get/pokemon', basedir.'/inc/ajax/getPKMN.ajax.php', 'pkmnajax' );

$match = $Router -> match();
if( $match ){

    require $match['target'];
} else {
  header("HTTP/1.1 404 Not Found");
}
