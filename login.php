<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rola'] === 'technik') header("Location: tech_panel.php");
    else header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $conn->real_escape_string($_POST['login']);
    $haslo = $_POST['haslo'];

    $stmt = $conn->prepare("SELECT id, rola, haslo FROM uzytkownicy WHERE login = ?");
    if ($stmt) {
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            if ($haslo === $user['haslo']) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['login'] = $login;
                $_SESSION['rola'] = $user['rola'];

                if ($user['rola'] === 'technik') header("Location: tech_panel.php");
                else header("Location: index.php");
                exit();
            } else {
                $error = "Błędne hasło.";
            }
        } else {
            $error = "Nie ma takiego użytkownika.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie | System Usterek Uniwersalny</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .header-logo {
            margin-bottom: 20px;
        }
        .header-logo img {
            max-width: 150px;
            height: auto;
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <header>
            <div class="header-logo">
                <!-- Dodano Logo Uniwersytetu -->
                <img src="Logo UR_en (5).png" alt="Logo Uczelni">
            </div>
            <h1>Logowanie do Systemu</h1>
            <p>Wprowadź swoje poświadczenia</p>
        </header>

        <?php if($error) echo "<div class='error'>$error</div>"; ?>

        <form method="POST" action="login.php" class="simple-form">
            <div class="input-group">
                <label>Login:</label>
                <input type="text" name="login" required placeholder="Wpisz np. student">
            </div>
            
            <div class="input-group">
                <label>Hasło:</label>
                <input type="password" name="haslo" required placeholder="Wpisz np. student123">
            </div>
            
            <button type="submit" class="btn" style="width:100%; margin-top:10px;">Zaloguj się do Panelu</button>
        </form>
        
        <div class="test-accounts">
            <p><strong>Wygenerowane konta do testów projektu:</strong></p>
            <ul>
                <li>Uczeń: <b>student</b> (hasło: student123)</li>
                <li>Technik: <b>technik</b> (hasło: technik123)</li>
            </ul>
        </div>
    </div>
    
</body>
</html>
