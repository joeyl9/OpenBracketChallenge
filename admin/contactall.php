<?php
include("functions.php");
validatecookie();
include("header.php");



?>

		

		<div id="main">

			<div class="full">
			<div id="container">
				<h2>Contact the participants </h2>
                <div style="margin-bottom: 20px;">
                    <a href="index.php" class="btn-outline">&larr; Back to Dashboard</a>
                </div>
			<form method="post" action="sendEmailUsers.php">

					<p><small>Subject:</small> <input type="text" name="subject" id="subject" /></p>
					<p><small>Content:</small> <textarea name="body" id="body" rows="15"></textarea></p>	
					<p><input type="submit" name="sendtoall" id="sendtoall" value="Send Email" /></p>
					<ul id="response" />
			</div>
			</form>
			</div>

			

			

		</div>

		



	</div>

<?php include('footer.php'); ?>
</body>
</html>


