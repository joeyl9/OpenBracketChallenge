# Bracket Challenge

This project started as a fork of the classic [Matt Felser Tourney project](http://sourceforge.net/projects/tourney) from the late 2000s. The original tournament flow is still there, but a lot of the surrounding code has been revised to address older PHP patterns, tighten up security, modernize the interface, and support additional features. The current codebase should be treated as its own project rather than a drop-in copy of the original release.

## Major changes

If you're familiar with the original `tourney` release, these are the main changes in the current codebase:

* **Database**: The deprecated `mysql_*` calls were removed. Database access now uses PDO with prepared statements.
* **Security cleanup**: Older authentication checks were replaced with standard session-based handling, and `htmlspecialchars` escaping was added in places where user-controlled content is displayed, including bracket names and comments.
* **Frontend UI**: Legacy table-based layout was replaced with a more modern responsive layout built with CSS Grid and Flexbox. The site is more mobile-friendly and includes a Theme Manager allowing for custom color schemes.
* **Bracket entry flow**: The old dropdown-based bracket form was replaced with an interactive JavaScript-driven picker that advances selections visually and helps prevent invalid picks.
* **Admin/setup improvements**: The admin area has been cleaned up, and a setup wizard was added for initial configuration.

## Tech stack

* PHP 8+
* MySQL / MariaDB
* Vanilla HTML / CSS / JavaScript

## Requirements

You will need a web server (Apache, Nginx, or IIS) with PHP 8+ and access to a MySQL or MariaDB database. The web server process should also have write access to any temporary directories used for sessions, generated files, or PDF-related output.

## Setup

1. Clone or copy this repository into a directory served by your web server.
2. Create a new MySQL/MariaDB database and a user with privileges for it.
3. Open `admin/install.php` in your browser.
4. Follow the setup wizard to configure the database connection and complete the initial setup.

If you are not using the wizard, you can manually copy `admin/database.php.tmpl` to `admin/database.php` and configure it directly.

## New Features

* **Install Wizard**: The `install.php` setup flow is newer than the older manual configuration path.
* **Admin Roles**: Registered accounts can be made administrators with different permission levels:
    * **Super Admin**: Full control. Can manage all settings, edit users, delete participants, and update site structure.
    * **Limited Admin**: Standard day-to-day management. Can edit brackets and view data. Cannot delete other administrators.
    * **Pay Editor**: Restricted access. Can only update payment status for participants. Ideal for a treasurer.
* **Registration Locks**: Control who can register and create a bracket:
    * **Open**: Anyone can register.
    * **Password Restricted**: Must enter a specific password to register.
    * **Link Only**: Users need a unique URL to access the form.
* **Advanced Payouts**: Control payout structures allowing 1st, 2nd, and 3rd place finishes to claim a cut of the pot. Added the ability to refund the last place finisher (taken from the pot first).
* **QR Code Payments**: Admins can upload a QR Code to make it easier to collect payments from participants.
* **PDF Export**: Print-friendly bracket output is included and can be generated before the brackets are closed.

## Features still being validated

Several newer features are present in the codebase but are still being validated:

* **Live Scoring**: An experimental script (`admin/fetch_live.php`) is included for pulling score updates. This has not been fully tested yet.
* **PWA Support**: Service worker and manifest support are included, but still need broader testing across devices and browsers.
* **Gamification**: Badge-related functionality is present, but should still be treated as in validation.
* **Historical Tracking**: Archive/Hall of Fame-style functionality exists for tracking past winners.
* **Deadline Cutoff**: Added a deadline cutoff for submissions.

## Project structure

* `admin/` - Admin dashboard, user management, setup tools, and maintenance scripts
* `api/` - JSON endpoints used by frontend interactions
* `css/`, `js/`, `images/` - Frontend assets
* `includes/` - Shared PHP helpers and reusable components

---

**Disclaimer**

This software is an independent open-source project and is not affiliated with, endorsed by, or sponsored by the NCAA. Terms such as "March Madness," "The Big Dance," "Final Four," "Elite Eight," and "Sweet Sixteen" are associated with NCAA trademarks. This application is intended as a generic bracket management tool. Any default data resembling real-world entities is included for demonstration purposes only.

