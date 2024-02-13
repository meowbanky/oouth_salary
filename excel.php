<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Export HTML Table to Excel (xlsx)</title>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/tableexport@5.2.0/dist/js/tableexport.min.js"></script>

</head>

<body>

    <table id="myTable" border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Country</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>John</td>
                <td>30</td>
                <td>USA</td>
            </tr>
            <tr>
                <td>Alice</td>
                <td>25</td>
                <td>Canada</td>
            </tr>
            <!-- Add more rows as needed -->
        </tbody>
    </table>

    <button id="exportButton">Export to Excel (xlsx)</button>

    <script>
        $(document).ready(function() {
            $("#exportButton").click(function() {
                $("#myTable").tableExport({
                    type: "excel",
                    escape: "false",
                });
            });
        });
    </script>

</body>

</html>