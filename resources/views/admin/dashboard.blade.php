@extends('layouts.bootstrap4')

@section('content')
    <div class="container">
        <div class="card-deck mb-3">
            <div class="card text-center">
                <div class="card-block">
                    <h1>{{ App\Organization::count() }}</h1>
                </div>
                <div class="card-footer text-muted">Churches</div>
            </div>
            <div class="card text-center">
                <div class="card-block">
                    <h1>{{ App\Organization::with('tickets')->get()->sumTicketQuantity() }}</h1>
                </div>
                <div class="card-footer text-muted">Tickets</div>
            </div>
        </div>

        <div class="card-deck mb-3">
            <div class="card text-center">
                <div class="card-block">
                    <h2>{{ money_format("%.2n", App\Organization::totalPaid()) }}</h2>
                </div>
                <div class="card-footer text-muted">Total Paid</div>
            </div>
            <div class="card text-center">
                <div class="card-block">
                    <h3>{{ money_format("%.2n", App\Organization::totalPaid('stripe')) }}</h3>
                </div>
                <div class="card-footer text-muted">Stripe</div>
            </div>
            <div class="card text-center">
                <div class="card-block">
                    <h3>{{ money_format("%.2n", App\Organization::totalPaid('other')) }}</h3>
                </div>
                <div class="card-footer text-muted">Check / Other</div>
            </div>
        </div>
    </div>
@endsection
