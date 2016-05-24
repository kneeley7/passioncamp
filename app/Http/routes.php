<?php

Route::any('/', function() {
    return redirect('/home');
    return redirect('http://passioncitychurch.com/students');
});


// Route::any('test', 'EchosignController@test');
// Route::any('refresh', 'EchosignController@refresh');
// Route::any('signature', 'EchosignController@signature');

Route::group(['middleware' => 'web'], function () {
    Route::group(['domain' => 'pccstudents.passioncamp.268generation.com'], function () {
        Route::get('registration', 'RegisterController@create')->name('register.create');
        Route::post('registration', 'RegisterController@store')->name('register.store');
        Route::get('registration/confirmation', 'RegisterController@confirmation')->name('register.confirmation');
    });

    Route::any('echosign/callback', 'EchosignController@callback')->name('echosign.callback');

    Route::get('login', 'Auth\AuthController@showLoginForm');
    Route::post('login', 'Auth\AuthController@login');
    Route::get('logout', 'Auth\AuthController@logout')->name('logout');

    Route::get('register/{user}/{hash}', 'Auth\AuthController@showRegistrationForm')->name('complete.registration');
    Route::post('register/{user}/{hash}', 'Auth\AuthController@register');

    // Password Reset Routes...
    Route::get('password/reset/{token?}', 'Auth\PasswordController@showResetForm');
    Route::post('password/email', 'Auth\PasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\PasswordController@reset');

    Route::group(['middleware' => 'auth'], function () {
        Route::get('/home', function () {
            return redirect()->route(Auth::user()->is_super_admin ? 'admin.organization.index' : 'account.dashboard');
        });

        Route::group(['middleware' => 'super'], function () {
            Route::get('deployrooms', function () {
                $rooms = new App\Repositories\RoomRepository;
                App\Organization::all()->each(function ($organization) use ($rooms) {
                    $rooms->bulkCreate($organization);
                });
            });

            Route::resource('admin/organization', 'OrganizationController');

            Route::post('admin/organization/{organization}/note', 'Organization\NoteController@store')->name('admin.organization.note.store');

            Route::get('admin/organization/{organization}/item/create', 'Organization\ItemController@create')->name('admin.organization.item.create');
            Route::post('admin/organization/{organization}/item/store', 'Organization\ItemController@store')->name('admin.organization.item.store');
            Route::get('admin/organization/{organization}/item/{item}/edit', 'Organization\ItemController@edit')->name('admin.organization.item.edit');
            Route::put('admin/organization/{organization}/item/{item}/update', 'Organization\ItemController@update')->name('admin.organization.item.update');

            Route::get('admin/organization/{organization}/payment/create', 'Organization\PaymentController@create')->name('admin.organization.payment.create');
            Route::post('admin/organization/{organization}/payment/store', 'Organization\PaymentController@store')->name('admin.organization.payment.store');

            Route::get('admin/organization/{organization}/user/create', 'Organization\UserController@create')->name('admin.organization.user.create');
            Route::post('admin/organization/{organization}/user/store', 'Organization\UserController@store')->name('admin.organization.user.store');

            Route::get('admin/organization/{organization}/attendees', 'Organization\TicketController@index')->name('admin.organization.ticket.index');

            Route::get('admin/organization/{organization}/registrations', 'Organization\OrderController@index')->name('admin.organization.order.index');

            Route::get('admin/hotels', 'HotelController@index')->name('hotel.index');
            Route::get('admin/hotel/{hotel}', 'HotelController@show')->name('hotel.show');
        });

        Route::get('registrations', 'OrderController@index')->name('order.index');
        Route::get('registration/create', 'OrderController@create')->name('order.create');
        Route::post('registration/store', 'OrderController@store')->name('order.store');
        Route::get('registration/{order}', 'OrderController@show')->name('order.show');
        
        Route::post('registration/{order}/note', 'Order\NoteController@store')->name('order.note.store');

        Route::get('registration/{order}/contact/create', 'Order\ContactController@create')->name('order.contact.create');
        Route::post('registration/{order}/contact', 'Order\ContactController@store')->name('order.contact.store');
        Route::get('registration/{order}/contact/edit', 'Order\ContactController@edit')->name('order.contact.edit');
        Route::patch('registration/{order}/contact', 'Order\ContactController@update')->name('order.contact.update');

        Route::get('registration/{order}/ticket/create', 'Order\TicketController@create')->name('order.ticket.create');
        Route::post('registration/{order}/ticket', 'Order\TicketController@store')->name('order.ticket.store');

        Route::get('registration/{order}/transaction/create', 'Order\TransactionController@create')->name('order.transaction.create');
        Route::post('registration/{order}/transaction', 'Order\TransactionController@store')->name('order.transaction.store');

        Route::get('tickets', 'TicketController@index')->name('ticket.index');
        Route::get('tickets/export', 'Ticket\ExportController@index')->name('ticket.export.index');
        Route::get('ticket/{ticket}', function (\App\Ticket $ticket) {
            return redirect()->route('order.show', $ticket->order);
        })->name('ticket.show');
        Route::get('ticket/{ticket}/edit', 'TicketController@edit')->name('ticket.edit');
        Route::patch('ticket/{ticket}', 'TicketController@update')->name('ticket.update');
        Route::delete('ticket/{ticket}', 'TicketController@delete')->name('ticket.delete');
        Route::patch('ticket/{ticket}/cancel', 'TicketController@cancel')->name('ticket.cancel');

        Route::get('ticket/{ticket}/waiver/create', 'Ticket\WaiverController@create')->name('ticket.waiver.create');
        Route::get('ticket/{ticket}/waiver/reminder', 'Ticket\WaiverController@reminder')->name('ticket.waiver.reminder');
        Route::get('ticket/{ticket}/waiver/cancel', 'Ticket\WaiverController@cancel')->name('ticket.waiver.cancel');

        Route::get('users', 'UserController@index')->name('user.index');
        Route::get('user/create', 'UserController@create')->name('user.create');
        Route::post('user', 'UserController@store')->name('user.store');
        Route::get('user/{user}/edit', 'UserController@edit')->name('user.edit');
        Route::put('user/{user}/update', 'UserController@update')->name('user.update');
        Route::post('user/{user}/password/reset', 'User\PasswordController@reset')->name('user.password.reset');
        Route::get('user/{user}/impersonate', 'ImpersonationController@impersonate')->name('user.impersonate');
        Route::get('user/stop-impersonating', 'ImpersonationController@stopImpersonating')->name('user.stop-impersonating');

        // Route::any('stripe/connect', 'StripeController@connect')->name('stripe.connect');

        Route::group(['prefix' => 'account', 'as' => 'account.'], function () {
            Route::get('dashboard', 'Account\DashboardController@index')->name('dashboard');
            Route::get('settings', 'Account\SettingsController@index')->name('settings');
        });
        
        Route::get('payment/create', 'Organization\PaymentController@create')->name('payment.create');
        Route::post('payment', 'Organization\PaymentController@store')->name('payment.store');

        Route::get('profile', 'ProfileController@show')->name('profile');
        Route::patch('profile', 'ProfileController@update')->name('profile.update');

        Route::get('transaction/{transaction}/edit', 'TransactionController@edit')->name('transaction.edit');
        Route::get('transaction/{transaction}/refund', 'TransactionController@refund')->name('transaction.refund.create');
        Route::post('transaction/{transaction}/refund', 'TransactionController@storeRefund')->name('transaction.refund.store');
        Route::patch('transaction/{transaction}', 'TransactionController@update')->name('transaction.update');
        Route::delete('transaction/{transaction}', 'TransactionController@delete')->name('transaction.delete');

        Route::get('roominglist', 'RoomingListController@index')->name('roominglist.index');
        Route::get('roominglist/{room}', 'RoomingListController@show')->name('roominglist.show');
        Route::get('roominglist/{room}/edit', 'RoomingListController@edit')->name('roominglist.edit');
        Route::patch('roominglist/{room}', 'RoomingListController@update')->name('roominglist.update');
        Route::put('roominglist/{ticket}/assign/{room}', 'RoomingListController@assign')->name('roominglist.assign');
        Route::put('roominglist/{ticket}/unassign', 'RoomingListController@unassign')->name('roominglist.unassign');
    });
});
