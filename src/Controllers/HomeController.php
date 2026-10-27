<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('site/home', [
            'pageTitle' => 'Powermagz Gym',
        ]);
    }
}
