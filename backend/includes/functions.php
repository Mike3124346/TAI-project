<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function getMovies(string $query = ''): array
{
	$pdo = getPDO();
	$sql = <<<'SQL'
		SELECT
			film.id,
			film.tytul,
			film.rok,
			film.gatunek,
			film.opis,
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
		SQL;

	$query = trim($query);
	$parameters = [];

	if ($query !== '') {
		$escapedQuery = strtr($query, [
			'!' => '!!',
			'%' => '!%',
			'_' => '!_',
		]);
		$sql .= "\nWHERE film.tytul LIKE :title_pattern ESCAPE '!'";
		$parameters['title_pattern'] = '%' . $escapedQuery . '%';
	}

	$sql .= "\nORDER BY film.rok DESC, film.tytul ASC";

	$statement = executeQuery($pdo, $sql, $parameters);

	return $statement->fetchAll();
}

function getMovieDetails(int $movieId): ?array
{
	assertPositiveId($movieId, 'Movie ID');

	$pdo = getPDO();
	$statement = executeQuery(
		$pdo,
		'SELECT id, tytul, rok, gatunek, opis FROM filmy WHERE id = :film_id',
		['film_id' => $movieId]
	);
	$movie = $statement->fetch();

	if ($movie === false) {
		return null;
	}

	$castStatement = executeQuery(
		$pdo,
		'SELECT actor.id, actor.imie, actor.nazwisko, movie_cast.rola
		 FROM obsada AS movie_cast
		 JOIN aktorzy AS actor ON actor.id = movie_cast.aktor_id
		 WHERE movie_cast.film_id = :film_id
		 ORDER BY actor.nazwisko ASC, actor.imie ASC',
		['film_id' => $movieId]
	);
	$movie['cast'] = $castStatement->fetchAll();

	$reviewStatement = executeQuery(
		$pdo,
		'SELECT id, autor, ocena, tresc, created_at
		 FROM recenzje
		 WHERE film_id = :film_id
		 ORDER BY created_at DESC, id DESC',
		['film_id' => $movieId]
	);
	$movie['reviews'] = $reviewStatement->fetchAll();

	return $movie;
}

function getActorDetails(int $actorId): ?array
{
	assertPositiveId($actorId, 'Actor ID');

	$pdo = getPDO();
	$statement = executeQuery(
		$pdo,
		'SELECT id, imie, nazwisko FROM aktorzy WHERE id = :actor_id',
		['actor_id' => $actorId]
	);
	$actor = $statement->fetch();

	if ($actor === false) {
		return null;
	}

	$filmStatement = executeQuery(
		$pdo,
		'SELECT film.id, film.tytul, film.rok, film.gatunek, movie_cast.rola
		 FROM obsada AS movie_cast
		 JOIN filmy AS film ON film.id = movie_cast.film_id
		 WHERE movie_cast.aktor_id = :actor_id
		 ORDER BY film.rok DESC, film.tytul ASC',
		['actor_id' => $actorId]
	);
	$actor['films'] = $filmStatement->fetchAll();

	return $actor;
}

function createReview(
	int $movieId,
	string $reviewText,
	int $rating,
	?string $displayName
): void {
	assertPositiveId($movieId, 'Movie ID');

	$reviewText = trim($reviewText);
	if ($reviewText === '') {
		throw new InvalidArgumentException('Review text is required.');
	}

	if (strlen($reviewText) > 65535 || !isValidUtf8($reviewText)) {
		throw new InvalidArgumentException('Review text is too long or invalid.');
	}

	if ($rating < 1 || $rating > 10) {
		throw new InvalidArgumentException('Rating must be an integer from 1 to 10.');
	}

	$displayName = $displayName === null ? null : trim($displayName);
	if ($displayName === '') {
		$displayName = null;
	}

	if ($displayName !== null && (!isValidUtf8($displayName) || utf8Length($displayName) > 100)) {
		throw new InvalidArgumentException('Display name must be 100 characters or fewer.');
	}

	$pdo = getPDO();
	$movieStatement = executeQuery(
		$pdo,
		'SELECT id FROM filmy WHERE id = :film_id',
		['film_id' => $movieId]
	);

	if ($movieStatement->fetchColumn() === false) {
		throw new InvalidArgumentException('The selected movie does not exist.');
	}

	executeQuery(
		$pdo,
		'INSERT INTO recenzje (film_id, autor, ocena, tresc)
		 VALUES (:film_id, :author, :rating, :review_text)',
		[
			'film_id' => $movieId,
			'author' => $displayName,
			'rating' => $rating,
			'review_text' => $reviewText,
		]
	);
}

function getCsrfToken(): string
{
	startAppSession();

	if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}

	return $_SESSION['csrf_token'];
}

function isValidCsrfToken(string $submittedToken): bool
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		return false;
	}

	$sessionToken = $_SESSION['csrf_token'] ?? null;

	return is_string($sessionToken)
		&& $sessionToken !== ''
		&& hash_equals($sessionToken, $submittedToken);
}

function assertPositiveId(int $id, string $label): void
{
	if ($id < 1) {
		throw new InvalidArgumentException($label . ' must be a positive integer.');
	}
}

function parsePositiveId($value, string $label): int
{
	if (is_int($value)) {
		assertPositiveId($value, $label);
		return $value;
	}

	if (!is_string($value) || preg_match('/^[0-9]+$/D', $value) !== 1) {
		throw new InvalidArgumentException($label . ' must be a positive integer.');
	}

	$normalizedValue = ltrim($value, '0');
	if ($normalizedValue === '') {
		throw new InvalidArgumentException($label . ' must be a positive integer.');
	}

	$id = filter_var($normalizedValue, FILTER_VALIDATE_INT, [
		'options' => ['min_range' => 1],
	]);

	if ($id === false) {
		throw new InvalidArgumentException($label . ' must be a positive integer.');
	}

	return $id;
}

function parseRating($value): int
{
	if (is_int($value)) {
		$rating = $value;
	} elseif (is_string($value) && preg_match('/^[0-9]+$/D', $value) === 1) {
		$rating = (int) $value;
	} else {
		throw new InvalidArgumentException('Rating must be an integer from 1 to 10.');
	}

	if ($rating < 1 || $rating > 10) {
		throw new InvalidArgumentException('Rating must be an integer from 1 to 10.');
	}

	return $rating;
}

function startAppSession(): void
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		return;
	}

	if (headers_sent()) {
		throw new RuntimeException('The session cannot be started after output.');
	}

	$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => $isHttps,
		'httponly' => true,
		'samesite' => 'Lax',
	]);

	if (!session_start()) {
		throw new RuntimeException('The session could not be started.');
	}
}

function isValidUtf8(string $value): bool
{
	return preg_match('//u', $value) === 1;
}

function utf8Length(string $value): int
{
	$length = preg_match_all('/./us', $value);

	if ($length === false) {
		throw new InvalidArgumentException('Text must be valid UTF-8.');
	}

	return $length;
}

function escapeHtml($value): string
{
	if ($value === null) {
		return '';
	}

	if (!is_scalar($value)) {
		throw new InvalidArgumentException('Only scalar values can be rendered as text.');
	}

	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function executeQuery(PDO $pdo, string $sql, array $parameters = []): PDOStatement
{
	try {
		$statement = $pdo->prepare($sql);
		$statement->execute($parameters);

		return $statement;
	} catch (PDOException $exception) {
		error_log('Database operation failed: ' . $exception->getMessage());
		throw new RuntimeException('Database operation failed.', 0, $exception);
	}
}
