    </main>
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-left">
                <div class="footer-brand">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="20" height="20">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        <path d="M12 5v6"/>
                        <path d="M9 8h6"/>
                    </svg>
                    <strong>MedCare Health Portal</strong>
                </div>
                <p>Comprehensive Patient Record & Care Coordination System &bull; Municipality of Tupi, South Cotabato</p>
            </div>
            <div class="footer-right">
                <p>&copy; <?php echo date('Y'); ?> MedCare. All clinical rights reserved.</p>
                <span class="footer-secure-tag">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    HIPAA / DPA Compliant & Encrypted
                </span>
            </div>
        </div>
    </footer>
<?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
    </div><!-- /#page-content-wrapper -->
</div><!-- /#wrapper -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<?php else: ?>
</div><!-- /#auth-wrapper -->
<?php endif; ?>

<script src="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile sidebar toggle (the menu button in the top bar)
(function () {
    var toggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!toggle) return;
    function setOpen(open) { document.body.classList.toggle('sidebar-open', open); }
    toggle.addEventListener('click', function () { setOpen(!document.body.classList.contains('sidebar-open')); });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
})();
</script>
</body>
</html>
