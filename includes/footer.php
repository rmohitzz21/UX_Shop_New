    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="img/1.png" alt="UX PACIFIC" class="logo" style="height:36px; margin: 0 auto; margin-bottom: 20px; display:block;" onerror="this.outerHTML='<span style=\'font-weight:700; font-size: 1.5rem; letter-spacing: -0.5px; display:flex; justify-content:center; gap:8px;\'><i class=\'ph ph-squares-four\'></i>UX PACIFIC</span>'" />
            </div>
            <nav class="footer-nav">
                <a href="https://academy.uxpacific.com/" target="_blank" rel="noopener">UXP Academy</a> &bull;
                <a href="https://uxpacific.com" target="_blank" rel="noopener">UX Pacific</a> &bull;
                <a href="https://community.uxpacific.com/" target="_blank" rel="noopener">UXP Community</a>
            </nav>
            <div class="social-links">
                <a href="https://www.linkedin.com/company/uxpacific/" class="social-icon" target="_blank" rel="noopener"><img src="img/in1.png" alt="LinkedIn" /></a>
                <a href="https://www.instagram.com/official_uxpacific/" class="social-icon" target="_blank" rel="noopener"><img src="img/i.webp" alt="Instagram" /></a>
                <a href="https://www.behance.net/ux_pacific" class="social-icon" target="_blank" rel="noopener"><img src="img/be.webp" alt="Behance" /></a>
                <a href="https://in.pinterest.com/uxpacific/" class="social-icon" target="_blank" rel="noopener"><img src="img/p.webp" alt="Pinterest" /></a>
                <a href="https://dribbble.com/social-ux-pacific" class="social-icon" target="_blank" rel="noopener"><img src="img/bl.webp" alt="Dribbble" /></a>
                <a href="https://medium.com/@uxpacific" class="social-icon" target="_blank" rel="noopener"><img src="img/medium.png" alt="Medium" /></a>
            </div>
            <p class="contact-info">+91 9274061063 <span style="margin: 0 10px;">|</span> <?php echo htmlspecialchars(getenv('SUPPORT_EMAIL') ?: 'support@uxpacific.com'); ?></p>
            <p class="address">512, Majestic Building, Near Law Garden BRTS Stand, Ahmedabad</p>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> UX Pacific. All rights reserved.</p>
            <div class="legal-links">
                <a href="policies.php">Our Policies</a> |
                <a href="contact.php">Contact Us</a>
                <!-- <a href="policies.php">Cookie Policy</a> |
                <a href="policies.php">Terms and Condition</a> -->
        
            </div>
        </div>
    </footer>
