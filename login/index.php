<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/dark-theme.css">
    <style>
    </style>
</head>

<body>
    <div class="container">
        <div class="card card-login">
            <div class="card-body">
                <h1>Hello!</h1>
                <form id="loginForm">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="username" name="username" required autofocus autocomplete="off" placeholder="Username">
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="off" placeholder="Password">
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            <div class="icon-container">
                <div class="icon"></div>
                <p class="icon-title">ChatFlow</p>
            </div>
        </div>
        <div class="card card-login card-login-2">
            <button type="button">Create an Account?</button>
            <button type="button">Forgot your Password?</button>
        </div>
        <div class="spacer"></div>
        <div class="footer">ChatFlow &copy; 2023</div>
    </div>

    <script>
        // Add JavaScript code to handle the login functionality here
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();
            // Perform the login logic here (e.g., validate credentials, make requests)
            // Redirect to the appropriate page if login is successful
        });
    </script>
</body>

</html>