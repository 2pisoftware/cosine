// vite.config.js
import { viteStaticCopy } from 'vite-plugin-static-copy'
import path, { resolve } from 'path'
import { defineConfig, build } from 'vite'
import { glob } from 'glob';
import vue from '@vitejs/plugin-vue';

import fs from "fs";

// We need to determine the root directory of the project - since everything is symlinked this requires a bit of thought
let scriptPath = __dirname;

console.log("dirname", scriptPath);

scriptPath = scriptPath.split('system')[0];

console.log("scriptPath", scriptPath);

const _x = [
    resolve(__dirname, 'src/js/app.ts').replace(/\\/g, "/"),
    resolve(__dirname, 'src/scss/app.scss').replace(/\\/g, "/"),
    ...glob.sync(resolve(__dirname, scriptPath, 'system/modules/**/assets/ts/*.ts').replace(/\\/g, "/")),
    ...glob.sync(resolve(__dirname, scriptPath, 'system/modules/**/assets/scss/*.scss').replace(/\\/g, "/")),
    ...glob.sync(resolve(__dirname, scriptPath, 'modules/**/assets/ts/*.ts').replace(/\\/g, "/")),
    ...glob.sync(resolve(__dirname, scriptPath, 'modules/**/assets/scss/*.scss').replace(/\\/g, "/")),
];

let _fileMapObj = {};
_x.forEach((file) => {
    let name = file.replace(/\\/g, "/").split('/').pop().split('.').shift();
    const ext = file.replace(/\\/g, "/").split('.').pop();

    if (_fileMapObj.hasOwnProperty(`${name}`)) {
        name = `${name}.${ext}`;
    }
    _fileMapObj[`${name}`] = file.replace(/\\/g, "/");
});

console.log("fileMap", _fileMapObj);

export default defineConfig({
    plugins: [vue(),
        viteStaticCopy({
            targets: [
              {
                src: resolve(__dirname, scriptPath, 'system/templates/base/node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff').replace(/\\/g, "/"),
                dest: 'fonts' 
              },
              {
                src: resolve(__dirname, scriptPath, 'system/templates/base/node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2').replace(/\\/g, "/"),
                dest: 'fonts' 
              }
            ]
          })
        ],
    build: {
        cssCodeSplit: true,
        minify: "terser",
        target: 'modules',
        lib:
        {
            entry: _fileMapObj,
            formats: ['es'],
        },
        rollupOptions: {
            output: {
                format: "es",
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name === 'style.css') return 'app.css';
                    return assetInfo.name;
                }
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                includePaths: [scriptPath + 'system/templates/base/src/scss/', scriptPath + 'system/templates/base/node_modules']
            }
        }
    },
    define: {
        'process.env': {
            'rootPath': scriptPath,
        },
    },
    resolve: {
        alias: {
            '~': resolve(__dirname, scriptPath + 'system/templates/base/node_modules').replace(/\\/g, "/"),
            '@': resolve(__dirname, scriptPath + 'system/templates/base/src').replace(/\\/g, "/"),
            'vue': 'vue/dist/vue.esm-bundler.js',
        },
    }
});
