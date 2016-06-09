<?php

namespace App\Http\Controllers;

use Auth;
use Gate;
use App\Order;
use App\Organization;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Repositories\OrganizationRepository;

class OrderController extends Controller
{
    protected $orders;
    protected $organizations;

    public function __construct(OrderRepository $orders, OrganizationRepository $organizations)
    {
        $this->orders = $orders;
        $this->organizations = $organizations;

        $this->middleware('admin')->except('show');
    }

    public function index(Request $request)
    {
        $orders = $this->orders
                  ->forUser(Auth::user())
                  ->search($request->search)
                  ->has('tickets')
                  ->with('tickets.person', 'organization.church')
                  ->paginate(5);

        if ($orders->count() > 0 && ! $request->search && ! $request->page) {
            return redirect()->route('order.index', ['page' => $orders->lastPage()]);
        }

        return view('order.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorize('owner', $order);
        $order->load('tickets.person', 'tickets.waiver', 'notes', 'organization.church', 'user.person');

        return view('order.show', compact('order'));
    }

    public function create()
    {
        $organizationOptions = $this->organizations->getChurchNameAndLocationList();

        return view('order.create', compact('organizationOptions'));
    }

    public function store(Request $request)
    {
        $organization = null;
        if (Auth::user()->isSuperAdmin()) {
            $organization = Organization::findOrFail($request->organization);
        }

        if (! $organization) {
            $organization = Auth::user()->organization;
        }

        $order = new Order;
        $order->organization()->associate($organization);
        $order->addContact($request->only('first_name', 'last_name', 'email', 'phone'));
        $order->save();

        return redirect()->route('order.show', $order)->with('success', 'Regsirtation created.');
    }
}
