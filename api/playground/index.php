<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Playground</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
    </style>
</head>

<body>
    <div class="container">
        <!-- Add a Bootstrap card to contain the API playgound which will have
        1. A dropdown box to select the API endpoint (currently only option is /api/v1/messages)
        2. A text field to input their API token (which will be used as a bearer token later)
        3. A text area to enter JSON data to send to the API endpoint
        4. A text area to display the response from the API endpoint
        5. A button to send the request to the API endpoint
        6. A button to clear both text areas
        -->
        <div class="card">
            <div class="card-header">
                API Playground
            </div>
            <div class="card-body">
                <form id="apiForm">
                    <div class="mb-3">
                        <label for="apiEndpoint" class="form-label">API Endpoint</label>
                        <select class="form-select" id="apiEndpoint">
                            <option value="/api/v1/messages">/api/v1/messages</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="apiToken" class="form-label">API Token</label>
                        <input type="text" class="form-control" id="apiToken" placeholder="Enter API Token">
                    </div>
                    <div class="mb-3">
                        <label for="apiData" class="form-label">API Data</label>
                        <textarea class="form-control" id="apiData" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="apiResponse" class="form-label">API Response</label>
                        <textarea class="form-control" id="apiResponse" rows="3" readonly></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Request</button>
                    <button type="button" class="btn btn-secondary">Clear</button>
                </form>
            </div>

        </div>

        <script>
            // Add JavaScript code to handle the API playground functionality here
            document.getElementById('apiForm').addEventListener('submit', function(event) {
                event.preventDefault();
                // make the ajax request and display the response
                var apiEndpoint = document.getElementById('apiEndpoint').value;
                var apiToken = document.getElementById('apiToken').value;
                var apiData = document.getElementById('apiData').value;
                var apiResponse = document.getElementById('apiResponse');
                var xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4) {
                        apiResponse.value = this.responseText;
                    }
                };
            });
        </script>
</body>

</html>