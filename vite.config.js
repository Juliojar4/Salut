import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin'
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import fs from 'fs';
import path from 'path';

// Automatically discover block assets
function discoverBlockAssets() {
  const blockAssets = [];
  const blocksDir = 'resources/blocks';
  
  // Check if directory exists
  if (!fs.existsSync(blocksDir)) {
    return blockAssets;
  }
  
  // Read all block directories
  const blockDirs = fs.readdirSync(blocksDir, { withFileTypes: true })
    .filter(dirent => dirent.isDirectory())
    .map(dirent => dirent.name);
  
  // For each block directory, look for assets
  blockDirs.forEach(blockDir => {
    const blockPath = path.join(blocksDir, blockDir);
    
    // Check if block.js exists
    const jsFile = path.join(blockPath, 'block.js');
    if (fs.existsSync(jsFile)) {
      blockAssets.push(jsFile);
    }
    
    // Check if block.css exists
    const cssFile = path.join(blockPath, 'block.css');
    if (fs.existsSync(cssFile)) {
      blockAssets.push(cssFile);
    }
  });
  
  return blockAssets;
}

export default defineConfig({
  base: '/app/themes/sage/public/build/',
  plugins: [
    tailwindcss(),
    laravel({
      input: [
        // Assets principais
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/editor.css',
        'resources/js/editor.js',
        'resources/js/blocks.js',
        'resources/css/blocks.css',
        // Block assets discovered automatically
        ...discoverBlockAssets(),
      ],
      refresh: true,
    }),

    wordpressPlugin(),

    // Generate the theme.json file in the public/build/assets directory
    // based on the Tailwind config and the theme.json file from base theme folder
    wordpressThemeJson({
      disableTailwindColors: false,
      disableTailwindFonts: false,
      disableTailwindFontSizes: false,
    }),
  ],
  resolve: {
    alias: {
      '@scripts': '/resources/js',
      '@styles': '/resources/css',
      '@fonts': '/resources/fonts',
      '@images': '/resources/images',
    },
  },
})
