<?php

require_once(__DIR__ . '/../../config.php');

function micp_file_request(): array {
    $pathinfo = $_SERVER['PATH_INFO'] ?? '';
    if ($pathinfo !== '') {
        $parts = explode('/', trim($pathinfo, '/'));
        $cmid = (int)array_shift($parts);
        $path = implode('/', array_map('rawurldecode', $parts));
        if ($cmid > 0) {
            return [$cmid, $path];
        }
    }

    return [required_param('cmid', PARAM_INT), optional_param('path', '', PARAM_PATH)];
}

function micp_file_storage_path(string $path): array {
    $path = trim($path);
    if ($path === '' || str_starts_with($path, '/') || strpos($path, '..') !== false) {
        throw new moodle_exception('invalidparameter');
    }

    $filename = basename($path);
    $directory = trim(dirname($path), '.');

    if ($directory === '') {
        return ['/0/', $filename];
    }

    return ['/' . trim($directory, '/') . '/', $filename];
}

[$cmid, $path] = micp_file_request();

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'micp');
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/micp:view', $context);

$fs = get_file_storage();
$file = null;
if ($path !== '') {
    [$filepath, $filename] = micp_file_storage_path($path);
    $file = $fs->get_file($context->id, 'mod_micp', 'launchpackage', 0, $filepath, $filename);
    if (!$file && $filepath === '/0/') {
        $file = $fs->get_file($context->id, 'mod_micp', 'launchpackage', 0, '/', $filename);
    }
} else {
    $files = $fs->get_area_files($context->id, 'mod_micp', 'launchpackage', 0, 'filepath, filename', false);
    foreach ($files as $f) {
        if ($f->is_directory()) {
            continue;
        }
        if ($f->get_filepath() === '/0/' && preg_match('/\.html\z/i', $f->get_filename())) {
            $file = $f;
            break;
        }
    }
    if (!$file) {
        foreach ($files as $f) {
            if ($f->is_directory()) {
                continue;
            }
            if (preg_match('/\.html\z/i', $f->get_filename())) {
                $file = $f;
                break;
            }
        }
    }
}

if (!$file) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found in launchpackage for cmid={$cmid}";
    exit;
}

send_stored_file($file, null, 0, false);
