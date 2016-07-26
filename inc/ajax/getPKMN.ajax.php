<?php
if (!isset($_POST['id']) || !isset($_POST['name']))
{
    header('HTTP/1.1 400 Bad Request');
    die();
} else {
    $id = (int) $_POST['id'];
    $name = filter_var($_POST['name']);

    header('HTTP/1.1 200 Ok');
    header('Content-Type: application/json; charset=utf-8');
    echo $PoGO->getPokemonFromDB($id);
}
