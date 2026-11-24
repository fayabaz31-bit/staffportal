@extends('layouts.portal-entry', ['audience' => 'staff'])

@section('title', 'Staff sign-in | NfLV Staff Portal')

@section('portal-form')
    @include('partials.login-form')
@endsection
