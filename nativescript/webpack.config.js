const webpack = require('@nativescript/webpack')

module.exports = (env) => {
  webpack.init(env)

  webpack.chainWebpack((config) => {
    config.optimization.concatenateModules(false)
    config.optimization.minimize(false)
    config.plugin('typescript-helpers').use(require('webpack').ProvidePlugin, [{
      __decorate: ['tslib', '__decorate'],
      __metadata: ['tslib', '__metadata'],
    }])
    config.devServer.hotOnly(true)
    config.devServer.hot(true)
  })

  return webpack.resolveConfig()
}
