<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/dark-theme.css">
</head>

<body>
    <div class="container">
        <h1>Login</h1>
        <form id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="mt-3">
            <p>Don't have an account? <a href="signup.html">Create Account</a></p>
            <p>Forgot your password? <a href="forgot-password.html">Reset Password</a></p>
        </div>
    </div>

    <script>
        // Add JavaScript code to handle the login functionality here
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();
            // Perform the login logic here (e.g., validate credentials, make API requests)
            // Redirect to the appropriate page if login is successful
        });
    </script>
</body>

</html>