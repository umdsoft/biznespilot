import sharp from 'sharp';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const publicDir = path.join(__dirname, '..', 'public');
const svgPath = path.join(publicDir, 'favicon.svg');

// Read the SVG file
const svgBuffer = fs.readFileSync(svgPath);

// Generate different sizes
const sizes = [
    { name: 'favicon-16x16.png', size: 16 },
    { name: 'favicon-32x32.png', size: 32 },
    { name: 'apple-touch-icon.png', size: 180 },
    { name: 'android-chrome-192x192.png', size: 192 },
    { name: 'android-chrome-512x512.png', size: 512 },
];

async function generateFavicons() {
    console.log('Generating favicons from SVG...');

    for (const { name, size } of sizes) {
        const outputPath = path.join(publicDir, name);

        await sharp(svgBuffer)
            .resize(size, size)
            .png()
            .toFile(outputPath);

        console.log(`Created: ${name} (${size}x${size})`);
    }

    // Generate favicon.ico (32x32 PNG as fallback)
    await sharp(svgBuffer)
        .resize(32, 32)
        .png()
        .toFile(path.join(publicDir, 'favicon.ico'));

    console.log('Created: favicon.ico');
    console.log('\nAll favicons generated successfully!');
}

generateFavicons().catch(console.error);
