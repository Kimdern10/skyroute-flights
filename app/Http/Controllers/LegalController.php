<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal', ['title' => 'Privacy policy', 'markdown' => Setting::privacyPolicy()]);
    }

    public function terms(): View
    {
        return view('legal', ['title' => 'Terms of use', 'markdown' => Setting::terms()]);
    }
}
