#!/usr/bin/env bash
# Hostinger cron: additive blog deployment. Live website is source of truth.
set -Eeuo pipefail
umask 077
ROOT=/home/u934246836/domains/almondsystems.com.ng/public_html
STATE=/home/u934246836/almond-blog-ci
RAW=https://raw.githubusercontent.com/kingkpono2/almondsystems.com.ng/main
[[ -f "$ROOT/index.html" && -d "$ROOT/services" && ! -L "$ROOT" ]] || exit 2
mkdir -p "$STATE/hashes" "$STATE/backups"
mkdir "$STATE/lock" 2>/dev/null || exit 0
TMP=$(mktemp -d "$STATE/stage.XXXXXX")
trap 'rm -rf "$TMP"; rmdir "$STATE/lock"' EXIT
get() { curl --fail --silent --show-error --location --connect-timeout 10 --max-time 70 "$RAW/$1" --output "$2"; test -s "$2"; }
get deployment/blog-manifest.txt "$TMP/manifest"
COUNT=0
while IFS= read -r P || [[ -n "$P" ]]; do
 [[ -z "$P" || "$P" == \#* ]] && continue
 [[ "$P" == blog/index.html || "$P" =~ ^blog/[a-z0-9-]+/index\.html$ ]] || exit 3
 mkdir -p "$TMP/$(dirname "$P")"
 get "$P" "$TMP/$P"
 grep -Eiq '<(html|!doctype[[:space:]]+html)[ >]' "$TMP/$P" || exit 4
 COUNT=$((COUNT+1))
done < "$TMP/manifest"
[[ "$COUNT" -gt 0 && "$COUNT" -le 30 ]] || exit 5
# All remote content downloaded and checked before writing.
[[ ! -L "$ROOT/blog" ]] || exit 6
while IFS= read -r P || [[ -n "$P" ]]; do
 [[ -z "$P" || "$P" == \#* ]] && continue
 D="$ROOT/$P"; SRC="$TMP/$P"; DIR=$(dirname "$D")
 HASH="$STATE/hashes/${P//\//_}.sha"
 [[ ! -L "$DIR" && ! -L "$D" ]] || continue
 if [[ ! -e "$D" ]]; then
   mkdir -p "$DIR"
   if (set -C; cat "$SRC" > "$D") 2>/dev/null; then
     chmod 644 "$D"
     sha256sum "$D" | cut -d' ' -f1 > "$HASH"
     echo "CREATED $P"
   fi
 elif [[ -f "$D" ]]; then
   CURRENT=$(sha256sum "$D" | cut -d' ' -f1)
   WANTED=$(sha256sum "$SRC" | cut -d' ' -f1)
   if [[ "$CURRENT" == "$WANTED" ]]; then
     echo "$CURRENT" > "$HASH"
   elif [[ -f "$HASH" && "$(cat "$HASH")" == "$CURRENT" ]]; then
     echo "UPDATE-MANAGED $P" >> "$TMP/actions"
   else
     echo "PRESERVE live-modified $P"
   fi
 fi
done < "$TMP/manifest"
if [[ -f "$TMP/actions" ]]; then
  # Backup before updating previously managed article files.
  BACKUP="$STATE/backups/blog-$(date -u +%Y%m%dT%H%M%SZ).tar.gz"
  tar -czf "$BACKUP" -C "$ROOT" blog
  [[ -s "$BACKUP" ]] || exit 7
  while read -r OP P; do
    D="$ROOT/$P"; SRC="$TMP/$P"; HASH="$STATE/hashes/${P//\//_}.sha"
    [[ -f "$D" && -f "$HASH" && ! -L "$D" && ! -L "$(dirname "$D")" ]] || continue
    [[ "$(sha256sum "$D" | cut -d' ' -f1)" == "$(cat "$HASH")" ]] || continue
    T=$(mktemp "$(dirname "$D")/.almond.XXXXXX")
    cp "$SRC" "$T"; chmod 644 "$T"; mv "$T" "$D"
    sha256sum "$D" | cut -d' ' -f1 > "$HASH"
    echo "UPDATED $P"
  done < "$TMP/actions"
fi
# Sitemap: add missing blog URLs; preserve existing XML entries and backup original.
SITEMAP="$ROOT/sitemap.xml"
if [[ -f "$SITEMAP" && ! -L "$SITEMAP" ]]; then
  cp -p "$SITEMAP" "$TMP/sitemap.original"
  php -r '
  $p=$argv[1];$manifest=$argv[2];$s=file_get_contents($p);
  if ($s===false || substr_count($s,"</urlset>")!==1) exit(0);
  $add="";
  foreach(file($manifest,FILE_IGNORE_NEW_LINES) as $f){
    $f=trim($f);
    if(!preg_match("~^blog/(?:[a-z0-9-]+/)?index\\.html$~",$f)) continue;
    $u="https://almondsystems.com.ng/".substr($f,0,-strlen("index.html"));
    if(strpos($s,"<loc>".$u."</loc>")===false)
       $add.="<url><loc>".$u."</loc><lastmod>".gmdate("Y-m-d")."</lastmod></url>\n";
  }
  if(!$add) exit(0);
  $n=str_replace("</urlset>",$add."</urlset>",$s);
  if(class_exists("DOMDocument")){$d=new DOMDocument();if(!@$d->loadXML($n))exit(1);}
  $t=tempnam(dirname($p),".almond.");
  if(file_put_contents($t,$n)===false)exit(1);
  chmod($t,fileperms($p)&0777);
  if(!rename($t,$p))exit(1);
  echo "Sitemap updated\n";
  ' "$SITEMAP" "$TMP/manifest" || true
fi
echo "Safe blog sync finished"
