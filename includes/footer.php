    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand">
                        <span class="brand-icon"><i class="fas fa-globe"></i></span>
                        <span class="brand-text"><?php echo APP_NAME; ?></span>
                    </div>
                    <p class="footer-desc">Connecting businesses with top remote talent worldwide. Build your dream team today.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>For Clients</h4>
                    <ul>
                        <li><a href="<?php echo baseUrl('client'); ?>">How to Hire</a></li>
                        <li><a href="<?php echo baseUrl('client/talent'); ?>">Browse Talent</a></li>
                        <li><a href="<?php echo baseUrl('client'); ?>">Post a Job</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>For Freelancers</h4>
                    <ul>
                        <li><a href="<?php echo baseUrl('remoworkers-dashboard'); ?>">Find Work</a></li>
                        <li><a href="<?php echo baseUrl('remoworkers-dashboard/profile'); ?>">Create Profile</a></li>
                        <li><a href="<?php echo baseUrl('remoworkers-dashboard'); ?>">Browse Jobs</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Main JS -->
    <script src="<?php echo baseUrl('assets/js/main.js'); ?>"></script>
    
    <?php if (isset($extraJs)): ?>
        <?php foreach ($extraJs as $js): ?>
            <script src="<?php echo baseUrl($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
