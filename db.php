<?php
// Uruchomienie sesji, wymagane do logowania we wszystkich plikach
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$username = "root"; // domyślny użytkownik XAMPP
$password = ""; // domyślnie brak hasła w XAMPP
$database = "usterki_uczelnia"; // nazwa naszej bazy

// Nawiązanie połączenia z bazą
$conn = new mysqli($host, $username, $password, $database);

// Sprawdzenie połączenia
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych (Sprawdź, czy baza z pliku baza.sql została zaimportowana): " . $conn->connect_error);
}

// Ustawienie kodowania polskich znaków
$conn->set_charset("utf8mb4");

// Funkcja pomocnicza: Sprawdzanie czy użytkownik jest zalogowany
function sprawdzLogowanie()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}
?>
