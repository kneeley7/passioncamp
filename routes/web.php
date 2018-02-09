<?php

Route::get('/', 'RedirectController@home');

Route::get('admin', 'Super\DashboardController')->middleware(['auth', 'super']);
Route::get('admin/roominglists', 'Super\RoominglistsController@index');
Route::post('admin/roominglists/export', 'RoominglistExportController@create');
Route::get('admin/roominglists/{version}/download', 'RoominglistExportController@download');

Route::get('admin/organizations/search', 'OrganizationController@search');
Route::resource('admin/organizations', 'OrganizationController');

Route::get('admin/rooms', 'RoomController@index');
Route::post('rooms/{room}/check-in', 'RoomController@checkin');
Route::post('rooms/{room}/key-received', 'RoomController@keyReceived');

// Route::get('rooms/{room}/label', 'RoomLabelController@show');
Route::post('rooms/{room}/print-label', 'RoomLabelController@printnode');
Route::get('rooms/{payload}/label', 'RoomLabelController@signedShow');

Route::get('admin/hotels', 'HotelController@index');
Route::get('admin/hotels/{hotel}', 'HotelController@show');

Route::get('admin/tickets', 'Super\TicketController@index');

Route::get('admin/users', 'Super\UserController@index');
Route::get('admin/users/create', 'Super\UserController@create');
Route::post('admin/users', 'Super\UserController@store');
Route::get('admin/users/{user}/edit', 'Super\UserController@edit');
Route::put('admin/users/{user}/update', 'Super\UserController@update');

Route::get('admin/organizations/{organization}/item/create', 'OrganizationItemController@create');
Route::post('admin/organizations/{organization}/item/store', 'OrganizationItemController@store');
Route::get('admin/organizations/{organization}/item/{item}/edit', 'OrganizationItemController@edit');
Route::match(['put', 'patch'], 'admin/organizations/{organization}/item/{item}/update', 'OrganizationItemController@update');

Route::get('admin/organizations/{organization}/users/create', 'OrganizationUserController@create');
Route::post('admin/organizations/{organization}/users', 'OrganizationUserController@store');

Route::get('admin/organizations/{organization}/payments', 'OrganizationPaymentController@index');
Route::post('admin/organizations/{organization}/payments/store', 'OrganizationPaymentController@store');

Route::get('account/dashboard', 'Account\DashboardController');

Route::get('account/payments', 'Account\PaymentController@index');
Route::post('account/payments', 'Account\PaymentController@store');

Route::get('account/settings', 'Account\SettingsController@index');

Route::get('account/users/create', 'Account\UserController@create');
Route::post('account/users', 'Account\UserController@store');

Route::get('roominglist', 'RoomingListController@index');
Route::get('rooms/{room}/edit', 'RoomController@edit');
Route::patch('rooms/{room}', 'RoomController@update');
Route::post('rooms/{room}/assignments', 'RoomAssignmentController@store');
Route::patch('rooms/{room}/assignments', 'RoomAssignmentController@update');
Route::delete('rooms/{room}/assignments', 'RoomAssignmentController@delete');

Route::get('orders', 'OrderController@index');
Route::post('orders/export', 'OrderExportsController@store');
Route::get('orders/{order}', 'OrderController@show');

Route::get('orders/{order}/tickets/create', 'OrderTicketController@create');
Route::post('orders/{order}/tickets', 'OrderTicketController@store');

Route::get('orders/{order}/transactions/create', 'OrderTransactionController@create');
Route::post('orders/{order}/transactions', 'OrderTransactionController@store');

Route::post('orders/{order}/notes', 'OrderNoteController@store');

Route::get('tickets', 'TicketController@index');
Route::get('tickets/search', 'TicketController@search');
Route::get('tickets/create', 'Account\TicketController@create');
Route::post('tickets', 'Account\TicketController@store');
Route::get('tickets/{ticket}/edit', 'TicketController@edit');
Route::patch('tickets/{ticket}', 'TicketController@update');
Route::delete('tickets/{ticket}', 'TicketController@delete');
Route::patch('tickets/{ticket}/cancel', 'TicketController@cancel');

Route::post('tickets/export', 'TicketExportController@store');

Route::post('tickets/{ticket}/waivers', 'TicketWaiversController@store');

Route::get('transactions/{split}/refund', 'TransactionRefundController@create');
Route::post('transactions/{split}/refund', 'TransactionRefundController@store');
Route::get('transactions/{split}/edit', 'TransactionController@edit');
Route::patch('transactions/{split}', 'TransactionController@update');
Route::delete('transactions/{split}', 'TransactionController@delete');

Route::get('users/{user}/edit', 'UserController@edit');
Route::patch('users/{user}', 'UserController@update');

Route::get('person/{person}/edit', 'PersonController@edit');
Route::patch('person/{person}', 'PersonController@update');

Route::post('organization/{organization}/notes', 'OrganizationNoteController@store');

Route::get('profile', 'ProfileController@show');
Route::patch('profile', 'ProfileController@update');
Route::delete('profile/oauth/{provider}', 'SocialAuthController@disconnect');

Route::get('login', 'Auth\LoginController@showLoginForm');
Route::post('login', 'Auth\LoginController@login');
Route::match(['get', 'post'], 'logout', 'Auth\LoginController@logout')->name('logout');

Route::get('register/{user}/{hash}', 'Auth\RegisterController@showRegistrationForm')->name('complete.registration');
Route::post('register/{user}/{hash}', 'Auth\RegisterController@register');
Route::post('register/{provider}/{user}/{hash}', 'Auth\RegisterController@registerWithSocial');

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

Route::get('impersonate/{user}', 'Auth\ImpersonationController@impersonate');
Route::get('stop-impersonating', 'Auth\ImpersonationController@stopImpersonating');

Route::get('oauth/{provider}/callback', 'SocialAuthController@callback');
Route::post('oauth/{provider}', 'SocialAuthController@redirect');

Route::get('waivers', 'WaiversController@index');
Route::post('waivers/{waiver}/reminder', 'WaiversController@reminder');
Route::post('waivers/{waiver}/refresh', 'WaiversController@refresh');
Route::delete('waivers/{waiver}', 'WaiversController@destroy');

Route::any('webhooks/adobesign', 'Webhooks\AdobeSignController');

Route::get('dashboard', 'User\DashboardController');
Route::get('payments', 'User\PaymentsController@index');
Route::post('payments', 'User\PaymentsController@store');

Route::get('ticket-items', 'TicketItemsController@index');

Route::get('printers', 'PrintersController@index');
Route::post('printers/{printer}/test', 'PrintersController@test');
Route::delete('printers', 'PrintersController@destroy');
Route::post('selected-printer', 'PrinterSelectionController@store');
Route::delete('selected-printer', 'PrinterSelectionController@destroy');

Route::get('checkin', 'CheckinController@index');
Route::get('checkin/all-leaders', 'CheckinController@allLeaders');
Route::post('checkin/{ticket}', 'CheckinController@create');
Route::delete('checkin/{ticket}', 'CheckinController@destroy');

// Route::get('tickets/{ticket}/wristband', 'TicketWristbandsController@show');
Route::get('tickets/{payload}/wristband', 'TicketWristbandsController@signedShow');
