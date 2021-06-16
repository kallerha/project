<?php

declare(strict_types=1);

namespace App\Controllers\www;

use FluencePrototype\Http\Messages\iResponse;
use FluencePrototype\Http\Messages\View;
use FluencePrototype\Http\Methods\iGet;
use FluencePrototype\Router\Route;

#[Route('main', 'www', '')]
class MainController implements iGet
{

    public function get(): iResponse
    {
        return new View($this, '_layout_www', 'Main');
    }

}