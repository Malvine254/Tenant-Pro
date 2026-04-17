#!/usr/bin/env node
/**
 * Tenant Pro — Production Rebuild Tool
 *
 * Usage:
 *   node rebuild-tool.js [port]
 *
 * Env vars:
 *   REBUILD_TOOL_PASSWORD=secret    Required in production
 *   REBUILD_TOOL_HOST=0.0.0.0       Bind host (default 127.0.0.1)
 *   REBUILD_TOOL_PORT=9090          Port (default 9090, overridden by argv[2])
 *   REBUILD_TOOL_SECRET=<random>    Cookie-signing secret (auto-generated if absent)
 *
 * Production with nginx — add to your server block:
 *   location /rebuild/ {
 *     proxy_pass http://127.0.0.1:9090/;
 *     proxy_set_header Host $host;
 *     proxy_set_header X-Real-IP $remote_addr;
 *   }
 *
 * Then access via: https://starmaxltd.com/rebuild/
 *
 * Start with PM2 (recommended for production):
 *   REBUILD_TOOL_PASSWORD=yourpassword pm2 start rebuild-tool.js --name rebuild-tool
 */

'use strict';

const http   = require('http');
const crypto = require('crypto');
const { exec } = require('child_process');
const os     = require('os');

const ROOT     = __dirname;
const PORT     = parseInt(process.env.REBUILD_TOOL_PORT ?? process.argv[2] ?? '9090', 10);
const BIND     = process.env.REBUILD_TOOL_HOST ?? '127.0.0.1';
const PASSWORD = process.env.REBUILD_TOOL_PASSWORD ?? '';
const SECRET   = process.env.REBUILD_TOOL_SECRET ?? crypto.randomBytes(32).toString('hex');
const IS_WIN   = os.platform() === 'win32';

if (!PASSWORD) {
  console.warn('\n  ⚠  WARNING: REBUILD_TOOL_PASSWORD is not set.');
  console.warn('     Anyone who can reach this port can run migrations & wipe caches.');
  console.warn('     Set REBUILD_TOOL_PASSWORD in your environment.\n');
}

// ── Session auth ──────────────────────────────────────────────────────────────
const SESSION_COOKIE = 'rbt_sess';
const SESSION_TTL    = 8 * 60 * 60 * 1000; // 8 hours

function signSession(payload) {
  const data = Buffer.from(JSON.stringify(payload)).toString('base64url');
  const sig  = crypto.createHmac('sha256', SECRET).update(data).digest('base64url');
  return `${data}.${sig}`;
}

function verifySession(value) {
  if (!value) return null;
  const [data, sig] = value.split('.');
  if (!data || !sig) return null;
  const expected = crypto.createHmac('sha256', SECRET).update(data).digest('base64url');
  try {
    if (!crypto.timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return null;
    const p = JSON.parse(Buffer.from(data, 'base64url').toString());
    if (Date.now() > p.exp) return null;
    return p;
  } catch { return null; }
}

function getSession(req) {
  for (const part of (req.headers.cookie ?? '').split(';')) {
    const [k, ...v] = part.trim().split('=');
    if (k.trim() === SESSION_COOKIE) return verifySession(decodeURIComponent(v.join('=')));
  }
  return null;
}

function authed(req) {
  if (!PASSWORD) return true; // open if no password configured
  return getSession(req) !== null;
}

// ── Cross-platform helpers ────────────────────────────────────────────────────
const rmDir = IS_WIN
  ? (d) => `if exist "${d}" rmdir /s /q "${d}" & echo cleared`
  : (d) => `rm -rf "${d}" && echo cleared`;

function run(cmd) {
  return new Promise((resolve) => {
    const shell = IS_WIN ? 'cmd.exe' : '/bin/sh';
    exec(cmd, { cwd: ROOT, maxBuffer: 5 * 1024 * 1024, shell }, (err, stdout, stderr) => {
      resolve({ code: err?.code ?? 0, out: stdout, err: stderr });
    });
  });
}

function fmt(r) {
  const parts = [];
  if (r.out) parts.push(r.out);
  if (r.err) parts.push('--- stderr ---\n' + r.err);
  parts.push(r.code !== 0 ? `\n✗ Exit code: ${r.code}` : '\n✓ Completed successfully');
  return parts.join('\n');
}

function esc(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// ── Actions ───────────────────────────────────────────────────────────────────
const ACTIONS = {
  status: {
    label: 'Show Status',
    description: 'Node/npm versions, platform, and port listening state.',
    danger: false,
    async command() {
      const [node, npm, prisma, p3000, p3001] = await Promise.all([
        run('node --version'),
        run('npm --version'),
        run('npx prisma --version 2>&1'),
        run(IS_WIN
          ? 'netstat -an 2>nul | findstr ":3000 " | findstr LISTENING'
          : 'ss -tlnp 2>/dev/null | grep :3000 || echo ""'),
        run(IS_WIN
          ? 'netstat -an 2>nul | findstr ":3001 " | findstr LISTENING'
          : 'ss -tlnp 2>/dev/null | grep :3001 || echo ""'),
      ]);
      return [
        `Node     : ${node.out.trim() || 'n/a'}`,
        `npm      : ${npm.out.trim() || 'n/a'}`,
        `Prisma   : ${(prisma.out || prisma.err).trim().split('\n')[0] || 'n/a'}`,
        `Platform : ${os.platform()} ${os.arch()} (${os.release()})`,
        `API:3000 : ${p3000.out.trim() ? '✓ LISTENING' : '✗ not running'}`,
        `Web:3001 : ${p3001.out.trim() ? '✓ LISTENING' : '✗ not running'}`,
        `Root     : ${ROOT}`,
      ].join('\n');
    },
  },

  clear_cache: {
    label: 'Clear All Caches',
    description: 'Delete .next build cache and dist/ folder.',
    danger: false,
    async command() {
      const [r1, r2] = await Promise.all([
        run(rmDir('admin-dashboard/.next')),
        run(rmDir('dist')),
      ]);
      return [
        '=== .next ===', r1.out || r1.err || 'done',
        '=== dist  ===', r2.out || r2.err || 'done',
      ].join('\n');
    },
  },

  build_backend: {
    label: 'Build Backend (NestJS)',
    description: 'tsc → dist/ via npm run build.',
    danger: false,
    command: () => run('npm run build').then(fmt),
  },

  build_frontend: {
    label: 'Build Frontend (Next.js)',
    description: 'next build for the admin dashboard.',
    danger: false,
    command: () => run('npm run build:web').then(fmt),
  },

  prisma_generate: {
    label: 'Prisma Generate',
    description: 'Regenerate Prisma client from schema.prisma.',
    danger: false,
    command: () => run('npx prisma generate').then(fmt),
  },

  prisma_migrate: {
    label: 'Run Migrations (Deploy)',
    description: 'prisma migrate deploy — applies pending migrations.',
    danger: true,
    command: () => run('npx prisma migrate deploy').then(fmt),
  },

  prisma_seed: {
    label: 'Seed Database',
    description: 'Run prisma/seed.js to populate initial data.',
    danger: true,
    command: () => run('npx prisma db seed').then(fmt),
  },

  full_rebuild: {
    label: 'Full Production Rebuild',
    description: 'Clear → Prisma generate → Build backend → Build frontend.',
    danger: true,
    async command() {
      const steps = [
        ['Clear .next',     rmDir('admin-dashboard/.next')],
        ['Clear dist',      rmDir('dist')],
        ['Prisma generate', 'npx prisma generate'],
        ['Build backend',   'npm run build'],
        ['Build frontend',  'npm run build:web'],
      ];
      const out = [];
      for (const [label, cmd] of steps) {
        out.push(`${'─'.repeat(48)}\n▶ ${label}\n${'─'.repeat(48)}`);
        const r = await run(cmd);
        out.push(r.out || r.err || '(no output)');
        if (r.code !== 0 && !label.startsWith('Clear')) {
          out.push(`\n✗ Step "${label}" failed (exit ${r.code}). Stopping.`);
          break;
        }
      }
      return out.join('\n');
    },
  },
};

// ── HTML: Login page ──────────────────────────────────────────────────────────
function renderLogin(error = '') {
  return `<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rebuild Tool — Sign in</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
  .box{width:100%;max-width:340px;background:#1e293b;border:1px solid #334155;border-radius:16px;padding:28px}
  .logo{display:flex;align-items:center;gap:10px;margin-bottom:24px}
  .mark{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:16px;flex-shrink:0}
  .t1{font-size:15px;font-weight:700;color:#f1f5f9}
  .t2{font-size:11px;color:#64748b;margin-top:2px}
  label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:6px}
  input{width:100%;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:10px 12px;color:#e2e8f0;font-size:14px;outline:none;margin-bottom:16px}
  input:focus{border-color:#6366f1;box-shadow:0 0 0 3px #6366f120}
  .btn{width:100%;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:11px;font-size:14px;font-weight:600;cursor:pointer}
  .btn:hover{background:#4f46e5}
  .err{background:#f8717115;border:1px solid #f8717140;border-radius:8px;padding:10px 12px;font-size:12px;color:#f87171;margin-bottom:16px}
</style></head><body>
<div class="box">
  <div class="logo">
    <div class="mark">TP</div>
    <div><div class="t1">Rebuild Tool</div><div class="t2">Tenant Pro Production</div></div>
  </div>
  ${error ? `<div class="err">${esc(error)}</div>` : ''}
  <form method="POST" action="/login">
    <label for="pw">Password</label>
    <input id="pw" type="password" name="password" autofocus required placeholder="••••••••">
    <button class="btn">Sign in →</button>
  </form>
</div></body></html>`;
}

// ── HTML: Main page ───────────────────────────────────────────────────────────
function renderMain(result = null, activeAction = null) {
  const cards = Object.entries(ACTIONS).map(([key, a]) => {
    const accent = a.danger ? '#f97316' : '#6366f1';
    const active = key === activeAction;
    return `
    <form method="POST" action="/" style="margin:0">
      <input type="hidden" name="action" value="${key}">
      <div style="border:1px solid ${active ? accent : '#334155'};border-left:3px solid ${accent};background:${active ? '#263148' : '#1e293b'};border-radius:10px;padding:16px 18px;margin-bottom:12px;transition:.15s">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
          <span style="font-size:14px;font-weight:600;color:#f1f5f9">${esc(a.label)}</span>
          ${a.danger ? `<span style="font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:${accent}20;color:${accent};border:1px solid ${accent}40">Writes DB/Disk</span>` : ''}
        </div>
        <p style="font-size:12px;color:#94a3b8;margin-bottom:12px;line-height:1.55">${esc(a.description)}</p>
        <button type="submit" style="background:${a.danger ? 'transparent' : '#6366f1'};color:${a.danger ? accent : '#fff'};border:${a.danger ? `1px solid ${accent}` : 'none'};border-radius:7px;padding:7px 18px;font-size:12px;font-weight:600;cursor:pointer">Run ▶</button>
      </div>
    </form>`;
  }).join('');

  const resultBlock = result
    ? `<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;display:flex;flex-direction:column;min-height:260px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;margin-bottom:12px">▌ ${esc(ACTIONS[activeAction]?.label ?? activeAction)}</div>
        <pre style="font-family:'Cascadia Code','Fira Code',monospace;font-size:12px;background:#0f172a;color:#a5b4fc;padding:16px;border-radius:8px;overflow:auto;white-space:pre-wrap;word-break:break-all;flex:1;line-height:1.6">${esc(result)}</pre>
       </div>`
    : `<div style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;min-height:260px;display:flex;align-items:center;justify-content:center;color:#475569;font-size:13px">Run an action to see output here.</div>`;

  return `<!DOCTYPE html><html lang="en"><head>
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
  .warnbar{background:#f9731612;border:1px solid #f9731630;border-radius:8px;padding:10px 16px;font-size:12px;color:#fdba74;margin-bottom:20px;line-height:1.6}
  .logout{font-size:11px;color:#f87171;border:1px solid #f8717130;padding:5px 14px;border-radius:6px;background:transparent;cursor:pointer}
  .logout:hover{background:#f871711a}
</style></head><body>
<div class="topbar">
  <div style="display:flex;align-items:center;gap:10px">
    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;color:#fff;flex-shrink:0">TP</div>
    <div>
      <div style="font-size:15px;font-weight:700;color:#f1f5f9">Tenant Pro — Rebuild Tool</div>
      <div style="font-size:11px;color:#64748b"><a href="/">↺ Refresh</a> &nbsp;·&nbsp; ${esc(ROOT)}</div>
    </div>
  </div>
  <form method="POST" action="/logout" style="margin:0">
    <button class="logout" type="submit">Sign out</button>
  </form>
</div>
<div class="grid">
  <div>
    <div class="lbl">Actions</div>
    <div class="warnbar">⚠ Frontend JS/CSS changes require <code>npm run build:web</code> or a Full Rebuild. Hot reload is not available here.</div>
    ${cards}
  </div>
  <div>
    <div class="lbl">Results</div>
    ${resultBlock}
  </div>
</div>
</body></html>`;
}

// ── HTTP server ───────────────────────────────────────────────────────────────
const server = http.createServer(async (req, res) => {
  const url = (req.url ?? '/').split('?')[0];

  // ── Login ──
  if (url === '/login') {
    if (req.method === 'GET') {
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(renderLogin());
      return;
    }
    if (req.method === 'POST') {
      let body = '';
      req.on('data', c => body += c);
      req.on('end', () => {
        const pw = new URLSearchParams(body).get('password') ?? '';
        if (!PASSWORD || pw === PASSWORD) {
          const token = signSession({ ok: true, exp: Date.now() + SESSION_TTL });
          res.writeHead(302, {
            'Set-Cookie': `${SESSION_COOKIE}=${encodeURIComponent(token)}; HttpOnly; SameSite=Lax; Path=/; Max-Age=${SESSION_TTL / 1000}`,
            Location: '/',
          });
          res.end();
        } else {
          res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
          res.end(renderLogin('Incorrect password.'));
        }
      });
      return;
    }
  }

  // ── Logout ──
  if (url === '/logout' && req.method === 'POST') {
    res.writeHead(302, {
      'Set-Cookie': `${SESSION_COOKIE}=; HttpOnly; SameSite=Lax; Path=/; Max-Age=0`,
      Location: '/login',
    });
    res.end();
    return;
  }

  // ── Auth gate ──
  if (!authed(req)) {
    res.writeHead(302, { Location: '/login' });
    res.end();
    return;
  }

  // ── Dashboard ──
  if (req.method === 'GET' && url === '/') {
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(renderMain());
    return;
  }

  // ── Run action ──
  if (req.method === 'POST' && url === '/') {
    let body = '';
    req.on('data', c => body += c);
    req.on('end', async () => {
      const action = new URLSearchParams(body).get('action') ?? '';
      if (!ACTIONS[action]) {
        res.writeHead(400, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(renderMain(`Unknown action: ${action}`));
        return;
      }
      let result;
      try {
        const r = ACTIONS[action].command();
        result = r instanceof Promise ? await r : r;
      } catch (err) {
        result = 'Error: ' + (err?.message ?? String(err));
      }
      res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
      res.end(renderMain(result, action));
    });
    return;
  }

  res.writeHead(404);
  res.end('Not found');
});

server.listen(PORT, BIND, () => {
  const displayHost = BIND === '0.0.0.0' ? 'localhost' : BIND;
  console.log(`\n  Tenant Pro Rebuild Tool`);
  console.log(`  ─────────────────────────────────────────────────`);
  console.log(`  URL      : http://${displayHost}:${PORT}`);
  console.log(`  Binding  : ${BIND}:${PORT}`);
  console.log(`  Root     : ${ROOT}`);
  console.log(`  Password : ${PASSWORD ? '✓ set' : '⚠  NOT SET — tool is open to anyone!'}`);
  if (BIND === '0.0.0.0') {
    console.log(`\n  Nginx:   proxy_pass http://127.0.0.1:${PORT}/;`);
  }
  console.log(`\n  Press Ctrl+C to stop.\n`);
});
