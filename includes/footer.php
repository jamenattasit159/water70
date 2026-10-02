<?php
// includes/footer.php - Modern SaaS Footer & Global Scripts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
?>

    </main>

    <?php if ($isLoggedIn): ?>
        <footer class="app-footer">
            <div>
                <strong>ระบบบริหารจัดการข้อมูลและสถานการณ์อุทกภัยระดับจังหวัด</strong> &bull; สสจ.อ่างทอง
            </div>
            <div>
                ปีงบประมาณ พ.ศ. 2569 &bull; มาตรฐานระบบสารสนเทศราชการยุคใหม่
            </div>
        </footer>
    </div> <!-- /.app-main-wrapper -->
</div> <!-- /.app-layout -->
<?php else: ?>
    </main>
<?php endif; ?>

<script src="assets/js/app.js"></script>
<script>
// Sidebar mobile toggle
const mobileMenuBtn = document.getElementById('mobile_menu_btn');
const sidebar = document.getElementById('app_sidebar');
const backdrop = document.getElementById('sidebar_backdrop');

if (mobileMenuBtn && sidebar && backdrop) {
    mobileMenuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('active');
    });

    backdrop.addEventListener('click', function() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('active');
    });
}

// User dropdown toggle
const userDropdownWrap = document.getElementById('user_dropdown_wrap');
const userDropdownBtn = document.getElementById('user_dropdown_btn');

if (userDropdownBtn && userDropdownWrap) {
    userDropdownBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdownWrap.classList.toggle('open');
    });

    document.addEventListener('click', function(e) {
        if (!userDropdownWrap.contains(e.target)) {
            userDropdownWrap.classList.remove('open');
        }
    });
}

// Global Theme toggle logic
const globalThemeBtn = document.getElementById('global_theme_toggle');
const globalIconSun = document.getElementById('global_icon_sun');
const globalIconMoon = document.getElementById('global_icon_moon');

function syncGlobalThemeIcons() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (globalIconSun && globalIconMoon) {
        if (isDark) {
            globalIconSun.style.display = 'block';
            globalIconMoon.style.display = 'none';
        } else {
            globalIconSun.style.display = 'none';
            globalIconMoon.style.display = 'block';
        }
    }
}
syncGlobalThemeIcons();

if (globalThemeBtn) {
    globalThemeBtn.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('water_theme', newTheme);
        syncGlobalThemeIcons();
    });
}
</script>
</body>
</html>
