<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:block {name : The block name}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new custom Gutenberg block';

    /**
     * Filesystem instance
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $blockSlug = Str::kebab($name);
        $blockTitle = Str::title(str_replace(['-', '_'], ' ', $name));
        
        $this->info("🎨 Creating block: {$blockTitle}");

        try {
            $this->createBlock($blockSlug, $blockTitle);
            $this->updateBlocksJs($blockSlug);
            $this->updateBlockManager($blockSlug);
            
            $this->line("");
            $this->info("✅ Block '{$blockTitle}' created successfully!");
            $this->line("📁 Location: resources/blocks/{$blockSlug}");
            $this->line("🏗️  Next: Run 'npm run build' to compile");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error creating block: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Create the block structure
     */
    protected function createBlock(string $slug, string $title): void
    {
        $blockPath = resource_path("blocks/{$slug}");
        
        // Create block directory
        if (!$this->files->isDirectory($blockPath)) {
            $this->files->makeDirectory($blockPath, 0755, true);
        }

        // Create block.json
        $blockJson = [
            'name' => "doctailwind/{$slug}",
            'title' => $title,
            'category' => 'design',
            'icon' => 'block-default',
            'description' => "Custom {$title} block",
            'textdomain' => 'doctailwind',
            'editorScript' => 'file:./block.jsx',
            'style' => 'file:./block.css',
            'render' => 'file:./block.php'
        ];

        $this->files->put(
            "{$blockPath}/block.json", 
            json_encode($blockJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Create block.jsx
        $jsContent = "import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';

registerBlockType('doctailwind/{$slug}', {
    edit: ({ attributes, setAttributes }) => {
        const { content } = attributes;
        const blockProps = useBlockProps();
        
        return (
            <div {...blockProps} className=\"{$slug}-block-editor p-4 border-2 border-dashed border-gray-300 rounded\">
                <h3 className=\"text-lg font-bold mb-2\">{$title}</h3>
                <RichText
                    tagName=\"div\"
                    value={content}
                    onChange={(newContent) => setAttributes({ content: newContent })}
                    placeholder=\"Enter your content...\"
                />
            </div>
        );
    },
    
    save: () => null // Server-side rendering
});";

        $this->files->put("{$blockPath}/block.jsx", $jsContent);

        // Create block.php
        $phpContent = "<?php
// Server-side rendering for {$title} block

\$content = \$attributes['content'] ?? '';
\$block_data = [
    'title' => '{$title}',
    'content' => \$content,
    'slug' => '{$slug}'
];

echo view('blocks.{$slug}', \$block_data)->render();";

        $this->files->put("{$blockPath}/block.php", $phpContent);

        // Create block.css
        $cssContent = ".{$slug}-block {
    padding: 1.5rem;
    margin: 1rem 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.{$slug}-block h3 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
}

.{$slug}-block-editor {
    background: #f9fafb;
    border: 2px dashed #d1d5db !important;
}

.{$slug}-block-editor:hover {
    border-color: #3b82f6 !important;
}";

        $this->files->put("{$blockPath}/block.css", $cssContent);

        // Create Blade template
        $bladePath = resource_path("views/blocks");
        if (!$this->files->isDirectory($bladePath)) {
            $this->files->makeDirectory($bladePath, 0755, true);
        }

        $bladeContent = "<div class=\"{$slug}-block\">
    <h3>{{ \$title }}</h3>
    <div class=\"block-content\">
        {!! wp_kses_post(\$content) !!}
    </div>
</div>";

        $this->files->put("{$bladePath}/{$slug}.blade.php", $bladeContent);
    }

    /**
     * Update blocks.js to include new block import
     */
    protected function updateBlocksJs(string $slug): void
    {
        $blocksJsPath = resource_path('js/blocks.js');
        
        if (!$this->files->exists($blocksJsPath)) {
            $this->error("❌ blocks.js file not found");
            return;
        }
        
        $content = $this->files->get($blocksJsPath);
        $importLine = "import '../blocks/{$slug}/block.jsx';";
        
        // Check if import already exists
        if (strpos($content, $importLine) !== false) {
            return;
        }
        
        // Add import after AUTO-IMPORTS comment or at the beginning
        if (strpos($content, '// AUTO-IMPORTS:') !== false) {
            $content = str_replace(
                "// AUTO-IMPORTS: Created blocks are automatically imported below this line",
                "// AUTO-IMPORTS: Created blocks are automatically imported below this line\n{$importLine}",
                $content
            );
        } else {
            // Add at the beginning
            $content = "{$importLine}\n" . $content;
        }
        
        $this->files->put($blocksJsPath, $content);
        $this->line("✅ Updated: blocks.js");
    }

    /**
     * Update BlockManager to include new block
     */
    protected function updateBlockManager(string $slug): void
    {
        $managerPath = app_path('Blocks/BlockManager.php');
        
        if (!$this->files->exists($managerPath)) {
            $this->error("❌ BlockManager.php not found");
            return;
        }
        
        $content = $this->files->get($managerPath);
        
        // Check if block already exists
        if (strpos($content, "'{$slug}'") !== false) {
            return;
        }
        
        // Find the blocks array and add the new block
        $pattern = '/(protected\s+array\s+\$blocks\s*=\s*\[)(.*?)(\/\/\s*Add new blocks here.*?\n.*?)(];)/s';
        
        if (preg_match($pattern, $content, $matches)) {
            $beforeComment = $matches[2];
            $comment = $matches[3];
            $end = $matches[4];
            
            // Add the new block before the closing bracket
            $newBlock = "        '{$slug}',\n    ";
            $newContent = str_replace(
                $matches[0],
                $matches[1] . $beforeComment . $comment . $newBlock . $end,
                $content
            );
            
            $this->files->put($managerPath, $newContent);
            $this->line("✅ Updated: BlockManager.php");
        }
    }
}
