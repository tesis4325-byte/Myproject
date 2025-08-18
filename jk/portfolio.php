<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="py-5">
	<div class="container">
		<div class="d-flex align-items-center justify-content-between mb-4">
			<h1 class="mb-0">Portfolio</h1>
		</div>
		<div class="row">
			<?php foreach (getProjects() as $project) { renderProjectCard($project); } ?>
		</div>
	</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


