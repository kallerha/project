<?php

declare(strict_types=1);

namespace App;

use Composer\Script\Event;
use FluencePrototype\Filesystem\Filesystem;

class AppConfig
{

    private static function scheme(): string
    {
        echo 'http or https?: ';

        $handle = fopen('php://stdin', 'r');
        $input = fgets($handle);

        fclose($handle);

        $input = trim($input);

        if ($input !== 'http' && $input !== 'https') {
            echo "Wrong input...\n";

            sleep(1);

            self::scheme();
        }

        return $input;
    }

    private static function host(): string
    {
        echo "hostname (DON'T INCLUDE SUBDOMAIN - REMEMBER TRAILING SLASH)?: ";

        $handle = fopen('php://stdin', 'r');
        $input = fgets($handle);

        fclose($handle);

        $input = trim($input);

        return trim($input);
    }

    public static function run(Event $event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        echo "Welcome to FluencePrototype!\n";

        sleep(1);

        echo "Some configuration needs to be done. Please, answer the following questions about your current environment: \n";

        sleep(3);

        $scheme = self::scheme();
        $host = self::host();
        $memcachedHost = '127.0.0.1';
        $memcachedPort = '11211';

        (new Filesystem($vendorDir . '/..'))->touchFile('', 'env')
            ->writeLine("SCHEME={$scheme}")
            ->writeLine("HOST={$host}")
            ->writeLine("DATABASE_HOST=127.0.0.1")
            ->writeLine("DATABASE_NAME=dev")
            ->writeLine("DATABASE_USERNAME=root")
            ->writeLine("DATABASE_PASSWORD=")
            ->writeLine("MEMCACHED_HOST={$memcachedHost}")
            ->writeLine("MEMCACHED_PORT={$memcachedPort}");

        echo 'OK! The input has been saved to a .env file in your project folder! Have a great day :-)';
    }

}