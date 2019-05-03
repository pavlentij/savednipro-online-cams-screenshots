<?php
/**
 * Project: savednipro.org
 * Author: Pavel Tkachenko
 * Date: 2/18/19
 */

//exit;

/*

ffmpeg -y -i http://cam.dnepr.com/hls/num4.m3u8 -f image2 -vframes 1 /var/www/savednipro.org/public/snapshot.jpg
cp /var/www/savednipro.org/public/snapshot.jpg /var/www/savednipro.org/public/snapshot_`date +"%d_%H-%M"`.jpg
/usr/local/bin/gdrive upload -p "1XmramUKcyGzjHS4bNv7l_KZ6MkdCXBY4" /var/www/savednipro.org/public/snapshot_`date +"%d_%H-%M"`.jpg
rm /var/www/savednipro.org/public/snapshot_`date +"%d_%H-%M"`.jpg

ffmpeg -y -i http://cam.dnepr.com/hls/num15.m3u8 -f image2 -vframes 1 /var/www/savednipro.org/public/snapshot.jpg
cp /var/www/savednipro.org/public/snapshot.jpg /var/www/savednipro.org/public/snapshot_`date +"%d_%H-%M"`.jpg
/usr/local/bin/gdrive upload -p "1wENuFwgebS3DLT_bZHmGygPOop-uOEx4" /var/www/savednipro.org/public/snapshot_`date +"%d_%H-%M"`.jpg
rm /var/www/savednipro.org/public/snapshot_`date +"%d_%H-%M"`.jpg


*/

$webcams = [
    [
        'url' => 'http://cam.dnepr.com/hls/num25.m3u8',
        'id' => '1-aH3AOzyDGWLQz7j-t_MoG_8bnOxY9TS'
    ],
    [
        'url' => 'http://cam.dnepr.com/hls/num4.m3u8',
        'id' => '1JcupuPaxdcmIuLRr2EdfaDnA8D0IbcWA',
    ],
    [
        'url' => 'http://cam.dnepr.com/hls/num15.m3u8',
        'id' => '1Bfl_yUVHbxUqGT9U3VkfTB4tgiLYEQRZ',
    ],
];

$now = strtotime('+2 hours');

foreach ($webcams as $webcam) {
    // Check that the path is exist
    $path = explode('/', date('Y/m/d', $now));

    $parent_id = $webcam['id'];

    $is_all = true;

    foreach ($path as $subdirectory) {
        sleep(1);
        $result = shell_exec('/usr/local/bin/gdrive list --query " \'' . $parent_id . '\' in parents"');

        if (stripos($result, 'error') !== false) {
            $is_all = false;
            break;
        }

        $result = explode("\n", $result);

        $is_found = false;
        foreach ($result as $i => $dir) {
            $dir = preg_replace('!\s+!', ' ', trim($dir));
            $result[$i] = $dir;

            $dir = explode(' ', $dir);
            if ($dir && isset($dir[2]) && $dir[2] == 'dir' && $dir[1] == $subdirectory) {
                $is_found = true;
                $parent_id = $dir[0];
                break;
            }
        }

        // if not found the create new dir
        if (!$is_found) {
            sleep(1);
            $result = shell_exec('/usr/local/bin/gdrive mkdir "' . $subdirectory . '" -p \'' . $parent_id . '\'');
            $result = explode(' ', $result);
            $parent_id = $result[1];
        }
    }

    // download snapshot and upload it
    if ($is_all && $parent_id) {
        $snapshot_file = '/var/www/savednipro.org/public/snapshot_' . date('Y-m-d_H-i_s', $now) . '.jpg';

        $commands = [
            'ffmpeg -y -i ' . $webcam['url'] . ' -f image2 -vframes 1 /var/www/savednipro.org/public/snapshot.jpg',
            'cp /var/www/savednipro.org/public/snapshot.jpg ' . $snapshot_file,
            '/usr/local/bin/gdrive upload -p "' . $parent_id . '" ' . $snapshot_file,
            'rm ' . $snapshot_file,
        ];

        foreach ($commands as $command) {
            sleep(1);
            shell_exec($command);
        }
    }
}