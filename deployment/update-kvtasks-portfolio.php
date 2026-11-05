<?php
// Hostinger safe, single-card replacement: preserves all unrelated live edits.
declare(strict_types=1);
$live='/home/u934246836/domains/almondsystems.com.ng/public_html/index.html';
$state='/home/u934246836/almond-blog-ci/backups';
$url='https://raw.githubusercontent.com/kingkpono2/almondsystems.com.ng/main/index.html';
if (!is_file($live) || is_link($live)) { fwrite(STDERR,"Unsafe site index\n");exit(1); }
$ctx=stream_context_create(['http'=>['timeout'=>35,'follow_location'=>1]]);
$repo=@file_get_contents($url,false,$ctx);
if (!is_string($repo) || strpos($repo,'<h3>KV Tasks</h3>')===false) { fwrite(STDERR,"Could not retrieve current KVTASKS card\n");exit(1); }
$old=file_get_contents($live);
if ($old===false) exit(1);
function card(string $html,string $needle): ?array {
  $p=strpos($html,$needle);
  if ($p===false)return null;
  $start=strrpos(substr($html,0,$p),'<article class="portfolio-card reveal">');
  $end=strpos($html,'</article>',$p);
  if ($start===false || $end===false || $end-$start>2500) return null;
  $end+=strlen('</article>');
  return [$start,$end,substr($html,$start,$end-$start)];
}
$new=card($repo,'<h3>KV Tasks</h3>');
if(!$new || strpos($new[2],'https://kvtasks.com/')===false)exit(1);
if (strpos($old,'<h3>KV Tasks</h3>')!==false) { echo "KV Tasks already present; unchanged\n";exit(0); }
$prior=card($old,'<h3>Lulu Culture Foundation</h3>');
if(!$prior || strpos($prior[2],'luluculturefoundation.org')===false || strpos($prior[2],'lulu-culture.png')===false) {
 echo "Lulu card not in recognized format; preserving live index\n";exit(0);
}
$replacement=substr_replace($old,$new[2],$prior[0],$prior[1]-$prior[0]);
$replacement=str_replace('Screenshots were captured from the live homepages on July 3, 2026 and included directly in the site.','Portfolio previews show real website homepages. The KV Tasks image is captured directly from its public website.',$replacement);
if (!is_dir($state) && !mkdir($state,0700,true))exit(1);
$backup=$state.'/homepage-before-KV-'.gmdate('YmdHis').'.html';
if (file_put_contents($backup,$old,LOCK_EX)!==strlen($old))exit(1);
if(hash_file('sha256',$live)!==hash('sha256',$old)) { echo "Live file changed during update; aborted\n";exit(1); }
$tmp=tempnam(dirname($live),'.almond-');
if(!$tmp || file_put_contents($tmp,$replacement,LOCK_EX)!==strlen($replacement))exit(1);
chmod($tmp,fileperms($live)&0777);
if(!rename($tmp,$live))exit(1);
echo "KV Tasks portfolio card published. Original homepage backed up: $backup\n";
