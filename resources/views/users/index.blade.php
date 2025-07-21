<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Table - DevExtreme</title>

    <link rel="stylesheet" href="{{ asset('/css/componentes.css') }}">

    <link rel="stylesheet" href="{{ asset('/js/plugins/select2/select2.min.css') }}">

    <link rel="stylesheet" href="{{ asset('/js/plugins/sweetalert2/css/sweetalert2.min.css') }}">

    {{-- Icon --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css" />
    <link href="{{ asset('css/awesomeicons/css/fontawesome.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/awesomeicons/css/brands.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/awesomeicons/css/solid.css') }}" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/20.2.6/css/dx-gantt.css">
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/22.1.3/css/dx.common.css">
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/22.1.3/css/dx.light.css">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        #userGrid {
            height: 600px;
            margin-top: 20px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .refresh-btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .refresh-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-actions">
            <h1>Users Management</h1>
            <button id="refreshBtn" class="refresh-btn">Refresh Data</button>
        </div>

        <div id="usersGrid"></div>
    </div>


    <script src="{{ asset('js/main.js') }}" type="module"></script>
    {{-- JS --}}
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <script src="{{ asset('js/plugins/sweetalert2/js/sweetalert2.min.js') }}"></script>

    <script src="{{ asset('/js/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('/js/plugins/select2/i18n/es.js') }}"></script>
    <script src="{{ asset('/js/plugins/select2/select2_init.js') }}"></script>

    <!-- devexpress -->
    <script src="https://cdn3.devexpress.com/jslib/22.2.3/js/dx-gantt.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
    <script src="{{ asset('/js/plugins/devextreme/devextreme.js') }}"></script>
    <script src="{{ asset('/js/plugins/devextreme/dx.messages.es.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>

    <script src="{{ asset('/js/web.js') }}"></script>

    <script src="{{ asset('/js/users/list.js') }}" type="module"></script>
</body>

</html>
