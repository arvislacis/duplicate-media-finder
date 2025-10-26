# Duplicate Media Finder

A PHP-based system for detecting duplicate images and videos on remote drives. The system identifies duplicates by comparing filenames and file sizes, making it efficient for slow remote storage.

## Features

- **Efficient Detection**: Compares files by filename and size first (optimized for slow remote drives)
- **Two Detection Types**:
  - Exact duplicates (same filename and size)
  - Size duplicates (same size, different filename)
- **File Size Filter**: Only processes files larger than specified file size
- **Path Exclusions**: Configure paths to ignore during scanning
- **Clickable Links**: Results include clickable links to open files in native OS viewers
- **Bootstrap Frontend**: Clean, responsive web interface with dark mode toggle
- **Statistics**: Detailed scan statistics and space usage analysis

## Requirements

- PHP 8.3 or higher
- Web server (Apache, Nginx, or built-in PHP development server)
- File system access to the remote drive

## Installation

1. Clone or download the files to your web server directory
2. Rename app_config.example.php to app_config.php and adjust configuration values as needed.
3. Ensure PHP has read access to the remote drive paths you want to scan
4. No additional dependencies or Composer packages required

## Usage

### Quick Start

1. Start a local PHP development server:
   ```bash
   cd /path/to/duplicate-media-finder
   php -S localhost:8080
   ```

2. Open your browser and navigate to:
   ```
   http://localhost:8080/frontend.html
   ```

3. Configure the scan settings:
   - Enter the remote drive path
   - Optionally add paths to ignore
   - Click "Test Path" to verify accessibility
   - Click "Start Duplicate Scan" to begin

### Configuration Options

- **Remote Drive Path**: The root directory to scan recursively
- **Ignored Paths**: Directories to exclude from scanning (one per line)
- **File Size Threshold**: 100KB minimum (configurable in app_config.php)
- **Supported Extensions**: jpg, jpeg, png, mp4 (configurable in app_config.php)

## File Structure

```
duplicate-media-finder/
├── Config.php             # Configuration management
├── FileScanner.php        # Directory scanning logic
├── DuplicateDetector.php  # Duplicate detection algorithms
├── index.php              # Main backend API
├── frontend.html          # Web interface
└── README.md              # This file
```

## API Endpoints

The system provides a JSON API accessible via `index.php`:

### Scan for Duplicates
```
POST index.php?action=scan

Parameters:
- base_path: Root directory to scan
- ignored_paths: Array of paths to ignore
```

### Test Path Accessibility
```
POST index.php?action=test_path

Parameters:
- test_path: Path to test for accessibility
```

## Results Format

The system outputs two types of duplicates:

### Exact Duplicates
Files with identical filename and file size:
- Grouped by filename and size
- Shows all file locations
- Calculates wasted storage space

### Size Duplicates
Files with same size but different filenames:
- Grouped by file size
- Potential duplicates requiring manual review
- Useful for finding renamed duplicates

## Performance Notes

- Optimized for remote/slow drives by using filename and size comparison
- No actual file content comparison (for speed)
- Recursive directory scanning with error handling
- Progress indication in the web interface

## Customization

### Adding More File Types
Edit `app_config.php` and modify the `supported_extensions` array:
```php
'detector' => [
    'supported_extensions' => ['jpg', 'jpeg', 'png', 'mp4', 'avi', 'mov'],
    'min_file_size' => 102400,
],
```

### Modifying File Size Threshold
Edit `app_config.php` and change the `min_file_size` setting:
```php
'detector' => [
    'supported_extensions' => ['jpg', 'jpeg', 'png', 'mp4'],
    'min_file_size' => 204800, // 200KB instead of 100KB
],
```

### Configuring File Viewers
Edit `app_config.php` to customize which applications open image and video files:
```php
'viewers' => [
    'image_viewer' => 'xviewer',  // or 'eog', 'gwenview', 'gimp', etc.
    'video_viewer' => 'celluloid', // or 'vlc', 'mpv', 'totem', etc.
    'video_extensions' => ['mp4']
],
```

### Styling
The frontend uses Bootstrap 5 via CDN with support for both light and dark modes. A toggle button in the top-right corner allows switching between themes, with preferences saved locally. Custom styles can be added to the `<style>` section in `frontend.html`.

## Security Considerations

- The system requires file system access to scan directories
- File paths are displayed in the interface - ensure appropriate access controls
- Consider running on a secure local network when scanning sensitive locations
- The "file://" links may require browser configuration to open local files

## Troubleshooting

### Common Issues

1. **"Path does not exist" error**
   - Verify the path is correct and accessible
   - Check file system permissions
   - Ensure the web server has read access

2. **"Path is not readable" error**
   - Check directory permissions
   - Verify the web server user has access rights

3. **Files not opening from links**
   - Browser security may block file:// links
   - Try copying the path and opening manually
   - Consider using a local file manager integration

4. **Scan takes too long**
   - Add more paths to the ignored paths list
   - Consider scanning smaller directory trees
   - Check network connectivity to remote drive

### Error Logging

PHP errors are logged to the standard error log. Check your web server's error log for detailed error information.

## Development

The system is built with clean, modular PHP code:

- `Config`: Handles all configuration and validation
- `FileScanner`: Recursively scans directories and collects file information
- `DuplicateDetector`: Analyzes files and identifies duplicates
- Frontend: Bootstrap-based interface with AJAX API calls

Each class is self-contained and can be extended or modified independently.

## License

[MIT License Copyright (c) 2025 Arvis Lācis](https://github.com/arvislacis/duplicate-media-finder/blob/master/LICENSE)
