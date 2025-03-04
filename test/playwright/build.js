const glob = require('glob');
const fs = require('fs');
const path = require('path');

async function copyFiles(sourceGlob, destDir, rename = fileName => fileName) {
    // Find all files and directories that match the glob pattern
    const files = await glob(sourceGlob, { nodir: true })

    // Create the destination directory if it doesn't exist
    if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir);
    }

    // Copy each file to the destination directory
    files.forEach(file => {
        const fileName = rename(path.basename(file));
        const destFile = path.join(destDir, fileName);
        fs.copyFileSync(file, destFile);
    });
}

async function main() {
    // pull all module test files into src directory
    await copyFiles('../../system/modules/**/tests/acceptance/playwright/*.test.ts', './src')
    await copyFiles('../../modules/**/tests/acceptance/playwright/*.test.ts', './src')

    // pull all module test util files into one directory for scoping
    await copyFiles('../../system/modules/**/tests/acceptance/playwright/*.utils.ts', './src/utils',
        fileName => fileName.replace('.utils.ts', '.ts')
    );
    await copyFiles('../../modules/**/tests/acceptance/playwright/*.utils.ts', './src/utils',
        fileName => fileName.replace('.utils.ts', '.ts')
    );
}

try {
    main();
} catch (err) {
    console.error(err);
}
