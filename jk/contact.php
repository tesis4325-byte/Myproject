<?php
	require_once __DIR__ . '/includes/header.php';
	$feedback = null;
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$feedback = send_contact_message([
			'name' => $_POST['name'] ?? '',
			'email' => $_POST['email'] ?? '',
			'message' => $_POST['message'] ?? '',
		]);
	}
?>

<section class="py-5">
	<div class="container">
		<div class="row g-5">
			<div class="col-lg-6">
				<h1 class="mb-4">Contact</h1>
				<p class="text-muted">Have a question or a project in mind? I’d love to hear from you.</p>

				<?php if ($feedback): ?>
					<div class="alert <?php echo $feedback['success'] ? 'alert-success' : 'alert-danger'; ?>" role="alert">
						<?php echo htmlspecialchars($feedback['message']); ?>
					</div>
				<?php endif; ?>

				<form method="post" class="needs-validation" novalidate>
					<div class="mb-3">
						<label for="name" class="form-label">Name</label>
						<input type="text" class="form-control" id="name" name="name" required>
						<div class="invalid-feedback">Please enter your name.</div>
					</div>
					<div class="mb-3">
						<label for="email" class="form-label">Email</label>
						<input type="email" class="form-control" id="email" name="email" required>
						<div class="invalid-feedback">Please enter a valid email.</div>
					</div>
					<div class="mb-3">
						<label for="message" class="form-label">Message</label>
						<textarea class="form-control" id="message" name="message" rows="5" required></textarea>
						<div class="invalid-feedback">Please enter a message.</div>
					</div>
					<button class="btn btn-primary" type="submit"><i class="bi bi-send me-2"></i>Send</button>
				</form>
			</div>
			<div class="col-lg-6">
				<div class="p-4 bg-white border rounded-3 shadow-sm h-100">
					<h5 class="mb-3">Let’s build something great</h5>
					<p class="text-muted">I specialize in PHP, JS, and modern UI. I can help with new builds, refactors, and performance improvements.</p>
					<ul class="list-unstyled mb-0">
						<li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Philippines</li>
						<li class="mb-2"><i class="bi bi-envelope me-2"></i>lihpuj12@gmail.com</li>
						<li class="mb-2"><i class="bi bi-linkedin me-2"></i>linkedin.com/in/juphil-kadusale-0367b0324</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


