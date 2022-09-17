<?php

class Load
{

    public function __construct()
    {
    }

    public function view($view, $request=array()) {

        $path = $_SERVER['DOCUMENT_ROOT']."/resources/views/";
        $file = $view . ".html";
        if (file_exists($path . $file)) {
            foreach($request as $key => $param) {
                ${$key} = $param;
            }
            include $path . $file;
            foreach($request as $key => $param) {
                unset(${$key});
            }
        }

    }

    public function route() {
        $routePath = $_SERVER['DOCUMENT_ROOT'] . '/api/regi/routes';
        if(is_dir($routePath)) {
            if($dh = opendir($routePath)) {
                require $_SERVER['DOCUMENT_ROOT'] . "/api/regi/Route.php";

                while(($route = readdir($dh)) !== false) {
                    if($route !== '.' && $route !== '..') require_once $routePath . '/' . $route;
                }

                Route::run();

                closedir($dh);
            }
        }

    }


}