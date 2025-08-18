<?php
	require_once __DIR__ . '/functions.php';
	$script = $_SERVER['SCRIPT_NAME'] ?? '';
	$prefix = (strpos($script, '/projects/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars(site_name()); ?></title>
	<meta name="description" content="<?php echo htmlspecialchars(site_description()); ?>">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
	<link href="<?php echo $prefix; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
	<a class="visually-hidden-focusable position-absolute top-0 start-0 m-3 p-2 bg-white text-dark rounded shadow" href="#main">Skip to content</a>
	<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
		<div class="container">
			<a class="navbar-brand fw-bold text-primary" href="<?php echo $prefix; ?>index.php"><?php echo htmlspecialchars(site_name()); ?></a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsMain" aria-controls="navbarsMain" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarsMain">
				<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
					<li class="nav-item"><a class="nav-link" href="<?php echo $prefix; ?>index.php">Home</a></li>
					<li class="nav-item"><a class="nav-link" href="<?php echo $prefix; ?>about.php">About</a></li>
					<li class="nav-item"><a class="nav-link" href="<?php echo $prefix; ?>portfolio.php">Portfolio</a></li>
					<li class="nav-item"><a class="nav-link" href="<?php echo $prefix; ?>contact.php">Contact</a></li>
				</ul>
			</div>
		</div>
	</nav>
	<main id="main">

