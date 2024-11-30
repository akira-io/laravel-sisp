/** @type {import('vite').UserConfig} */
export default {
    build: {
        assetsDir: "",
        rollupOptions: {
            input: [ "resources/css/sisp.css"],
            output: {
                assetFileNames: "sisp.css",
                entryFileNames: "sisp.css",
            },
        },
    },
};
