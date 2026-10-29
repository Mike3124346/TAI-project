<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/functions.php';

startAppSession();

$movieId = null;
$movie = null;
$pageError = null;
$formError = null;
$reviewText = '';
$displayName = '';
$ratingValue = '';
$reviewNotice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$rawId = $_POST['id'] ?? null;
	try {
		$movieId = parsePositiveId($rawId, 'Movie ID');
	} catch (InvalidArgumentException $exception) {
		http_response_code(400);
		$pageError = 'The movie address is invalid.';
	}

	if ($movieId !== null) {
		$submittedToken = $_POST['csrf_token'] ?? null;
		if (!is_string($submittedToken) || !isValidCsrfToken($submittedToken)) {
			http_response_code(403);
			$formError = 'Your form session expired. Reload the page and try again.';
		} else {
			$rawReviewText = $_POST['review_text'] ?? null;
			$rawDisplayName = $_POST['display_name'] ?? null;
			$rawRating = $_POST['rating'] ?? null;
			$reviewText = is_string($rawReviewText) ? $rawReviewText : '';
			$displayName = is_string($rawDisplayName) ? $rawDisplayName : '';
			$ratingValue = is_string($rawRating) ? $rawRating : '';

			try {
				if (!is_string($rawReviewText) || !is_string($rawRating)) {
					throw new InvalidArgumentException('Complete the review and choose a rating.');
				}
				if ($rawDisplayName !== null && !is_string($rawDisplayName)) {
					throw new InvalidArgumentException('Enter a valid display name.');
				}

				$rating = parseRating($rawRating);
				createReview($movieId, $rawReviewText, $rating, $rawDisplayName);

				$_SESSION['review_notice'] = 'Your review has been added.';
				header('Location: film.php?id=' . $movieId . '#reviews', true, 303);
				exit;
			} catch (InvalidArgumentException $exception) {
				http_response_code(422);
				$formError = $exception->getMessage();
			} catch (Throwable $exception) {
				error_log('Review submission failed.');
				http_response_code(500);
				$pageError = 'The review could not be saved. Please try again later.';
			}
		}
	}
} else {
	try {
		$movieId = parsePositiveId($_GET['id'] ?? null, 'Movie ID');
	} catch (InvalidArgumentException $exception) {
		http_response_code(400);
		$pageError = 'The movie address is invalid.';
	}
}

if ($movieId !== null && $pageError === null) {
	try {
		$movie = getMovieDetails($movieId);
		if ($movie === null) {
			http_response_code(404);
			$pageError = 'This film could not be found.';
		}
	} catch (Throwable $exception) {
		error_log('Movie details request failed.');
		http_response_code(500);
		$pageError = 'Film details are temporarily unavailable. Please try again later.';
	}
}

if (isset($_SESSION['review_notice']) && is_string($_SESSION['review_notice'])) {
	$reviewNotice = $_SESSION['review_notice'];
	unset($_SESSION['review_notice']);
}

$csrfToken = null;
if ($movie !== null) {
	try {
		$csrfToken = getCsrfToken();
	} catch (Throwable $exception) {
		error_log('Review form session could not be initialized.');
		http_response_code(500);
		$pageError = 'The review form is temporarily unavailable.';
	}
}

$artwork = [
	'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1400&q=85',
	'https://images.unsplash.com/photo-1478720568477-152d9b164e26?auto=format&fit=crop&w=1400&q=85',
	'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?auto=format&fit=crop&w=1400&q=85',
	'https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=1400&q=85',
];
$movieArtwork = $movie !== null ? $artwork[((int) $movie['id']) % count($artwork)] : $artwork[0];
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#11110f">
	<title><?= $movie !== null ? escapeHtml($movie['tytul']) . ' | Movie Database' : 'Film | Movie Database' ?></title>
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
		<a class="header-back-link" href="index.php"><span aria-hidden="true">←</span> All films</a>
	</header>

	<main class="detail-main">
		<?php if ($pageError !== null && $movie === null): ?>
			<section class="error-page" role="alert">
				<p class="eyebrow">The archive</p>
				<h1><?= escapeHtml($pageError) ?></h1>
				<a class="button-link" href="index.php">Return to the collection</a>
			</section>
		<?php elseif ($movie !== null): ?>
			<?php if ($pageError !== null): ?>
				<div class="notice notice-error" role="alert"><?= escapeHtml($pageError) ?></div>
			<?php endif; ?>
			<section class="movie-hero" aria-labelledby="movie-title">
				<div class="movie-hero-image">
					<img src="<?= escapeHtml($movieArtwork) ?>" alt="" fetchpriority="high">
				</div>
				<div class="movie-hero-content">
					<p class="eyebrow"><span class="eyebrow-line"></span> <?= escapeHtml($movie['gatunek']) ?> <span class="meta-dot" aria-hidden="true"></span> <?= escapeHtml($movie['rok']) ?></p>
					<h1 id="movie-title"><?= escapeHtml($movie['tytul']) ?></h1>
					<?php if (!empty($movie['opis'])): ?>
						<p class="movie-description"><?= escapeHtml($movie['opis']) ?></p>
					<?php endif; ?>
					<a class="text-link" href="#reviews">Read the reviews <span aria-hidden="true">↓</span></a>
				</div>
			</section>

			<section class="detail-section cast-section" aria-labelledby="cast-heading">
				<div class="section-heading">
					<div>
						<p class="eyebrow">The people behind the frame</p>
						<h2 id="cast-heading">Cast</h2>
					</div>
					<p class="result-count"><?= count($movie['cast']) ?> <span><?= count($movie['cast']) === 1 ? 'performer' : 'performers' ?></span></p>
				</div>
				<?php if ($movie['cast'] === []): ?>
					<p class="muted-copy">Cast details have not been added yet.</p>
				<?php else: ?>
					<ul class="cast-list">
						<?php foreach ($movie['cast'] as $actor): ?>
							<li class="cast-item">
								<a href="aktor.php?id=<?= (int) $actor['id'] ?>">
									<span class="cast-name"><?= escapeHtml($actor['imie'] . ' ' . $actor['nazwisko']) ?></span>
									<?php if (!empty($actor['rola'])): ?><span class="cast-role"><?= escapeHtml($actor['rola']) ?></span><?php endif; ?>
									<span class="cast-arrow" aria-hidden="true">↗</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>

			<section class="detail-section reviews-section" id="reviews" aria-labelledby="reviews-heading">
				<div class="section-heading">
					<div>
						<p class="eyebrow">Audience notes</p>
						<h2 id="reviews-heading">Reviews</h2>
					</div>
					<p class="result-count"><?= count($movie['reviews']) ?> <span><?= count($movie['reviews']) === 1 ? 'review' : 'reviews' ?></span></p>
				</div>

				<?php if ($reviewNotice !== null): ?>
					<div class="notice notice-success" role="status"><?= escapeHtml($reviewNotice) ?></div>
				<?php endif; ?>
				<?php if ($formError !== null): ?>
					<div class="notice notice-error" role="alert"><?= escapeHtml($formError) ?></div>
				<?php endif; ?>

				<div class="review-layout">
					<div class="review-list">
						<?php if ($movie['reviews'] === []): ?>
							<div class="empty-reviews">
								<span class="empty-mark" aria-hidden="true">✳</span>
								<p>No reviews yet. Leave the first note.</p>
							</div>
						<?php else: ?>
							<?php foreach ($movie['reviews'] as $review): ?>
								<article class="review-entry">
									<div class="review-entry-top">
										<p class="review-author"><?= escapeHtml($review['autor'] ?: 'Anonymous') ?></p>
										<p class="review-rating"><span aria-hidden="true">✳</span> <?= escapeHtml($review['ocena']) ?><span class="rating-out-of"> / 10</span></p>
									</div>
									<p class="review-date"><?= escapeHtml(date('M j, Y', strtotime((string) $review['created_at']))) ?></p>
									<p class="review-copy"><?= nl2br(escapeHtml($review['tresc'])) ?></p>
								</article>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<form class="review-form" method="post" action="film.php?id=<?= (int) $movie['id'] ?>#reviews" data-review-form novalidate>
						<div class="form-heading">
							<p class="eyebrow">Your perspective</p>
							<h3>Leave a review</h3>
						</div>
						<input type="hidden" name="id" value="<?= (int) $movie['id'] ?>">
						<input type="hidden" name="csrf_token" value="<?= escapeHtml($csrfToken ?? '') ?>">
						<div class="form-field">
							<label for="review-rating">Rating <span aria-hidden="true">*</span></label>
							<select id="review-rating" name="rating" required data-rating-field>
								<option value="">Choose a score</option>
								<?php for ($score = 10; $score >= 1; $score--): ?>
									<option value="<?= $score ?>" <?= $ratingValue === (string) $score ? 'selected' : '' ?>><?= $score ?> / 10</option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="form-field">
							<label for="review-text">Review <span aria-hidden="true">*</span></label>
							<textarea id="review-text" name="review_text" rows="5" maxlength="65535" required data-review-field><?= escapeHtml($reviewText) ?></textarea>
						</div>
						<div class="form-field">
							<label for="display-name">Name <span class="optional-label">Optional</span></label>
							<input id="display-name" name="display_name" type="text" maxlength="100" value="<?= escapeHtml($displayName) ?>" autocomplete="nickname">
						</div>
						<p class="form-note">Your review can be submitted without a name.</p>
						<button class="button-primary" type="submit">Publish review <span aria-hidden="true">↗</span></button>
						<p class="form-status" data-form-status aria-live="polite"></p>
					</form>
				</div>
			</section>
		<?php endif; ?>
	</main>

	<footer class="site-footer">
		<a href="index.php">Movie Database</a>
		<span>Stories worth keeping.</span>
	</footer>
</body>
</html>
