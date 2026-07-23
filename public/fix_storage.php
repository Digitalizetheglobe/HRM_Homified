<?php
// ⚠️ DELETE THIS FILE IMMEDIATELY AFTER USE — SECURITY RISK IF LEFT ON SERVER

$target = dirname(__DIR__) . '/storage/app/public';
$link   = __DIR__ . '/storage';

echo "<pre>";
echo "Target : $target\n";
echo "Link   : $link\n\n";

if (!is_dir($target)) {
    echo "❌ ERROR: Target directory does not exist: $target\n";
    echo "Make sure your storage/app/public folder exists on the server.\n";
} elseif (file_exists($link) || is_link($link)) {
    if (is_link($link)) {
        echo "ℹ️ Symlink already exists and points to: " . readlink($link) . "\n";
    } else {
        echo "⚠️ A real folder called 'storage' already exists in public/. Cannot create symlink.\n";
    }
} else {
    if (symlink($target, $link)) {
        echo "✅ Storage symlink created successfully!\n";
        echo "Your files at /storage/... URLs will now work.\n";
    } else {
        echo "❌ symlink() failed. Your host may not allow symlinks.\n\n";
        echo "--- FALLBACK: Copying files instead ---\n";
        // Fallback: copy files from storage/app/public into public/storage
        if (!is_dir($link)) {
            mkdir($link, 0755, true);
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $copied = 0;
        foreach ($iterator as $item) {
            $destPath = $link . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($destPath)) mkdir($destPath, 0755, true);
            } else {
                copy($item->getRealPath(), $destPath);
                $copied++;
            }
        }
        echo "✅ Fallback complete: Copied $copied files to public/storage/\n";
        echo "⚠️ NOTE: Future uploads won't auto-copy. Run this script again after new uploads.\n";
    }
}
echo "</pre>";
