import * as esbuild from 'esbuild'

esbuild.build({
    entryPoints: ['./resources/js/tributejs.js'],
    outfile: './resources/dist/js/tributejs.js',
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    treeShaking: true,
    target: ['es2020'],
    allowOverwrite: true,
    minify: true,
})
