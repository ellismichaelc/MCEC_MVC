<?php
$parts = __FILE__;
$parts = explode("/", $parts);
$my_fn = $parts[ count($parts) - 1 ];

chdir(__DIR__);

include_once("mcec.php");
include_once("mcec_app.php");
include_once("mcec_class.php");
include_once("mcec_db.php");
include_once("mcec_db_table.php");
include_once("mcec_scraper.php");

foreach(glob('mcec*.php') as $cls) {
    if($cls !== $my_fn && !strstr($cls, "mcec_loader")) {
        include_once($cls);
    }
}

if(!isset($_SERVER) || !isset($_SERVER['SCRIPT_FILENAME'])) exit;

$dir = $_SERVER['SCRIPT_FILENAME'];
chdir(dirname($dir));

$dirs = ['lib/', './', 'modules/'];
spl_autoload_register(function ($class_name) use($dirs) {
    foreach($dirs as $dir) {
        $path1 = $dir . $class_name . ".php";
        $path2 = $dir . $class_name . ".class.php";

        if(file_exists($path1)) include($path1);
        if(file_exists($path2)) include($path2);
    }
});
?>