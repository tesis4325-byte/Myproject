<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="hero">
	<div class="container">
		<div class="row align-items-center g-5">
			<div class="col-lg-6 order-2 order-lg-1">
				<h1 class="hero-title display-5 mb-3 text-gradient">Hi, I'm <?php echo htmlspecialchars(site_name()); ?>.</h1>
				<p class="hero-subtitle lead mb-4"><?php echo htmlspecialchars(site_tagline()); ?></p>
				<div class="hero-cta d-flex flex-wrap gap-3 mb-4">
					<a href="portfolio.php" class="btn btn-primary btn-lg"><i class="bi bi-grid-3x3-gap me-2"></i>View Portfolio</a>
					<a href="#about" class="btn btn-outline-primary btn-lg"><i class="bi bi-person me-2"></i>About Me</a>
				</div>
				<div class="d-flex align-items-center gap-3">
					<a href="https://github.com/tesis4325-byte/Myproject" class="text-decoration-none"><i class="bi bi-github fs-3"></i></a>
					<a href="https://www.linkedin.com/in/juphil-kadusale-0367b0324/" class="text-decoration-none"><i class="bi bi-linkedin fs-3"></i></a>
					<a href="mailto:lihpuj12@gmail.com" class="text-decoration-none"><i class="bi bi-envelope fs-3"></i></a>
				</div>
			</div>
			<div class="col-lg-6 order-1 order-lg-2 text-center">
				<div class="ratio ratio-1x1" style="max-width:320px;margin-inline:auto;">
					<img src="assets/images/12.png" alt="Profile photo" class="rounded-circle object-fit-cover shadow-sm">
				</div>
			</div>
		</div>
	</div>
</section>

<section id="about" class="py-5">
	<div class="container">
		<div class="row g-5 align-items-center">
			<div class="col-lg-6">
				<h2 class="mb-3">About Me</h2>
				<p class="text-muted">Hi! I’m JK DEVWORKZ, a passionate Web Developer specializing in PHP, HTML, CSS, JavaScript, and Bootstrap 5. I build modern, responsive, and professional web applications, including portfolio sites, library management systems, and POS systems. I focus on clean, maintainable, and modular code, ensuring fast and user-friendly experiences. Always eager to learn new technologies and improve my craft, I aim to deliver high-quality solutions that make a real impact.</p>
				<div class="d-flex gap-3 mt-4">
					<a href="assets/resume.pdf" class="btn btn-outline-primary"><i class="bi bi-file-earmark-arrow-down me-2"></i>Download Resume</a>
					<a href="contact.php" class="btn btn-primary"><i class="bi bi-chat-dots me-2"></i>Contact Me</a>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="row g-3">
					<div class="col-6">
						<div class="p-4 bg-white border rounded-3 shadow-sm h-100 reveal">
							<h6 class="text-muted mb-1">Experience</h6>
							<p class="mb-0 fw-bold">3+ years</p>
						</div>
					</div>
					<div class="col-6">
						<div class="p-4 bg-white border rounded-3 shadow-sm h-100 reveal">
							<h6 class="text-muted mb-1">Focus</h6>
							<p class="mb-0 fw-bold">PHP, JS, HTML, CSS, MySQL, jQuery, Bootstrap, Tailwind CSS, Git, GitHub, React.js</p>
						</div>
					</div>
					<div class="col-6">
						<div class="p-4 bg-white border rounded-3 shadow-sm h-100 reveal">
							<h6 class="text-muted mb-1">Stack</h6>
							<p class="mb-0 fw-bold">Bootstrap 5, jQuery, MySQL, PHP, HTML, CSS, Tailwind CSS, Git, GitHub, React.js</p>
						</div>
					</div>
					<div class="col-6">
						<div class="p-4 bg-white border rounded-3 shadow-sm h-100 reveal">
							<h6 class="text-muted mb-1">Country</h6>
							<p class="mb-0 fw-bold">Philippines</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="py-5">
	<div class="container">
		<div class="d-flex align-items-center justify-content-between mb-4">
			<h2 class="mb-0">Featured Work</h2>
			<a class="btn btn-outline-primary" href="portfolio.php">See all</a>
		</div>
		<div class="row">
			<?php foreach (array_slice(getProjects(), 0, 3) as $project) { renderProjectCard($project); } ?>
		</div>
	</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


