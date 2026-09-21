/**
 * SIMHPSB – Dark Mode Manager
 * Menyimpan preferensi di localStorage dan mendeteksi system preference.
 * Script ini harus di-load SEBELUM render untuk menghindari flash of light mode.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'simhpsb_dark_mode';

    /**
     * Tentukan apakah dark mode harus aktif:
     * 1. Pakai nilai tersimpan jika ada.
     * 2. Fallback ke preferensi sistem (prefers-color-scheme).
     */
    function shouldUseDark() {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved !== null) return saved === 'true';
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /** Terapkan / lepas class "dark" dari <html> */
    function applyTheme(dark) {
        if (dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    /** Toggle dan simpan ke localStorage */
    function toggleDarkMode() {
        var isDark = document.documentElement.classList.contains('dark');
        var next = !isDark;
        applyTheme(next);
        localStorage.setItem(STORAGE_KEY, String(next));

        // Update ikon tooltip jika ada
        var btn = document.getElementById('darkModeToggle');
        if (btn) {
            btn.setAttribute('title', next ? 'Mode Terang' : 'Mode Gelap');
        }
    }

    /** Inisialisasi: jalankan segera agar tidak flicker */
    applyTheme(shouldUseDark());

    /** Setelah DOM siap: pasang listener tombol toggle */
    function onDOMReady() {
        var btn = document.getElementById('darkModeToggle');
        if (btn) {
            var isDark = document.documentElement.classList.contains('dark');
            btn.setAttribute('title', isDark ? 'Mode Terang' : 'Mode Gelap');
            btn.addEventListener('click', toggleDarkMode);
        }

        // Ikuti perubahan system preference (misal: OS berubah ke dark saat app terbuka),
        // hanya jika pengguna belum pernah memilih manual.
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                if (localStorage.getItem(STORAGE_KEY) === null) {
                    applyTheme(e.matches);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onDOMReady);
    } else {
        onDOMReady();
    }
})();
