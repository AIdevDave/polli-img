# Pollinations.ai Image Generator

A powerful PHP script and library for generating AI images using the [Pollinations.ai](https://pollinations.ai) API. Works both as a command-line tool and as an includable PHP library for web projects.

## Features

- 🎨 Generate images from text prompts
- 🖼️ Multiple aspect ratios (portrait, landscape, square)
- 🎯 Custom resolutions
- 🎭 Multiple AI models (Flux, Turbo, GPT Image, and more)
- 🔒 Private generation by default (not visible to public)
- 💾 Flexible output options
- 🛠️ Both CLI and library usage
- 🔐 Secure API key management via `.env` file

## Requirements

- PHP 7.4 or higher
- cURL extension enabled
- Pollinations.ai API key

## Installation

1. **Clone or download this repository:**

```bash
git clone https://github.com/yourusername/polli-img.git
cd polli-img
```

2. **Make the script executable:**

```bash
chmod +x polli.php
```

3. **Get your API key:**

   - Visit [enter.pollinations.ai](https://enter.pollinations.ai)
   - Sign up or log in
   - Navigate to your dashboard
   - Create a new API key
   - **For server-side use (recommended):** Use secret keys starting with `sk_` - these have no rate limits and can spend Pollen
   - **For client-side use:** Use publishable keys starting with `pk_` - these are IP rate-limited (1 pollen/hour per IP+key)

4. **Configure your API key:**

```bash
# Copy the example environment file
cp .env.example .env

# Edit .env and add your API key
nano .env
```

Add your key to the `.env` file:
```
POLLINATIONS_API_KEY=sk_your_secret_key_here
```

**⚠️ Security Note:** Never commit your `.env` file to version control. It's included in `.gitignore` by default.

## Usage

### Command Line

#### Basic Usage

```bash
./polli.php --img -c "house on the cliff with sunset view over the sea"
```

#### Common Examples

**Portrait orientation:**
```bash
./polli.php -P -c "portrait of a person in professional attire"
```

**Landscape orientation:**
```bash
./polli.php -L -c "mountain landscape at dawn"
```

**Square orientation:**
```bash
./polli.php -S -c "abstract geometric pattern"
```

**Custom resolution:**
```bash
./polli.php -R 1920x1080 -c "wide cinematic scene"
```

**Specify model:**
```bash
./polli.php -m turbo -c "fast generated image"
```

**Custom output path:**
```bash
./polli.php -o ./outputs/myimage.png -c "custom save location"
```

**JPG format instead of PNG:**
```bash
./polli.php -f jpg -c "compressed image format"
```

**With seed for reproducibility:**
```bash
./polli.php --seed 12345 -c "reproducible generation"
```

**List available models:**
```bash
./polli.php --list-models
```

### Command-Line Options

| Option | Description | Example |
|--------|-------------|---------|
| `--img` | Generate image (default mode) | `--img` |
| `-c, --content` | Image prompt/description (required) | `-c "sunset beach"` |
| `-m, --model` | Model to use (default: flux) | `-m turbo` |
| `-P, --portrait` | Portrait aspect ratio (768x1024) | `-P` |
| `-L, --landscape` | Landscape aspect ratio (1024x768) | `-L` |
| `-S, --square` | Square aspect ratio (1024x1024) | `-S` |
| `-R, --resolution` | Custom resolution (e.g., 1280x720) | `-R 1920x1080` |
| `-o, --output` | Output file path | `-o ./images/pic.png` |
| `-f, --format` | Image format: png (default) or jpg | `-f jpg` |
| `--seed` | Seed for reproducibility | `--seed 12345` |
| `--enhance` | Enhance prompt | `--enhance` |
| `--safe` | Enable safe mode | `--safe` |
| `--list-models` | List available models | `--list-models` |
| `-h, --help` | Show help message | `-h` |

### Available Models

To see the current list of available models with descriptions and pricing:

```bash
./polli.php --list-models
```

**Common models include:**
- `flux` - Default model (Flux)
- `turbo` - Faster generation
- `gptimage` - GPT-based image generation
- `kontext` - Context-aware generation
- `seedream` - Dream-like images
- `nanobanana` - Nano Banana
- `nanobanana-pro` - Nano Banana Pro

**Video models** (for future support):
- `veo` - Text-to-video (4-8 seconds)
- `seedance` - Text-to-video and image-to-video (2-10 seconds)

### As a PHP Library

You can include `polli.php` in your web projects:

```php
<?php
require_once 'polli.php';

// Load environment variables
loadEnv(__DIR__ . '/.env');

// Generate an image
$result = polli([
    'prompt' => 'a beautiful sunset over mountains',
    'model' => 'flux',
    'aspect' => 'landscape', // or 'portrait', 'square'
    'output' => './generated/image.png',
    'format' => 'png'
]);

if ($result['success']) {
    echo "Image saved to: " . $result['path'];
    echo "<br>Size: " . number_format($result['size']) . " bytes";
    echo "<br><img src='" . $result['path'] . "' alt='Generated image'>";
} else {
    echo "Error: " . $result['error'];
}
```

#### Library Options

The `polli()` function accepts an array with these options:

```php
$options = [
    'prompt' => 'Your image description',    // Required
    'model' => 'flux',                       // AI model to use
    'width' => 1024,                         // Image width in pixels
    'height' => 768,                         // Image height in pixels
    'aspect' => 'landscape',                 // 'portrait', 'landscape', or 'square'
    'resolution' => '1920x1080',             // Alternative to width/height
    'output' => './image.png',               // Where to save the image
    'format' => 'png',                       // 'png' or 'jpg'
    'seed' => 12345,                         // For reproducible results
    'enhance' => false,                      // Enhance the prompt
    'safe' => false,                         // Enable safe mode
    'nologo' => true,                        // No logo (default: true)
    'private' => true,                       // Private generation (default: true)
    'nofeed' => true,                        // Not in public feed (default: true)
    'api_key' => 'your_api_key'              // Optional, reads from env by default
];

$result = polli($options);
```

#### Return Value

The function returns an array with:

```php
[
    'success' => true/false,
    'path' => '/path/to/saved/image.png',   // If successful
    'data' => '...',                         // Binary image data
    'size' => 123456,                        // File size in bytes
    'content_type' => 'image/png',          // MIME type
    'error' => 'Error message'               // If failed
]
```

### Web Integration Example

```php
<?php
require_once 'polli.php';
loadEnv();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = $_POST['prompt'] ?? '';
    $model = $_POST['model'] ?? 'flux';
    
    $result = polli([
        'prompt' => $prompt,
        'model' => $model,
        'aspect' => 'square',
        'output' => './generated/' . time() . '.png'
    ]);
    
    if ($result['success']) {
        $imageUrl = $result['path'];
        echo json_encode(['success' => true, 'url' => $imageUrl]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Image Generator</title>
</head>
<body>
    <h1>AI Image Generator</h1>
    <form method="POST">
        <label>Prompt:</label><br>
        <textarea name="prompt" rows="4" cols="50" required></textarea><br><br>
        
        <label>Model:</label><br>
        <select name="model">
            <option value="flux">Flux</option>
            <option value="turbo">Turbo</option>
            <option value="gptimage">GPT Image</option>
        </select><br><br>
        
        <button type="submit">Generate Image</button>
    </form>
</body>
</html>
```

## Default Settings

By default, the script configures images to be:
- **No logo** (`nologo=true`) - No watermark on images
- **Private** (`private=true`) - Not visible to public
- **No feed** (`nofeed=true`) - Not added to public feed

These ensure your generated images remain private and clean.

## File Output

If no output path is specified, images are saved with auto-generated names:
```
img_20260113_143022.png
```

Format: `img_YYYYMMDD_HHMMSS.ext`

## Error Handling

The script provides clear error messages:

- **Missing API key:** "API key not found. Please set POLLINATIONS_API_KEY in .env file"
- **Missing prompt:** "Prompt is required. Use -c or --content option"
- **HTTP errors:** Shows status code and error message from API
- **File errors:** Shows if unable to save to specified path

## Security Best Practices

1. **Never commit `.env` file** - It's in `.gitignore` by default
2. **Use secret keys (`sk_`)** for server-side applications
3. **Use publishable keys (`pk_`)** only for client-side with IP restrictions
4. **Set proper file permissions** on `.env`:
   ```bash
   chmod 600 .env
   ```
5. **Keep API keys secret** - Don't share them or expose them in client-side code

## API Documentation

For full API documentation, see [APIDOCS.md](https://github.com/pollinations/pollinations/blob/main/APIDOCS.md) or visit:
- [Pollinations.ai API Docs](https://gen.pollinations.ai/docs)
- [Get API Key](https://enter.pollinations.ai)

## Troubleshooting

### "API key not found"
- Make sure `.env` file exists in the same directory as `polli.php`
- Check that your API key is correctly set in `.env`
- Verify the key starts with `sk_` or `pk_`

### "cURL error"
- Ensure cURL extension is enabled in PHP: `php -m | grep curl`
- Check your internet connection
- Verify firewall allows outbound HTTPS connections

### "HTTP 401 error"
- Your API key is invalid or expired
- Get a new key from [enter.pollinations.ai](https://enter.pollinations.ai)

### "HTTP 400 error"
- Check your prompt is not empty
- Verify model name is correct (use `--list-models`)
- Check resolution format is correct (e.g., `1920x1080`)

### Permission denied when saving
- Check write permissions on output directory
- Create the directory first: `mkdir -p ./outputs`
- Check disk space

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License - feel free to use this in your projects!

## Credits

- Powered by [Pollinations.ai](https://pollinations.ai)
- API Documentation: [gen.pollinations.ai/docs](https://gen.pollinations.ai/docs)

## Support

- **Issues:** Open an issue on GitHub
- **API Support:** Visit [pollinations.ai](https://pollinations.ai)
- **Documentation:** [APIDOCS.md](https://github.com/pollinations/pollinations/blob/main/APIDOCS.md)

---

**Happy generating! 🎨✨**
