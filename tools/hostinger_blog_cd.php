<?php
/** Almond Systems: additive, conflict-aware Hostinger deployment for blog content. */
declare(strict_types=1);
ini_set('default_socket_timeout', '25');
date_default_timezone_set('UTC');
$root = '/home/u934246836/domains/almondsystems.com.ng/public_html';
$stateDir = '/home/u934246836/.almondsystems-blog-cd';
$repo = 'kingkpono2/almondsystems.com.ng';
$now = gmdate('c');
$messages = [];
$note = static function (string $message) use (&$messages): void { $messages[] = $message; echo $message . "\n"; };
$download = static function (string $url): ?string {
    $opts = ['http' => ['header' => "User-Agent: AlmondSystems-Hostinger-CD/1.0\r\nAccept: application/vnd.github+json\r\n", 'timeout' => 22, 'ignore_errors' => false]];
    $body = @file_get_contents($url, false, stream_context_create($opts));
    return $body === false ? null : $body;
};
$atomic = static function (string $path, string $content): bool {
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) return false;
    $temp = tempnam($dir, '.almond-cd-');
    if ($temp === false) return false;
    if (file_put_contents($temp, $content, LOCK_EX) !== strlen($content)) { @unlink($temp); return false; }
    @chmod($temp, 0644);
    if (!rename($temp, $path)) { @unlink($temp); return false; }
    return true;
};
if (!is_file($root . '/index.html') || !is_dir($root . '/services')) { fwrite(STDERR, "ABORT: live website not found\n"); exit(1); }
if (!is_dir($stateDir) && !mkdir($stateDir, 0700, true) && !is_dir($stateDir)) exit(1);
$lock = fopen($stateDir . '/lock', 'c+');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { echo "Already running; exit\n"; exit(0); }
$manifestFile = $stateDir . '/manifest.json';
$manifest = is_file($manifestFile) ? json_decode((string)file_get_contents($manifestFile), true) : [];
if (!is_array($manifest)) $manifest = [];
$backupDir = $stateDir . '/backups/' . gmdate('Ymd-His');
$backup = static function (string $rel) use ($root, $backupDir): bool {
    $source = $root . '/' . $rel;
    if (!is_file($source) || is_link($source)) return false;
    $destination = $backupDir . '/' . $rel;
    if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0700, true) && !is_dir(dirname($destination))) return false;
    return copy($source, $destination);
};
$treeUrl = "https://api.github.com/repos/{$repo}/git/trees/main?recursive=1";
$tree = json_decode((string)$download($treeUrl), true);
if (!is_array($tree) || empty($tree['tree']) || !empty($tree['truncated'])) { $note('ERROR: GitHub tree unavailable. No site changes.'); exit(1); }
$paths = [];
foreach ($tree['tree'] as $entry) {
    $path = $entry['path'] ?? '';
    if (($entry['type'] ?? '') === 'blob' && (preg_match('~^blog/(?:[a-z0-9-]+/)?index\\.html$~D', $path) || in_array($path, ['about/kpono-abasi-akpabio/index.html','assets/js/almond-whatsapp-float.js'], true)) && ($entry['size'] ?? 0) < 1024 * 1024) $paths[] = $path;
}
if (!in_array('blog/index.html', $paths, true)) { $note('ERROR: No blog index. No site changes.'); exit(1); }
sort($paths);
if (count($paths) > 101) { $note('ERROR: Too many blog pages. No changes.'); exit(1); }
$sourceMap = [];
foreach ($paths as $path) {
    $content = $download("https://raw.githubusercontent.com/{$repo}/main/{$path}");
    if ($content === null || strlen($content) < 100 || (substr($path, -5) === '.html' ? stripos($content, '<html') === false : strpos($content, 'data-almond-whatsapp') === false)) { $note("ERROR: Cannot download valid {$path}; no changes."); exit(1); }
    if (strncmp($path, 'blog/', 5) === 0 && strpos($content, 'almond-whatsapp-float.js') === false) $content = preg_replace('~</body>~i','<script src="/assets/js/almond-whatsapp-float.js" defer></script></body>',$content,1) ?? $content;
    $sourceMap[$path] = $content;
}
$note('START ' . $now . ', found ' . count($sourceMap) . ' blog pages.');
$changes = 0; $conflicts = 0;
foreach ($sourceMap as $path => $content) {
    $target = $root . '/' . $path;
    if (is_link($target)) { $note('SKIP symbolic link: ' . $path); $conflicts++; continue; }
    $remoteHash = hash('sha256', $content);
    if (is_file($target)) {
        $liveHash = hash_file('sha256', $target);
        $oldHash = $manifest[$path] ?? null;
        if ($liveHash === $remoteHash) { $manifest[$path] = $liveHash; continue; }
        if (!$oldHash || $liveHash !== $oldHash) { $note('PROTECTED live edits: ' . $path); $conflicts++; continue; }
        if (!$backup($path)) { $note('ERROR: backup failed, skip update ' . $path); $conflicts++; continue; }
    } elseif (file_exists($target)) { $note('PROTECTED non-file path: ' . $path); $conflicts++; continue; }
    if ($atomic($target, $content)) { $manifest[$path] = $remoteHash; $note('DEPLOYED: ' . $path); $changes++; }
    else { $note('ERROR writing: ' . $path); $conflicts++; }
}
$patch = static function (string $rel, callable $transform) use ($root, $backup, $atomic, $note, &$changes): void {
    $target = $root . '/' . $rel;
    if (!is_file($target) || is_link($target)) return;
    $old = (string)file_get_contents($target);
    $new = $transform($old);
    if ($old === $new) return;
    if (!$backup($rel)) { $note('ERROR: backup failed, skip patch ' . $rel); return; }
    if ((string)file_get_contents($target) !== $old) { $note('PROTECTED concurrent edits: ' . $rel); return; }
    if ($atomic($target, $new)) { $note('SAFE LINK PATCH: ' . $rel); $changes++; }
};
$blogLink = '<section aria-label="Digital insights" style="padding:24px 16px;margin:32px auto;max-width:1100px"><h2>Digital insights for Nigerian businesses</h2><p>Website design, WordPress, hosting, mobile applications and AI automation advice.</p><a href="/blog/">Read Almond Systems digital guides &rarr;</a></section>';
$patch('index.html', static function (string $html) use ($blogLink): string {
    if (strpos($html, 'href="/blog/"') !== false || substr_count(strtolower($html), '</main>') !== 1) return $html;
    return preg_replace('~</main>~i', $blogLink . "\n</main>", $html, 1) ?? $html;
});
$patch('services/index.html', static function (string $html) use ($blogLink): string {
    if (strpos($html, 'href="/blog/"') !== false || substr_count(strtolower($html), '</main>') !== 1) return $html;
    return preg_replace('~</main>~i', $blogLink . "\n</main>", $html, 1) ?? $html;
});
$patch('sitemap.xml', static function (string $xml) use ($paths): string {
    if (substr_count($xml, '</urlset>') !== 1) return $xml;
    $additions = '';
    foreach ($paths as $path) {
        if (substr($path, -10) !== 'index.html') continue;
        $slug = preg_replace('~index\.html$~', '', $path);
        $url = 'https://almondsystems.com.ng/' . $slug;
        if (strpos($xml, '<loc>' . $url . '</loc>') === false) $additions .= '  <url><loc>' . $url . '</loc><lastmod>' . gmdate('Y-m-d') . "</lastmod></url>\n";
    }
    if ($additions === '') return $xml;
    $updated = str_replace('</urlset>', $additions . '</urlset>', $xml);
    if (function_exists('simplexml_load_string') && @simplexml_load_string($updated) === false) return $xml;
    return $updated;
});

// Non-destructive homepage updates: modify only matching elements, backup first.
$repoHome=$download("https://raw.githubusercontent.com/{$repo}/main/index.html");
$card=static function(string $html,string $heading):?array{
  $p=strpos($html,$heading);if($p===false)return null;
  $a=strrpos(substr($html,0,$p),'<article class="portfolio-card reveal">');
  $b=strpos($html,'</article>',$p);if($a===false||$b===false||$b-$a>2500)return null;
  $b+=strlen('</article>');return [$a,$b,substr($html,$a,$b-$a)];
};
if(is_string($repoHome)){
  $new=$card($repoHome,'<h3>KV Tasks</h3>');
  if($new&&strpos($new[2],'kvtasks.com')!==false)$patch('index.html',static function(string $html)use($card,$new):string{
    if(strpos($html,'<h3>KV Tasks</h3>')!==false)return $html;
    $old=$card($html,'<h3>Lulu Culture Foundation</h3>');
    if(!$old||strpos($old[2],'luluculturefoundation.org')===false)return $html;
    return substr_replace($html,$new[2],$old[0],$old[1]-$old[0]);
  });
}
$patch('index.html',static function(string $html):string{
  if(strpos($html,'href="/about/kpono-abasi-akpabio/"')!==false)return $html;
  $m='<div class="founder-links">';
  return substr_count($html,$m)===1 ? str_replace($m,$m.'<a class="btn btn-light" href="/about/kpono-abasi-akpabio/">Read founder profile</a>',$html) : $html;
});
$float='<script src="/assets/js/almond-whatsapp-float.js" defer></script>';
$marketing=['index.html'];
foreach(['services','products','downloads','tools','domains'] as $dir){
 $d=$root.'/'.$dir;
 if(!is_dir($d)||is_link($d))continue;
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d,FilesystemIterator::SKIP_DOTS));
 foreach($it as $f){
  if(!$f->isFile()||$f->isLink()||$f->getFilename()!=='index.html')continue;
  $rel=substr($f->getPathname(),strlen($root)+1);
  if(substr_count($rel,'/')<=4)$marketing[]=$rel;
 }
}
foreach($marketing as $rel)$patch($rel,static function(string $html)use($float):string{
 if(strpos($html,'almond-whatsapp-float.js')!==false||substr_count(strtolower($html),'</body>')!==1)return $html;
 return preg_replace('~</body>~i',$float."\n</body>",$html,1)??$html;
});

$manifest['_last_successful_check'] = $now;
$atomic($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
$publicStatus = $root . '/.well-known/almond-cd-status.txt';
$atomic($publicStatus, 'Almond Systems blog deployment checked: ' . $now . "\nBlog pages in GitHub: " . count($paths) . "\nFiles updated: {$changes}\nProtected conflicts: {$conflicts}\n");
$note("DONE changes={$changes} protected_conflicts={$conflicts}");
