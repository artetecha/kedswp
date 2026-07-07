import { spawn } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

const PROJECT = process.env.UPSUN_PROJECT || 'idpo3r4eqatcu';
const ENV = process.env.UPSUN_ENV || process.env.TARGET_ENV || 'main';

// learnpress-course-review fatals under wp-cli (admin-only dependency loaded
// unconditionally); it plays no part in this workflow, so skip it everywhere.
const WP = 'wp --skip-plugins=learnpress-course-review';

function upsunSsh(remoteCommand: string, stdin?: string): Promise<string> {
  return new Promise((resolve, reject) => {
    const child = spawn(
      'upsun',
      ['ssh', '-p', PROJECT, '-e', ENV, '--no-interaction', remoteCommand],
      { stdio: ['pipe', 'pipe', 'pipe'] },
    );
    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (d) => (stdout += d));
    child.stderr.on('data', (d) => (stderr += d));
    child.on('error', reject);
    child.on('close', (code) => {
      if (code === 0) return resolve(stdout);
      reject(new Error(`upsun ssh exited ${code}\ncommand: ${remoteCommand}\nstdout: ${stdout}\nstderr: ${stderr}`));
    });
    if (stdin !== undefined) child.stdin.write(stdin);
    child.stdin.end();
  });
}

// Run a PHP script from tests/crm-e2e/php/ on the Upsun environment via
// `wp eval-file - <args>`. The script prints one line of JSON prefixed with
// "JSON:" as its last output; anything before it (plugin notices) is ignored.
export async function wpEvalFile<T>(script: string, args: string[] = []): Promise<T> {
  const source = fs.readFileSync(path.join(__dirname, '..', 'php', script), 'utf8');
  const quoted = args.map((a) => `'${a.replace(/'/g, `'\\''`)}'`).join(' ');
  const stdout = await upsunSsh(`cd /app/wordpress && ${WP} eval-file - ${quoted}`, source);
  const marker = stdout.lastIndexOf('JSON:');
  if (marker === -1) {
    throw new Error(`wp eval-file ${script}: no JSON marker in output.\nstdout: ${stdout}`);
  }
  const parsed = JSON.parse(stdout.slice(marker + 'JSON:'.length)) as T & { error?: string };
  if (parsed && typeof parsed === 'object' && 'error' in parsed && parsed.error) {
    throw new Error(`${script}: ${parsed.error}`);
  }
  return parsed;
}

export async function contactTags(email: string): Promise<string[]> {
  const res = await wpEvalFile<{ tags: string[] | null }>('contact-tags.php', [email]);
  if (!res.tags) throw new Error(`no FluentCRM contact for ${email}`);
  return res.tags;
}

// FluentCampaign attaches the Enrolled/Completed tags synchronously on the
// LearnPress hooks, but the tag *removal* on completion is a funnel action
// that runs through FluentCRM's scheduler (WP-Cron). Poll, and nudge cron
// every other attempt so a sleepy test environment can't stall the funnel.
export async function waitForTags(
  email: string,
  predicate: (tags: string[]) => boolean,
  label: string,
  timeoutMs = 180_000,
): Promise<string[]> {
  const started = Date.now();
  let tags: string[] = [];
  let attempt = 0;
  for (;;) {
    tags = await contactTags(email);
    if (predicate(tags)) return tags;
    if (Date.now() - started > timeoutMs) break;
    attempt += 1;
    if (attempt % 2 === 0) {
      await upsunSsh(`cd /app/wordpress && ${WP} cron event run --due-now`).catch(() => undefined);
    }
    await new Promise((r) => setTimeout(r, 5_000));
  }
  throw new Error(`Timed out waiting for ${label}. Contact tags: ${JSON.stringify(tags)}`);
}
