<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
sprawdzLogowanie();

// Zabezpieczamy dostep tylko dla studenta
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['login'];
$rola = $_SESSION['rola'];

$message = "";

// --------- DODAWANIE ZGLOSZENIA ---------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['akcja']) && $_POST['akcja'] == 'dodaj') {
    $tytul = htmlspecialchars($_POST['tytul']);
    $lokalizacja = htmlspecialchars($_POST['lokalizacja']);
    $opis = htmlspecialchars($_POST['opis']);
    
    // Proste zabezpieczenie przed zlym priorytetem
    $dopuszczalne = ['Niski', 'Średni', 'Wysoki'];
    $priorytet = in_array($_POST['priorytet'], $dopuszczalne) ? $_POST['priorytet'] : 'Średni';

    // Walidacja i sql instrukcja
    if (!empty($tytul) && !empty($lokalizacja) && !empty($opis)) {
        $stmt = $conn->prepare("INSERT INTO usterka (autor_id, tytul, lokalizacja, opis, priorytet) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $tytul, $lokalizacja, $opis, $priorytet);
            if ($stmt->execute()) {
                $message = "<div class='success-box'>✅ Usterka pomyślnie zgłoszona!</div>";
            } else {
                $message = "<div class='error-box'>❌ Wystąpił błąd przy zapisie na bazie.</div>";
            }
            $stmt->close();
        }
    } else {
        $message = "<div class='error-box'>💡 Uzupełnij wszystkie wymagane pola.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zgłaszanie Usterek | Student</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Górne Menu z LOGO uczelni -->
    <nav class="navbar">
        <div class="user-info">
            <img src="Logo UR_en (5).png" class="uni-logo" alt="Logo Uczelni">
            Zalogowano: <b><?= htmlspecialchars($user_name) ?></b> (Rola: Student)
        </div>
        <div class="nav-links">
            <?php if ($rola == 'technik'): ?> 
                <a href="tech_panel.php" class="btn alt-btn">Panel Technika</a>
            <?php endif; ?>
            <a href="logout.php" class="btn alt-btn text-danger">Wyloguj</a>
        </div>
    </nav>
    <br>

    <div class="main-container">

        <!-- Moduł Formularza - Lewa strona -->
        <div class="form-panel custom-box">
            <h2>Dodaj nowe zgłoszenie</h2>
            <?= $message ?>
            
            <form method="POST" action="index.php">
                <input type="hidden" name="akcja" value="dodaj">
                
                <div class="input-group">
                    <label>Tytuł Usterki:</label>
                    <input type="text" name="tytul" required placeholder="np. Wyciek wody przy oknie">
                </div>
                
                <div class="input-group">
                    <label>Sama Lokalizacja:</label>
                    <input type="text" name="lokalizacja" required placeholder="np. Budynek B, sala 210">
                </div>
                
                <div class="input-group">
                    <label>Priorytet (Wybierz):</label>
                    <select name="priorytet">
                        <option value="Niski">Niski (problem estetyczny)</option>
                        <option value="Średni" selected>Średni (utrudnia pracę)</option>
                        <option value="Wysoki">Wysoki (blokujący pracę)</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Opis problemu (dokładny):</label>
                    <textarea name="opis" rows="4" required placeholder="Opisz dokładnie objawy problemu..."></textarea>
                </div>

                <button type="submit" class="btn main-btn" style="width: 100%;">Wyślij Zgłoszenie</button>
            </form>
        </div>

        <!-- Moduł Listy Usterek Ucznia - Prawa strona -->
        <div class="list-panel custom-box" style="margin-top: 20px;">
            <h2>Twoja Historia Zgłoszeń</h2>
            
            <?php
            // Pobieramy TYLKO zgłoszenia tego zalogowanego studenta (autor_id)
            $stmt = $conn->prepare("SELECT * FROM usterka WHERE autor_id = ? ORDER BY data_zgloszenia DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    
                    // Pokazywanie daty, wyciaganie rekordow z petli While
                    $data = date('d-m-Y H:i', strtotime($row['data_zgloszenia']));
                    
                    echo "<div class='issue'>";
                    echo "  <div class='issue-top'>";
                    echo "    <strong>{$row['tytul']}</strong>";
                    echo "    <span class='badge'>[{$row['priorytet']}] - {$row['status']}</span>";
                    echo "  </div>";
                    echo "  <p class='issue-loc'>Miejsce: {$row['lokalizacja']} - (Dodano: {$data}) </p>";
                    echo "  <p class='issue-desc'>Opis: {$row['opis']}</p>";
                    
                    // Jesli technik zostawil notatkę komentarza na ten temat
                    if (!empty($row['komentarz_technika'])) {
                        echo "  <div class='answer'>🛠️ Odp: {$row['komentarz_technika']}</div>";
                    }
                    
                    echo "</div>"; // Koniec '.issue'
                }
            } else {
                echo "<p style='color: gray; margin-top:20px;'>Nie dodałeś jeszcze żadnego zgłoszenia.</p>";
            }
            ?>

        </div>
    </div>
</body>
</html>
