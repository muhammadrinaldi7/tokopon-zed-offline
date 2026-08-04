<!DOCTYPE html>
<html>

<head>
    <title>Laporan Data AI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        tr:nth-child(even) {
            background-color: #fcfcfc;
        }
    </style>
</head>

<body>

    <h2>Laporan Data AI - Zedpos</h2>

    <table>
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ str_replace('_', ' ', $heading) }}</th>
                    @end traverses
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $row)
                <tr>
                    @foreach ($headings as $heading)
                        <td>{{ $row[$heading] ?? '-' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
