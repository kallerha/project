<?php

declare(strict_types=1);

namespace App;

use FluencePrototype\Cache\Cache;
use FluencePrototype\Console\Commands;
use FluencePrototype\Dispatcher\Dispatcher;
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
        setlocale(LC_ALL, 'da', 'da_DK', 'da-DK');
        date_default_timezone_set('Europe/Berlin');
        session_start();

        try {
            /** BOOTSTRAP STARTS */
            // parse .env file and store them them in $_ENV
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

            // establish db connection
            $dsn = 'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=' . $_ENV['DATABASE_NAME'];

            R::setup(dsn: $dsn, username: $_ENV['DATABASE_USERNAME'], password: $_ENV['DATABASE_PASSWORD']);
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
            $dispatcher->dispatch()->render();
        } catch (DirectoryNotFoundException | InvalidDirectoryPathException | InvalidFilePathException | NotFoundException | MethodNotAllowedException $exception) {
            die($exception->getCode());
        }
    }

}