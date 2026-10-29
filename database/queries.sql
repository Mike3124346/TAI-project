-- Execute each statement through PDO::prepare() and PDOStatement::execute().
-- Never concatenate request values into SQL. Bind title_pattern as
-- '%' + the search text escaped for ESCAPE '!' + '%'.

-- Movie catalog, including aggregate rating and review count.
SELECT
	film.id,
	film.tytul,
	film.rok,
	film.gatunek,
	(
		SELECT ROUND(AVG(review.ocena), 1)
		FROM recenzje AS review
		WHERE review.film_id = film.id
	) AS srednia_ocen,
	(
		SELECT COUNT(*)
		FROM recenzje AS review
		WHERE review.film_id = film.id
	) AS liczba_recenzji
FROM filmy AS film
ORDER BY film.rok DESC, film.tytul ASC;

-- Search titles case-insensitively. Escape !, % and _ in the application
-- before wrapping the value in percent signs and binding :title_pattern.
SELECT film.id, film.tytul, film.rok, film.gatunek
FROM filmy AS film
WHERE film.tytul LIKE :title_pattern ESCAPE '!'
ORDER BY film.rok DESC, film.tytul ASC;

-- Movie details.
SELECT film.id, film.tytul, film.rok, film.gatunek, film.opis
FROM filmy AS film
WHERE film.id = :film_id;

-- Cast for a movie.
SELECT actor.id, actor.imie, actor.nazwisko, movie_cast.rola
FROM obsada AS movie_cast
JOIN aktorzy AS actor ON actor.id = movie_cast.aktor_id
WHERE movie_cast.film_id = :film_id
ORDER BY actor.nazwisko ASC, actor.imie ASC;

-- Reviews for a movie. A NULL author is displayed as Anonymous by the view.
SELECT review.id, review.autor, review.ocena, review.tresc, review.created_at
FROM recenzje AS review
WHERE review.film_id = :film_id
ORDER BY review.created_at DESC, review.id DESC;

-- Actor details.
SELECT actor.id, actor.imie, actor.nazwisko
FROM aktorzy AS actor
WHERE actor.id = :actor_id;

-- Filmography for an actor.
SELECT film.id, film.tytul, film.rok, film.gatunek, movie_cast.rola
FROM obsada AS movie_cast
JOIN filmy AS film ON film.id = movie_cast.film_id
WHERE movie_cast.aktor_id = :actor_id
ORDER BY film.rok DESC, film.tytul ASC;

-- Insert a review after server-side validation of the film, text and rating.
INSERT INTO recenzje (film_id, autor, ocena, tresc)
VALUES (:film_id, :author, :rating, :review_text);
