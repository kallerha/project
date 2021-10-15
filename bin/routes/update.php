<?php

declare(strict_types=1);

use FluencePrototype\Auth\AcceptRoles;
use FluencePrototype\Collections\Collections;
use FluencePrototype\Filesystem\DirectoryNotFoundException;
use FluencePrototype\Filesystem\Filesystem;
use FluencePrototype\Filesystem\InvalidDirectoryPathException;
use FluencePrototype\Filesystem\InvalidFilepathException;
use FluencePrototype\Router\InvalidRouteNameException;
use FluencePrototype\Router\InvalidRoutePathException;
use FluencePrototype\Router\Route;
use FluencePrototype\Router\RouteNameAlreadyExistsException;

require __DIR__ . '/../../vendor/autoload.php';

function getRoles(string $controller): array
{
    try {
        $controllerClass = new ReflectionClass(objectOrClass: $controller);
        $controllerClassAttributes = $controllerClass->getAttributes(name: AcceptRoles::class);

        if (!empty($controllerClassAttributes)) {
            /** @var ReflectionAttribute $acceptRolesAttribute */
            $acceptRolesAttribute = array_pop(array: $controllerClassAttributes);
            $acceptRolesAttributeParameters = $acceptRolesAttribute->getArguments();

            return array_pop(array: $acceptRolesAttributeParameters);
        }
    } catch (ReflectionException) {
    }

    return [];
}

try {
    $routeCacheCollections = new Collections();
    $namedRouteArray = [];
    $filesystem = (new Filesystem())->cd('src/App/Controllers');
    $files = $filesystem->listFilesRecursively();
    $declaredClasses = get_declared_classes();

    foreach ($files as $file) {
        require $file->toFullPath();
    }

    $controllers = array_diff(get_declared_classes(), $declaredClasses);

    foreach ($controllers as $controller) {
        $reflectionClass = new ReflectionClass(objectOrClass: $controller);
        $attributes = $reflectionClass->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Route::class) {

                /** @var Route $route */
                $route = $attribute->newInstance();
                $routeCacheCollections->addArray(array: $route->toRouteArray(controller: $controller));

                if (isset($namedRouteArray[$route->getName()])) {
                    throw new RouteNameAlreadyExistsException();
                }

                $namedRouteArray[$route->getName()] = [
                    'path' => $route->getPath() . (!$route->isFile() && $route->getPath() !== '' ? '/' : ''),
                    'roles' => getRoles(controller: $controller),
                    'subdomain' => $route->getSubdomain()
                ];
            }
        }
    }

    $filesystem = new Filesystem();
    $file = $filesystem->touchFile(filename: 'route.cache', extension: 'php');
    $file->clear();
    $file->writeLine(content: '<?php');
    $file->write(content: 'return ');
    $file->write(content: $routeCacheCollections->mergeArraysIntoMultidimensionalArrayAndParseToString());
    $file->write(content: ';');
    $file = $filesystem->touchFile(filename: 'route.names.cache', extension: 'php');
    $file->clear();
    $file->writeLine(content: '<?php');
    $file->write(content: 'return ');
    $file->write(content: var_export(value: $namedRouteArray, return: true));
    $file->write(content: ';');

    die('Success!');
} catch (ReflectionException | DirectoryNotFoundException | InvalidDirectoryPathException | InvalidFilepathException | InvalidRouteNameException | InvalidRoutePathException | RouteNameAlreadyExistsException $e) {
    die('Something went wrong...');
}