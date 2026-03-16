-- Baza Danych dla Systemu Zgłaszania Usterek

CREATE DATABASE IF NOT EXISTS usterki_uczelnia;
USE usterki_uczelnia;

-- Tabela uzytkowników (studenci / pracownicy oraz technicy)
CREATE TABLE IF NOT EXISTS uzytkownicy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    haslo VARCHAR(255) NOT NULL,
    rola ENUM('student', 'technik') NOT NULL DEFAULT 'student'
);

-- Przykładowe konta (hasła zapisane w trybie plain text dla maksymalnej prostoty projektu)
INSERT IGNORE INTO uzytkownicy (login, haslo, rola) VALUES 
('student', 'student123', 'student'),
('janek', 'janek123', 'student'),
('technik', 'technik123', 'technik');

-- Tabela usterek (rozbudowana o nowe pola)
CREATE TABLE IF NOT EXISTS usterka (
    id INT AUTO_INCREMENT PRIMARY KEY,
    autor_id INT NOT NULL,
    tytul VARCHAR(255) NOT NULL,
    opis TEXT NOT NULL,
    lokalizacja VARCHAR(255) NOT NULL,
    priorytet ENUM('Niski', 'Średni', 'Wysoki') DEFAULT 'Średni',
    status ENUM('Nowe', 'W toku', 'Naprawione', 'Odrzucone') DEFAULT 'Nowe',
    komentarz_technika TEXT NULL,
    data_zgloszenia DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES uzytkownicy(id) ON DELETE CASCADE
);
