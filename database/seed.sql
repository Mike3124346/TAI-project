USE moviedatabase;

-- Fictional sample records. Re-running this file does not add duplicate samples.
INSERT INTO filmy (tytul, rok, gatunek, opis)
SELECT sample.tytul, sample.rok, sample.gatunek, sample.opis
FROM (
		SELECT 'The Glass Meridian' AS tytul, 2018 AS rok, 'Drama' AS gatunek,
					 'A lighthouse archivist discovers letters that change the history of a coastal town.' AS opis
		UNION ALL
		SELECT 'Silent Orbit', 2022, 'Science Fiction',
					 'A small research crew must decide whether to answer an unexplained signal.'
		UNION ALL
		SELECT 'Paper Kingdom', 2015, 'Mystery',
					 'A mapmaker follows a trail of altered maps through a quiet mountain city.'
) AS sample
WHERE NOT EXISTS (
		SELECT 1
		FROM filmy AS existing
		WHERE existing.tytul = sample.tytul
			AND existing.rok = sample.rok
);

INSERT INTO aktorzy (imie, nazwisko)
SELECT sample.imie, sample.nazwisko
FROM (
		SELECT 'Mara' AS imie, 'Vale' AS nazwisko
		UNION ALL
		SELECT 'Elias', 'North'
		UNION ALL
		SELECT 'June', 'Calder'
		UNION ALL
		SELECT 'Tomas', 'Reed'
) AS sample
WHERE NOT EXISTS (
		SELECT 1
		FROM aktorzy AS existing
		WHERE existing.imie = sample.imie
			AND existing.nazwisko = sample.nazwisko
);

INSERT IGNORE INTO obsada (film_id, aktor_id, rola)
SELECT film.id, actor.id, sample.rola
FROM (
		SELECT 'The Glass Meridian' AS tytul, 2018 AS rok,
					 'Mara' AS imie, 'Vale' AS nazwisko, 'Lena Ward' AS rola
		UNION ALL
		SELECT 'The Glass Meridian', 2018, 'Elias', 'North', 'Jon Bell'
		UNION ALL
		SELECT 'Silent Orbit', 2022, 'Elias', 'North', 'Dr. Ivo Sen'
		UNION ALL
		SELECT 'Silent Orbit', 2022, 'June', 'Calder', 'Mira Sol'
		UNION ALL
		SELECT 'Paper Kingdom', 2015, 'Mara', 'Vale', 'Ada Rowan'
		UNION ALL
		SELECT 'Paper Kingdom', 2015, 'Tomas', 'Reed', 'Niko Pell'
) AS sample
JOIN filmy AS film
	ON film.tytul = sample.tytul
 AND film.rok = sample.rok
JOIN aktorzy AS actor
	ON actor.imie = sample.imie
 AND actor.nazwisko = sample.nazwisko;

INSERT INTO recenzje (film_id, autor, ocena, tresc)
SELECT film.id, 'M.K.', 9,
			 'A patient mystery with a strong sense of place and a satisfying ending.'
FROM filmy AS film
WHERE film.tytul = 'The Glass Meridian'
	AND film.rok = 2018
	AND NOT EXISTS (
			SELECT 1
			FROM recenzje AS existing
			WHERE existing.film_id = film.id
				AND existing.autor = 'M.K.'
				AND existing.tresc = 'A patient mystery with a strong sense of place and a satisfying ending.'
	);

INSERT INTO recenzje (film_id, autor, ocena, tresc)
SELECT film.id, NULL, 8,
			 'Thoughtful performances make this compact science-fiction story memorable.'
FROM filmy AS film
WHERE film.tytul = 'Silent Orbit'
	AND film.rok = 2022
	AND NOT EXISTS (
			SELECT 1
			FROM recenzje AS existing
			WHERE existing.film_id = film.id
				AND existing.autor IS NULL
				AND existing.tresc = 'Thoughtful performances make this compact science-fiction story memorable.'
	);
