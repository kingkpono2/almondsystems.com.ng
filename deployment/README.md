# Almond Systems: Hostinger deployment

The live Hostinger site is **authoritative**. Never deploy the full GitHub repository over public_html. Hostinger has manual/automatic backups; the manual snapshot was verified on 2026-11-05.

## Production folder
`/home/u934246836/domains/almondsystems.com.ng/public_html`

## Non-destructive deployment files
- `deployment/hostinger-cron-deploy.sh` fetches only paths listed in `deployment/blog-manifest.txt`. It creates missing blog files and updates only pages for which its previous published checksum matches the live page. If a person edits a published page directly on Hostinger, that page is preserved.
- The publisher appends missing article URLs to the existing sitemap; it never replaces the sitemap with GitHub's version.
- `deployment/update-kvtasks-portfolio.php` replaces only the recognisable Lulu Culture Foundation card with a KV Tasks card from the current GitHub homepage, after backing up the full live index and checking for concurrent changes. It does NOT copy GitHub index.html over Hostinger. Existing layout, articles, hero and services are preserved. A live website screenshot for KV Tasks is shown through the Thum.io screenshot URL.

## One-time / scheduled setup
In Hostinger hPanel → Websites → almondsystems.com.ng → Advanced → Cron Jobs → Custom, choose a scheduled run (15 minutes is generally sufficient). Enter:

```sh
mkdir -p /home/u934246836/almond-blog-ci && curl -fsSLo /home/u934246836/almond-blog-ci/sync.sh https://raw.githubusercontent.com/kingkpono2/almondsystems.com.ng/main/deployment/hostinger-cron-deploy.sh && /bin/bash /home/u934246836/almond-blog-ci/sync.sh >> /home/u934246836/almond-blog-ci/deploy.log 2>&1; curl -fsSLo /home/u934246836/almond-blog-ci/portfolio.php https://raw.githubusercontent.com/kingkpono2/almondsystems.com.ng/main/deployment/update-kvtasks-portfolio.php && /usr/bin/php /home/u934246836/almond-blog-ci/portfolio.php >> /home/u934246836/almond-blog-ci/deploy.log 2>&1
```

This command executes code from the public `main` branch and must be operated only on a repository and hosting account that the owner controls. For stronger supply-chain security in long-term production, pin a protected release tag and review changes before updating it.

## Verification
- `https://almondsystems.com.ng/blog/`
- All five article URLs from `deployment/blog-manifest.txt`
- `https://almondsystems.com.ng/sitemap.xml`
- `https://almondsystems.com.ng/#portfolio` — KV Tasks visible and Lulu removed.
- `/home/u934246836/almond-blog-ci/deploy.log` should log CREATED, PRESERVE, or UPDATED outcomes.

**Status:** This README and the deployment scripts are committed to GitHub. Automatic deployment is NOT active until a Hostinger cron job is created and observed running. Hostinger browser automation was blocked by Cloudflare CAPTCHA on 2026-11-05, so do not claim that cron was installed.
