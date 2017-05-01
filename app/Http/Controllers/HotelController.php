<?php

namespace App\Http\Controllers;

use App\Hotel;
use App\Http\Controllers\Controller;
use App\Scopes\OrganizationCountsScope;

class HotelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('super');
    }

    public function index()
    {
        $hotels = Hotel::withCount(['organizations' => function ($q) {
            $q->withoutGlobalScopes();
        }])->get();

        return view('admin.hotel.index')->withHotels($hotels);
    }

    public function show(Hotel $hotel)
    {
        $hotel->load('organizations.church');

        return view('admin.hotel.show')->withHotel($hotel);
    }
}
