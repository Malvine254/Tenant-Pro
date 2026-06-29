<?php
/**
 * Tenant Pro — Production Rebuild Tool (PHP version)
 *
 * Upload this file to your web root (same folder as .htaccess).
 * Access via: https://starmaxltd.com/rebuild-tool.php
 *
 * Set the password below or via environment variable REBUILD_TOOL_PASSWORD.
 * CHANGE THE DEFAULT PASSWORD before uploading!
 */

define('ROOT', dirname(__FILE__));

// ── Auth disabled — re-enable before going live ───────────────────────────────
// To protect this tool, uncomment the block below and set a password.
//
// define('TOOL_PASSWORD', 'YourPasswordHere');
// session_start();
// if ($_POST['_action'] ?? '' === 'login') {
//     if (hash_equals(TOOL_PASSWORD, $_POST['password'] ?? '')) {
//         $_SESSION['authed'] = true; $_SESSION['exp'] = time() + 28800;
//         header('Location: ' . $_SERVER['PHP_SELF']); exit;
//     }
// }
// if ($_POST['_action'] ?? '' === 'logout') {
//     session_destroy(); header('Location: ' . $_SERVER['PHP_SELF']); exit;
// }
// if (empty($_SESSION['authed']) || time() > ($_SESSION['exp'] ?? 0)) {
//     // renderLogin(); exit;
// }

// ── Run action ────────────────────────────────────────────────────────────────
$result     = null;
$activeKey  = null;

$isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

function runCmd(string $cmd): string {
    $output = [];
    $code   = 0;
    exec($cmd . ' 2>&1', $output, $code);
    $out = implode("\n", $output);
    return $out . "\n" . ($code !== 0 ? "✗ Exit code: $code" : "✓ Completed successfully");
}

function rmDir(string $path): string {
    global $isWin;
    $abs = ROOT . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    if ($isWin) {
        return is_dir($abs) ? runCmd("rmdir /s /q \"$abs\"") : "Already clear.";
    }
    return runCmd("rm -rf " . escapeshellarg($abs));
}

$actions = [
    'status' => [
        'label'       => 'Show Status',
        'description' => 'PHP, Node, npm versions and which processes are listening.',
        'danger'      => false,
    ],
    'clear_cache' => [
        'label'       => 'Clear All Caches',
        'description' => 'Delete admin-dashboard/.next and dist/ folders.',
        'danger'      => false,
    ],
    'build_backend' => [
        'label'       => 'Build Backend (NestJS)',
        'description' => 'npm run build in project root → dist/',
        'danger'      => false,
    ],
    'build_frontend' => [
        'label'       => 'Build Frontend (Next.js)',
        'description' => 'npm run build:web → admin-dashboard/out/',
        'danger'      => false,
    ],
    'prisma_generate' => [
        'label'       => 'Prisma Generate',
        'description' => 'Regenerate Prisma client from schema.',
        'danger'      => false,
    ],
    'prisma_migrate' => [
        'label'       => 'Run Migrations (Deploy)',
        'description' => 'prisma migrate deploy — applies pending migrations.',
        'danger'      => true,
    ],
    'prisma_seed' => [
        'label'       => 'Seed Database',
        'description' => 'Run prisma/seed.js to populate initial data.',
        'danger'      => true,
    ],
    'full_rebuild' => [
        'label'       => 'Full Production Rebuild',
        'description' => 'Clear → Prisma generate → Build backend → Build frontend.',
        'danger'      => true,
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $activeKey = $_POST['action'];

    switch ($activeKey) {
        case 'status':
            $node  = trim(shell_exec('node --version 2>&1') ?? 'n/a');
            $npm   = trim(shell_exec('npm --version 2>&1') ?? 'n/a');
            $php   = phpversion();
            $uname = php_uname();
            $root  = ROOT;
            $p3000 = shell_exec($isWin ? 'netstat -an 2>nul | findstr ":3000 "' : 'ss -tlnp 2>/dev/null | grep :3000');
            $p3001 = shell_exec($isWin ? 'netstat -an 2>nul | findstr ":3001 "' : 'ss -tlnp 2>/dev/null | grep :3001');
            $result = implode("\n", [
                "PHP      : $php",
                "Node     : $node",
                "npm      : $npm",
                "Platform : $uname",
                "API:3000 : " . ($p3000 ? "✓ LISTENING" : "✗ not running"),
                "Web:3001 : " . ($p3001 ? "✓ LISTENING" : "✗ not running"),
                "Root     : $root",
            ]);
            break;

        case 'clear_cache':
            $r1 = rmDir('admin-dashboard/.next');
            $r2 = rmDir('dist');
            $result = "=== .next ===\n$r1\n\n=== dist ===\n$r2";
            break;

        case 'build_backend':
            $result = runCmd("cd " . escapeshellarg(ROOT) . " && npm run build");
            break;

        case 'build_frontend':
            $result = runCmd("cd " . escapeshellarg(ROOT) . " && npm run build:web");
            break;

        case 'prisma_generate':
            $result = runCmd("cd " . escapeshellarg(ROOT) . " && npx prisma generate");
            break;

        case 'prisma_migrate':
            $result = runCmd("cd " . escapeshellarg(ROOT) . " && npx prisma migrate deploy");
            break;

        case 'prisma_seed':
            $result = runCmd("cd " . escapeshellarg(ROOT) . " && npx prisma db seed");
            break;

        case 'full_rebuild':
            $steps = [
                ['Clear .next',     fn() => rmDir('admin-dashboard/.next')],
                ['Clear dist',      fn() => rmDir('dist')],
                ['Prisma generate', fn() => runCmd("cd " . escapeshellarg(ROOT) . " && npx prisma generate")],
                ['Build backend',   fn() => runCmd("cd " . escapeshellarg(ROOT) . " && npm run build")],
                ['Build frontend',  fn() => runCmd("cd " . escapeshellarg(ROOT) . " && npm run build:web")],
            ];
            $parts = [];
            foreach ($steps as [$label, $fn]) {
                $parts[] = str_repeat('─', 48) . "\n▶ $label\n" . str_repeat('─', 48);
                $out = $fn();
                $parts[] = $out;
                if (str_contains($out, '✗ Exit code') && !str_starts_with($label, 'Clear')) {
                    $parts[] = "\n✗ Step \"$label\" failed. Stopping.";
                    break;
                }
            }
            $result = implode("\n", $parts);
            break;

        default:
            $result = "Unknown action.";
    }
}

// ── HTML helpers ──────────────────────────────────────────────────────────────
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function renderLogin(?string $error = null): void { ?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rebuild Tool — Sign in</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.box{width:100%;max-width:340px;background:#1e293b;border:1px solid #334155;border-radius:16px;padding:28px}
.logo{display:flex;align-items:center;gap:10px;margin-bottom:24px}
.mark{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:16px;flex-shrink:0}
.t1{font-size:15px;font-weight:700;color:#f1f5f9}.t2{font-size:11px;color:#64748b;margin-top:2px}
label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:6px}
input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:10px 12px;color:#e2e8f0;font-size:14px;outline:none;margin-bottom:16px}
input:focus{border-color:#6366f1;box-shadow:0 0 0 3px #6366f120}
.btn{width:100%;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:600;cursor:pointer}.btn:hover{background:#4f46e5}
.err{background:#f8717115;border:1px solid #f8717140;border-radius:8px;padding:10px 12px;font-size:12px;color:#f87171;margin-bottom:16px}
</style></head><body>
<div class="box">
  <div class="logo">
    <div class="mark">TP</div>
    <div><div class="t1">Rebuild Tool</div><div class="t2">Tenant Pro Production</div></div>
  </div>
  <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="_action" value="login">
    <label for="pw">Password</label>
    <input id="pw" type="password" name="password" autofocus required placeholder="••••••••">
    <button class="btn" type="submit">Sign in →</button>
  </form>
</div></body></html>
<?php }

// ── Main page ─────────────────────────────────────────────────────────────────
global $actions, $result, $activeKey;

$cards = '';
foreach ($actions as $key => $a) {
    $accent = $a['danger'] ? '#f97316' : '#6366f1';
    $active = $key === $activeKey;
    $cards .= '
    <form method="POST" style="margin:0">
      <input type="hidden" name="action" value="' . e($key) . '">
      <div style="border:1px solid ' . ($active ? $accent : '#334155') . ';border-left:3px solid ' . $accent . ';background:' . ($active ? '#263148' : '#1e293b') . ';border-radius:10px;padding:16px 18px;margin-bottom:12px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
          <span style="font-size:14px;font-weight:600;color:#f1f5f9">' . e($a['label']) . '</span>
          ' . ($a['danger'] ? '<span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:' . $accent . '20;color:' . $accent . ';border:1px solid ' . $accent . '40">Writes DB/Disk</span>' : '') . '
        </div>
        <p style="font-size:12px;color:#94a3b8;margin-bottom:12px;line-height:1.55">' . e($a['description']) . '</p>
        <button type="submit" style="background:' . ($a['danger'] ? 'transparent' : '#6366f1') . ';color:' . ($a['danger'] ? $accent : '#fff') . ';border:' . ($a['danger'] ? "1px solid $accent" : 'none') . ';border-radius:7px;padding:7px 18px;font-size:12px;font-weight:600;cursor:pointer">Run ▶</button>
      </div>
    </form>';
}

$resultBlock = $result !== null
    ? '<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;display:flex;flex-direction:column;min-height:260px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:12px">▌ ' . e($actions[$activeKey]['label'] ?? $activeKey) . '</div>
        <pre style="font-family:monospace;font-size:12px;background:#0f172a;color:#a5b4fc;padding:16px;border-radius:8px;overflow:auto;white-space:pre-wrap;word-break:break-all;flex:1;line-height:1.6">' . e($result) . '</pre>
       </div>'
    : '<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;min-height:260px;display:flex;align-items:center;justify-content:center;color:#475569;font-size:13px">Run an action to see output here.</div>';

?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tenant Pro — Rebuild Tool</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
a{color:#818cf8;text-decoration:none}
.topbar{background:#1e293b;border-bottom:1px solid #334155;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.grid{max-width:1100px;margin:0 auto;padding:32px 24px;display:grid;grid-template-columns:1fr 1.2fr;gap:28px;align-items:start}
@media(max-width:740px){.grid{grid-template-columns:1fr}}
.lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#64748b;margin-bottom:14px}
.logout{font-size:11px;color:#f87171;border:1px solid #f8717130;padding:5px 14px;border-radius:6px;background:transparent;cursor:pointer}.logout:hover{background:#f871711a}
</style></head><body>
<div class="topbar">
  <div style="display:flex;align-items:center;gap:10px">
    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;color:#fff;flex-shrink:0">TP</div>
    <div>
      <div style="font-size:15px;font-weight:700;color:#f1f5f9">Tenant Pro — Rebuild Tool</div>
      <div style="font-size:11px;color:#64748b"><?= e(ROOT) ?></div>
    </div>
  </div>
  <form method="POST" style="margin:0;display:none">
    <input type="hidden" name="_action" value="logout">
    <button class="logout" type="submit">Sign out</button>
  </form>
</div>
<div class="grid">
  <div>
    <div class="lbl">Actions</div>
    <?= $cards ?>
  </div>
  <div>
    <div class="lbl">Results</div>
    <?= $resultBlock ?>
  </div>
</div>
</body></html>
