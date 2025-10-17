/**
 * Cloudflare Turnstile Front-end Script
 *
 * @author    blauwfruit
 * @copyright 2025 blauwfruit
 * @license   MIT License
 */
(function() {
    function initTurnstile() {
        // Get site key from window variable set by PHP
        var siteKey = window.turnstileSiteKey || '';
        if (!siteKey) {
            return;
        }

        // Find all forms
        var forms = document.querySelectorAll('form');
        
        forms.forEach(function(form) {
            // Skip if already processed
            if (form.hasAttribute('data-turnstile-processed')) {
                return;
            }
            form.setAttribute('data-turnstile-processed', 'true');
            
            // Find any submit button or input
            var submitButtons = form.querySelectorAll(
                'input[type="submit"], button[type="submit"], button:not([type]), ' +
                'input[name^="submit"], button[name^="submit"]'
            );
            
            // If no explicit submit buttons, check for any button (defaults to submit)
            if (submitButtons.length === 0) {
                var buttons = form.querySelectorAll('button');
                if (buttons.length > 0) {
                    submitButtons = buttons;
                }
            }
            
            // Skip forms without any buttons/inputs that could submit
            if (submitButtons.length === 0) {
                return;
            }
        
            
            // Find insertion point - prefer before footer, then before submit button
            var footer = form.querySelector('footer, .form-footer, .card-footer');
            var insertBefore = footer || submitButtons[0];
            
            // Create Turnstile widget (will auto-render)
            var turnstileDiv = document.createElement('div');
            turnstileDiv.className = 'cf-turnstile';
            turnstileDiv.setAttribute('data-sitekey', siteKey);
            turnstileDiv.setAttribute('data-theme', 'light');
            turnstileDiv.style.marginBottom = '15px';
            turnstileDiv.style.marginTop = '15px';
            
            // Insert before the target element
            insertBefore.parentNode.insertBefore(turnstileDiv, insertBefore);
            
        });
    }
    
    // Wait for Turnstile API to load before initializing
    function waitForTurnstile() {
        if (window.turnstile) {
            initTurnstile();
        } else {
            // Check every 100ms for up to 5 seconds
            var attempts = 0;
            var maxAttempts = 50;
            var checkInterval = setInterval(function() {
                attempts++;
                if (window.turnstile) {
                    clearInterval(checkInterval);
                    initTurnstile();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    // Try anyway - Turnstile might render automatically
                    initTurnstile();
                }
            }, 100);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForTurnstile);
    } else {
        waitForTurnstile();
    }
    
    // Re-run on AJAX updates
    document.addEventListener('updatedCheckoutStep', function() {
        setTimeout(initTurnstile, 100);
    });
    
    // Watch for dynamically added forms
    var observer = new MutationObserver(function(mutations) {
        clearTimeout(observer.timeout);
        observer.timeout = setTimeout(initTurnstile, 200);
    });
    
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();
