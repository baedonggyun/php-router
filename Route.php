<?php
include_once $_SERVER['DOCUMENT_ROOT'] . "/api/regi/ApiController.php";

class Route {

    private static $routes = Array();
    private static $params = Array();
    public static $body = Array();

    /**
     * Function used to add a new route
     * @param string $expression    Route string or expression
     * @param callable $function    Function to call if route with allowed method is found
     * @param string|array $method  Either a string of allowed method or an array with string values
     *
     */
    public static function add($expression, $function, $method = 'get'){
        array_push(self::$routes, Array(
            'expression' => $expression,
            'function' => $function,
            'method' => $method
        ));
    }

    public static function getAll(){
        return self::$routes;
    }

    public static function pathNotFound($function) {
        self::$pathNotFound = $function;
    }

    public static function methodNotAllowed($function) {
        self::$methodNotAllowed = $function;
    }

    public static function run($basepath = '', $case_matters = false, $trailing_slash_matters = false, $multimatch = false) {

        // The basepath never needs a trailing slash
        // Because the trailing slash will be added using the route expressions
        $basepath = rtrim($basepath, '/');

        // Parse current URL
        $parsed_url = parse_url($_SERVER['REQUEST_URI']);

        $path = '/api/regi';

        // Get current request method
        $method = $_SERVER['REQUEST_METHOD'];

        $path_match_found = false;

        $route_match_found = false;

        foreach (self::$routes as $route) {
            // If the method matches check the path

            // Add basepath to matching string
            if ($basepath != '' && $basepath != '/') {
                $route['expression'] = '('.$basepath.')'.$route['expression'];
            }

            $expression = explode('/:', $route['expression']);

            // Add 'find string start' automatically
            $route['expression'] = '^'.$expression[0];

            // Add 'find string end' automatically
            $route['expression'] = $route['expression'].'$';

            $parsed_url_arr = explode('/', $parsed_url['path']);
            if(!empty($expression[1])) {
                $params = array_pop($parsed_url_arr);
                self::$params[$expression[1]] = $params;
            }

            $pathPattern = preg_replace('/'.str_replace('/', '\/', $path).'/i', '', implode('/', $parsed_url_arr));
            $pathPattern = urldecode($pathPattern);

            $file = file_get_contents("php://input");
            if($route['method'] == 'PUT') {
                parse_str($file,$result);
            } else {
                $result = json_decode($file, 1);
            }
            if(!empty($result)) {
                $_REQUEST = array_merge($_REQUEST, $result);
            }

            if(!empty($_REQUEST)) {
                self::$body = $_REQUEST;
            }

            // Check path match
            if (preg_match('#'.$route['expression'].'#'.($case_matters ? '' : 'i').'u', $pathPattern, $matches)) {
                $path_match_found = true;

                // Cast allowed method to array if it's not one already, then run through all methods
                foreach ((array)$route['method'] as $allowedMethod) {

                    // Check method match
                    if (strtolower($method) == strtolower($allowedMethod)) {
                        array_shift($matches); // Always remove first element. This contains the whole string

                        if ($basepath != '' && $basepath != '/') {
                            array_shift($matches); // Remove basepath
                        }

                        if($return_value = call_user_func_array($route['function'], $matches)) {
                            echo $return_value;
                        }

                        $route_match_found = true;

                        // Do not check other routes
                        break;
                    }
                }
            }

            // Break the loop if the first found route is a match
            if($route_match_found) {
                break;
            }

        }

        // No matching route was found
        if (!$route_match_found) {
            // But a matching path exists
            header('HTTP/1.1 404');

        }
    }

    public static function action($action) {

        if(!empty($action)) {
            $controllerAction = explode('@', $action);
            $controllerDir = $controllerAction[0];
            $action = $controllerAction[1];
            $controllerPath = $_SERVER['DOCUMENT_ROOT'] . '/api/regi/Controllers/';
            $controllerFile = $controllerPath . $controllerDir . ".php";
            if (file_exists($controllerFile)) {
                include_once $controllerFile;
                $controller = explode('/', $controllerDir);
                $controllerClass = end($controller);
                $class = new $controllerClass;
                if(class_exists($controllerClass)) {
                    try {

                        $result = $class->$action(
                            array(
                                'params' => self::$params,
                                'body' => self::$body
                            )
                        );

                        echo json_encode(
                            array(
                                'code' => '0000',
                                'msg' => '성공',
                                'data' => $result
                            ), true
                        );

                    } catch (Exception $e) {

                        $code = $e->getCode();
                        $msg = $e->getMessage();

                        echo json_encode(
                            array(
                                'code' => (!empty($code)) ? $code : 2001,
                                'meg' => !empty($msg) ? $msg : '잘못된 접근입니다.'
                            )
                        );

                    }

                }
            }
        }

    }

}