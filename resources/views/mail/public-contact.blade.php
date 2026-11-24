<!DOCTYPE html>
<html lang="en">
    <head><meta charset="utf-8"><title>NFLV website enquiry</title></head>
    <body>
        <h1>Website enquiry</h1>
        <p><strong>From</strong> {{ $enquiry['name'] }} &lt;{{ $enquiry['email'] }}&gt;</p>
        <p><strong>Subject</strong> {{ $enquiry['subject'] }}</p>
        <div style="white-space: pre-wrap;">{{ $enquiry['message'] }}</div>
        <p>Submitted through the Nigeria French Language Village Contact page.</p>
    </body>
</html>
