<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>NFLV results statement</title>
        <style>body{font:13px/1.7 DejaVu Sans,sans-serif;color:#173d35}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left}</style>
    </head>
    <body>
        <h1>Nigeria French Language Village</h1>
        <h2>Student results statement</h2>
        <p>{{ $student->name }} · {{ $student->student_number }}<br>{{ $student->programme }}</p>
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Session</th>
                    <th>Score</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->course->code }} · {{ $enrollment->course->title }}</td>
                        <td>{{ $enrollment->course->session }}</td>
                        <td>{{ $enrollment->score }}</td>
                        <td>{{ $enrollment->grade }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p>Published results as at {{ now()->format('j F Y') }}. This student copy is not an official transcript.</p>
    </body>
</html>
