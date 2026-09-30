const { getDefaultConfig, mergeConfig } = require('@react-native/metro-config');

/**
 * Metro configuration
 * https://reactnative.dev/docs/metro
 *
 * @type {import('@react-native/metro-config').MetroConfig}
 */
// Release builds run from a short `subst` drive; Metro resolves some files
// through the real path, so watch that too.
const config = { watchFolders: [require('fs').realpathSync.native(__dirname)] };

module.exports = mergeConfig(getDefaultConfig(__dirname), config);
