<?php

foreach(glob('cache/*.html') as $file) {
  if(filemtime($file) < time() - 7*24*60*60 ) // 7 days ago
    @unlink($file);
}

foreach(glob('cache/*.html#*') as $file) {
  if(filemtime($file) < time() - 7*24*60*60 ) // 7 days ago
    @unlink($file);
}
