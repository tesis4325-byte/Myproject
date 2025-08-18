    </main>
    <footer class="border-top py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 small text-muted">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(site_name()); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <a href="https://github.com/tesis4325-byte/Myproject" class="text-decoration-none me-3"><i class="bi bi-github"></i> GitHub</a>
                    <a href="https://www.linkedin.com/in/juphil-kadusale-0367b0324/" class="text-decoration-none me-3"><i class="bi bi-linkedin"></i> LinkedIn</a>
                    <a href="mailto:lihpuj12@gmail.com" class="text-decoration-none"><i class="bi bi-envelope"></i> Email</a>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php $script = $_SERVER['SCRIPT_NAME'] ?? ''; $prefix = (strpos($script, '/projects/') !== false) ? '../' : ''; ?>
    <script src="<?php echo $prefix; ?>assets/js/main.js"></script>
  </body>
</html>

