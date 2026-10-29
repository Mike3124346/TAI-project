<?php
declare(strict_types=1);

function getPDO(): PDO
{
	static $connection = null;

	if ($connection instanceof PDO) {
		return $connection;
	}

	$host = getenv('DB_HOST');
	$database = getenv('DB_NAME');
	$username = getenv('DB_USER');
	$password = getenv('DB_PASSWORD');
	$port = getenv('DB_PORT') ?: '3306';

	if (
		$host === false || trim($host) === ''
		|| $database === false || trim($database) === ''
		|| $username === false || trim($username) === ''
		|| $password === false
	) {
		throw new RuntimeException('Database configuration is incomplete.');
	}

	$validatedPort = filter_var($port, FILTER_VALIDATE_INT, [
		'options' => ['min_range' => 1, 'max_range' => 65535],
	]);

	if ($validatedPort === false) {
		throw new RuntimeException('Database configuration is invalid.');
	}

	$dsn = sprintf(
		'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
		$host,
		$validatedPort,
		$database
	);

	try {
		$connection = new PDO($dsn, $username, $password, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		]);
	} catch (PDOException $exception) {
		error_log('Database connection failed: ' . $exception->getMessage());
		throw new RuntimeException('Database connection is unavailable.', 0, $exception);
	}

	return $connection;
}
