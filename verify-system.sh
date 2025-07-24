#!/bin/bash

echo "🔍 Auto Blocks - Complete System Verification"
echo "============================================="
echo ""

# Check if we're in the correct theme directory
if [ ! -f "style.css" ] || [ ! -f "functions.php" ]; then
    echo "❌ Run this script in the WordPress theme root directory"
    exit 1
fi

echo "✅ WordPress theme detected!"
echo ""

# Check critical files
echo "📋 Checking critical files:"

files=(
    "app/Blocks/BlockManager.php"
    "app/Console/Commands/MakeBlockCommand.php"
    "app/Console/Commands/SyncBlocksCommand.php"
    "app/setup.php"
    "resources/js/blocks.js"
    "resources/js/app.js"
    "resources/js/editor.js"
    "resources/css/blocks.css"
    "resources/blocks.php"
    "vite.config.js"
    "sync-blocks.sh"
)

missing_files=0
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file (MISSING)"
        missing_files=$((missing_files + 1))
    fi
done

echo ""
echo "📊 Verification results:"
echo "   Total files: ${#files[@]}"
echo "   Present files: $((${#files[@]} - missing_files))"
echo "   Missing files: $missing_files"

if [ $missing_files -gt 0 ]; then
    echo ""
    echo "⚠️  ATTENTION: Some files are missing!"
    echo "Run the installation script:"
    echo "   bash vendor/juliojar4/auto-blocks/install-auto-blocks.sh"
    echo ""
fi

# Check blocks.js content
echo ""
echo "🔍 Checking blocks.js content:"
if [ -f "resources/js/blocks.js" ]; then
    if grep -q "AUTO-IMPORTS:" resources/js/blocks.js; then
        echo "✅ AUTO-IMPORTS marker present"
    else
        echo "❌ AUTO-IMPORTS marker missing"
    fi
    
    if grep -q "import.*blocks.css" resources/js/blocks.js; then
        echo "✅ CSS import present"
    else
        echo "❌ CSS import missing"
    fi
else
    echo "❌ blocks.js file not found"
fi

# Check app.js content
echo ""
echo "🔍 Checking app.js content:"
if [ -f "resources/js/app.js" ]; then
    if grep -q "import.*blocks" resources/js/app.js; then
        echo "✅ blocks.js import present in app.js"
    else
        echo "❌ blocks.js import missing in app.js"
    fi
else
    echo "❌ app.js file not found"
fi

# Check editor.js content
echo ""
echo "🔍 Checking editor.js content:"
if [ -f "resources/js/editor.js" ]; then
    if grep -q "import.*blocks" resources/js/editor.js; then
        echo "✅ blocks.js import present in editor.js"
    else
        echo "❌ blocks.js import missing in editor.js"
    fi
else
    echo "❌ editor.js file not found"
fi

# Check BlockManager integration in setup.php
echo ""
echo "🔍 Checking BlockManager integration in setup.php:"
if [ -f "app/setup.php" ]; then
    if grep -q "use App\\\\Blocks\\\\BlockManager" app/setup.php; then
        echo "✅ BlockManager import present"
    else
        echo "❌ BlockManager import missing"
    fi
    
    if grep -q "BlockManager()" app/setup.php; then
        echo "✅ BlockManager instance present"
    else
        echo "❌ BlockManager instance missing"
    fi
    
    if grep -q "register()" app/setup.php; then
        echo "✅ register() call present"
    else
        echo "❌ register() call missing"
    fi
else
    echo "❌ setup.php file not found"
fi

# Check existing blocks
echo ""
echo "🔍 Checking existing blocks:"
if [ -d "resources/blocks" ]; then
    block_count=0
    for block_dir in resources/blocks/*; do
        if [ -d "$block_dir" ]; then
            block_name=$(basename "$block_dir")
            if [ -f "$block_dir/block.jsx" ]; then
                echo "✅ Block found: $block_name"
                
                # Check if it's in blocks.js
                if grep -q "import.*blocks/$block_name/block.jsx" resources/js/blocks.js; then
                    echo "   ✅ Import present in blocks.js"
                else
                    echo "   ❌ Import MISSING in blocks.js"
                    echo "   💡 Run: bash sync-blocks.sh"
                fi
                block_count=$((block_count + 1))
            fi
        fi
    done
    
    if [ $block_count -eq 0 ]; then
        echo "ℹ️  No blocks found yet"
        echo "   💡 Create a block with: lando wp acorn make:block my-block --with-js --with-css"
    fi
else
    echo "ℹ️  resources/blocks directory doesn't exist yet"
fi

# Check environment
echo ""
echo "🔍 Checking environment:"
if [ -f ".lando.yml" ]; then
    echo "✅ Lando detected - use: lando wp acorn make:block"
else
    echo "ℹ️  Lando not detected - use: wp acorn make:block"
fi

if [ -f "package.json" ]; then
    echo "✅ package.json present"
    if command -v npm >/dev/null 2>&1; then
        echo "✅ npm available"
    fi
    if command -v yarn >/dev/null 2>&1; then
        echo "✅ yarn available"
    fi
else
    echo "❌ package.json not found"
fi

echo ""
echo "🎯 SUMMARY:"
if [ $missing_files -eq 0 ]; then
    echo "✅ Auto Blocks system is correctly installed!"
    echo ""
    echo "📋 Available commands:"
    echo "   - Create block: lando wp acorn make:block block-name --with-js --with-css"
    echo "   - Synchronize: bash sync-blocks.sh"
    echo "   - Compile: yarn build"
else
    echo "⚠️  System needs to be installed or repaired"
    echo ""
    echo "📋 To install/repair:"
    echo "   bash vendor/juliojar4/auto-blocks/install-auto-blocks.sh"
fi

echo ""
