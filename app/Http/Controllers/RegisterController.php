<?php

namespace App\Http\Controllers;

use App\User;
use App\Order;
use App\Person;
use App\Ticket;
use App\OrderItem;
use Carbon\Carbon;
use App\Organization;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Order\SendConfirmationEmail;
use App\Jobs\Order\AddToMailChimp;

class RegisterController extends Controller
{
    protected $organization;
    protected $ticket_price;
    protected $can_pay_deposit;

    public function __construct()
    {
        $this->organization = Organization::whereSlug('pcc')->firstOrFail();
        $this->ticket_price = $this->getCurrentTicketPrice();
        $this->can_pay_deposit = now()->lte(Carbon::parse('2018-05-03')->endOfDay());
    }

    public function getCurrentTicketPrice()
    {
        if (request('code') == 'rising') {
            return 365;
        }

        $prices = [
            '375' => '2018-01-01',
            '400' => '2018-04-08',
            '420' => '2018-05-06',
        ];

        return collect($prices)->filter(function ($date) {
            return now()->gte(Carbon::parse($date)->endOfDay());
        })->keys()->sort()->last();
    }

    public function create()
    {
        // return view('register.closed');

        return view('register.create', [
            'ticketPrice' => $this->ticket_price,
            'can_pay_deposit' => $this->can_pay_deposit,
        ]);
    }

    public function store()
    {
        if ($this->ticket_price > 375 && request('num_tickets') >= 2) {
            $this->ticket_price = 375;
        }

        $this->validate(request(), [
            'contact.first_name' => 'required',
            'contact.last_name' => 'required',
            'contact.email' => 'required|email',
            'contact.phone' => 'required',
            'billing.street' => 'required',
            'billing.city' => 'required',
            'billing.state' => 'required',
            'billing.zip' => 'required',
            'num_tickets' => 'required|numeric|min:1',
            'tickets.*.first_name' => 'required',
            'tickets.*.last_name' => 'required',
            'tickets.*.gender' => 'required',
            'tickets.*.grade' => 'required',
            'payment_type' => 'required',
        ]);

        \DB::beginTransaction();

        $user = User::firstOrCreate(['email' => request('contact.email')]);
        if (! optional($user->person)->exists) {
            $user->person()->associate(
                Person::create(array_collapse(request([
                    'contact.first_name',
                    'contact.last_name',
                    'contact.email',
                    'contact.phone',
                    'billing.street',
                    'billing.city',
                    'billing.state',
                    'billing.zip',
                ])))
            )->save();
        } else {
            $user->person->fill(array_collapse(request([
                'contact.first_name',
                'contact.last_name',
                'contact.email',
                'contact.phone',
                'billing.street',
                'billing.city',
                'billing.state',
                'billing.zip',
            ])))->save();
        }

        $order = $user->orders()->create([
            'organization_id' => $this->organization->id,
        ]);

        // record donation
        $donation_total = request('fund_amount') == 'other' ? request('fund_amount_other') : request('fund_amount');
        if ($donation_total > 0) {
            $order->items()->create([
                'type' => 'donation',
                'organization_id' => $this->organization->id,
                'price' => $donation_total * 100,
            ]);
        }

        // record tickets
        collect(request('tickets'))->each(function ($data) use ($order) {
            $order->tickets()->create([
                'agegroup' => 'student',
                'ticket_data' => array_only($data, ['school', 'roommate_requested']) + ['code' => request('code')],
                'price' => $this->ticket_price * 100,
                'organization_id' => $this->organization->id,
                'person_id' => Person::create(array_only($data, [
                    'first_name', 'last_name', 'email', 'phone',
                    'gender', 'grade', 'allergies',
                    'considerations',
                ]))->id,
            ]);
        });

        while ($order->tickets()->count() < request('num_tickets')) {
            $order->tickets()->create([
                'agegroup' => 'student',
                'price' => $this->ticket_price * 100,
                'organization_id' => $this->organization->id,
                'person_id' => Person::create()->id,
            ]);
        }

        try {
            $charge = \Stripe\Charge::create(
                [
                    'amount' => $this->can_pay_deposit && request('payment_type') == 'deposit' ? $order->deposit_total : $order->grand_total,
                    'currency' => 'usd',
                    'source' => request('stripeToken'),
                    'description' => 'Passion Camp',
                    'statement_descriptor' => 'PCC SMMR CMP',
                    'metadata' => [
                        'order_id' => $order->id,
                        'email' => $user->person->email,
                        'name' => $user->person->name
                    ]
                ],
                [
                    'api_key' => config('services.stripe.secret'),
                    'stripe_account' => $this->organization->setting('stripe_user_id'),
                ]
            );
        } catch (\Exception $e) {
            \DB::rollback();
            return redirect()->route('register.create')->withInput()->with('error', $e->getMessage());
        }

        // Add payment to order
        $order->addTransaction([
            'source' => 'stripe',
            'identifier' => $charge->id,
            'amount' => $charge->amount,
            'cc_brand' => $charge->source->brand,
            'cc_last4' => $charge->source->last4,
        ]);

        \DB::commit();

        SendConfirmationEmail::dispatch($order);
        AddToMailChimp::dispatch($order);

        return request()->expectsJson()
               ? $order->toArray()
               : redirect()->route('register.confirmation')->with('order_id', $order->id);
    }

    public function confirmation()
    {
        if (! session()->has('order_id')) {
            return redirect()->route('register.create');
        }

        $order = Order::findOrFail(session('order_id'));

        return view('register.confirmation')->withOrder($order);
    }
}
