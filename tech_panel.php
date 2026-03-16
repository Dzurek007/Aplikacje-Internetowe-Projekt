<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
sprawdzLogowanie();

// Zabezpieczenie na poziomie roli - po wpisaniu tego url uczeń zostanie skierowany do indexu
if ($_SESSION['rola'] !== 'technik') {
    die("<div style='color:red; text-align:center; margin-top:50px;'><h3>Brak dostępu! Nie masz uprawnień technika.</h3><br><a href='index.php'>Wróć do Panelu Głównego</a></div>");
}

$user_name = $_SESSION['login'];
$message = "";

// --------- AKTUALIZACJA ZGLOSZENIA ---------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['akcja']) && $_POST['akcja'] == 'aktualizuj') {
    $id_usterki = intval($_POST['issue_id']);
    $nowy_status = $_POST['status'];
    $nowy_komentarz = htmlspecialchars($_POST['komentarz']);

    $stmt = $conn->prepare("UPDATE usterka SET status = ?, komentarz_technika = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $nowy_status, $nowy_komentarz, $id_usterki);
        if ($stmt->execute()) {
             $message = "<div class='success-box'>Pomyślnie zaktualizowano zgłoszenie #{$id_usterki}.</div>";
        } else {
             $message = "<div class='error-box'>Błąd zapisu w bazie danych.</div>";
        }
        $stmt->close();
    }
}

// --------- USUWANIE ZGLOSZENIA ---------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['akcja']) && $_POST['akcja'] == 'usun') {
    $id_usterki = intval($_POST['issue_id']);
    
    $stmt = $conn->prepare("DELETE FROM usterka WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id_usterki);
        if ($stmt->execute()) {
             $message = "<div class='success-box'>Trwale usunięto zgłoszenie #{$id_usterki} z systemu.</div>";
        } else {
             $message = "<div class='error-box'>Nie udało się usunąć zgłoszenia. Wystąpił błąd.</div>";
        }
        $stmt->close();
    }
}

// --------- FILTROWANIE WYNIKÓW ---------
$where_clauses = ["1=1"]; // Domyslnie pobiera wszystkie rekosty jezeli zaden filtr nie wystepuje
$param_types = "";
$param_values = [];

// Filtr STATUS
$filter_status = isset($_GET['f_status']) ? $_GET['f_status'] : '';
if ($filter_status !== '' && in_array($filter_status, ['Nowe', 'W toku', 'Naprawione', 'Odrzucone'])) {
    $where_clauses[] = "status = ?";
    $param_types .= "s";
    $param_values[] = $filter_status;
}

// Filtr PRIORYTET
$filter_prio = isset($_GET['f_prio']) ? $_GET['f_prio'] : '';
if ($filter_prio !== '' && in_array($filter_prio, ['Niski', 'Średni', 'Wysoki'])) {
    $where_clauses[] = "priorytet = ?";
    $param_types .= "s";
    $param_values[] = $filter_prio;
}

// Filtr LOKALIZACJA (Slowo klucz)
$filter_loc = isset($_GET['f_loc']) ? $_GET['f_loc'] : '';
if ($filter_loc !== '') {
    $where_clauses[] = "lokalizacja LIKE ?";
    $param_types .= "s";
    $param_values[] = '%' . $filter_loc . '%';
}

// Składanie finalnego zapytania z tabeli USTERKA polączonej z tablą UŻYTKOWNIK
$sql = "SELECT u.*, us.login as zglaszajacy FROM usterka u 
        JOIN uzytkownicy us ON u.autor_id = us.id 
        WHERE " . implode(' AND ', $where_clauses) . " 
        ORDER BY data_zgloszenia DESC";

$stmt = $conn->prepare($sql);
if (!empty($param_values)) {
    // Dynamiczne przypinanie parametrow '?' (Prepared statement zabezpieczajace przed SQL Injection)
    $stmt->bind_param($param_types, ...$param_values);
}
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Technika | Zarządzanie Usterkami</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Górne Menu z LOGO wbudowane w user-info (patrz css .uni-logo) -->
    <nav class="navbar">
        <div class="user-info">
            <img src="Logo UR_en (5).png" class="uni-logo" alt="Logo Uczelni">
            Zalogowano: <b><?= htmlspecialchars($user_name) ?></b> (Rola: Technik)
        </div>
        <div class="nav-links">
            <a href="logout.php" class="btn alt-btn text-danger" style="margin-left: 10px;">Wyloguj się z systemu</a>
        </div>
    </nav>
    <br>

    <div class="main-container" style="display:flex; gap:20px; flex-wrap:wrap">
        <?= $message; ?>

        <!-- Lewy Pasek z Filtrowaniem -->
        <div class="filter-panel custom-box" style="flex: 1; min-width: 250px;">
            <h3>Filtrowanie wyników</h3>
            <hr>
            <form method="GET" action="tech_panel.php">
                
                <div class="input-group">
                    <label>Filtruj Status</label>
                    <select name="f_status">
                        <option value="">(Wszystkie)</option>
                        <option value="Nowe" <?= $filter_status=='Nowe'?'selected':'' ?>>Nowe</option>
                        <option value="W toku" <?= $filter_status=='W toku'?'selected':'' ?>>W toku</option>
                        <option value="Naprawione" <?= $filter_status=='Naprawione'?'selected':'' ?>>Naprawione</option>
                        <option value="Odrzucone" <?= $filter_status=='Odrzucone'?'selected':'' ?>>Odrzucone</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <label>Filtruj Priorytet</label>
                    <select name="f_prio">
                        <option value="">(Wszystkie)</option>
                        <option value="Niski" <?= $filter_prio=='Niski'?'selected':'' ?>>Niski</option>
                        <option value="Średni" <?= $filter_prio=='Średni'?'selected':'' ?>>Średni</option>
                        <option value="Wysoki" <?= $filter_prio=='Wysoki'?'selected':'' ?>>Wysoki</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>Filtruj Budynek / Lokalizacje</label>
                    <input type="text" name="f_loc" value="<?= htmlspecialchars($filter_loc) ?>" placeholder="Słowo klucz...">
                </div>
                
                <button type="submit" class="btn" style="width:100%; margin-bottom: 10px;">Szukaj</button>
                <a href="tech_panel.php" class="btn alt-btn" style="text-align:center; display:block;">Wyczyść filtry</a>
            </form>
        </div>

        <!-- Prawy panel (Srodek) z Listą Useteryk dla Administratora -->
        <div class="list-panel custom-box" style="flex: 3; min-width: 400px; padding: 20px;">
            <h2>Zgłoszenia w Systemie</h2>
            
            <?php
            // Wypisywanie wyników zapytania SELECT w Pętli WHILE
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    $data = date('d-m-Y H:i', strtotime($row['data_zgloszenia']));

                    echo "<div class='issue admin-card' style='margin-bottom:20px; padding-bottom:15px; border-bottom:2px dashed #cbd5e1;'>";
                    
                    // Informacje o Usterce (Wizualnie)
                    echo "  <div class='issue-top'>";
                    echo "    <strong>#" . $row['id'] . " - " . htmlspecialchars($row['tytul']) . "</strong>";
                    echo "    <span class='badge'>[{$row['priorytet']}] Od: {$row['zglaszajacy']}</span>";
                    echo "  </div>";
                    echo "  <p class='issue-loc'>Miejsce: {$row['lokalizacja']} - (Zgłoszono: {$data}) </p>";
                    echo "  <p class='issue-desc'>Opis: {$row['opis']}</p>";
                    
                    // ---------------- Formularz z Obsługa Edycji Technika ------------
                    echo "  <div class='admin-controls' style='background:#f8fafc; padding:15px; border:1px solid #e2e8f0; border-radius:5px; margin-top:20px; display:flex; gap:5px; flex-wrap:wrap; align-items:flex-end;'>";
                    
                    echo "    <form method='POST' action='tech_panel.php' style='display:flex; gap:10px; margin:0; flex-wrap:wrap; align-items:flex-end; flex:1;'>";
                    echo "      <input type='hidden' name='akcja' value='aktualizuj'>";
                    echo "      <input type='hidden' name='issue_id' value='{$row['id']}'>";
                    
                    // Pole 1: STATUS
                    echo "      <div>";
                    echo "        <label>Status:</label><br>";
                    echo "        <select name='status'>";
                    $statuses = ['Nowe', 'W toku', 'Naprawione', 'Odrzucone'];
                    foreach ($statuses as $s) {
                        $sel = ($row['status'] == $s) ? 'selected' : '';
                        echo "<option value='{$s}' {$sel}>{$s}</option>";
                    }
                    echo "        </select>";
                    echo "      </div>";

                    // Pole 2: KOMENTARZ
                    echo "      <div style='flex:1;'>";
                    echo "        <label>Komentarz do studenta:</label><br>";
                    echo "        <input type='text' name='komentarz' value='" . htmlspecialchars((string)$row['komentarz_technika']) . "' style='width:100%;' placeholder='Dodaj ewentualną notatkę...'>";
                    echo "      </div>";

                    // ZAPISZ
                    echo "      <button type='submit' class='btn'>Zapisz Zmianę</button>";
                    echo "    </form>";

                    // Formularz nr 2: USUN do wyrzucenia z Bazy Danych
                    echo "    <form method='POST' action='tech_panel.php' onsubmit='return confirm(\"Czy potwiedasz kasowanie WPISU DLA WSZYSTKICH? (#{$row['id']})\");' style='margin:0;'>";
                    echo "      <input type='hidden' name='akcja' value='usun'>";
                    echo "      <input type='hidden' name='issue_id' value='{$row['id']}'>";
                    echo "      <button type='submit' class='btn alt-btn text-danger'>Usuń ten Wpis</button>";
                     echo "    </form>";

                    echo "  </div>";

                    echo "</div>"; // Koniec .issue
                }
            } else {
                echo "<p style='color: gray; margin-top:20px;'>Do wykonania: Brak zgłoszeń psujących do wymogów filtrowania.</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
