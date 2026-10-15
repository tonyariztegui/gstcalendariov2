<?php

header("Content-Type: text/css");

if (extension_loaded("zlib") && (ini_get("output_handler") != "ob_gzhandler")) {
    ini_set("zlib.output_compression", 1);
}

readfile('main.css');

?>