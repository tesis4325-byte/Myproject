<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="py-5">
	<div class="container">
		<h1 class="mb-4">Admin (Read-only demo)</h1>
		<p class="text-muted">This demo reads from <code>data/projects.json</code>. Edit that file to add/update projects. For a full admin with authentication and uploads, wire this page to a database and secure it.</p>
		<div class="row">
			<?php foreach (getProjects() as $project) { renderProjectCard($project); } ?>
		</div>
	</div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


