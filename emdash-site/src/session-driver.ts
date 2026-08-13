import { mkdirSync } from "node:fs";
import { join } from "node:path";
import type { SessionDriver } from "astro";
import fsLite from "unstorage/drivers/fs-lite";

/**
 * Resolve session storage at runtime (process.cwd), not at build time.
 * Local `astro build` + rsync otherwise bakes a Mac absolute path into the
 * server bundle and admin login fails until Hub Rebuild.
 */
export default function createSessionDriver(): SessionDriver {
	const base = join(process.cwd(), ".astro", "sessions");
	mkdirSync(base, { recursive: true });
	return fsLite({ base });
}
