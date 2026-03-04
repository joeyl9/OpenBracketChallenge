/**
 * Lightweight Modern Tooltip Replacement for wz_tooltip.js
 * Polyfills Tip() and UnTip() to support legacy calls.
 */

(function () {
    // Legacy Polyfill Constants (for wz_tooltip calls)
    window.DELAY = 0;
    window.FADEIN = 0;
    window.FADEOUT = 0;
    window.WIDTH = 0;
    window.BGCOLOR = "";
    window.BORDERCOLOR = "";
    window.FONTCOLOR = "";
    window.FONTSIZE = "";

    // Create Tooltip Element
    const tooltip = document.createElement('div');
    tooltip.id = "modern-tooltip";
    tooltip.style.position = "fixed";
    tooltip.style.display = "none";
    tooltip.style.background = "rgba(15, 23, 42, 0.95)";
    tooltip.style.color = "#fff";
    tooltip.style.padding = "8px 12px";
    tooltip.style.borderRadius = "6px";
    tooltip.style.fontSize = "0.9rem";
    tooltip.style.pointerEvents = "none";
    tooltip.style.zIndex = "10000";
    tooltip.style.border = "1px solid #475569";
    tooltip.style.boxShadow = "0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)";
    // Allow wide tables, scroll if too tall
    tooltip.style.maxWidth = "min(500px, 90vw)";
    tooltip.style.maxHeight = "80vh";
    tooltip.style.overflow = "auto";

    document.body.appendChild(tooltip);

    // Track mouse
    let mouseX = 0;
    let mouseY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;

        if (tooltip.style.display === "block") {
            updatePosition();
        }
    });

    function updatePosition() {
        const offset = 15;
        const viewportWidth = document.documentElement.clientWidth;
        const viewportHeight = document.documentElement.clientHeight;

        const tipWidth = tooltip.offsetWidth;
        const tipHeight = tooltip.offsetHeight;

        // Default: Bottom-Right of cursor
        let left = mouseX + offset;
        let top = mouseY + offset;

        // horizontal flip check
        if (left + tipWidth > viewportWidth) {
            left = mouseX - tipWidth - offset;
        }

        // vertical flip check
        if (top + tipHeight > viewportHeight) {
            top = mouseY - tipHeight - offset;
        }

        // CRITICAL: Hard Clamp to Viewport
        // If flipping pushed it off the left edge, pin it to left 
        if (left < 10) {
            left = 10;
        }
        // If it's still too wide for the right, pin to right
        else if (left + tipWidth > viewportWidth - 10) {
            left = viewportWidth - tipWidth - 10;
        }

        // Hard Clamp Top/Bottom
        if (top < 10) {
            top = 10;
        } else if (top + tipHeight > viewportHeight - 10) {
            top = viewportHeight - tipHeight - 10;
        }

        tooltip.style.left = left + "px";
        tooltip.style.top = top + "px";
    }

    // Global Polyfills
    window.Tip = function (text) {
        // Ignore other legacy arguments (delay, fadein, colors, etc.)
        if (!text) return;

        tooltip.innerHTML = text; // Allow HTML as legacy did
        tooltip.style.display = "block";
        updatePosition();
    };

    window.UnTip = function () {
        tooltip.style.display = "none";
    };

})();
