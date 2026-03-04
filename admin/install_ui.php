<?php
// Secure Installation Upgrade
require_once("functions.php");
if(file_exists("install.lock")) {
    $db_locked = true;
}

if($db_locked) {
	// Already installed
	header("Location: ../admin/login.php");
	exit("System already installed. Please remove database configuration to reinstall.");
}

$default_deadline = date('Y-m-d 12:00:00', strtotime('next Thursday'));
?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

<head>
	<title>Bracket Challenge Installation</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="Content-Language" content="en-us" />
	<meta name="author" content="Bracket Challenge Team" />
	<style type="text/css" media="all">
		@import "../images/style.css";
	</style>
	<!-- Flatpickr (Local) -->
	<link rel="stylesheet" href="../css/flatpickr.min.css">
	<link rel="stylesheet" href="../css/flatpickr-dark.css">
	<script src="../js/flatpickr.min.js"></script>

	<style>
		:root {
			--primary-blue: #0f172a;
			/* Dark Background */
			--secondary-blue: #1e293b;
			/* Lighter Component Background */
			--accent-orange: #f97316;
			/* Orange Accent */
			--text-light: #ffffff;
			--text-muted: #94a3b8;
			--border-color: rgba(255, 255, 255, 0.1);
			--accent-text: #ffffff;
		}

		/* Flatpickr Theme Overrides to match Admin Theme */
		.flatpickr-calendar {
			background: var(--primary-blue, #111) !important;
			border: 1px solid var(--border-color, #444) !important;
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
		}

		/* Main Headers (Month/Year & Weekdays) */
		.flatpickr-months .flatpickr-month,
		.flatpickr-weekdays,
		.flatpickr-weekday,
		.flatpickr-current-month .flatpickr-monthDropdown-months {
			background: var(--primary-blue, #111) !important;
			color: var(--text-light, #fff) !important;
			fill: var(--text-light, #fff) !important;
		}

		.flatpickr-day {
			color: var(--text-light, #fff) !important;
		}

		.flatpickr-day.prevMonthDay,
		.flatpickr-day.nextMonthDay {
			color: var(--text-muted, #ccc) !important;
			opacity: 0.5;
		}

		.flatpickr-day:hover,
		.flatpickr-day:focus {
			background: rgba(255, 255, 255, 0.1) !important;
			border-color: rgba(255, 255, 255, 0.1) !important;
			color: var(--text-light, #fff) !important;
		}

		.flatpickr-day.selected,
		.flatpickr-day.startRange,
		.flatpickr-day.endRange,
		.flatpickr-day.selected:hover,
		.flatpickr-day.selected:focus {
			background: var(--accent-orange, #f59e0b) !important;
			border-color: var(--accent-orange, #f59e0b) !important;
			color: var(--accent-text, #fff) !important;
		}

		.flatpickr-time {
			background: var(--primary-blue, #111) !important;
			border-top: 1px solid var(--border-color, #444) !important;
		}

		.flatpickr-time input:hover,
		.flatpickr-time .flatpickr-am-pm:hover,
		.flatpickr-time input:focus,
		.flatpickr-time .flatpickr-am-pm:focus {
			background: rgba(255, 255, 255, 0.1) !important;
			color: var(--text-light, #fff) !important;
		}

		.flatpickr-time .numInputWrapper span.arrowUp:after {
			border-bottom-color: var(--text-light, #fff) !important;
		}

		.flatpickr-time .numInputWrapper span.arrowDown:after {
			border-top-color: var(--text-light, #fff) !important;
		}

		/* Ensure today matches theme if not selected */
		.flatpickr-day.today {
			border-color: var(--accent-orange, #f59e0b) !important;
			color: var(--text-light, #fff) !important;
			/* Ensure readable */
		}

		.flatpickr-day.today:hover {
			background: rgba(255, 255, 255, 0.1) !important;
			color: var(--text-light, #fff) !important;
		}

		/* Dropdown arrow fix */
		.flatpickr-current-month .flatpickr-monthDropdown-months .flatpickr-monthDropdown-month {
			background-color: var(--primary-blue, #111) !important;
			color: var(--text-light) !important;
		}

		/* Navigation Arrows - Month */
		.flatpickr-months .flatpickr-prev-month svg,
		.flatpickr-months .flatpickr-next-month svg {
			fill: var(--text-light, #fff) !important;
		}

		.flatpickr-months .flatpickr-prev-month:hover svg,
		.flatpickr-months .flatpickr-next-month:hover svg {
			fill: var(--accent-orange, #f59e0b) !important;
		}

		/* Time Separator */
		.flatpickr-time .flatpickr-time-separator {
			color: var(--text-light, #fff) !important;
		}
	</style>

<body>
	<script type="text/javascript">
		function copySeeds() {
			for (var j = 2; j < 17; j++) {

				for (var i = 1; i < 7; i++) {
					var source = document.getElementsByName("1-" + i + "_scoring")[0];
					var target = document.getElementsByName(j + "-" + i + "_scoring")[0];

					target.value = source.value;
				}
			}
		}
	</script>
	<div class="content">
		<div id="top">
			<!-- Updated Copyright -->
			<div class="rightlinks">| <a href="#"><span class="info">Copyright &copy; <?php echo date('Y'); ?> Bracket Challenge</span></a></div>
		</div>
		<div id="header">

			<div class="info">
				<h1>Bracket Challenge</h1>
				<h2>Set Up Your New Site</h2>
			</div>
		</div>

		<div id="subheader">
			<div id="menu">
				<ul>
					<li><a href="../index.php">HOME</a></li>
					<li><a href="../submit.php">SUBMIT</a></li>
					<li><a href="../rules.php">RULES</a></li>
					<li><a href="../standings.php">STANDINGS</a></li>
					<li><a href="../admin/">ADMIN AREA</a></li>
				</ul>
			</div>
		</div>
		<div id="main">
			<div class="full">
				<ol>
					<li>Rename admin/database.php.tmpl to admin/database.php</li>
					<li>Edit database.php to include the proper database configuration</li>
					<li>Fill out the form below and click finish! You are done!</li>
				</ol>
				<form method="post" action="install.php">
					<table border="1" cellpadding="3">
						<tr>
							<td>Site Title</td>
							<td><input type="text" name="title" /></td>
						</tr>
						<tr>
							<td>Site Subtitle</td>
							<td><input type="text" name="subtitle" /></td>
						</tr>
						<tr>
							<td>Administrator Name</td>
							<td><input type="text" name="adminname" /></td>
						</tr>
						<tr>
							<td>Administrator Password</td>
							<td><input type="password" name="password" /></td>
						</tr>
						<tr>
							<td>Administrator Email</td>
							<td><input type="text" name="email" /></td>
						</tr>
						<tr>
							<td>Entry Fee (in $)</td>
							<td><input type="text" name="cost" /></td>
						</tr>
						<tr>
							<td>Cut Type</td>
							<td>
								<input type="radio" name="cutType" value="1">
								In Percent
								<input type="radio" name="cutType" value="0">
								In Dollars
							</td>
						</tr>
						<tr>
							<td>Cut Amount (in $ or %)</td>
							<td><input type="text" name="cut" /></td>
						</tr>
						<tr>
							<td>Second Chance Tournament? (Must fill in first 2 rounds in master bracket)</td>
							<td><input type="checkbox" name="sweet16Competition" /></td>
						</tr>
						<tr>
							<td>Region 1 Name (plays Region 2)</td>
							<td><input type="text" name="region1name" /></td>
						</tr>
						<tr>
							<td>Region 2 Name (plays Region 1)</td>
							<td><input type="text" name="region2name" /></td>
						</tr>
						<tr>
							<td>Region 3 Name (plays Region 4)</td>
							<td><input type="text" name="region3name" /></td>
						</tr>
						<tr>
							<td>Region 4 Name (plays Region 3)</td>
							<td><input type="text" name="region4name" /></td>
						</tr>
						<tr>
							<td>Submission Deadline (YYYY-MM-DD HH:MM:SS)</td>
							<td><input type="text" name="deadline" class="flatpickr"
									value="<?php echo $default_deadline; ?>" /></td>
						</tr>

						<tr>
							<td>Scoring</td>
							<td>
								<a href="javascript:copySeeds()">Copy first row to all seeds</a>
								<table>
									<tr align="center">
										<td>Seed</td>
										<td>R1</td>
										<td>R2</td>
										<td>R3</td>
										<td>R4</td>
										<td>R5</td>
										<td>R6</td>
									</tr>
									<?php for($i=1; $i<=16; $i++) { ?>
									<tr>
										<td><?php echo $i; ?></td>
										<td><input type="text" name="<?php echo $i; ?>-1_scoring" size="3" /></td>
										<td><input type="text" name="<?php echo $i; ?>-2_scoring" size="3" /></td>
										<td><input type="text" name="<?php echo $i; ?>-3_scoring" size="3" /></td>
										<td><input type="text" name="<?php echo $i; ?>-4_scoring" size="3" /></td>
										<td><input type="text" name="<?php echo $i; ?>-5_scoring" size="3" /></td>
										<td><input type="text" name="<?php echo $i; ?>-6_scoring" size="3" /></td>
									</tr>
									<?php } ?>

								</table>
							</td>
						</tr>
						<tr>
							<td>Do you want notification emails sent to yourself and entrants?<br />
								(Uncheck if you have no mailserver)</td>
							<td><input type="checkbox" name="mail" checked /></td>
						</tr>
						<tr>
							<td>
								<strong>Clean Installation</strong><br>
								Installing database for Version <?php echo defined('APP_VERSION') ? APP_VERSION : '1.0'; ?>. 
								<br><span style="color:red;">Warning: This will overwrite any existing tournament usage data.</span>
							</td>
							<td><input type="checkbox" name="overwrite_db" value="1" /> (Confirm Overwrite)</td>
						</tr>
						<tr>
							<td colspan="2"><input type="submit" value="Finish!" /></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
	<script>
		flatpickr(".flatpickr", {
			enableTime: true,
			dateFormat: "Y-m-d H:i:S",
			theme: "dark"
		});
	</script>
</body>

</html>

