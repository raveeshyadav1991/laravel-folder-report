<?php

namespace Raveesh\FolderReport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FolderReportController
{
    public function index(Request $request)
    {
        $relativePath = $request->get('path', 'storage');
        $basePath = base_path($relativePath);

        if (!File::exists($basePath)) {
            return response("❌ Path does not exist: {$relativePath}", 404);
        }

        $data = $this->scanFolders($basePath);
        $html = $this->generateHtmlReport($data, $basePath, $relativePath);

        return response($html);
    }

    protected function scanFolders($path)
    {
        $folders = File::directories($path);
        $result = [];

        foreach ($folders as $folder) {
            $size = $this->getFolderSize($folder);
            $result[] = [
                'name' => basename($folder),
                'path' => $folder,
                'size' => $size,
                'subfolders' => $this->scanFolders($folder),
            ];
        }

        return $result;
    }

    protected function getFolderSize($path)
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    protected function generateHtmlReport($data, $basePath, $relativePath)
    {
        $treeHtml = $this->renderTree($data);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Folder Size Report</title>
<style>
body { font-family: Arial, sans-serif; background: #f7f7f7; color: #333; margin: 20px; }
h1 { color: #444; }
ul { list-style: none; padding-left: 20px; }
li { margin: 5px 0; }
.folder { font-weight: bold; }
.size { color: #008000; font-size: 0.9em; }
details { margin: 4px 0; background: #fff; border-radius: 5px; padding: 8px; box-shadow: 0 0 3px rgba(0,0,0,0.1); }
summary { cursor: pointer; font-size: 1em; }
summary:hover { color: #007bff; }
input[type=text] { padding: 8px; width: 300px; border-radius: 4px; border: 1px solid #ccc; }
button { padding: 8px 12px; border: none; background: #007bff; color: white; border-radius: 4px; cursor: pointer; }
button:hover { background: #0056b3; }
</style>
</head>
<body>
<h1>📁 Folder Size Report</h1>
<form method="get" action="">
  <label><strong>Path (relative to project root):</strong></label>
  <input type="text" name="path" value="{$relativePath}">
  <button type="submit">View</button>
</form>
<p><strong>Absolute Path:</strong> {$basePath}</p>
<hr>
{$treeHtml}
</body>
</html>
HTML;
    }

    protected function renderTree($folders)
    {
        $html = '<ul>';
        foreach ($folders as $folder) {
            $size = $this->formatBytes($folder['size']);
            $html .= "<li><details open><summary>📂 <span class='folder'>{$folder['name']}</span> — <span class='size'>{$size}</span></summary>";
            if (!empty($folder['subfolders'])) {
                $html .= $this->renderTree($folder['subfolders']);
            }
            $html .= '</details></li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
