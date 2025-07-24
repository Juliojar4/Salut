<?php

namespace App\Blocks;

/**
 * Custom Block Manager
 * 
 * Simplified system: just list block names here
 * and it will automatically discover all necessary files
 */
class BlockManager
{
    /**
     * Simple list of blocks to register
     * Just add the block folder name here!
     */
    protected array $blocks = [
        // Add new blocks here - just the folder name!
        // Example: 'my-block', 'text-block', 'hero-section'
            'block-name',
    ];

    /**
     * Block namespace
     */
    protected string $namespace = 'doctailwind';

    /**
     * Register all blocks automatically
     */
    public function register(): void
    {
        // Register blocks in WordPress
        foreach ($this->blocks as $blockName) {
            $this->registerSingleBlock($blockName);
        }
        
        // Enqueue assets
        add_action('enqueue_block_editor_assets', [$this, 'enqueueAssets']);
    }

    /**
     * Register a single block
     */
    protected function registerSingleBlock(string $blockName): void
    {
        $blockPath = get_template_directory() . "/resources/blocks/{$blockName}";
        
        // Check if block folder exists
        if (!is_dir($blockPath)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BlockManager: Block folder '{$blockName}' not found at {$blockPath}");
            }
            return;
        }

        // Check if block.json exists
        $blockJsonPath = "{$blockPath}/block.json";
        if (!file_exists($blockJsonPath)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BlockManager: block.json not found for '{$blockName}' at {$blockJsonPath}");
            }
            return;
        }

        // Register block using directory
        $result = register_block_type($blockPath);
        
        // Load block-specific assets
        $this->enqueueBlockSpecificAssets($blockName);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($result) {
                error_log("BlockManager: Block '{$blockName}' registered successfully");
            } else {
                error_log("BlockManager: Failed to register block '{$blockName}'");
            }
        }
    }

    /**
     * Load global block assets
     */
    public function enqueueAssets(): void
    {
        // Global block JavaScript
        $this->enqueueAsset('js', 'blocks', [
            'wp-blocks', 
            'wp-element', 
            'wp-block-editor', 
            'wp-components', 
            'wp-i18n'
        ]);

        // Global block CSS
        $this->enqueueAsset('css', 'blocks');
    }

    /**
     * Load an asset (JS or CSS)
     */
    protected function enqueueAsset(string $type, string $name, array $dependencies = []): void
    {
        $manifestPath = get_template_directory() . '/public/build/manifest.json';
        
        if (!file_exists($manifestPath)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BlockManager: manifest.json not found");
            }
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $assetKey = "resources/{$type}/{$name}.{$type}";
        
        if (!isset($manifest[$assetKey])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BlockManager: Asset '{$assetKey}' not found in manifest");
            }
            return;
        }

        $assetInfo = $manifest[$assetKey];
        $assetUrl = get_template_directory_uri() . '/public/build/' . $assetInfo['file'];
        $version = $this->getAssetVersion($assetInfo);

        if ($type === 'js') {
            wp_enqueue_script(
                "custom-blocks-{$name}",
                $assetUrl,
                $dependencies,
                $version,
                true
            );
        } else {
            wp_enqueue_style(
                "custom-blocks-{$name}",
                $assetUrl,
                [],
                $version
            );
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("BlockManager: Asset {$type} '{$name}' loaded: {$assetUrl}");
        }
    }

    /**
     * Get asset version
     */
    protected function getAssetVersion(array $assetInfo): string
    {
        // Use file hash if available
        if (isset($assetInfo['file'])) {
            return hash('crc32', $assetInfo['file']);
        }
        
        // Fallback to theme version
        return wp_get_theme()->get('Version') ?: '1.0.0';
    }

    /**
     * Add a new block to the list
     */
    public function addBlock(string $blockName): void
    {
        if (!in_array($blockName, $this->blocks)) {
            $this->blocks[] = $blockName;
        }
    }

    /**
     * Get list of registered blocks
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Get block namespace
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Load block-specific assets
     */
    protected function enqueueBlockSpecificAssets(string $blockName): void
    {
        // Hook to load assets in editor
        add_action('enqueue_block_editor_assets', function() use ($blockName) {
            $this->loadBlockAssets($blockName, 'editor');
        });
        
        // Hook to load assets on frontend
        add_action('wp_enqueue_scripts', function() use ($blockName) {
            $this->loadBlockAssets($blockName, 'frontend');
        });
    }

    /**
     * Load JS and CSS assets for a specific block
     */
    protected function loadBlockAssets(string $blockName, string $context = 'editor'): void
    {
        $manifestPath = get_template_directory() . '/public/build/manifest.json';
        
        if (!file_exists($manifestPath)) {
            return;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        // Load block-specific JavaScript
        $jsKey = "resources/blocks/{$blockName}/block.js";
        if (isset($manifest[$jsKey])) {
            $assetInfo = $manifest[$jsKey];
            $assetUrl = get_template_directory_uri() . '/public/build/' . $assetInfo['file'];
            
            wp_enqueue_script(
                "block-{$blockName}-js",
                $assetUrl,
                ['wp-blocks', 'wp-element', 'wp-dom-ready'],
                $this->getAssetVersion($assetInfo),
                true
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BlockManager: Block-specific JS loaded for '{$blockName}': {$assetUrl}");
            }
        }
        
        // Load block-specific CSS
        $cssKey = "resources/blocks/{$blockName}/block.css";
        if (isset($manifest[$cssKey])) {
            $assetInfo = $manifest[$cssKey];
            $assetUrl = get_template_directory_uri() . '/public/build/' . $assetInfo['file'];
            
            wp_enqueue_style(
                "block-{$blockName}-css",
                $assetUrl,
                [],
                $this->getAssetVersion($assetInfo)
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("BlockManager: Block-specific CSS loaded for '{$blockName}': {$assetUrl}");
            }
        }
    }
}
