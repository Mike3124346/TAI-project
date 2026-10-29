<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/functions.php';

$query = '';
$movies = [];
$pageError = null;
$requestIsValid = true;

if (array_key_exists('q', $_GET)) {
	if (!is_string($_GET['q']) || !isValidUtf8($_GET['q'])) {
		http_response_code(400);
		$pageError = 'Enter a valid search term.';
		$requestIsValid = false;
	} else {
		$query = trim($_GET['q']);
		if (utf8Length($query) > 255) {
			http_response_code(400);
			$pageError = 'Search terms must be 255 characters or fewer.';
			$requestIsValid = false;
		}
	}
}

if ($requestIsValid) {
	try {
		$movies = getMovies($query);
	} catch (Throwable $exception) {
		error_log('Movie catalog request failed.');
		http_response_code(500);
		$pageError = 'The catalog is temporarily unavailable. Please try again later.';
	}
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
	<title>Movie Database</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="frontend/css/style.css">
	<script src="frontend/js/script.js" defer></script>
</head>
<body>
	<header class="site-header">
		<a class="brand" href="index.php" aria-label="Movie Database home">
			<span class="brand-mark" aria-hidden="true">M</span>
			<span>Movie<span class="brand-light">Database</span></span>
		</a>
		<p class="header-edition">THE SCREEN INDEX <span>NO. 01</span></p>
	</header>

	<main>
		<section class="catalog-intro" aria-labelledby="page-title">
			<div>
				<p class="eyebrow"><span class="eyebrow-line"></span> A living archive of film</p>
				<h1 id="page-title">Movie <em>Database</em></h1>
			</div>
			<form class="search-form" action="index.php" method="get" role="search">
				<label class="visually-hidden" for="movie-search">Search movie titles</label>
				<input
					id="movie-search"
					name="q"
					type="search"
					value="<?= escapeHtml($query) ?>"
					placeholder="Search titles"
					autocomplete="off"
				>
				<button class="search-button" type="submit" aria-label="Search movies">
					<span aria-hidden="true">⌕</span>
				</button>
			</form>
		</section>

		<section class="catalog-section" aria-labelledby="catalog-heading">
			<div class="section-heading">
				<div>
					<p class="eyebrow">Curated collection</p>
					<h2 id="catalog-heading"><?= $query !== '' ? 'Search results' : 'The collection' ?></h2>
				</div>
				<p class="result-count">
					<?php if ($pageError === null): ?>
						<?= count($movies) ?> <span><?= count($movies) === 1 ? 'film' : 'films' ?></span>
					<?php endif; ?>
				</p>
			</div>

			<?php if ($pageError !== null): ?>
				<div class="notice notice-error" role="alert"><?= escapeHtml($pageError) ?></div>
			<?php elseif ($movies === []): ?>
				<div class="empty-state">
					<span class="empty-mark" aria-hidden="true">—</span>
					<h3><?= $query !== '' ? 'No matching films' : 'The collection is waiting' ?></h3>
					<p><?= $query !== '' ? 'Try another title.' : 'Films will appear here once they are added to the catalog.' ?></p>
					<?php if ($query !== ''): ?>
						<a class="text-link" href="index.php">View all films <span aria-hidden="true">↗</span></a>
					<?php endif; ?>
				</div>
			<?php else: ?>
				<div class="movie-grid">
					<?php foreach ($movies as $index => $movie): ?>
						<article class="movie-card">
							<a class="movie-card-link" href="film.php?id=<?= (int) $movie['id'] ?>">
								<div class="poster-frame">
									<img
										class="poster-image"
										src="<?= escapeHtml($artwork[$index % count($artwork)]) ?>"
										alt=""
										loading="lazy"
										decoding="async"
									>
									<span class="poster-index" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
									<span class="poster-open" aria-hidden="true">↗</span>
								</div>
								<div class="movie-card-content">
									<div class="movie-card-meta">
										<span><?= escapeHtml($movie['rok']) ?></span>
										<span class="meta-dot" aria-hidden="true"></span>
										<span><?= escapeHtml($movie['gatunek']) ?></span>
									</div>
									<h3><?= escapeHtml($movie['tytul']) ?></h3>
									<div class="card-bottom">
										<p class="movie-card-description"><?= escapeHtml($movie['opis'] ?? '') ?></p>
										<p class="rating-chip" aria-label="<?= is_numeric($movie['srednia_ocen']) ? 'Average rating ' . escapeHtml($movie['srednia_ocen']) . ' out of 10' : 'Not rated yet' ?>">
											<span aria-hidden="true">✳</span>
											<?= is_numeric($movie['srednia_ocen']) ? escapeHtml(number_format((float) $movie['srednia_ocen'], 1)) : '—' ?>
										</p>
									</div>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</main>

	<footer class="site-footer">
		<span>Movie Database</span>
		<span>Stories worth keeping.</span>
	</footer>
</body>
</html>
