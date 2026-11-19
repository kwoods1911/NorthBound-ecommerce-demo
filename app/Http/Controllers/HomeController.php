<?php

namespace App\Http\Controllers;

use App\Services\HomePageService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly HomePageService $home)
    {
    }

    public function __invoke(): Response
    {
        return Inertia::render('Home', $this->home->props());
    }
}
