<?php
declare(strict_types=1);

function failVideo(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    exit($message);
}

function downloadText(string $url): string|false
{
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'ZeniteOfertas-HeroVideo/1.0',
            CURLOPT_HTTPHEADER => ['Accept: text/plain'],
        ]);

        $content = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return is_string($content) && $status >= 200 && $status < 300 ? $content : false;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'follow_location' => 1,
            'header' => "User-Agent: ZeniteOfertas-HeroVideo/1.0\r\nAccept: text/plain\r\n",
        ],
    ]);

    $content = @file_get_contents($url, false, $context);
    return is_string($content) ? $content : false;
}

function loadVideoBytes(): string
{
    $physicalVideo = __DIR__ . '/hero-video.mp4';
    if (is_file($physicalVideo) && filesize($physicalVideo) > 100000) {
        $bytes = file_get_contents($physicalVideo);
        if (is_string($bytes) && substr($bytes, 4, 4) === 'ftyp') {
            return $bytes;
        }
    }

    $encoded = '';

    for ($index = 1; $index <= 10; $index++) {
        $name = sprintf('hero-upload-%02d.b64', $index);
        $localPath = __DIR__ . '/' . $name;
        $content = is_file($localPath) ? file_get_contents($localPath) : false;

        if (!is_string($content) || trim($content) === '') {
            $remoteUrls = [
                'https://raw.githubusercontent.com/RuanMarcos38/Zenit-ofertas/main/assets/' . $name,
                'https://cdn.jsdelivr.net/gh/RuanMarcos38/Zenit-ofertas@main/assets/' . $name,
            ];

            foreach ($remoteUrls as $url) {
                $content = downloadText($url);
                if (is_string($content) && trim($content) !== '') {
                    break;
                }
            }
        }

        if (!is_string($content) || trim($content) === '') {
            failVideo(503, 'Não foi possível carregar o vídeo da Hero.');
        }

        $encoded .= preg_replace('/\s+/', '', $content) ?? '';
    }

    $video = base64_decode($encoded, true);
    if (!is_string($video) || strlen($video) < 100000 || substr($video, 4, 4) !== 'ftyp') {
        failVideo(500, 'O arquivo de vídeo reconstruído é inválido.');
    }

    return $video;
}

$video = loadVideoBytes();
$size = strlen($video);
$start = 0;
$end = $size - 1;
$status = 200;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/i', $_SERVER['HTTP_RANGE'], $match)) {
    $requestedStart = $match[1] !== '' ? (int) $match[1] : null;
    $requestedEnd = $match[2] !== '' ? (int) $match[2] : null;

    if ($requestedStart === null && $requestedEnd !== null) {
        $length = min($requestedEnd, $size);
        $start = $size - $length;
    } elseif ($requestedStart !== null) {
        $start = $requestedStart;
        if ($requestedEnd !== null) {
            $end = min($requestedEnd, $end);
        }
    }

    if ($start < 0 || $start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        failVideo(416, 'Faixa de bytes inválida.');
    }

    $status = 206;
}

$length = $end - $start + 1;
http_response_code($status);
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('Content-Length: ' . $length);
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');

if ($status === 206) {
    header(sprintf('Content-Range: bytes %d-%d/%d', $start, $end, $size));
}

echo substr($video, $start, $length);
