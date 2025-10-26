<?php

/**
 * File scanner class for recursively scanning directories
 */
class FileScanner
{
    private Config $config;
    private array $scannedFiles;
    private int $totalScanned;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->scannedFiles = [];
        $this->totalScanned = 0;
    }

    /**
     * Scan the configured base path for supported files
     */
    public function scan(): array
    {
        $this->scannedFiles = [];
        $this->totalScanned = 0;

        $basePath = $this->config->getBasePath();
        if (empty($basePath) || !is_dir($basePath)) {
            throw new Exception("Invalid base path: " . $basePath);
        }

        $this->scanDirectory($basePath);

        return [
            'files' => $this->scannedFiles,
            'total_scanned' => $this->totalScanned
        ];
    }

    /**
     * Recursively scan a directory
     */
    private function scanDirectory(string $directory): void
    {
        if ($this->config->shouldIgnorePath($directory)) {
            return;
        }

        try {
            $iterator = new DirectoryIterator($directory);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                }

                $fullPath = $fileInfo->getPathname();

                if ($this->config->shouldIgnorePath($fullPath)) {
                    continue;
                }

                if ($fileInfo->isDir()) {
                    $this->scanDirectory($fullPath);
                } elseif ($fileInfo->isFile()) {
                    $this->processFile($fileInfo);
                }
            }
        } catch (Exception $e) {
            // Log error but continue scanning
            error_log("Error scanning directory {$directory}: " . $e->getMessage());
        }
    }

    /**
     * Process a single file
     */
    private function processFile(SplFileInfo $fileInfo): void
    {
        $filename = $fileInfo->getFilename();
        $fullPath = $fileInfo->getPathname();

        // Check if file extension is supported
        if (!$this->config->isSupportedExtension($filename)) {
            return;
        }

        try {
            $fileSize = $fileInfo->getSize();

            // Check minimum file size
            if ($fileSize < $this->config->getMinFileSize()) {
                return;
            }

            $this->scannedFiles[] = [
                'filename' => $filename,
                'filepath' => $fullPath,
                'filesize' => $fileSize,
                'basename' => pathinfo($filename, PATHINFO_FILENAME),
                'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                'modified_time' => $fileInfo->getMTime()
            ];

            $this->totalScanned++;

        } catch (Exception $e) {
            // Log error but continue scanning
            error_log("Error processing file {$fullPath}: " . $e->getMessage());
        }
    }

    /**
     * Get statistics about the scan
     */
    public function getStats(): array
    {
        $stats = [
            'total_files' => count($this->scannedFiles),
            'total_scanned' => $this->totalScanned,
            'total_size' => 0,
            'extensions' => []
        ];

        foreach ($this->scannedFiles as $file) {
            $stats['total_size'] += $file['filesize'];

            $ext = $file['extension'];
            if (!isset($stats['extensions'][$ext])) {
                $stats['extensions'][$ext] = ['count' => 0, 'size' => 0];
            }
            $stats['extensions'][$ext]['count']++;
            $stats['extensions'][$ext]['size'] += $file['filesize'];
        }

        return $stats;
    }
}
