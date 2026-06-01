// ============================================================
// assets/js/script.js
// JavaScript untuk validasi form, UX, dan session timer
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // --------------------------------------------------------
    // PASSWORD STRENGTH METER
    // Menilai kekuatan password secara real-time
    // --------------------------------------------------------
    const pwInput    = document.getElementById('password');
    const strengthBar  = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    if (pwInput && strengthBar) {
        pwInput.addEventListener('input', function () {
            const val = this.value;
            const score = getStrengthScore(val);
            updateStrengthUI(score);
        });
    }

    /**
     * Menilai kekuatan password dengan beberapa kriteria
     * Return: 0 (sangat lemah) - 5 (sangat kuat)
     */
    function getStrengthScore(pw) {
        let score = 0;
        if (pw.length >= 8)  score++;   // Minimal 8 karakter
        if (pw.length >= 12) score++;   // Bonus: 12+ karakter
        if (/[a-z]/.test(pw)) score++;  // Ada huruf kecil
        if (/[A-Z]/.test(pw)) score++;  // Ada huruf besar
        if (/[0-9]/.test(pw)) score++;  // Ada angka
        if (/[^a-zA-Z0-9]/.test(pw)) score++; // Ada simbol
        return Math.min(score, 5);
    }

    /**
     * Update tampilan strength bar sesuai score
     */
    function updateStrengthUI(score) {
        const levels = [
            { pct: '0%',   color: '#ff4d6d', label: '' },
            { pct: '20%',  color: '#ff4d6d', label: '🔴 Sangat Lemah' },
            { pct: '40%',  color: '#ffd60a', label: '🟡 Lemah' },
            { pct: '60%',  color: '#ffd60a', label: '🟠 Sedang' },
            { pct: '80%',  color: '#00e676', label: '🟢 Kuat' },
            { pct: '100%', color: '#00d4ff', label: '💎 Sangat Kuat' },
        ];
        const lvl = levels[score];
        strengthBar.style.width          = lvl.pct;
        strengthBar.style.backgroundColor = lvl.color;
        if (strengthText) {
            strengthText.textContent   = lvl.label;
            strengthText.style.color   = lvl.color;
        }
    }

    // --------------------------------------------------------
    // TOGGLE VISIBILITY PASSWORD
    // --------------------------------------------------------
    document.querySelectorAll('.toggle-pw').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input    = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type   = 'text';
                this.innerHTML = '👁️‍🗨️';
            } else {
                input.type   = 'password';
                this.innerHTML = '👁️';
            }
        });
    });

    // --------------------------------------------------------
    // VALIDASI FORM REGISTER (Client-side)
    // --------------------------------------------------------
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const pw  = document.getElementById('password').value;
            const cpw = document.getElementById('confirm_password').value;
            const usr = document.getElementById('username').value;

            // Cek username
            if (!/^[a-zA-Z0-9_]{3,50}$/.test(usr)) {
                e.preventDefault();
                showFormError('Username hanya boleh huruf, angka, dan underscore (3-50 karakter).');
                return;
            }

            // Cek panjang password
            if (pw.length < 8) {
                e.preventDefault();
                showFormError('Password minimal 8 karakter.');
                return;
            }

            // Cek kekuatan minimal
            if (!/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[0-9]/.test(pw)) {
                e.preventDefault();
                showFormError('Password harus mengandung huruf besar, huruf kecil, dan angka.');
                return;
            }

            // Cek konfirmasi
            if (pw !== cpw) {
                e.preventDefault();
                showFormError('Konfirmasi password tidak cocok.');
                return;
            }
        });
    }

    function showFormError(msg) {
        let el = document.getElementById('jsError');
        if (!el) {
            el = document.createElement('div');
            el.id = 'jsError';
            el.className = 'alert-crypto';
            const form = document.getElementById('registerForm') || document.getElementById('loginForm');
            if (form) form.prepend(el);
        }
        el.textContent = '⚠️ ' + msg;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // --------------------------------------------------------
    // SESSION COUNTDOWN TIMER
    // Menampilkan sisa waktu session (15 menit)
    // --------------------------------------------------------
    const timerEl = document.getElementById('sessionTimer');
    if (timerEl) {
        let remaining = parseInt(timerEl.dataset.remaining || '900', 10);

        function updateTimer() {
            if (remaining <= 0) {
                timerEl.textContent = 'Session habis!';
                timerEl.style.color = '#ff4d6d';
                setTimeout(() => { window.location.href = '/secure-login/index.php?msg=session_expired'; }, 1000);
                return;
            }

            const m = Math.floor(remaining / 60).toString().padStart(2, '0');
            const s = (remaining % 60).toString().padStart(2, '0');
            timerEl.innerHTML = `<span>${m}:${s}</span>`;

            // Warna merah jika kurang dari 2 menit
            if (remaining < 120) {
                timerEl.style.borderColor = 'rgba(255,77,109,0.4)';
                timerEl.querySelector('span').style.color = '#ff4d6d';
            }

            remaining--;
        }

        updateTimer();
        setInterval(updateTimer, 1000);
    }

    // --------------------------------------------------------
    // MOBILE SIDEBAR TOGGLE
    // --------------------------------------------------------
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar       = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // --------------------------------------------------------
    // CRYPTO DEMO: Generate hash secara client-side
    // Menggunakan Web Crypto API untuk demonstrasi visual
    // --------------------------------------------------------
    const demoForm = document.getElementById('demoForm');
    if (demoForm) {
        demoForm.addEventListener('submit', function (e) {
            e.preventDefault();
            // Form di-submit ke PHP untuk demo server-side (lebih akurat)
            this.submit();
        });
    }

    // --------------------------------------------------------
    // AUTO-HIDE ALERTS setelah 5 detik
    // --------------------------------------------------------
    const alerts = document.querySelectorAll('.alert-auto-hide');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // --------------------------------------------------------
    // ANIMASI COUNTER statistik dashboard
    // --------------------------------------------------------
    document.querySelectorAll('.stat-counter').forEach(el => {
        const target = parseInt(el.dataset.target || '0', 10);
        let current  = 0;
        const step   = Math.ceil(target / 40);
        const timer  = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = current.toLocaleString('id-ID');
        }, 30);
    });

});
