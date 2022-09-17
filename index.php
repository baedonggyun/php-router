<?php
header('Content-Type: application/json');

include $_SERVER['DOCUMENT_ROOT']."/api/regi/Load.php";

(new Load())->route();