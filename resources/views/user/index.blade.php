@extends('layouts.semantic')

@section('content')
    <div class="ui container">
        <header class="page-header">
            <h1>Users</h1>
            <a href="{{ route('user.create') }}">Add User</a>
        </header>

        <table class="ui very basic table">
            @foreach ($users as $user)
                <tr>
                    <td>{!! $user->email or '<i style="font-size:85%;font-weight:normal">none</i>' !!}</td>
                    <td>{{ $user->person->name or '' }}</td>
                    <td>{{ $user->auth_organization }}</td>
                    <td><a href="{{ route('user.edit', $user) }}">edit</a></td>
                    <td>
                        @can ('impersonate', $user)
                            <a href="{{ route('user.impersonate', $user) }}">impersonate</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@stop
