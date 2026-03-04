$(function () {
    // These first three lines of code compensate for Javascript being turned on and off. 
    // It simply changes the submit input field from a type of "submit" to a type of "button".

    var paraTag = $('input#submit').parent('p');
    $(paraTag).children('input').remove();
    $(paraTag).append('<input type="button" name="submit" id="submit" value="Email Us Now!" />');

    $('#main input#submit').click(function () {
        $('#main').append('<img src="images/ajax-loader.gif" class="loaderIcon" alt="Loading..." />');

        var name = $('input#name').val();
        var email = $('input#email').val();
        var comments = $('textarea#comments').val();

        $.ajax({
            type: 'post',
            url: 'sendEmail.php',
            data: 'name=' + name + '&email=' + email + '&comments=' + comments,

            success: function (results) {
                $('#main img.loaderIcon').fadeOut(1000);
                $('ul#response').html(results);
            }
        }); // end ajax
    });
});

// Mobile Menu Logic
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('mobile-menu-close');
    const drawer = document.getElementById('mobile-drawer');
    const backdrop = document.getElementById('mobile-backdrop');
    const body = document.body;

    // Only init if elements exist (mobile mode or markup present)
    if (!btn || !drawer) return;

    // Clone Navigation
    const desktopMenu = document.querySelector('#menu ul');
    const targetContainer = document.getElementById('drawer-nav-container');
    if (desktopMenu && targetContainer && targetContainer.innerHTML === '') {
        const clonedMenu = desktopMenu.cloneNode(true);

        // Filter out items that should not be in mobile drawer (like duplicate Admin)
        const itemsToRemove = clonedMenu.querySelectorAll('.nav-item-admin, .nav-item-account, .menu-toggle');
        itemsToRemove.forEach(el => el.remove());

        targetContainer.appendChild(clonedMenu);
    }

    // Safety Cleanup on Load
    document.documentElement.classList.remove('menu-open');
    document.body.classList.remove('menu-open');

    if (backdrop) backdrop.hidden = true;
    if (drawer) drawer.setAttribute('aria-hidden', 'true');

    function openMenu() {
        // Robust: Add to BOTH html and body
        document.documentElement.classList.add('menu-open');
        document.body.classList.add('menu-open');

        drawer.setAttribute('aria-hidden', 'false');
        btn.setAttribute('aria-expanded', 'true');
        backdrop.hidden = false;
    }

    function closeMenu() {
        // Robust: Remove from BOTH html and body
        document.documentElement.classList.remove('menu-open');
        document.body.classList.remove('menu-open');

        drawer.setAttribute('aria-hidden', 'true');
        btn.setAttribute('aria-expanded', 'false');
        setTimeout(() => {
            if (!document.body.classList.contains('menu-open')) backdrop.hidden = true;
        }, 300); // Match transition duration
    }

    btn.addEventListener('click', openMenu);

    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    if (backdrop) backdrop.addEventListener('click', closeMenu);

    // Auto-Close on Link Click (Delegation)
    drawer.addEventListener('click', function (e) {
        // Handle Links
        if (e.target.tagName === 'A' || e.target.closest('a')) {
            closeMenu();
        }
        // Handle Close Button (Delegation)
        const closeEl = e.target.closest('[data-menu-close]');
        if (closeEl) {
            e.preventDefault();
            closeMenu();
        }
    });

    // ESC Key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.body.classList.contains('menu-open')) {
            closeMenu();
        }
    });
});
