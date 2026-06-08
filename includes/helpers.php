<?php

/**
 * Utilitários Fix Now: CPF, notificações e caminhos públicos de upload.
 */

function fixnow_only_digits(string $s): string
{
    return preg_replace('/\D+/', '', $s);
}

function fixnow_validar_cpf(?string $cpf): bool
{
    $d = fixnow_only_digits((string)$cpf);
    if (strlen($d) !== 11 || preg_match('/^(\d)\1{10}$/', $d)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $s = 0;
        for ($c = 0; $c < $t; $c++) {
            $s += (int)$d[$c] * (($t + 1) - $c);
        }
        $r = ((10 * $s) % 11) % 10;
        if ((int)$d[$t] !== $r) {
            return false;
        }
    }
    return true;
}

function fixnow_notificar_cliente(PDO $pdo, int $clienteId, string $mensagem, ?int $chamadoId = null): void
{
    $pdo->prepare('INSERT INTO notificacao (tipo_destinatario, cliente_id, chamado_id, mensagem) VALUES (\'cliente\', ?, ?, ?)')
        ->execute([$clienteId, $chamadoId, $mensagem]);
}

function fixnow_notificar_prestador(PDO $pdo, int $tecnicoId, string $mensagem, ?int $chamadoId = null): void
{
    $pdo->prepare('INSERT INTO notificacao (tipo_destinatario, tecnico_id, chamado_id, mensagem) VALUES (\'prestador\', ?, ?, ?)')
        ->execute([$tecnicoId, $chamadoId, $mensagem]);
}

function fixnow_notificar_admin(PDO $pdo, string $mensagem): void
{
    $pdo->prepare('INSERT INTO notificacao (tipo_destinatario, mensagem) VALUES (\'admin\', ?)')
        ->execute([$mensagem]);
}

function fixnow_public_upload_dir(): string
{
    return dirname(__DIR__) . '/assets/img/uploads/';
}

function fixnow_public_perfil_dir(): string
{
    return dirname(__DIR__) . '/assets/img/perfil/';
}

function fixnow_web_path_from_fs(string $absolutePath): string
{
    $root = realpath(dirname(__DIR__));
    $abs = realpath($absolutePath);
    if ($root === false || $abs === false) {
        return '';
    }
    $rel = str_replace('\\', '/', substr($abs, strlen($root) + 1));
    return $rel;
}

function fixnow_upload_file(array $file, string $targetDir, string $prefix): ?array
{
    if (empty($file['tmp_name']) || empty($file['name']) || ($file['error'] ?? 4) !== 0) {
        return null;
    }
    $permitidos = [
        'image/jpeg'  => 'jpg', 'image/jpg'   => 'jpg', 'image/pjpeg' => 'jpg',
        'image/png'   => 'png', 'image/x-png' => 'png',
        'image/webp'  => 'webp',
        'image/gif'   => 'gif',
        'application/pdf' => 'pdf',
        'application/zip'            => 'zip',
        'application/x-zip'          => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
    ];
    $tipo = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!isset($permitidos[$tipo])) {
        // fallback: magic bytes
        $bytes = @file_get_contents($file['tmp_name'], false, null, 0, 12);
        if ($bytes !== false) {
            if (substr($bytes, 0, 3) === "\xFF\xD8\xFF")          $tipo = 'image/jpeg';
            elseif (substr($bytes, 0, 8) === "\x89PNG\r\n\x1A\n") $tipo = 'image/png';
            elseif (substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP') $tipo = 'image/webp';
            elseif (substr($bytes, 0, 4) === '%PDF')               $tipo = 'application/pdf';
            elseif (substr($bytes, 0, 2) === 'PK')                 $tipo = 'application/zip';
        }
        if (!isset($permitidos[$tipo])) {
            return null;
        }
    }
    if ($file['size'] > 10 * 1024 * 1024) { // 10 MB máx
        return null;
    }
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $ext     = $permitidos[$tipo];
    $nomeArq = $prefix . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $dest    = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $nomeArq;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    $webPath = fixnow_web_path_from_fs($dest);
    return $webPath !== '' ? ['path' => $webPath, 'nome' => basename($file['name'])] : null;
}

function fixnow_upload_image(array $file, string $targetDir, string $prefix): ?string
{
    if (empty($file['tmp_name']) || empty($file['name'])) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $permitidas = [
        'image/jpeg'  => 'jpg',
        'image/jpg'   => 'jpg',   // alias não-padrão retornado por alguns sistemas Windows
        'image/pjpeg' => 'jpg',   // JPEG progressivo (IE/Edge antigo)
        'image/png'   => 'png',
        'image/x-png' => 'png',
        'image/webp'  => 'webp',
    ];
    $tipo = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    // fallback: verificar pelo cabeçalho dos bytes se mime não foi reconhecido
    if (!isset($permitidas[$tipo]) && !empty($file['tmp_name'])) {
        $bytes = @file_get_contents($file['tmp_name'], false, null, 0, 12);
        if ($bytes !== false) {
            if (substr($bytes, 0, 3) === "\xFF\xD8\xFF")          $tipo = 'image/jpeg';
            elseif (substr($bytes, 0, 8) === "\x89PNG\r\n\x1A\n") $tipo = 'image/png';
            elseif (substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP') $tipo = 'image/webp';
        }
    }
    if (!isset($permitidas[$tipo])) {
        return null;
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $ext = $permitidas[$tipo];
    $nomeArq = $prefix . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $nomeArq;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    $webPath = fixnow_web_path_from_fs($dest);
    return $webPath !== '' ? $webPath : null;
}
