<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
</head>
<body>
    <h2>Test Login Form</h2>
    <form action="php/check-login.php" method="post">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="john" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="abcd" required>
        </div>
        <div>
            <input type="submit" value="Login">
        </div>
    </form>
    
    <h3>Test Accounts</h3>
    <p>Admin: username: elias, password: 1234</p>
    <p>Staff: username: john, password: abcd</p>
</body>
</html>