#!/usr/bin/env php
<?php
/**
 * Pollinations.ai Image Generator
 * 
 * A PHP script for generating images using the Pollinations.ai API
 * Can be used both from command line and included in web projects
 * 
 * @author (aidev.dave) David
 * @license MIT
 */

// Load environment variables from .env file
function loadEnv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

/**
 * Generate image using Pollinations.ai API
 * 
 * @param array $options Configuration options
 * @return array Result with 'success', 'path' (if saved), 'data' (binary), 'error' (if failed)
 */
function polli($options = []) {
    // Default options
    $defaults = [
        'img' => true,
        'prompt' => '',
        'model' => 'flux',
        'width' => null,
        'height' => null,
        'resolution' => null,
        'aspect' => null, // 'portrait', 'landscape', 'square'
        'format' => 'png',
        'output' => null,
        'nologo' => true,
        'private' => true,
        'nofeed' => true,
        'seed' => null,
        'enhance' => false,
        'safe' => false,
        'api_key' => getenv('POLLINATIONS_API_KEY') ?: null
    ];
    
    $options = array_merge($defaults, $options);
    
    // Validate API key
    if (empty($options['api_key'])) {
        return [
            'success' => false,
            'error' => 'API key not found. Please set POLLINATIONS_API_KEY in .env file or pass it as an option.'
        ];
    }
    
    // Validate prompt
    if (empty($options['prompt'])) {
        return [
            'success' => false,
            'error' => 'Prompt is required. Use -c or --content option.'
        ];
    }
    
    // Handle aspect ratios
    if ($options['aspect']) {
        switch ($options['aspect']) {
            case 'portrait':
            case 'P':
                $options['width'] = 768;
                $options['height'] = 1024;
                break;
            case 'landscape':
            case 'L':
                $options['width'] = 1024;
                $options['height'] = 768;
                break;
            case 'square':
            case 'S':
                $options['width'] = 1024;
                $options['height'] = 1024;
                break;
        }
    }
    
    // Handle custom resolution (e.g., "1280x720")
    if ($options['resolution']) {
        if (preg_match('/^(\d+)x(\d+)$/i', $options['resolution'], $matches)) {
            $options['width'] = (int)$matches[1];
            $options['height'] = (int)$matches[2];
        }
    }
    
    // Build API URL
    $baseUrl = 'https://gen.pollinations.ai/image';
    $prompt = urlencode($options['prompt']);
    $url = "$baseUrl/$prompt";
    
    // Build query parameters
    $params = [];
    
    if ($options['model']) {
        $params['model'] = $options['model'];
    }
    
    if ($options['width']) {
        $params['width'] = $options['width'];
    }
    
    if ($options['height']) {
        $params['height'] = $options['height'];
    }
    
    if ($options['seed']) {
        $params['seed'] = $options['seed'];
    }
    
    if ($options['nologo']) {
        $params['nologo'] = 'true';
    }
    
    if ($options['private']) {
        $params['private'] = 'true';
    }
    
    if ($options['nofeed']) {
        $params['nofeed'] = 'true';
    }
    
    if ($options['enhance']) {
        $params['enhance'] = 'true';
    }
    
    if ($options['safe']) {
        $params['safe'] = 'true';
    }
    
    // Add query parameters to URL
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $options['api_key']
    ]);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        return [
            'success' => false,
            'error' => "cURL error: $error"
        ];
    }
    
    // Check HTTP status
    if ($httpCode !== 200) {
        $errorMessage = "HTTP $httpCode error";
        
        // Try to parse JSON error response
        $jsonError = json_decode($response, true);
        if ($jsonError && isset($jsonError['error']['message'])) {
            $errorMessage .= ": " . $jsonError['error']['message'];
        }
        
        return [
            'success' => false,
            'error' => $errorMessage
        ];
    }
    
    // Determine file extension based on content type
    $extension = $options['format'];
    if (strpos($contentType, 'image/jpeg') !== false) {
        $extension = 'jpg';
    } elseif (strpos($contentType, 'image/png') !== false) {
        $extension = 'png';
    }
    
    // Generate output filename if not specified
    if (!$options['output']) {
        $timestamp = date('Ymd_His');
        $options['output'] = "./img_$timestamp.$extension";
    } else {
        // If output is a directory, generate filename
        if (is_dir($options['output'])) {
            $timestamp = date('Ymd_His');
            $options['output'] = rtrim($options['output'], '/') . "/img_$timestamp.$extension";
        }
    }
    
    // Create directory if it doesn't exist
    $directory = dirname($options['output']);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Save the image
    $result = file_put_contents($options['output'], $response);
    
    if ($result === false) {
        return [
            'success' => false,
            'error' => "Failed to save image to: {$options['output']}"
        ];
    }
    
    return [
        'success' => true,
        'path' => $options['output'],
        'data' => $response,
        'size' => strlen($response),
        'content_type' => $contentType
    ];
}

/**
 * List available models
 */
function listModels($apiKey = null) {
    if (!$apiKey) {
        $apiKey = getenv('POLLINATIONS_API_KEY');
    }
    
    if (!$apiKey) {
        echo "Error: API key not found.\n";
        return;
    }
    
    $url = 'https://gen.pollinations.ai/image/models';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "Error: Failed to fetch models (HTTP $httpCode)\n";
        return;
    }
    
    $models = json_decode($response, true);
    
    if (!$models) {
        echo "Error: Failed to parse models response\n";
        return;
    }
    
    echo "\n=== Available Image Models ===\n\n";
    
    foreach ($models as $model) {
        echo "Model: {$model['name']}\n";
        
        if (isset($model['description']) && $model['description']) {
            echo "  Description: {$model['description']}\n";
        }
        
        if (isset($model['aliases']) && !empty($model['aliases'])) {
            echo "  Aliases: " . implode(', ', $model['aliases']) . "\n";
        }
        
        if (isset($model['pricing']) && !empty($model['pricing'])) {
            echo "  Pricing: ";
            foreach ($model['pricing'] as $key => $value) {
                if ($key !== 'currency') {
                    echo "$key: $value ";
                }
            }
            if (isset($model['pricing']['currency'])) {
                echo $model['pricing']['currency'];
            }
            echo "\n";
        }
        
        echo "\n";
    }
}

/**
 * Display help message
 */
function showHelp() {
    echo <<<HELP

Pollinations.ai Image Generator
================================

Usage: ./polli.php [options]

Options:
  --img                  Generate image (default mode)
  -c, --content TEXT     Image prompt/description (required)
  -m, --model MODEL      Model to use (default: flux)
  -P, --portrait         Portrait aspect ratio (768x1024)
  -L, --landscape        Landscape aspect ratio (1024x768)
  -S, --square           Square aspect ratio (1024x1024)
  -R, --resolution WxH   Custom resolution (e.g., 1280x720)
  -o, --output PATH      Output file path (default: ./img_YYYYMMDD_HHMMSS.png)
  -f, --format FORMAT    Image format: png (default) or jpg
  --seed NUMBER          Seed for reproducibility
  --enhance              Enhance prompt
  --safe                 Enable safe mode
  --list-models          List available models
  -h, --help             Show this help message

Default Settings:
  - No logo on images (nologo=true)
  - Private generation (private=true)
  - Not added to public feed (nofeed=true)

Examples:
  # Generate an image with flux model
  ./polli.php --img -m flux -c "house on the cliff with sunset view over the sea"
  
  # Portrait orientation
  ./polli.php -P -c "portrait of a person"
  
  # Custom resolution and output path
  ./polli.php -R 1920x1080 -o ./outputs/sunset.png -c "beautiful sunset"
  
  # JPG format
  ./polli.php -f jpg -c "mountain landscape"
  
  # List available models
  ./polli.php --list-models

Setup:
  1. Get API key from https://enter.pollinations.ai
  2. Create .env file: echo "POLLINATIONS_API_KEY=your_key_here" > .env
  3. Run the script!

HELP;
}

// Command-line interface
if (php_sapi_name() === 'cli') {
    // Load environment variables
    loadEnv();
    
    // Parse command-line arguments
    $options = [
        'img' => false,
        'prompt' => '',
        'model' => 'flux',
        'aspect' => null,
        'resolution' => null,
        'output' => null,
        'format' => 'png',
        'seed' => null,
        'enhance' => false,
        'safe' => false,
        'list_models' => false,
        'help' => false
    ];
    
    $args = array_slice($argv, 1);
    $i = 0;
    
    while ($i < count($args)) {
        $arg = $args[$i];
        
        switch ($arg) {
            case '--img':
                $options['img'] = true;
                break;
                
            case '-c':
            case '--content':
                if (isset($args[$i + 1])) {
                    $options['prompt'] = $args[++$i];
                }
                break;
                
            case '-m':
            case '--model':
                if (isset($args[$i + 1])) {
                    $options['model'] = $args[++$i];
                }
                break;
                
            case '-P':
            case '--portrait':
                $options['aspect'] = 'portrait';
                break;
                
            case '-L':
            case '--landscape':
                $options['aspect'] = 'landscape';
                break;
                
            case '-S':
            case '--square':
                $options['aspect'] = 'square';
                break;
                
            case '-R':
            case '--resolution':
                if (isset($args[$i + 1])) {
                    $options['resolution'] = $args[++$i];
                }
                break;
                
            case '-o':
            case '--output':
                if (isset($args[$i + 1])) {
                    $options['output'] = $args[++$i];
                }
                break;
                
            case '-f':
            case '--format':
                if (isset($args[$i + 1])) {
                    $options['format'] = $args[++$i];
                }
                break;
                
            case '--seed':
                if (isset($args[$i + 1])) {
                    $options['seed'] = $args[++$i];
                }
                break;
                
            case '--enhance':
                $options['enhance'] = true;
                break;
                
            case '--safe':
                $options['safe'] = true;
                break;
                
            case '--list-models':
                $options['list_models'] = true;
                break;
                
            case '-h':
            case '--help':
                $options['help'] = true;
                break;
        }
        
        $i++;
    }
    
    // Handle help
    if ($options['help'] || (count($args) === 0)) {
        showHelp();
        exit(0);
    }
    
    // Handle list models
    if ($options['list_models']) {
        listModels();
        exit(0);
    }
    
    // Generate image
    echo "Generating image with Pollinations.ai...\n";
    echo "Prompt: {$options['prompt']}\n";
    echo "Model: {$options['model']}\n";
    
    if ($options['aspect']) {
        echo "Aspect: {$options['aspect']}\n";
    }
    
    if ($options['resolution']) {
        echo "Resolution: {$options['resolution']}\n";
    }
    
    echo "\n";
    
    $result = polli($options);
    
    if ($result['success']) {
        echo "✓ Success!\n";
        echo "Image saved to: {$result['path']}\n";
        echo "File size: " . number_format($result['size']) . " bytes\n";
        echo "Content type: {$result['content_type']}\n";
    } else {
        echo "✗ Error: {$result['error']}\n";
        exit(1);
    }
}
