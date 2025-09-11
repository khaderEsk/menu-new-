<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArtisanController extends Controller
{
    public function storageLink()
    {
        Artisan::call('storage:link');
        return 'Storage link created!';
    }

    public function migrate()
    {
        Artisan::call('migrate');
        return 'migrate';
    }
}
