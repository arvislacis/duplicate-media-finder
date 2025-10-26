<?php

/**
 * Duplicate detector class for finding duplicate files
 */
class DuplicateDetector
{
    private array $files;
    private array $exactDuplicates;
    private array $sizeDuplicates;

    public function __construct()
    {
        $this->files = [];
        $this->exactDuplicates = [];
        $this->sizeDuplicates = [];
    }

    /**
     * Analyze files for duplicates
     */
    public function analyze(array $files): array
    {
        $this->files = $files;
        $this->exactDuplicates = [];
        $this->sizeDuplicates = [];

        $this->findExactDuplicates();
        $this->findSizeDuplicates();

        return [
            'exact_duplicates' => $this->exactDuplicates,
            'size_duplicates' => $this->sizeDuplicates,
            'stats' => $this->getStats()
        ];
    }

    /**
     * Find files with exact filename and size matches
     */
    private function findExactDuplicates(): void
    {
        $groupedFiles = [];

        // Group files by filename and size
        foreach ($this->files as $file) {
            $key = $file['filename'] . '|' . $file['filesize'];

            if (!isset($groupedFiles[$key])) {
                $groupedFiles[$key] = [];
            }

            $groupedFiles[$key][] = $file;
        }

        // Find groups with more than one file
        foreach ($groupedFiles as $key => $group) {
            if (count($group) > 1) {
                $this->exactDuplicates[] = [
                    'group_key' => $key,
                    'filename' => $group[0]['filename'],
                    'filesize' => $group[0]['filesize'],
                    'filesize_formatted' => $this->formatFileSize($group[0]['filesize']),
                    'files' => $group,
                    'count' => count($group)
                ];
            }
        }

        // Sort by file size (largest first)
        usort($this->exactDuplicates, function($a, $b) {
            return $b['filesize'] - $a['filesize'];
        });
    }

    /**
     * Find files with same size but different filenames
     */
    private function findSizeDuplicates(): void
    {
        $sizeGroups = [];

        // Group files by size only
        foreach ($this->files as $file) {
            $size = $file['filesize'];

            if (!isset($sizeGroups[$size])) {
                $sizeGroups[$size] = [];
            }

            $sizeGroups[$size][] = $file;
        }

        // Find size groups with multiple different filenames
        foreach ($sizeGroups as $size => $group) {
            if (count($group) > 1) {
                // Check if filenames are different
                $filenames = array_unique(array_column($group, 'filename'));

                if (count($filenames) > 1) {
                    // Skip if these files are already in exact duplicates
                    $hasExactDuplicates = false;
                    foreach ($this->exactDuplicates as $exactGroup) {
                        if ($exactGroup['filesize'] == $size) {
                            $hasExactDuplicates = true;
                            break;
                        }
                    }

                    if (!$hasExactDuplicates) {
                        $this->sizeDuplicates[] = [
                            'filesize' => $size,
                            'filesize_formatted' => $this->formatFileSize($size),
                            'files' => $group,
                            'count' => count($group),
                            'unique_filenames' => count($filenames)
                        ];
                    }
                }
            }
        }

        // Sort by file size (largest first)
        usort($this->sizeDuplicates, function($a, $b) {
            return $b['filesize'] - $a['filesize'];
        });
    }

    /**
     * Get statistics about duplicates found
     */
    private function getStats(): array
    {
        $exactDuplicateFiles = 0;
        $exactDuplicateSize = 0;
        $sizeDuplicateFiles = 0;
        $sizeDuplicateSize = 0;

        foreach ($this->exactDuplicates as $group) {
            $exactDuplicateFiles += $group['count'];
            $exactDuplicateSize += $group['filesize'] * ($group['count'] - 1); // Space that could be saved
        }

        foreach ($this->sizeDuplicates as $group) {
            $sizeDuplicateFiles += $group['count'];
            $sizeDuplicateSize += $group['filesize'] * ($group['count'] - 1); // Potential space
        }

        return [
            'exact_duplicate_groups' => count($this->exactDuplicates),
            'exact_duplicate_files' => $exactDuplicateFiles,
            'exact_duplicate_wasted_space' => $exactDuplicateSize,
            'exact_duplicate_wasted_space_formatted' => $this->formatFileSize($exactDuplicateSize),
            'size_duplicate_groups' => count($this->sizeDuplicates),
            'size_duplicate_files' => $sizeDuplicateFiles,
            'size_duplicate_potential_space' => $sizeDuplicateSize,
            'size_duplicate_potential_space_formatted' => $this->formatFileSize($sizeDuplicateSize)
        ];
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get all duplicates in a unified format
     */
    public function getAllDuplicates(): array
    {
        return [
            'exact' => $this->exactDuplicates,
            'size_only' => $this->sizeDuplicates
        ];
    }
}
