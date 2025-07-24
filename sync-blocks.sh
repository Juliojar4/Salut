#!/bin/bash

echo "🔄 Auto Blocks - Block Synchronizer"
echo "===================================="
echo ""

# Check if we're in the correct directory
if [ ! -f "resources/js/blocks.js" ]; then
    echo "❌ blocks.js file not found!"
    echo "Run this script in the theme root directory."
    exit 1
fi

echo "✅ Looking for created blocks..."

# Find all block.jsx files
BLOCKS_FOUND=0
BLOCKS_ADDED=0

# Read current blocks.js content
BLOCKS_JS_CONTENT=$(cat resources/js/blocks.js)

# Look for block.jsx files
if [ -d "resources/blocks" ]; then
    for BLOCK_DIR in resources/blocks/*; do
        if [ -d "$BLOCK_DIR" ] && [ -f "$BLOCK_DIR/block.jsx" ]; then
            BLOCKS_FOUND=$((BLOCKS_FOUND + 1))
            BLOCK_NAME=$(basename "$BLOCK_DIR")
            IMPORT_LINE="import '../blocks/$BLOCK_NAME/block.jsx';"
            
            echo "📦 Block found: $BLOCK_NAME"
            
            # Check if import already exists
            if ! grep -q "$IMPORT_LINE" resources/js/blocks.js; then
                echo "➕ Adding import for $BLOCK_NAME"
                
                # Add after AUTO-IMPORTS marker or before console.log
                if grep -q "AUTO-IMPORTS:" resources/js/blocks.js; then
                    sed -i "/AUTO-IMPORTS:/ a\\$IMPORT_LINE" resources/js/blocks.js
                else
                    # Create temporary file with new import
                    awk -v import="$IMPORT_LINE" '
                    /console\.log.*Auto Blocks.*System loaded/ {
                        print import "\n"
                        print
                        next
                    }
                    { print }
                    ' resources/js/blocks.js > resources/js/blocks.js.tmp
                    mv resources/js/blocks.js.tmp resources/js/blocks.js
                fi
                
                BLOCKS_ADDED=$((BLOCKS_ADDED + 1))
            else
                echo "✅ Import already exists for $BLOCK_NAME"
            fi
        fi
    done
else
    echo "⚠️  resources/blocks directory not found"
fi

echo ""
echo "📊 Results:"
echo "   Blocks found: $BLOCKS_FOUND"
echo "   Imports added: $BLOCKS_ADDED"

if [ $BLOCKS_ADDED -gt 0 ]; then
    echo ""
    echo "🔧 Next steps:"
    echo "   1. yarn build"
    echo "   2. Check blocks in WordPress editor"
else
    echo ""
    echo "✅ All blocks are already synchronized!"
fi

echo ""
