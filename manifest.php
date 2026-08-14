<?php
/** Manifest PWA dinâmico — cada personal tem o app com a própria marca */
require __DIR__ . '/inc/boot.php';
header('Content-Type: application/manifest+json; charset=utf-8');
$m = marca();
$dir = BASE_DIR === '' ? '' : BASE_DIR;
$logo = $m['logo'] ?: 'assets/icone.png';   // logo do personal ou ícone padrão do app

echo json_encode([
    'name' => $m['nome'],
    'short_name' => mb_substr($m['nome'], 0, 12),
    'start_url' => $dir . '/index.php',
    'scope' => $dir . '/',
    'display' => 'standalone',
    'background_color' => '#000000',
    'theme_color' => $m['cor'],
    'orientation' => 'portrait',
    'icons' => [
        ['src' => $dir . '/' . $logo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $dir . '/assets/icone-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
        ['src' => $dir . '/assets/icone.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
