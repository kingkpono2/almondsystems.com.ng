# Almond Systems Hostinger safe blog deployment

**Site:** almondsystems.com.ng  
**Repository:** kingkpono2/almondsystems.com.ng, main  
**Hostinger:** Business Web Hosting; hPanel > Websites > almondsystems.com.ng > Advanced > Cron Jobs

## Primary principle

The live `public_html` site is authoritative. Never deploy the entire GitHub repository over it, use `git pull` in the web root, or replace existing live files blindly.

A manual Hostinger full-site backup was created on 2026-11-05 before this deployment plan. Hostinger backups can be restored independently.

## Automatic deployment

The runner `tools/hostinger_blog_cd.php` is a PHP CLI script designed for this Hostinger account. It:
- Reads the public GitHub main-branch tree and downloads only `blog/index.html` and `blog/<slug>/index.html` pages.
- Creates missing blog pages.
- Updates a previously deployed blog page **only if the current live checksum still equals the checksum of the previously deployed version**. Live-edited content wins over GitHub, and a conflict is reported rather than overwriting it.
- Creates file-specific pre-change backups outside `public_html` before updating an existing managed page.
- Adds a small blog link to existing `index.html` and `services/index.html`, preserving the rest of each file, provided the markup allows a safe insertion.
- Appends missing blog URLs to the existing `sitemap.xml`, leaving other entries intact.
- Saves manifest and backups under `/home/u934246836/.almondsystems-blog-cd/`.
- Records a no-secret status at `https://almondsystems.com.ng/.well-known/almond-cd-status.txt`.
- Uses a lock to prevent concurrent deployments.

### Cron job

Select **Custom** mode and `*/5 * * * *` schedule in hPanel. Use this command:

```sh
cd ~; mkdir -p .almondsystems-blog-cd; curl -fsSL https://raw.githubusercontent.com/kingkpono2/almondsystems.com.ng/main/tools/hostinger_blog_cd.php -o .almondsystems-blog-cd/r.php && php .almondsystems-blog-cd/r.php >> .almondsystems-blog-cd/log 2>&1
```

**Important:** This cron job must be saved in Hostinger before continuous deployment is active. Merely committing the runner to GitHub does not publish anything. Verify the status page and live blog before claiming success. Do not set up a second identical job.

## Initial tests

- `https://almondsystems.com.ng/blog/` returns HTTP 200 and lists five articles.
- Each article URL returns HTTP 200 with content and canonical.
- `https://almondsystems.com.ng/sitemap.xml` contains six blog URLs.
- The homepage and services pages still function and include a blog link, unless safe patching was skipped.
- Status file timestamps update on each successful cron execution.
- Live files (especially existing `index.html`, service pages, assets and `.htaccess`) retain all prior content except the additive link snippet.

## Rollback

First disable cron in Hostinger. Restore the 2026-11-05 Hostinger backup if a full rollback is needed, or restore the specific copies in `.almondsystems-blog-cd/backups/`. The runner never deletes existing live content. Do not overwrite an existing live article to resolve a conflict; review the difference manually.

## Limitations

This is **blog-only**, conflict-aware continuous delivery; it is not a full-site GitHub deployment or bidirectional Git synchronization. A separate audited process is required to pull newer live site files back into GitHub. It uses public GitHub raw content, requires outbound HTTPS and PHP CLI in Hostinger cron, and should be monitored for errors.
## Safe site enhancements (Nov 5)
The same Cron runner now adds the standalone Kpono-Abasi Akpabio founder page, floating WhatsApp CTA across existing static pages, and an exact-match Lulu Culture Foundation → KV Tasks portfolio card replacement. It never copies the older GitHub homepage over the live homepage; each surgical patch makes a backup. New founder URL is added to the existing sitemap. Blog pages get the same WhatsApp script on download. Changes to other existing files remain protected. Check Cron Jobs before adding another job.

## Hostinger UI creation status — November 5, 2026

The correct cron **Custom** command is shown above and is 251 characters long, meeting Hostinger's 255-character field maximum. Set Minute ***/5**, and Hour, Day, Month, Weekday all **\***. The earlier longer 327-character command is invalid and must not be used.

An authenticated browser automation attempt filled the form, but **Hostinger blocked Save with Cloudflare Turnstile / network request failed**. Consequently no cron job was created or activated. The live /blog and /about/kpono-abasi-akpabio endpoints were verified 404 immediately afterward. This document describes a prepared deployment; it is NOT evidence of a successful production rollout.

To activate: from a normal human-signed-in hPanel browser, open Advanced → Cron Jobs, create ONE Custom job with the short command and schedule above, verify it is listed, then check /blog/, /about/kpono-abasi-akpabio/ and /.well-known/almond-cd-status.txt after its first execution. Do not add a second job if one exists.

Safety: The PHP runner never runs git pull in live document root; it creates missing pages and modifies identified live files only through surgical, backed-up patches with explicit guards. Live edits to managed files are protected by recorded checksums. Hostinger manual snapshot from Nov 5 is a separate restore point.
