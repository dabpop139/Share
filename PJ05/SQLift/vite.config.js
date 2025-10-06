import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'
const resolve = (dir) => { return path.resolve(__dirname, dir) }

// https://vitejs.dev/config/
export default defineConfig({
  server: {
    host: '0.0.0.0', // 绑定到所有网络接口
    port: 5174,      // 设置端口为 5174
  },
  plugins: [vue({
    // 开启$ref语法糖,省略ref变量的.value
    reactivityTransform: true
  })],
  resolve: {
    alias: {
      "@": resolve('./src')
    }
  },
  /* build: {
    rollupOptions: {
      output: {
        chunkFileNames: 'assets/[name]-[hash].js',
        entryFileNames: 'assets/[name]-[hash].js',
        // assetFileNames: 'assets/[ext]/[name]-[hash].[ext]',
        // 创建自定义的公共 chunk，将静态资源分拆打包：将 node_modules 中的代码单独打包成一个 JS 文件
        manualChunks(id) {
          if (id.includes('node_modules')) {
            // return id.toString().split('node_modules/')[1].split('/')[0].toString()
            return 'vendor'
          }
        }
      }
    }
  } */
})