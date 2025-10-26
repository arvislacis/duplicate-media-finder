<?php

/**
 * Configuration class for duplicate image/video detection system
 */
class Config
{
    /** @var string The root path to scan for duplicates */
    private string $basePath;

    /** @var array Paths to ignore during scanning */
    private array $ignoredPaths;

    /** @var array Supported file extensions */
    private array $supportedExtensions;

    /** @var int Minimum file size in bytes (100KB) */
    private int $minFileSize;

    public function __construct()
    {
        $this->basePath = '';
        $this->ignoredPaths = [];
        
        // Load configuration from app_config.php
        $appConfig = $this->loadAppConfig();
        $this->supportedExtensions = $appConfig['detector']['supported_extensions'] ?? ['jpg', 'jpeg', 'png', 'mp4'];
        $this->minFileSize = $appConfig['detector']['min_file_size'] ?? 102400; // 100KB in bytes
    }

    /**
     * Load application configuration from app_config.php
     */
    private function loadAppConfig(): array
    {
        $configPath = dirname(__FILE__) . '/app_config.php';
        if (!file_exists($configPath)) {
            return ['detector' => ['supported_extensions' => ['jpg', 'jpeg', 'png', 'mp4'], 'min_file_size' => 102400]];
        }
        return require $configPath;
    }

    /**
     * Set the base path to scan
     */
    public function setBasePath(string $path): void
    {
        $this->basePath = rtrim($path, '/\\');
    }

    /**
     * Get the base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set paths to ignore
     */
    public function setIgnoredPaths(array $paths): void
    {
        $this->ignoredPaths = array_map(function($path) {
            return rtrim($path, '/\\');
        }, $paths);
    }

    /**
     * Get ignored paths
     */
    public function getIgnoredPaths(): array
    {
        return $this->ignoredPaths;
    }

    /**
     * Check if a path should be ignored
     */
    public function shouldIgnorePath(string $path): bool
    {
        $normalizedPath = rtrim($path, '/\\');

        foreach ($this->ignoredPaths as $ignoredPath) {
            if (strpos($normalizedPath, $ignoredPath) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get supported file extensions
     */
    public function getSupportedExtensions(): array
    {
        return $this->supportedExtensions;
    }

    /**
     * Check if file extension is supported
     */
    public function isSupportedExtension(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $this->supportedExtensions);
    }

    /**
     * Get minimum file size
     */
    public function getMinFileSize(): int
    {
        return $this->minFileSize;
    }

    /**
     * Validate configuration
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->basePath)) {
            $errors[] = "Base path is required";
        } elseif (!is_dir($this->basePath)) {
            $errors[] = "Base path does not exist or is not a directory";
        } elseif (!is_readable($this->basePath)) {
            $errors[] = "Base path is not readable";
        }

        return $errors;
    }
}
