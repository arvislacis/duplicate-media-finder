<?php

/**
 * Application configuration file example
 * Modify these settings as needed for your environment
 */

return [
    // Default paths (can be overridden by user input)
    'default_paths' => [
        'default_remote_drive_path' => '/path/to/your/remote/drive', // Default remote drive path to preload
        'default_paths_to_ignore' => [
            // Add paths to ignore here, e.g.:
            // '/path/to/ignore',
        ]
    ],

    // Duplicate detector settings
    'detector' => [
        'supported_extensions' => ['jpg', 'jpeg', 'png', 'mp4'], // File types to scan
        'min_file_size' => 102400, // Minimum file size in bytes (100KB)
    ],

    // File viewer applications
    'viewers' => [
        'image_viewer' => 'your_image_viewer_app',  // Application to open image files (jpg, jpeg, png, gif, etc.)
        'video_viewer' => 'your_video_viewer_app', // Application to open video files (mp4, avi, mkv, mov, etc.)
        'video_extensions' => ['mp4'],
    ],
];
