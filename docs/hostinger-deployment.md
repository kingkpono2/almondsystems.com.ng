# Almond Systems: safe Hostinger blog deployment

## Production safety policy
- The LIVE production website at https://almondsystems.com.ng/ is authoritative for all existing root and service files.
- Never point Hostinger's Git integration at the website root (`public_html`) unless a separately verified full live-to-GitHub synchronization is complete.
- A Hostinger full files/database backup was successfully created November 5, 2026 via hPanel Backups, before blog publication.
- GitHub `backup/github-before-live-sync-2026-11-05` preserves the earlier GitHub main snapshot.
- SSH was enabled on hPanel, but the ChatGPT execution environment could not connect to the SSH port. Avoid storing SSH passwords and private keys in this repo.

## Isolated, blog-only continuous deployment
This is designed to publish new posts without touching the currently live homepage, service pages, assets, or configuration.

1. In Hostinger hPanel for `almondsystems.com.ng`, open **Advanced → Git**.
2. Connect GitHub via OAuth and authorize only `kingkpono2/almondsystems.com.ng` if prompted.
3. Select repository `kingkpono2/almondsystems.com.ng`.
4. **Branch:** `deploy/almond-blog` (**NOT** `main`).
5. **Root directory:** `public_html/blog` (**NOT** `public_html`). This directory was absent at pre-deployment inspection; check again before the first deploy.
6. Confirm all four settings and press **Deploy**. Turn **Auto-deployment ON**.
7. Verify live URLs:
   - https://almondsystems.com.ng/blog/
   - https://almondsystems.com.ng/blog/website-design-uyo-business-guide/
   - https://almondsystems.com.ng/blog/wordpress-website-maintenance-nigeria/
   - https://almondsystems.com.ng/blog/web-hosting-nigeria-buying-guide/
   - https://almondsystems.com.ng/blog/android-ios-app-development-uyo/
   - https://almondsystems.com.ng/blog/ai-automation-solutions-nigeria-small-business/

The isolated deployment branch has the contents of `main:blog/` at its repository root. A GitHub Actions workflow `.github/workflows/publish-blog-branch.yml` mirrors future changes from the `main` branch's `blog/` directory into `deploy/almond-blog`. The Hostinger Git integration auto-deploys that branch to `public_html/blog`. This makes editing or adding an article in `main:blog/` sufficient for subsequent publishing, once OAuth and Hostinger auto-deployment are enabled. Verify the Actions job and Hostinger deploy logs on first run.

## Important non-automated steps
The existing production `index.html`, `services/index.html`, `sitemap.xml`, and `.htaccess` MUST NOT be replaced with GitHub versions until the latest live files have been downloaded, reviewed, merged, tested and backed up. The blog-only integration will NOT publish the newer homepage SEO edits or sitemap links in main. Merge and publish these separately and safely, preserving any live-only edits. For Google indexing, the blog can be discovered via direct links and manual sitemap addition after the live sitemap is reviewed.

## Recovery
In hPanel, Advanced → Git, turn off Auto-deployment if needed and consult **All deployments** for previous revisions. In hPanel Backups, restore the correct backup only after reviewing its scope and possible changes made since it was created.
