<?php

declare(strict_types=1);

namespace App;

use FluencePrototype\Auth\ForbiddenException;
use FluencePrototype\Cache\Cache;
use FluencePrototype\Dispatcher\Dispatcher;
use FluencePrototype\Dispatcher\InvalidDependencyException;
use FluencePrototype\Filesystem\DirectoryNotFoundException;
use FluencePrototype\Filesystem\Filesystem;
use FluencePrototype\Filesystem\InvalidDirectoryPathException;
use FluencePrototype\Filesystem\InvalidFilePathException;
use FluencePrototype\Http\HttpUrl;
use FluencePrototype\Http\Messages\MethodNotAllowedException;
use FluencePrototype\Http\Messages\NotFoundException;
use FluencePrototype\Http\Messages\Request;
use FluencePrototype\Router\RouteInformation;
use FluencePrototype\Router\RouteMatcher;
use RedBeanPHP\R;
use ReflectionException;

/**
 * Class MainApp
 * @package App
 */
class MainApp
{

    /**
     * MainApp constructor.
     */
    public function __construct()
    {
        setlocale(LC_ALL, 'da', 'da_DK', 'da-DK', 'DK_da.UTF-8', 'da_DK.UTF-8', 'DK_da.utf8', 'da_DK.utf8');
        date_default_timezone_set('Europe/Berlin');
        mb_internal_encoding('UTF-8');
        header('Strict-Transport-Security: max-age=31556926; includeSubDomains; preload');

        try {
            /** BOOTSTRAP STARTS */
            // parse .env file and store them in $_ENV
            $cache = new Cache();

            if (!$lines = $cache->fetch(key: 'env')) {
                $filesystem = new Filesystem();
                $file = $filesystem->openFile(filename: '', extension: 'env');
                $lines = $cache->store(key: 'env', value: $file->getLines());
            }

            foreach ($lines as $line) {
                [$key, $value] = explode(separator: '=', string: $line);

                $_ENV[trim($key)] = trim($value);
            }

            session_name($_ENV['SESSION_NAME']);
            session_set_cookie_params(0, '/', '.' . $_ENV['G_RECAPTCHA_HOSTNAME'], true, true);

            // establish db connection
            $dsn = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=' . $_ENV['DATABASE_NAME'] . ';charset=utf8';

            R::setup(dsn: $dsn, username: $_ENV['DATABASE_USERNAME'], password: $_ENV['DATABASE_PASSWORD']);
            R::useFeatureSet('novice/latest');
            R::freeze(tf: false);
            R::usePartialBeans(yesNoBeans: true);

            // evaluate url and eventually reinforce subdomain on 301 redirect
            $currentUrl = HttpUrl::createFromCurrentUrl();
            $host = $currentUrl->getHost() . $currentUrl->getPath();

            if (str_starts_with(haystack: $host, needle: $_ENV['HOST'])) {
                $currentUrl->setHost(host: 'www.' . $currentUrl->getHost());

                header(header: 'HTTP/1.1 301 Moved Permanently');
                header(header: 'Location: ' . $currentUrl);

                exit;
            }
            /** BOOTSTRAP ENDS */

            // match and dispatch route from request
            $routeMatcher = new RouteMatcher();
            $request = new Request();

            if (!$routeInformationArray = $routeMatcher->matchRouteWithRequestPath(request: $request)) {
                throw new NotFoundException();
            }

            $routeInformation = RouteInformation::createFromArray(routeInformationArray: $routeInformationArray);
            $dispatcher = new Dispatcher(request: $request, routeInformation: $routeInformation);

            ob_start();

            $dispatcher->dispatch()->render();

            ob_flush();
        } catch (ForbiddenException | InvalidDependencyException | InvalidFilePathException | NotFoundException | MethodNotAllowedException | ReflectionException | DirectoryNotFoundException | InvalidDirectoryPathException  $exception) {
            $request = new Request();

            if ($exception instanceof ForbiddenException and class_exists('App\\Controllers\\' . $request->getSubdomain() . '\\ForbiddenController')) {

                $routeInformationArray = [
                    'isFile' => false,
                    'name' => '404',
                    'parametersLength' => 0,
                    'path' => '',
                    'resource' => 'App\\Controllers\\' . $request->getSubdomain() . '\\ForbiddenController'
                ];

                $routeInformation = RouteInformation::createFromArray(routeInformationArray: $routeInformationArray);
                $dispatcher = new Dispatcher(request: $request, routeInformation: $routeInformation);

                ob_start();

                try {
                    $dispatcher->dispatch()->render();
                } catch (InvalidDependencyException | ReflectionException | MethodNotAllowedException | NotFoundException) {
                }

                ob_flush();
            }

            if ($exception instanceof NotFoundException and class_exists('App\\Controllers\\' . $request->getSubdomain() . '\\NotFoundController')) {
                $request = new Request();

                $routeInformationArray = [
                    'isFile' => false,
                    'name' => '404',
                    'parametersLength' => 0,
                    'path' => '',
                    'resource' => 'App\\Controllers\\' . $request->getSubdomain() . '\\NotFoundController'
                ];

                $routeInformation = RouteInformation::createFromArray(routeInformationArray: $routeInformationArray);
                $dispatcher = new Dispatcher(request: $request, routeInformation: $routeInformation);

                ob_start();

                try {
                    $dispatcher->dispatch()->render();
                } catch (InvalidDependencyException | ReflectionException | MethodNotAllowedException | NotFoundException) {
                }

                ob_flush();
            }

            if ($exception instanceof MethodNotAllowedException and class_exists('App\\Controllers\\' . $request->getSubdomain() . '\\MethodNotAllowedController')) {
                $request = new Request();

                $routeInformationArray = [
                    'isFile' => false,
                    'name' => '405',
                    'parametersLength' => 0,
                    'path' => '',
                    'resource' => 'App\\Controllers\\' . $request->getSubdomain() . '\\MethodNotAllowedController'
                ];

                $routeInformation = RouteInformation::createFromArray(routeInformationArray: $routeInformationArray);
                $dispatcher = new Dispatcher(request: $request, routeInformation: $routeInformation);

                ob_start();

                try {
                    $dispatcher->dispatch()->render();
                } catch (InvalidDependencyException | ReflectionException | MethodNotAllowedException | NotFoundException) {
                }

                ob_flush();
            }

            die($exception->getCode());
        }
    }

}