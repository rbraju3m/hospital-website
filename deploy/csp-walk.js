#!/usr/bin/env node
//
// RBR Hospital — walk the site and read the Content Security Policy back.
//
// The CSP is the one response header that can take this site down: a directive
// too tight anywhere breaks that page for every visitor, and nothing in the
// PHPUnit suite would catch it, because the suite never runs a browser. So
// before CSP_ENFORCE is switched on — and again after anything is added that
// loads a script, a font, an image or a frame — somebody has to walk the site
// in a browser and read the console. This does that walk, and reads it for you.
//
// It drives headless Chrome over the DevTools protocol and listens for
// `securitypolicyviolation` — the DOM event behind each console line. That is
// deliberately not console scraping: the event carries the effective directive,
// the blocked URI and the source line, which is the difference between "the
// gallery is broken" and "img-src refused a blob:".
//
// Run it:
//   npm run build                      # NOT `npm run dev` -- see below
//   php8.3 artisan serve --host=127.0.0.1 --port=8321
//   node deploy/csp-walk.js --base=http://127.0.0.1:8321 deploy/csp-walk.plan.json
//
// Exits 0 when the walk is clean and 1 when anything was reported, so it can
// gate a deployment. `--json` prints the raw findings instead of the report.
//
// Four things that will waste an afternoon if you do not know them:
//
//   * `npm run dev` serves the bundle from Vite's own origin, and the header is
//     deliberately not sent while Vite is hot. A walk against a hot dev server
//     is a walk with no policy at all: perfectly clean, and meaningless. Build.
//
//   * A signed URL is signed over its host, so a confirmation link generated
//     with APP_URL=http://hospital.local 403s when fetched on a dev port. Pass
//     `--map=hospital.local:80=127.0.0.1:8321` and walk the real hostname;
//     Chrome resolves it to the dev server and the signature holds.
//
//   * Signed-in screens are best walked against the TEST schema on a second
//     port, not the dev database:
//         DB_DATABASE=hospital_site_test php8.3 artisan serve --port=8322
//     That way the accounts a walk needs are created in somewhere disposable
//     rather than in real data. See deploy/csp-walk.panel.json.
//
//   * Do not run PHPUnit while a walk is in flight. RefreshDatabase truncates
//     the test schema, the session goes with it, and every page quietly starts
//     landing on a login — which reports clean, because a login page is clean.
//     The report prints where each step actually landed. Read that column.
//
// A plan is a JSON array of steps:
//   label     what to call it in the report
//   url       may contain {BASE}, and any {PLACEHOLDER} passed with --set
//   settle    ms to wait after navigation      (default 2200)
//   action    JS evaluated in the page afterwards, for opening a lightbox,
//             filling a form, pressing a key. Push a note onto window.__errs
//             to have it appear in the report.
//   after     ms to wait after the action      (default 1500)
//
// An action that submits a form destroys its own execution context; that is
// expected and handled. Anything the action wants to report has to be pushed
// before it navigates.

// ESM, because package.json says "type": "module" and this file lives in the
// repository. Nothing here is imported by the bundle; it is a standalone tool.
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME = process.env.CHROME_BIN || 'google-chrome';
const PORT = Number(process.env.CSP_WALK_CDP_PORT || 9222);

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/* ------------------------------------------------------------------ args -- */

const args = process.argv.slice(2);
const opts = { base: '', map: '', json: false, set: {}, plan: '' };

for (const arg of args) {
    if (arg === '--json') opts.json = true;
    else if (arg.startsWith('--base=')) opts.base = arg.slice(7).replace(/\/$/, '');
    else if (arg.startsWith('--map=')) opts.map = arg.slice(6);
    else if (arg.startsWith('--set=')) {
        const [key, ...rest] = arg.slice(6).split('=');
        opts.set[key] = rest.join('=');
    } else if (!arg.startsWith('--')) opts.plan = arg;
}

if (!opts.plan) {
    console.error('usage: node deploy/csp-walk.js --base=http://127.0.0.1:8321 [--map=host:80=127.0.0.1:8321] [--set=KEY=value] [--json] <plan.json>');
    process.exit(2);
}

const fill = (text) => {
    let out = String(text).replaceAll('{BASE}', opts.base);
    for (const [key, value] of Object.entries(opts.set)) out = out.replaceAll(`{${key}}`, value);
    return out;
};

/* ------------------------------------------------------------------- cdp -- */

let nextId = 1;

function rpc(ws, method, params, sessionId) {
    const id = nextId++;

    return new Promise((resolve, reject) => {
        const onMessage = (event) => {
            let message;
            try { message = JSON.parse(event.data); } catch { return; }
            if (message.id !== id) return;

            ws.removeEventListener('message', onMessage);
            message.error ? reject(new Error(`${method}: ${message.error.message}`)) : resolve(message.result);
        };

        ws.addEventListener('message', onMessage);
        ws.send(JSON.stringify({ id, method, params: params || {}, sessionId }));
    });
}

/* Installed before anything on the page runs, so a violation raised by the
   very first inline script is still caught. Report-only or enforced makes no
   difference to the event -- `disposition` says which it was. */
const RECORDER = `
  window.__csp = [];
  document.addEventListener('securitypolicyviolation', function (e) {
    window.__csp.push({
      directive: e.effectiveDirective || e.violatedDirective,
      blocked: e.blockedURI,
      source: e.sourceFile ? e.sourceFile + ':' + e.lineNumber + ':' + e.columnNumber : null,
      sample: e.sample ? String(e.sample).slice(0, 120) : null,
      disposition: e.disposition
    });
  });
  window.__errs = [];
  window.addEventListener('error', function (e) {
    window.__errs.push(String(e.message || e.type));
  });
`;

async function chrome() {
    const profile = fs.mkdtempSync(path.join(os.tmpdir(), 'csp-walk-'));

    const flags = [
        '--headless=new', `--remote-debugging-port=${PORT}`, '--no-sandbox', '--disable-gpu',
        `--user-data-dir=${profile}`, '--no-first-run', '--disable-features=Translate', 'about:blank',
    ];

    if (opts.map) flags.splice(4, 0, `--host-resolver-rules=MAP ${opts.map}`);

    const proc = spawn(CHROME, flags, { stdio: 'ignore' });
    proc.on('error', () => {
        console.error(`Could not start ${CHROME}. Set CHROME_BIN to a Chrome or Chromium binary.`);
        process.exit(2);
    });

    for (let attempt = 0; attempt < 60; attempt++) {
        try {
            const version = await (await fetch(`http://127.0.0.1:${PORT}/json/version`)).json();
            if (version.webSocketDebuggerUrl) return { proc, profile, version };
        } catch { /* not up yet */ }
        await sleep(250);
    }

    proc.kill();
    throw new Error(`Chrome never opened a debugging port on ${PORT}.`);
}

/* ------------------------------------------------------------------ walk -- */

async function walk() {
    const plan = JSON.parse(fs.readFileSync(opts.plan, 'utf8'));
    const { proc, profile, version } = await chrome();

    const browser = new WebSocket(version.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => { browser.onopen = resolve; browser.onerror = reject; });

    const { targetId } = await rpc(browser, 'Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await rpc(browser, 'Target.attachToTarget', { targetId, flatten: true });
    const send = (method, params) => rpc(browser, method, params, sessionId);

    await send('Page.enable');
    await send('Runtime.enable');
    await send('Page.addScriptToEvaluateOnNewDocument', { source: RECORDER });
    await send('Emulation.setDeviceMetricsOverride', {
        width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false,
    });

    const read = 'JSON.stringify({v: window.__csp || [], e: window.__errs || [],'
        + ' t: document.title, u: location.pathname})';

    const results = [];

    for (const step of plan) {
        await send('Page.navigate', { url: fill(step.url) });
        await sleep(step.settle || 2200);

        if (step.action) {
            // A submit destroys its own context. Expected; the next read runs
            // against the document it navigated to.
            await send('Runtime.evaluate', { expression: fill(step.action), awaitPromise: true }).catch(() => {});
            await sleep(step.after || 1500);
        }

        let got = await send('Runtime.evaluate', { expression: read, returnByValue: true }).catch(() => null);
        if (!got?.result?.value) {
            await sleep(1200);
            got = await send('Runtime.evaluate', { expression: read, returnByValue: true }).catch(() => null);
        }

        let parsed = { v: [], e: [], t: '?', u: '?' };
        try { parsed = JSON.parse(got.result.value); } catch { /* keep the blank */ }

        results.push({
            label: step.label,
            url: fill(step.url),
            landed: parsed.u,
            title: parsed.t,
            violations: parsed.v || [],
            notes: parsed.e || [],
        });
    }

    browser.close();
    proc.kill();

    // Chrome writes to its profile for a moment after the signal, and a
    // leftover temp directory is not a reason to fail a walk that finished.
    await sleep(400);
    try {
        fs.rmSync(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
    } catch { /* it is in os.tmpdir(); leave it to the system */ }

    return results;
}

/* ---------------------------------------------------------------- report -- */

function report(results) {
    let offending = 0;
    let bounced = 0;

    for (const page of results) {
        const violations = page.violations;
        const strayed = /login/.test(page.landed) && !/login/.test(page.label);

        if (violations.length) offending++;
        if (strayed) bounced++;

        const mark = violations.length ? '!!' : strayed ? '??' : '  ';
        const state = violations.length ? `VIOLATIONS(${violations.length})` : 'clean';
        console.log(`${mark} ${page.label.padEnd(38)}${state.padEnd(15)}${page.landed}`);

        const seen = new Set();
        for (const violation of violations) {
            const key = `${violation.directive}|${violation.blocked}|${violation.source}`;
            if (seen.has(key)) continue;
            seen.add(key);

            console.log(`      ${violation.disposition} ${violation.directive}`
                + ` blocked=${violation.blocked} @ ${violation.source || '-'}`
                + (violation.sample ? ` sample=${violation.sample}` : ''));
        }

        for (const note of new Set(page.notes)) console.log(`      note: ${note}`);
    }

    console.log(`\n${results.length} visits: ${offending} with violations`
        + `, ${bounced} that landed on a login and prove nothing.`);

    // A bounced page reports clean because a login page IS clean. Failing on it
    // is the only thing that stops a wiped session reading as a passing walk.
    return offending === 0 && bounced === 0 ? 0 : 1;
}

walk()
    .then((results) => {
        if (opts.json) {
            console.log(JSON.stringify(results, null, 2));
            process.exit(results.some((page) => page.violations.length) ? 1 : 0);
        }

        process.exit(report(results));
    })
    .catch((error) => {
        console.error('The walk failed:', error.message);
        process.exit(2);
    });
