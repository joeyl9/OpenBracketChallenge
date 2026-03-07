<?php
// admin/themes.php
require_once 'database.php';
require_once 'functions.php';
validatecookie();
check_admin_auth('super');

$error = "";
$success = "";

// ------------------------------------
// Handle Create/Update
// ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'edit') {
        $name     = trim($_POST['name']);
        
        // Handle Group Name (Select vs New Input)
        $groupSelect = $_POST['group_select'] ?? '';
        $groupNew    = trim($_POST['group_new'] ?? '');
        $group       = ($groupSelect === 'NEW' && !empty($groupNew)) ? $groupNew : $groupSelect;
        // Fallback if they selected NEW but left text empty
        if (empty($group)) $group = 'Uncategorized';

        $accent   = $_POST['accent'];
        $header1  = $_POST['header1'];
        $header2  = $_POST['header2'];
        $bg1      = $_POST['bg1'];
        $bg2      = $_POST['bg2'];

        if ($action === 'create') {
            // Auto-generate key from name
            $key = strtolower(preg_replace('/[^a-z0-9_]/', '_', $name));
            $key = trim($key, '_');
            if (empty($key)) $key = 'theme_' . time();
            
            // Check for duplicate key, append number if exists
            $baseKey = $key;
            $counter = 1;
            while (true) {
                $check = $db->prepare("SELECT id FROM themes WHERE theme_key = ?");
                $check->execute([$key]);
                if ($check->rowCount() == 0) break;
                $key = $baseKey . '_' . $counter;
                $counter++;
            }

            if (empty($name)) {
                $error = "Name is required.";
            } else {
                $sql = "INSERT INTO themes (theme_key, name, group_name, accent, header1, header2, bg1, bg2) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                if ($stmt->execute([$key, $name, $group, $accent, $header1, $header2, $bg1, $bg2])) {
                    $success = "Theme '$name' created successfully!";
                } else {
                    $error = "Failed to create theme.";
                }
            }
        } elseif ($action === 'edit') {
             $orig_key = $_POST['original_theme_key'];
             
             $sql = "UPDATE themes SET name=?, group_name=?, accent=?, header1=?, header2=?, bg1=?, bg2=? WHERE theme_key=?";
             $stmt = $db->prepare($sql);
             if ($stmt->execute([$name, $group, $accent, $header1, $header2, $bg1, $bg2, $orig_key])) {
                 $success = "Theme updated.";
             } else {
                 $error = "Update failed.";
             }
        }
    }
    
    if ($action === 'delete') {
        $del_key = $_POST['delete_key'];
        if ($del_key === 'default') {
            $error = "Cannot delete the default theme.";
        } else {
            $stmt = $db->prepare("DELETE FROM themes WHERE theme_key = ?");
            if ($stmt->execute([$del_key])) {
                $success = "Theme deleted.";
            } else {
                $error = "Delete failed.";
            }
        }
    }
}

// ------------------------------------
// Fetch Data
// ------------------------------------
$themeList = getThemes(); // Now pulls from DB

// Group for display
$grouped = [];
foreach ($themeList as $k => $t) {
    // Ensure we capture all groups for the dropdown later
    $g = $t['group'] ?: 'Misc';
    $grouped[$g][$k] = $t;
}
ksort($grouped);
// Get flat list of groups for the dropdown
$allGroups = array_keys($grouped);

include('header.php');
?>

<style>
    /* CSS Grid Layout */
    .theme-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .theme-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .theme-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        border-color: var(--accent-orange);
    }

    .theme-card h4 {
        margin-top: 0;
        margin-bottom: 5px;
        color: var(--text-light);
        font-size: 1.2rem;
    }

    .theme-key {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-family: monospace;
        margin-bottom: 15px;
        display: block;
        opacity: 0.7;
    }

    /* Preview Box */
    .mini-preview {
        height: 60px;
        border-radius: 6px;
        margin-bottom: 15px;
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
    }

    .preview-header {
        height: 100%;
        display: flex; 
        align-items: center; 
        justify-content: center;
        font-weight: bold;
        color: white; 
        font-size: 1.2rem;
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    }

    /* Swatches */
    .swatches {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--border-color);
    }

    .color-swatch {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid var(--border-color);
        cursor: help;
        transition: transform 0.2s;
    }
    
    .color-swatch:hover {
        transform: scale(1.2);
        border-color: white;
    }

    /* Actions */
    .card-actions {
        margin-top: auto;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    /* Buttons */
    .btn-custom {
        padding: 6px 12px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        transition: background 0.2s;
        color: #fff;
    }

    .btn-primary {
        background: var(--accent-orange);
        color: var(--accent-text, #fff);
    }
    .btn-primary:hover {
        background: var(--accent-orange-hover);
    }

    .btn-danger {
        background: rgba(220, 38, 38, 0.2);
        color: #fca5a5;
        border: 1px solid #991b1b;
    }
    .btn-danger:hover {
        background: #991b1b;
        color: #fff;
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-light);
    }
    .btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    /* Custom Modal */
    .custom-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(2px);
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .custom-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.95);
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        z-index: 1001;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        max-height: 90vh;
    }

    .custom-modal-overlay.open {
        display: block;
        opacity: 1;
    }

    .custom-modal.open {
        opacity: 1;
        pointer-events: auto;
        transform: translate(-50%, -50%) scale(1);
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--text-light);
    }

    .modal-close {
        background: transparent;
        border: none;
        color: var(--text-muted);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    .modal-close:hover { color: var(--text-light); }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
    }

    .modal-footer {
        padding: 20px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: rgba(0,0,0,0.1);
    }

    /* Form Styles */
    .form-group { margin-bottom: 15px; }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: var(--text-light);
        font-size: 0.9rem;
    }

    .form-input {
        width: 100%;
        padding: 10px;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        color: var(--text-light);
        font-size: 1rem;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: var(--accent-orange);
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.2);
    }
    
    .row-split {
        display: flex;
        gap: 15px;
    }
    .col-half {
        flex: 1;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px 0;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 15px;
    }
    
    .page-header h1 {
        margin: 0;
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<div class="page-header">
    <h1><i class="fa-solid fa-palette" style="color:var(--accent-orange);"></i> Theme Manager</h1>
    <div>
        <a href="index.php" class="btn-outline" style="margin-right:10px;">&larr; Back to Dashboard</a>
        <button class="btn-custom btn-primary" onclick="openModal('create')"><i class="fa fa-plus"></i> New Theme</button>
    </div>
</div>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php foreach ($grouped as $groupName => $themes): ?>
    <h3 style="color: var(--accent-orange); border-bottom: 1px solid var(--border-color); padding-bottom: 5px; margin-top: 30px;"><?php echo htmlspecialchars($groupName); ?></h3>
    <div class="theme-grid">
        <?php foreach ($themes as $key => $t): ?>
            <div class="theme-card">
                <div class="mini-preview" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($t['header1']); ?> 0%, <?php echo htmlspecialchars($t['header2']); ?> 100%); border-bottom: 3px solid <?php echo htmlspecialchars($t['accent']); ?>;">
                    <div class="preview-header">Title</div>
                </div>
            
                <h4><?php echo htmlspecialchars($t['name']); ?></h4>
                <span class="theme-key"><?php echo htmlspecialchars($key); ?></span>

                <div class="swatches">
                    <div title="Accent: <?php echo htmlspecialchars($t['accent']); ?>" class="color-swatch" style="background: <?php echo htmlspecialchars($t['accent']); ?>;"></div>
                    <div title="H1: <?php echo htmlspecialchars($t['header1']); ?>" class="color-swatch" style="background: <?php echo htmlspecialchars($t['header1']); ?>;"></div>
                    <div title="H2: <?php echo htmlspecialchars($t['header2']); ?>" class="color-swatch" style="background: <?php echo htmlspecialchars($t['header2']); ?>;"></div>
                    <div title="BG1: <?php echo htmlspecialchars($t['bg1']); ?>" class="color-swatch" style="background: <?php echo htmlspecialchars($t['bg1']); ?>;"></div>
                    <div title="BG2: <?php echo htmlspecialchars($t['bg2']); ?>" class="color-swatch" style="background: <?php echo htmlspecialchars($t['bg2']); ?>;"></div>
                </div>

                <div class="card-actions">
                    <button class="btn-custom btn-secondary" onclick='editTheme(<?php echo htmlspecialchars(json_encode(array_merge(['key' => $key], $t)), ENT_QUOTES, 'UTF-8'); ?>)'>Edit</button>
                    <button class="btn-custom btn-secondary" onclick='duplicateTheme(<?php echo htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8'); ?>)'>Duplicate</button>
                    <?php if($key !== 'default'): ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this theme?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="delete_key" value="<?php echo $key; ?>">
                            <button class="btn-custom btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<!-- Custom Modal -->
<div class="custom-modal-overlay" id="modalOverlay" onclick="closeModal(event)"></div>
<div class="custom-modal" id="themeModal">
    <form method="post">
        <?php csrf_field(); ?>
        <div class="modal-header">
            <h3 id="modalTitle">Create Theme</h3>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="original_theme_key" id="originalThemeKey">
            
            <div class="form-group">
                <label>Group Name</label>
                <select class="form-input" name="group_select" id="groupSelect" onchange="toggleGroupInput()" required>
                    <?php foreach($allGroups as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>"><?php echo htmlspecialchars($g); ?></option>
                    <?php endforeach; ?>
                    <option value="NEW">+ Create New Group...</option>
                </select>
                <input type="text" class="form-input" name="group_new" id="groupNew" placeholder="Enter new group name" style="display:none; margin-top: 10px;">
            </div>

            <div class="form-group">
                <label>Theme Name</label>
                <input type="text" class="form-input" name="name" id="themeName" required placeholder="e.g. My Cool Theme">
            </div>
            
            <div class="form-group" id="keyContainer" style="display:none;">
                <label>Theme Key</label>
                <input type="text" class="form-input" id="themeKeyDisplay" readonly disabled> 
                <small style="color:var(--text-muted);">Unique ID (Auto-generated)</small>
            </div>

            <div class="row-split">
                <div class="col-half">
                    <div class="form-group">
                        <label>Accent Color</label>
                        <input type="color" class="form-input" name="accent" id="accentColor" style="height: 45px; padding: 2px;">
                    </div>
                </div>
                 <div class="col-half">
                    <div class="form-group">
                        <label>Header Start (Top)</label>
                        <input type="color" class="form-input" name="header1" id="h1Color" style="height: 45px; padding: 2px;">
                    </div>
                </div>
            </div>
            
            <div class="row-split">
                <div class="col-half">
                    <div class="form-group">
                        <label>Header End (Bottom)</label>
                        <input type="color" class="form-input" name="header2" id="h2Color" style="height: 45px; padding: 2px;">
                    </div>
                </div>
                <div class="col-half">
                    <div class="form-group">
                        <label>Background Top</label>
                        <input type="color" class="form-input" name="bg1" id="bg1Color" style="height: 45px; padding: 2px;">
                    </div>
                </div>
            </div>
            
             <div class="row-split">
                <div class="col-half">
                    <div class="form-group">
                        <label>Background Bottom</label>
                        <input type="color" class="form-input" name="bg2" id="bg2Color" style="height: 45px; padding: 2px;">
                    </div>
                </div>
            </div>

        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn-custom btn-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn-custom btn-primary">Save Theme</button>
        </div>
    </form>
</div>

<script>
function toggleGroupInput() {
    var select = document.getElementById('groupSelect');
    var input = document.getElementById('groupNew');
    if (select.value === 'NEW') {
        input.style.display = 'block';
        input.required = true;
        input.focus();
    } else {
        input.style.display = 'none';
        input.required = false;
    }
}

function openModal(mode) {
    document.getElementById('formAction').value = mode;
    
    // Reset Group UX
    document.getElementById('groupSelect').value = '<?php echo $allGroups[0] ?? "System"; ?>';
    toggleGroupInput();
    
    if (mode === 'create') {
        document.getElementById('modalTitle').innerText = 'Create New Theme';
        document.getElementById('originalThemeKey').value = '';
        
        // Hide Key Field in Create Mode
        document.getElementById('keyContainer').style.display = 'none';
        
        document.getElementById('themeName').value = '';
        
        // Defaults
        document.getElementById('accentColor').value = '#f97316';
        document.getElementById('h1Color').value = '#0f172a';
        document.getElementById('h2Color').value = '#1e293b';
        document.getElementById('bg1Color').value = '#0f172a';
        document.getElementById('bg2Color').value = '#1e293b';
    }
    
    document.getElementById('modalOverlay').classList.add('open');
    document.getElementById('themeModal').classList.add('open');
}

function editTheme(data) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Edit Theme: ' + data.name;
    document.getElementById('originalThemeKey').value = data.key;
    

    var select = document.getElementById('groupSelect');
    var groupExists = false;
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === data.group) {
            groupExists = true;
            break;
        }
    }
    
    if (groupExists) {
        select.value = data.group;
        toggleGroupInput();
    } else {
        select.value = 'NEW';
        toggleGroupInput();
        document.getElementById('groupNew').value = data.group;
    }
    
    // Show Key in Edit Mode (Read-Only)
    document.getElementById('keyContainer').style.display = 'block';
    document.getElementById('themeKeyDisplay').value = data.key;
    
    document.getElementById('themeName').value = data.name;
    
    document.getElementById('accentColor').value = data.accent;
    document.getElementById('h1Color').value = data.header1;
    document.getElementById('h2Color').value = data.header2;
    document.getElementById('bg1Color').value = data.bg1;
    document.getElementById('bg2Color').value = data.bg2;
    
    document.getElementById('modalOverlay').classList.add('open');
    document.getElementById('themeModal').classList.add('open');
}

function duplicateTheme(data) {
    // Duplication is basically opening 'create' but with filled data
    openModal('create');
    
    document.getElementById('modalTitle').innerText = 'Duplicate Theme';
    
    document.getElementById('themeName').value = data.name + ' (Copy)';
    
    // Pre-select group
    var select = document.getElementById('groupSelect');
    if ([...select.options].some(o => o.value === data.group)) {
        select.value = data.group;
    }
    toggleGroupInput();

    document.getElementById('accentColor').value = data.accent;
    document.getElementById('h1Color').value = data.header1;
    document.getElementById('h2Color').value = data.header2;
    document.getElementById('bg1Color').value = data.bg1;
    document.getElementById('bg2Color').value = data.bg2;
}

function closeModal(e) {
    if (e && e.target !== document.getElementById('modalOverlay')) return;
    document.getElementById('modalOverlay').classList.remove('open');
    document.getElementById('themeModal').classList.remove('open');
}
</script>

<?php include('footer.php'); ?>
</body>
</html>


