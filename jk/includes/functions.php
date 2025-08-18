<?php
	declare(strict_types=1);

	/**
	 * Site-wide helper functions and data providers.
	 */

	function site_name(): string {
		return 'JK DEVWORKZ';
	}

	function site_description(): string {
		return 'PHP Developer · Building performant web experiences';
	}

	function site_tagline(): string {
		return 'Crafting elegant, scalable, and user-focused solutions';
	}

	function base_url(): string {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
		$dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
		return $protocol . $host . ($dir ? $dir . '/' : '/');
	}

	/**
	 * Resolve an asset path safely with an optional prefix.
	 * If the provided path is an absolute URL (http/https) or an absolute path starting with '/',
	 * it will be returned unchanged. Otherwise, the optional prefix will be prepended.
	 */
	function resolve_asset_src(string $path, string $prefix = ''): string {
		$trimmed = trim($path);
		if ($trimmed === '') {
			return '';
		}
		if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//#i', $trimmed)) { // http:, https:, or protocol-relative
			return $trimmed;
		}
		if ($trimmed[0] === '/') { // absolute path from web root
			return $trimmed;
		}
		return $prefix . $trimmed;
	}

	/**
	 * Load projects from data/projects.json if present, else return static examples.
	 * @return array<int, array<string, mixed>>
	 */
	function getProjects(): array {
		$dataFile = dirname(__DIR__) . '/data/projects.json';
		if (is_readable($dataFile)) {
			$json = file_get_contents($dataFile);
			if ($json !== false) {
				$parsed = json_decode($json, true);
				if (is_array($parsed)) {
					return $parsed;
				}
			}
		}

		// Fallback demo projects
		return [
			[
				'slug' => 'project1',
				'title' => 'E-commerce UI Revamp',
				'summary' => 'Modern, responsive storefront with improved conversion UX.',
				'description' => 'A complete redesign of the storefront and checkout experience using a component-driven approach. Performance optimized, A/B tested, and accessible.',
				'image' => 'assets/images/Screenshot (58).png',
				'tags' => ['Bootstrap 5', 'PHP', 'Performance', 'HTML', 'CSS', 'Tailwind CSS', 'GitHub', 'React.js', 'jQuery', 'MySQL'],
				'url' => '#',
				'repo' => '#',
				'screenshots' => ['assets/images/Screenshot (58).png', 'assets/images/Screenshot (59).png']
			],
			[
				'slug' => 'project2',
				'title' => 'Portfolio CMS',
				'summary' => 'A lightweight CMS to manage portfolio items and content.',
				'description' => 'CRUD interface for projects with image uploads, category filters, and markdown content. Built for simplicity and speed.',
				'image' => 'assets/images/1.png',
				'tags' => ['PHP', 'MySQL', 'HTML', 'CSS', 'Tailwind CSS', 'GitHub', 'React.js', 'jQuery', 'REST APIs'],
				'url' => '#',
				'repo' => '#',
				'screenshots' => ['assets/images/2.png']
			]
		];
	}

	/**
	 * @param string $slug
	 * @return array<string, mixed>|null
	 */
	function getProjectBySlug(string $slug): ?array {
		foreach (getProjects() as $project) {
			if (($project['slug'] ?? '') === $slug) {
				return $project;
			}
		}
		return null;
	}

	/**
	 * Render a portfolio card.
	 * @param array<string, mixed> $project
	 */
	function renderProjectCard(array $project): void {
		$slug = htmlspecialchars($project['slug'] ?? '');
		$title = htmlspecialchars($project['title'] ?? 'Untitled');
		$summary = htmlspecialchars($project['summary'] ?? '');
		$image = htmlspecialchars($project['image'] ?? 'assets/images/Screenshot (58).png');
		$tags = $project['tags'] ?? [];
		$projectUrl = 'projects/' . rawurlencode($slug) . '.php';
		?>
		<div class="col-sm-6 col-lg-4 d-flex">
			<a href="<?php echo $projectUrl; ?>" class="card project-card shadow-sm mb-4 text-decoration-none flex-fill reveal">
				<div class="ratio ratio-4x3 overflow-hidden">
					<img src="<?php echo $image; ?>" alt="<?php echo $title; ?>" class="object-fit-cover w-100 h-100">
				</div>
				<div class="card-body">
					<h5 class="card-title mb-2"><?php echo $title; ?></h5>
					<p class="card-text text-muted"><?php echo $summary; ?></p>
					<div class="d-flex flex-wrap gap-2">
						<?php foreach ($tags as $tag): ?>
							<span class="badge rounded-pill bg-light text-primary border"><?php echo htmlspecialchars((string)$tag); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</a>
		</div>
		<?php
	}

	/**
	 * Very light email sender. Returns an array with success boolean and message.
	 * In local dev, PHP mail() may need configuration. As a fallback we log to data/contact_submissions.log
	 * @param array<string, string> $data
	 * @return array{success: bool, message: string}
	 */
	function send_contact_message(array $data): array {
		$name = trim($data['name'] ?? '');
		$email = trim($data['email'] ?? '');
		$message = trim($data['message'] ?? '');

		if ($name === '' || $email === '' || $message === '') {
			return ['success' => false, 'message' => 'Please fill in all required fields.'];
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return ['success' => false, 'message' => 'Please provide a valid email address.'];
		}

		$to = getenv('PORTFOLIO_CONTACT_TO') ?: 'lihpuj12@gmail.com';
		$subject = 'New Portfolio Contact from ' . $name;
		$headers = 'From: ' . $name . ' <' . $to . ">\r\n" . 'Reply-To: ' . $email . ">\r\n" . 'Content-Type: text/plain; charset=utf-8';
		$body = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}\n";

		$sent = false;
		// Attempt to send
		if (function_exists('mail')) {
			$sent = @mail($to, $subject, $body, $headers);
		}

		if ($sent) {
			return ['success' => true, 'message' => 'Thanks! Your message has been sent.'];
		}

		// Fallback to logging locally
		$logDir = dirname(__DIR__) . '/data';
		if (!is_dir($logDir)) {
			@mkdir($logDir, 0775, true);
		}
		$logFile = $logDir . '/contact_submissions.log';
		$logEntry = '[' . date('Y-m-d H:i:s') . "] {$name} <{$email}>: " . str_replace(["\r", "\n"], [' ', ' '], $message) . "\n";
		@file_put_contents($logFile, $logEntry, FILE_APPEND);
		return ['success' => true, 'message' => 'Thanks! Your message has been received.'];
	}

?>


