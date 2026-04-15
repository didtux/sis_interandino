<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing.index');
    }

    public function nosotros()
    {
        return view('landing.nosotros');
    }

    public function niveles()
    {
        return view('landing.niveles');
    }

    public function historia()
    {
        return view('landing.historia');
    }

    public function contacto()
    {
        return view('landing.contacto');
    }
}
