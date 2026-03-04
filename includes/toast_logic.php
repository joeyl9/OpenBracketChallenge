<?php
// Global Message Handling for Toasts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']); // Clear after one use
}
// Check for GET Message
if(isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']);
}

// Display Toast if $msg is set (either from above or passed from local scope)
if(isset($msg) && $msg) { ?>
<div id="toast-notification" style="visibility:hidden; min-width:250px; background-color:var(--bg-secondary); border-left:4px solid var(--accent-orange); color:var(--text-light); border-radius:4px; padding:15px 20px; position:fixed; z-index:10000; right:30px; bottom:30px; box-shadow:0 4px 15px rgba(0,0,0,0.5); font-size:0.95rem; transform:translateY(100px); transition:all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); display:flex; align-items:center; gap:12px;">
    <i class="fa-solid fa-circle-check" style="color:var(--accent-orange); font-size:1.2rem;"></i>
    <span style="font-weight:500; font-family:'Inter', sans-serif;"><?php echo htmlspecialchars($msg); ?></span>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const toast = document.getElementById("toast-notification");
        // Trigger reflow/delay to allow transition
        setTimeout(() => {
            toast.style.visibility = "visible";
            toast.style.transform = "translateY(0)";
        }, 100);
        
        // Hide after 4 seconds
        setTimeout(function(){ 
            toast.style.transform = "translateY(100px)";
            setTimeout(function(){ toast.style.visibility = "hidden"; }, 400); 
        }, 4000); 
    });
</script>
<?php } ?>
