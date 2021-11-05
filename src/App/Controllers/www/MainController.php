<?php

declare(strict_types=1);

namespace App\Controllers\www;

use FluencePrototype\Http\Messages\iResponse;
use FluencePrototype\Http\Messages\Response\Text;
use FluencePrototype\Http\Methods\iGet;
use FluencePrototype\Router\Route;
use JetBrains\PhpStorm\Pure;

#[Route('main', 'www', '')]
class MainController implements iGet
{

    #[Pure] public function get(): iResponse
    {
        return new Text('Hello, world!');
    }

}