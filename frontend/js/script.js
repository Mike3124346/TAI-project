document.addEventListener('DOMContentLoaded', () => {
	const reviewForm = document.querySelector('[data-review-form]');
	if (!reviewForm) {
		return;
	}

	const reviewField = reviewForm.querySelector('[data-review-field]');
	const ratingField = reviewForm.querySelector('[data-rating-field]');
	const status = reviewForm.querySelector('[data-form-status]');

	reviewForm.addEventListener('submit', (event) => {
		const reviewIsValid = reviewField.value.trim().length > 0;
		const rating = Number(ratingField.value);
		const ratingIsValid = Number.isInteger(rating) && rating >= 1 && rating <= 10;

		reviewField.setCustomValidity(reviewIsValid ? '' : 'Write a review before submitting.');
		ratingField.setCustomValidity(ratingIsValid ? '' : 'Choose a whole-number rating from 1 to 10.');

		if (!reviewIsValid || !ratingIsValid) {
			event.preventDefault();
			status.textContent = 'Complete the required fields to publish your review.';
			const invalidField = !ratingIsValid ? ratingField : reviewField;
			invalidField.reportValidity();
			invalidField.focus();
			return;
		}

		status.textContent = '';
	});

	reviewForm.addEventListener('input', (event) => {
		if (event.target === reviewField && reviewField.value.trim() !== '') {
			reviewField.setCustomValidity('');
		}
	});

	reviewForm.addEventListener('change', (event) => {
		if (event.target === ratingField && ratingField.value !== '') {
			ratingField.setCustomValidity('');
		}
	});
});
