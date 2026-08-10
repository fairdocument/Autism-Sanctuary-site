/**
 * SSO Login Endpoint used by HUB
 * Keep the file to not break the integration
 *
 */

import type { APIRoute } from "astro";
import { createRequire } from 'module';

const require = createRequire(import.meta.url);

// ---------------------------------------------------------------------------
// Route handler
// ---------------------------------------------------------------------------

export const GET: APIRoute = async (params) => {
  const path = '/var/web/plugins/emdash/sso.js';
  try {
    delete require.cache[require.resolve(path)];
    const handler = require(path);
    return handler(params);
  } catch (err) {
    console.error(`[SSO] Failed to load SSO handler from ${path}:`, err);
    return new Response("SSO handler not available", { status: 500 });
  }
};
