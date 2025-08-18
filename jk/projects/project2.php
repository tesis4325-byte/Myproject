<?php
	require_once __DIR__ . '/../includes/header.php';
	$project = getProjectBySlug('project2');
	if (!$project) {
		header('Location: ../portfolio.php');
		exit;
	}
	$assetPrefix = '../';
?>

<section class="py-5">
	<div class="container">
		<a href="../portfolio.php" class="btn btn-sm btn-outline-secondary mb-4"><i class="bi bi-arrow-left me-1"></i>Back to Portfolio</a>
		<div class="row g-5">
			<div class="col-lg-7">
				<div class="ratio ratio-16x9 mb-3">
					<img class="object-fit-cover w-100 h-100 rounded-3 border" src="<?php echo htmlspecialchars(resolve_asset_src((string)($project['image'] ?? ''), $assetPrefix)); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>">
				</div>
				<?php if (!empty($project['screenshots'])): ?>
					<div class="row g-3">
						<?php foreach ($project['screenshots'] as $shot): ?>
							<div class="col-6">
								<div class="ratio ratio-4x3">
									<img class="object-fit-cover w-100 h-100 rounded-3 border" src="<?php echo htmlspecialchars(resolve_asset_src((string)$shot, $assetPrefix)); ?>" alt="Screenshot">
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="col-lg-5">
				<h1 class="mb-3"><?php echo htmlspecialchars($project['title']); ?></h1>
				<p class="text-muted"><?php echo htmlspecialchars($project['description']); ?></p>
				<div class="d-flex flex-wrap gap-2 mb-4">
					<?php foreach (($project['tags'] ?? []) as $tag): ?>
						<span class="badge rounded-pill bg-light text-primary border"><?php echo htmlspecialchars((string)$tag); ?></span>
					<?php endforeach; ?>
				</div>
				<div class="d-flex gap-3">
					<?php if (!empty($project['url'])): ?><a href="<?php echo htmlspecialchars($project['url']); ?>" class="btn btn-primary" target="_blank" rel="noreferrer"><i class="bi bi-box-arrow-up-right me-2"></i>Live</a><?php endif; ?>
					<?php if (!empty($project['repo'])): ?><a href="https://github.com/tesis4325-byte/Myproject/tree/main/SARI%20SARI%20STORE<?php echo htmlspecialchars($project['repo']); ?>" class="btn btn-outline-primary" target="_blank" rel="noreferrer"><i class="bi bi-github me-2"></i>Code</a><?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>


