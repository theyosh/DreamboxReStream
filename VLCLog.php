<?php
header('Content-type: text/plain');
header('Content-Disposition: attachment; filename="VLCLog.txt"');
echo @file_get_contents('/tmp/restream.vlc.log.txt');
echo @file_get_contents('/tmp/restream.vlc.log.txt.1');
echo @file_get_contents('/tmp/restream.vlc.log.txt.2');
exit;