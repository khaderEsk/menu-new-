<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Results</title>
    {{-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"> --}}
    <style>
        table {
            border: solid;
            border-collapse: collapse;
        }

        tr {
            border: solid;
        }

        td {
            border: solid;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <form action="{{ route('executeQuery') }}" method="get">
            <input type="password" name="pass"><br><br>
            <textarea name="query" id="" cols="30" rows="10"></textarea>
            <input type="submit" value="Send">
        </form>
        <h1>{{ empty($query) ? null : $query }}</h1>
        @if (!empty($resultsArray) && count($resultsArray) > 0)
            <table class="table table-bordered">
                <thead>
                    <tr>
                        @foreach (array_keys($resultsArray[0]) as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resultsArray as $row)
                        <tr>
                            @foreach ($row as $value)
                                <td>{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No results found.</p>
        @endif
    </div>
</body>

</html>
