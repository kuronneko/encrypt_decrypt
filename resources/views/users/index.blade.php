<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
        }

        .modal-header {
            padding: 20px 25px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: #495057;
            font-size: 1.5rem;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .close:hover,
        .close:focus {
            color: #000;
            background-color: #f8f9fa;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
            font-size: 14px;
        }

        .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-footer {
            padding: 20px 25px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 8px 8px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 80px;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-actions">
            <h1>Users Management</h1>
            <div>
                <button id="addUserBtn" class="refresh-btn" style="background-color: #28a745; margin-right: 10px;">Add User</button>
                <button id="refreshBtn" class="refresh-btn">Refresh Data</button>
            </div>
        </div>

        <div id="usersGrid"></div>

        <!-- Modal for Add User -->
        <div id="addUserModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New User</h2>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button type="button" id="generateRandomBtn" class="btn" style="background-color: #17a2b8; color: white; font-size: 12px; padding: 6px 12px;">
                            🎲 Generate Random Data
                        </button>
                        <span class="close">&times;</span>
                    </div>
                </div>
                <div class="modal-body">
                    <div style="background-color: #e7f3ff; border: 1px solid #b3d9ff; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; color: #0066cc;">
                        <i class="fa-solid fa-info-circle"></i>
                        <strong>Quick Start:</strong> Random data has been pre-filled to help you test the form quickly. Click "🎲 Generate Random Data" to get new values, or modify any field as needed.
                    </div>
                    <form id="addUserForm">
                        <div class="form-group">
                            <label for="userName">Name <span class="required">*</span></label>
                            <input type="text" id="userName" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="userEmail">Email <span class="required">*</span></label>
                            <input type="email" id="userEmail" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="userPassword">Password <span class="required">*</span></label>
                            <input type="password" id="userPassword" name="password" required>
                        </div>

                        <div class="form-group">
                            <label for="userPasswordConfirmation">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="userPasswordConfirmation" name="password_confirmation" required>
                        </div>

                        <h3 style="margin-top: 20px; margin-bottom: 15px; color: #333;">Location Information</h3>

                        <div class="form-group">
                            <label for="locationName">Location Name</label>
                            <input type="text" id="locationName" name="location_name" placeholder="e.g., Home, Office">
                        </div>

                        <div class="form-group">
                            <label for="locationAddress">Address</label>
                            <input type="text" id="locationAddress" name="address" placeholder="Street address">
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 1; margin-right: 10px;">
                                <label for="locationCity">City</label>
                                <input type="text" id="locationCity" name="city">
                            </div>
                            <div class="form-group" style="flex: 1; margin-left: 10px;">
                                <label for="locationState">State/Province</label>
                                <input type="text" id="locationState" name="state">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 1; margin-right: 10px;">
                                <label for="locationCountry">Country</label>
                                <input type="text" id="locationCountry" name="country">
                            </div>
                            <div class="form-group" style="flex: 1; margin-left: 10px;">
                                <label for="locationPostalCode">Postal Code</label>
                                <input type="text" id="locationPostalCode" name="postal_code">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="locationNotes">Notes</label>
                            <textarea id="locationNotes" name="notes" rows="3" placeholder="Additional notes about this location"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
                    <button type="submit" id="saveUserBtn" class="btn btn-primary">Save User</button>
                </div>
            </div>
        </div>
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
    <script src="{{ asset('/js/users/modal.js') }}" type="module"></script>
</body>

</html>
