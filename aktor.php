<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/functions.php';

$actor = null;
$pageError = null;

try {
	$actorId = parsePositiveId($_GET['id'] ?? null, 'Actor ID');
	$actor = getActorDetails($actorId);
	if ($actor === null) {
		http_response_code(404);
		$pageError = 'This performer could not be found.';
	}
} catch (InvalidArgumentException $exception) {
	http_response_code(400);
	$pageError = 'The performer address is invalid.';
} catch (Throwable $exception) {
	error_log('Actor details request failed.');
	http_response_code(500);
	$pageError = 'Performer details are temporarily unavailable. Please try again later.';
}

$artwork = [
	'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=900&q=85',
	'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=900&q=85',
	'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?auto=format&fit=crop&w=900&q=85',
	'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=900&q=85',
];
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#11110f">
	<title><?= $actor !== null ? escapeHtml($actor['imie'] . ' ' . $actor['nazwisko']) . ' | Movie Database' : 'Performer | Movie Database' ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
	<header class="site-header">
		<a class="brand" href="index.php" aria-label="Movie Database home">
			<span class="brand-mark" aria-hidden="true">M</span>
			<span>Movie<span class="brand-light">Database</span></span>
		</a>
		<a class="header-back-link" href="index.php"><span aria-hidden="true">←</span> All films</a>
	</header>

	<main class="detail-main">
		<?php if ($actor === null): ?>
			<section class="error-page" role="alert">
				<p class="eyebrow">The archive</p>
				<h1><?= escapeHtml($pageError ?? 'This performer could not be found.') ?></h1>
				<a class="button-link" href="index.php">Return to the collection</a>
			</section>
		<?php else: ?>
			<section class="actor-heading" aria-labelledby="actor-name">
				<div class="actor-monogram" aria-hidden="true"><?= escapeHtml(mb_substr($actor['imie'], 0, 1, 'UTF-8') . mb_substr($actor['nazwisko'], 0, 1, 'UTF-8')) ?></div>
				<div>
					<p class="eyebrow"><span class="eyebrow-line"></span> Performer index</p>
					<h1 id="actor-name"><?= escapeHtml($actor['imie'] . ' ' . $actor['nazwisko']) ?></h1>
				</div>
			</section>

			<section class="catalog-section actor-filmography" aria-labelledby="filmography-heading">
				<div class="section-heading">
					<div>
						<p class="eyebrow">On screen</p>
						<h2 id="filmography-heading">Filmography</h2>
					</div>
					<p class="result-count"><?= count($actor['films']) ?> <span><?= count($actor['films']) === 1 ? 'film' : 'films' ?></span></p>
				</div>
				<?php if ($actor['films'] === []): ?>
					<div class="empty-state compact-empty">
						<span class="empty-mark" aria-hidden="true">—</span>
						<h3>No films listed</h3>
						<p>Filmography details have not been added yet.</p>
					</div>
				<?php else: ?>
					<div class="movie-grid">
						<?php foreach ($actor['films'] as $index => $movie): ?>
							<article class="movie-card">
								<a class="movie-card-link" href="film.php?id=<?= (int) $movie['id'] ?>">
									<div class="poster-frame">
										<img class="poster-image" src="<?= escapeHtml($artwork[$index % count($artwork)]) ?>" alt="" loading="lazy" decoding="async">
										<span class="poster-index" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
										<span class="poster-open" aria-hidden="true">↗</span>
									</div>
									<div class="movie-card-content">
										<div class="movie-card-meta"><span><?= escapeHtml($movie['rok']) ?></span><span class="meta-dot" aria-hidden="true"></span><span><?= escapeHtml($movie['gatunek']) ?></span></div>
										<h3><?= escapeHtml($movie['tytul']) ?></h3>
										<div class="card-bottom"><p class="movie-card-description"><?= escapeHtml($movie['rola'] ?? '') ?></p><span class="filmography-arrow" aria-hidden="true">↗</span></div>
									</div>
								</a>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>
	</main>

	<footer class="site-footer">
		<a href="index.php">Movie Database</a>
		<span>Stories worth keeping.</span>
	</footer>
</body>
</html>
