// Dark Mode Toggle Functionality
(function(){
    const STORAGE_KEY = 'simhpsb_dark_mode';
    const btn = document.getElementById('darkModeToggle');
    
    if (!btn) return;
    
    // Set initial title
    const isDark = document.documentElement.classList.contains('dark');
    btn.setAttribute('title', isDark ? 'Mode Terang' : 'Mode Gelap');
    
    // Toggle dark mode on click
    btn.addEventListener('click', function(){
        const html = document.documentElement;
        const nextDark = !html.classList.contains('dark');
        
        if(nextDark) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
        
        // Save preference
        localStorage.setItem(STORAGE_KEY, String(nextDark));
        
        // Update button title
        btn.setAttribute('title', nextDark ? 'Mode Terang' : 'Mode Gelap');
    });
})();
