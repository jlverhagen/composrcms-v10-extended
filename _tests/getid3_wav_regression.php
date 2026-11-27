<?php
// Standalone: php _tests/getid3_wav_regression.php
error_reporting(E_ALL);
set_error_handler(function ($level, $message, $file, $line) {
    if (error_reporting() & $level) { throw new ErrorException($message, 0, $level, $file, $line); }
    return false;
});
require dirname(__DIR__) . '/sources_custom/getid3/getid3.php';
foreach (array(array(8000, 1, 8), array(8000, 1, 16), array(44100, 2, 16)) as $case) {
    list($rate, $channels, $bits) = $case;
    $sample_bytes = (int) ($bits / 8);
    $block = $channels * $sample_bytes;
    $samples = str_repeat(chr($bits === 8 ? 128 : 0), $rate * $block);
    $format = pack('vvVVvv', 1, $channels, $rate, $rate * $block, $block, $bits);
    $body = 'WAVEfmt ' . pack('V', strlen($format)) . $format . 'data' . pack('V', strlen($samples)) . $samples;
    $path = tempnam(sys_get_temp_dir(), 'getid3-wav-');
    try {
        file_put_contents($path, 'RIFF' . pack('V', strlen($body)) . $body);
        $analyzer = new getID3();
        $info = $analyzer->analyze($path);
        if (!empty($info['error']) || ($info['fileformat'] ?? null) !== 'wav' || ($info['audio']['sample_rate'] ?? null) !== $rate || ($info['audio']['channels'] ?? null) !== $channels || ($info['audio']['bits_per_sample'] ?? null) !== $bits || abs(($info['playtime_seconds'] ?? 0) - 1) > 0.000001) {
            throw new RuntimeException('Unexpected WAV metadata: ' . json_encode($info['error'] ?? $info));
        }
    } finally { unlink($path); }
}
foreach (array(array(null, 'café.wav'), array('', 'café.wav'), array('.wav', 'café')) as $case) {
    if (getid3_lib::mb_basename('/tmp/café.wav', $case[0]) !== $case[1]) { throw new RuntimeException('Unexpected basename behavior'); }
}
echo "3 WAV metadata and 3 basename cases passed\n";
