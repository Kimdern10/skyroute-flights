<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Popular trips shown on the home page, as [from, to].
     *
     * @var list<array{0: string, 1: string}>
     */
    public const array POPULAR_ROUTES = [
        ['JFK', 'LHR'], ['LAX', 'NRT'], ['LHR', 'DXB'], ['CDG', 'JFK'],
        ['DXB', 'SIN'], ['LOS', 'LHR'], ['SFO', 'CDG'], ['SYD', 'LAX'],
    ];

    public function __invoke(): View
    {
        return view('home', [
            'cities' => Airport::query()->pluck('city', 'iata_code'),
            'routes' => self::POPULAR_ROUTES,
        ]);
    }
}
