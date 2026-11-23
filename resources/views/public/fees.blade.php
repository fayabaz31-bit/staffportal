@extends('layouts.public-information')

@section('title', 'Fees & Payments | Nigeria French Language Village')
@section('information-heading', 'Fees & payments')
@section('information-intro', 'Find out how to pay tuition and follow up on a payment.')

@section('information-content')
    <h2>Pay tuition</h2>
    <p>Confirm the fee for your programme in your admission instructions, then use the <a href="https://portal.frenchvillage.edu.ng/paynow">payment page in the NFLV student portal</a>. Keep your receipt and payment reference.</p>

    <h2>Need help with a payment?</h2>
    <p><a href="{{ route('website.contact') }}">Contact NFLV</a> if you need help confirming a payment. Have your payment reference ready.</p>
@endsection
