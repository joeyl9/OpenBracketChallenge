<!-- Generic Confirmation Modal -->
<div id="confirm-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:10002; backdrop-filter:blur(4px); justify-content:center; align-items:center;">
    <div style="background:var(--bg-secondary); width:450px; border-radius:12px; border:1px solid var(--border-color); box-shadow:0 25px 50px -12px rgba(0,0,0,0.5); overflow:hidden; animation:modalPop 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        
        <!-- Header -->
        <div style="padding:20px; border-bottom:1px solid var(--border-color); background:rgba(255,255,255,0.03);">
            <h3 id="confirm-title" style="margin:0; font-size:1.2rem; color:var(--text-light); font-weight:700;">Confirm Action</h3>
        </div>
        
        <!-- Body -->
        <div style="padding:25px; color:var(--text-muted); line-height:1.6; font-size:1rem;" id="confirm-message">
            Are you sure you want to proceed?
        </div>
        
        <!-- Footer -->
        <div style="padding:20px; background:rgba(0,0,0,0.2); display:flex; justify-content:flex-end; gap:12px; border-top:1px solid var(--border-color);">
            <button onclick="closeConfirm()" style="padding:10px 20px; background:transparent; border:1px solid var(--border-color); color:var(--text-muted); border-radius:6px; cursor:pointer; font-weight:600; transition:all 0.2s;">
                Cancel
            </button>
            <button id="confirm-btn-yes" style="padding:10px 20px; background:var(--accent-orange); border:none; color:white; border-radius:6px; cursor:pointer; font-weight:600; box-shadow:0 4px 6px rgba(0,0,0,0.1); transition:all 0.2s;">
                Confirm
            </button>
        </div>
    </div>
</div>

<style>
@keyframes modalPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<script>
let confirmCallback = null;

function showConfirm(title, message, callback) {
    document.getElementById('confirm-title').innerText = title;
    // Replace newlines with <br> for formatting
    document.getElementById('confirm-message').innerHTML = message.replace(/\n/g, '<br>');
    
    confirmCallback = callback;
    
    // Show Modal
    const modal = document.getElementById('confirm-modal');
    modal.style.display = 'flex';
}

function closeConfirm() {
    document.getElementById('confirm-modal').style.display = 'none';
    confirmCallback = null;
}

document.getElementById('confirm-btn-yes').addEventListener('click', function() {
    if(confirmCallback) confirmCallback();
    closeConfirm();
});
</script>
