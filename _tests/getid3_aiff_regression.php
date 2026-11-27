<?php
// Standalone: php _tests/getid3_aiff_regression.php
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (error_reporting() & $level) { throw new ErrorException($message, 0, $level, $file, $line); }
    return false;
});
require dirname(__DIR__) . '/sources_custom/getid3/getid3.php';
$rates = array(
    8000 => '400bfa00000000000000',
    11025 => '400cac44000000000000',
    22050 => '400dac44000000000000',
    32000 => '400dfa00000000000000',
    44100 => '400eac44000000000000',
    48000 => '400ebb80000000000000',
    96000 => '400fbb80000000000000',
);
foreach ($rates as $rate => $encoded) {
    foreach (array(1, 2) as $channels) {
        $samples = str_repeat(chr(0), $rate * $channels * 2);
        $comm = pack('nNn', $channels, $rate, 16) . hex2bin($encoded);
        $ssnd = pack('NN', 0, 0) . $samples;
        $body = 'AIFF' . 'COMM' . pack('N', strlen($comm)) . $comm . 'SSND' . pack('N', strlen($ssnd)) . $ssnd;
        $path = tempnam(sys_get_temp_dir(), 'getid3-aiff-');
        try {
            file_put_contents($path, 'FORM' . pack('N', strlen($body)) . $body);
            $analyzer = new getID3();
            $info = $analyzer->analyze($path);
            if (!empty($info['error']) || ($info['fileformat'] ?? null) !== 'aiff' || ($info['audio']['sample_rate'] ?? null) !== $rate || ($info['audio']['channels'] ?? null) !== $channels || ($info['audio']['bits_per_sample'] ?? null) !== 16 || abs(($info['playtime_seconds'] ?? 0) - 1) > 0.000001) {
                throw new RuntimeException('Unexpected AIFF metadata for ' . $rate . ' Hz / ' . $channels . ' channels: ' . json_encode($info['error'] ?? $info));
            }
        } finally {
            unlink($path);
        }
    }
}
echo "14 AIFF metadata cases passed\n";
