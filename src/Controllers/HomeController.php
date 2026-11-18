<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('site/home', [
            'pageTitle' => 'PowerMagz Gym — Commit to Be Healthy | Tabaco City',
        ]);
    }

    public function privacy(): void
    {
        $this->view('site/legal', [
            'pageTitle' => 'Privacy Policy — PowerMagz Gym',
            'doc'       => 'privacy',
            'heading'   => 'Privacy Policy',
            'kicker'    => 'Your data',
            'updated'   => 'November 11, 2026',
            'intro'     => 'This policy explains what PowerMagz Gym collects when you join, train and pay, why we need it, and the choices you have.',
        ]);
    }

    public function terms(): void
    {
        $this->view('site/legal', [
            'pageTitle' => 'Terms & Conditions — PowerMagz Gym',
            'doc'       => 'terms',
            'heading'   => 'Terms & Conditions',
            'kicker'    => 'Membership rules',
            'updated'   => 'November 11, 2026',
            'intro'     => 'These terms apply to every PowerMagz Gym membership, class booking, payment and visit. Please read them before you enroll.',
        ]);
    }
}
