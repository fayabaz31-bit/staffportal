<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>NFLV payment receipt</title>
        <style>body{font:14px/1.7 DejaVu Sans,sans-serif;color:#173d35}h1{font-size:24px}table{width:100%}td{padding:10px;border-bottom:1px solid #ddd}</style>
    </head>
    <body>
        <h1>Nigeria French Language Village</h1>
        <h2>Payment receipt</h2>
        <table>
            <tr>
                <td>Student</td>
                <td>{{ $student->name }} · {{ $student->student_number }}</td>
            </tr>
            <tr>
                <td>Description</td>
                <td>{{ $invoice->description }}</td>
            </tr>
            <tr>
                <td>Session</td>
                <td>{{ $invoice->session }}</td>
            </tr>
            <tr>
                <td>Amount paid</td>
                <td>NGN {{ number_format($invoice->amount_kobo / 100, 2) }}</td>
            </tr>
            <tr>
                <td>Remita reference</td>
                <td>{{ $invoice->rrr }}</td>
            </tr>
            <tr>
                <td>Bill reference</td>
                <td>{{ $invoice->reference }}</td>
            </tr>
            <tr>
                <td>Payment verified</td>
                <td>{{ $invoice->paid_at->format('j F Y H:i') }}</td>
            </tr>
        </table>
        <p>Generated {{ now()->format('j F Y H:i') }} · Nigeria French Language Village, Badagry</p>
    </body>
</html>
