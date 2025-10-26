<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include required classes
require_once 'Config.php';
require_once 'FileScanner.php';
require_once 'DuplicateDetector.php';

/**
 * Main application class
 */
class DuplicateImageDetector
{
    private Config $config;
    private FileScanner $scanner;
    private DuplicateDetector $detector;
    private array $appConfig;

    public function __construct()
    {
        $this->config = new Config();
        $this->scanner = new FileScanner($this->config);
        $this->detector = new DuplicateDetector();
        $this->appConfig = require 'app_config.php';
    }

    /**
     * Handle the main request
     */
    public function handleRequest(): array
    {
        try {
            $action = $_GET['action'] ?? $_POST['action'] ?? 'scan';

            switch ($action) {
                case 'scan':
                    return $this->handleScan();
                case 'test_path':
                    return $this->handleTestPath();
                case 'open_file':
                    return $this->handleOpenFile();
                case 'get_defaults':
                    return $this->handleGetDefaults();
                case 'delete_file':
                    return $this->handleDeleteFile();
                default:
                    throw new Exception("Invalid action: " . $action);
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Handle scan request
     */
    private function handleScan(): array
    {
        // Get configuration from request
        $basePath = $_POST['base_path'] ?? '';
        $ignoredPaths = $_POST['ignored_paths'] ?? [];

        // Parse ignored paths if it's a string
        if (is_string($ignoredPaths)) {
            $ignoredPaths = array_filter(array_map('trim', explode("\n", $ignoredPaths)));
        }

        // Configure the system
        $this->config->setBasePath($basePath);
        $this->config->setIgnoredPaths($ignoredPaths);

        // Validate configuration
        $validationErrors = $this->config->validate();
        if (!empty($validationErrors)) {
            throw new Exception("Configuration errors: " . implode(', ', $validationErrors));
        }

        // Start timing
        $startTime = microtime(true);

        // Scan for files
        $scanResult = $this->scanner->scan();
        $scanStats = $this->scanner->getStats();

        // Detect duplicates
        $duplicateResult = $this->detector->analyze($scanResult['files']);

        // Calculate execution time
        $executionTime = microtime(true) - $startTime;

        return [
            'success' => true,
            'scan_stats' => $scanStats,
            'duplicates' => $duplicateResult,
            'execution_time' => round($executionTime, 2),
            'timestamp' => date('Y-m-d H:i:s'),
            'config' => [
                'base_path' => $this->config->getBasePath(),
                'ignored_paths' => $this->config->getIgnoredPaths(),
                'min_file_size' => $this->config->getMinFileSize(),
                'supported_extensions' => $this->config->getSupportedExtensions()
            ]
        ];
    }

    /**
     * Handle path testing request
     */
    private function handleTestPath(): array
    {
        $testPath = $_POST['test_path'] ?? '';

        if (empty($testPath)) {
            throw new Exception("Test path is required");
        }

        $result = [
            'path' => $testPath,
            'exists' => file_exists($testPath),
            'is_directory' => is_dir($testPath),
            'is_readable' => is_readable($testPath),
            'is_writable' => is_writable($testPath)
        ];

        if ($result['is_directory'] && $result['is_readable']) {
            try {
                $iterator = new DirectoryIterator($testPath);
                $fileCount = 0;
                $dirCount = 0;

                foreach ($iterator as $fileInfo) {
                    if ($fileInfo->isDot()) continue;

                    if ($fileInfo->isDir()) {
                        $dirCount++;
                    } else {
                        $fileCount++;
                    }

                    // Limit counting for performance
                    if ($fileCount + $dirCount > 1000) {
                        break;
                    }
                }

                $result['file_count'] = $fileCount;
                $result['dir_count'] = $dirCount;
                $result['sample_accessible'] = true;

            } catch (Exception $e) {
                $result['sample_accessible'] = false;
                $result['error'] = $e->getMessage();
            }
        }

        return [
            'success' => true,
            'result' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Handle opening file in appropriate viewer (Xviewer for images, Celluloid for videos)
     */
    private function handleOpenFile(): array
    {
        $filePath = $_POST['file_path'] ?? '';

        if (empty($filePath)) {
            throw new Exception("File path is required");
        }

        if (!file_exists($filePath)) {
            throw new Exception("File does not exist: " . $filePath);
        }

        if (!is_readable($filePath)) {
            throw new Exception("File is not readable: " . $filePath);
        }

        // Escape the file path for shell execution
        $escapedPath = escapeshellarg($filePath);

        // Get viewer settings from configuration
        $viewerConfig = $this->appConfig['viewers'] ?? [];
        $imageViewer = $viewerConfig['image_viewer'] ?? 'xviewer';
        $videoViewer = $viewerConfig['video_viewer'] ?? 'celluloid';
        $videoExtensions = $viewerConfig['video_extensions'] ?? ['mp4'];

        // Determine file type and appropriate viewer
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, $videoExtensions)) {
            // Open video files with configured video viewer
            $command = "{$videoViewer} {$escapedPath} > /dev/null 2>&1 &";
            $viewer = ucfirst($videoViewer);
        } else {
            // Open image files with configured image viewer
            $command = "{$imageViewer} {$escapedPath} > /dev/null 2>&1 &";
            $viewer = ucfirst($imageViewer);
        }

        exec($command, $output, $returnCode);

        return [
            'success' => true,
            'message' => "Opened file in {$viewer}",
            'file' => $filePath,
            'viewer' => $viewer,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Handle getting default configuration values
     */
    private function handleGetDefaults(): array
    {
        $defaultPaths = $this->appConfig['default_paths'] ?? [];

        return [
            'success' => true,
            'defaults' => [
                'remote_drive_path' => $defaultPaths['default_remote_drive_path'] ?? '',
                'paths_to_ignore' => implode("\n", $defaultPaths['default_paths_to_ignore'] ?? [])
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Handle file deletion request
     */
    private function handleDeleteFile(): array
    {
        $filePath = $_POST['file_path'] ?? '';

        if (empty($filePath)) {
            throw new Exception("File path is required");
        }

        if (!file_exists($filePath)) {
            throw new Exception("File does not exist: " . $filePath);
        }

        if (!is_writable(dirname($filePath))) {
            throw new Exception("Directory is not writable: " . dirname($filePath));
        }

        // Attempt to delete the file
        if (unlink($filePath)) {
            return [
                'success' => true,
                'message' => "File deleted successfully",
                'file' => $filePath,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            throw new Exception("Failed to delete file: " . $filePath);
        }
    }
}

// Initialize and run the application
try {
    $app = new DuplicateImageDetector();
    $result = $app->handleRequest();
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
