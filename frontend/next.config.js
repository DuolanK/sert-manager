/** @type {import('next').NextConfig} */

// Backend entrypoint reachable from inside the Docker network.
// The nginx service (service name "nginx") listens on port 80 and routes
// /api/* to the Laravel backend. This is baked in at build time; override
// via the API_PROXY_TARGET build arg if the topology ever changes.
const API_PROXY_TARGET = process.env.API_PROXY_TARGET || 'http://nginx';

const nextConfig = {
  output: 'standalone',
  async rewrites() {
    return [
      {
        source: '/api/:path*',
        destination: `${API_PROXY_TARGET}/api/:path*`,
      },
    ];
  },
};

module.exports = nextConfig;
