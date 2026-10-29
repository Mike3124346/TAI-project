CREATE DATABASE IF NOT EXISTS moviedatabase
		CHARACTER SET utf8mb4
		COLLATE utf8mb4_0900_ai_ci;

USE moviedatabase;

CREATE TABLE IF NOT EXISTS filmy (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		tytul VARCHAR(255) NOT NULL,
		rok SMALLINT UNSIGNED NOT NULL,
		gatunek VARCHAR(100) NOT NULL,
		opis TEXT NULL,
		PRIMARY KEY (id),
		KEY idx_filmy_tytul (tytul),
		KEY idx_filmy_gatunek_rok (gatunek, rok)
) ENGINE=InnoDB
	DEFAULT CHARACTER SET utf8mb4
	COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS aktorzy (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		imie VARCHAR(100) NOT NULL,
		nazwisko VARCHAR(100) NOT NULL,
		PRIMARY KEY (id),
		KEY idx_aktorzy_nazwisko_imie (nazwisko, imie)
) ENGINE=InnoDB
	DEFAULT CHARACTER SET utf8mb4
	COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS obsada (
		film_id INT UNSIGNED NOT NULL,
		aktor_id INT UNSIGNED NOT NULL,
		rola VARCHAR(150) NULL,
		PRIMARY KEY (film_id, aktor_id),
		KEY idx_obsada_aktor_film (aktor_id, film_id),
		CONSTRAINT fk_obsada_film
				FOREIGN KEY (film_id) REFERENCES filmy (id)
				ON UPDATE CASCADE
				ON DELETE CASCADE,
		CONSTRAINT fk_obsada_aktor
				FOREIGN KEY (aktor_id) REFERENCES aktorzy (id)
				ON UPDATE CASCADE
				ON DELETE CASCADE
) ENGINE=InnoDB
	DEFAULT CHARACTER SET utf8mb4
	COLLATE utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS recenzje (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		film_id INT UNSIGNED NOT NULL,
		autor VARCHAR(100) NULL,
		ocena TINYINT UNSIGNED NOT NULL,
		tresc TEXT NOT NULL,
		created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY idx_recenzje_film_data (film_id, created_at),
		CONSTRAINT chk_recenzje_ocena
				CHECK (ocena BETWEEN 1 AND 10),
		CONSTRAINT fk_recenzje_film
				FOREIGN KEY (film_id) REFERENCES filmy (id)
				ON UPDATE CASCADE
				ON DELETE CASCADE
) ENGINE=InnoDB
	DEFAULT CHARACTER SET utf8mb4
	COLLATE utf8mb4_0900_ai_ci;
