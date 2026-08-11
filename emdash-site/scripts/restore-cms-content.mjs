/**
 * Restore WordPress news bodies into EmDash posts and upsert key CMS pages.
 *
 * Run on the staging host from public_html:
 *   node scripts/restore-cms-content.mjs
 *
 * Options:
 *   --dry-run   Print actions without writing
 *   --posts-only
 *   --pages-only
 */
import { execFileSync } from "node:child_process";
import { readFileSync, readdirSync, existsSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import { randomBytes } from "node:crypto";
import Database from "better-sqlite3";
import { gutenbergToPortableText } from "@emdash-cms/gutenberg-to-portable-text";

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, "..");
const args = new Set(process.argv.slice(2));
const dryRun = args.has("--dry-run");
const postsOnly = args.has("--posts-only");
const pagesOnly = args.has("--pages-only");

const DB_PATH = process.env.EMDASH_DB || join(root, "data.db");
const WP_PATH = process.env.WP_PATH || "/home/sites/autismsanctuary/public_html";
const PAGES_DIR = join(root, "seed/cms-pages");

function ulidLike() {
	const time = Date.now().toString(36).toUpperCase().padStart(10, "0");
	const rand = randomBytes(10).toString("hex").toUpperCase().slice(0, 16);
	return `01${time}${rand}`.slice(0, 26);
}

function fetchWpPosts() {
	let raw;
	if (process.env.WP_SSH) {
		const remote = process.env.WP_SSH;
		const key = process.env.WP_SSH_KEY || `${process.env.HOME}/.ssh/cursor_wpmudev_ed25519`;
		const localPhp = join(__dirname, "export-wp-posts.php");
		const remotePhp = "/tmp/emdash-export-wp-posts.php";
		execFileSync(
			"scp",
			["-i", key, "-o", "IdentitiesOnly=yes", localPhp, `${remote}:${remotePhp}`],
			{ stdio: "pipe" },
		);
		raw = execFileSync(
			"ssh",
			[
				"-i",
				key,
				"-o",
				"IdentitiesOnly=yes",
				remote,
				`wp --path=${WP_PATH} eval-file ${remotePhp} 2>/dev/null; rm -f ${remotePhp}`,
			],
			{ encoding: "utf8", maxBuffer: 20 * 1024 * 1024 },
		);
	} else {
		raw = execFileSync(
			"wp",
			["--path=" + WP_PATH, "eval-file", join(__dirname, "export-wp-posts.php")],
			{ encoding: "utf8", maxBuffer: 20 * 1024 * 1024 },
		);
	}
	const start = raw.indexOf("[");
	const end = raw.lastIndexOf("]");
	if (start < 0 || end < 0) throw new Error("Failed to parse WP posts JSON");
	return JSON.parse(raw.slice(start, end + 1));
}

function htmlToPt(html) {
	const cleaned = String(html || "")
		.replace(/&nbsp;/g, " ")
		.replace(/<p>\s*<\/p>/gi, "")
		.trim();
	if (!cleaned) return [];
	try {
		return gutenbergToPortableText(cleaned);
	} catch (err) {
		console.warn("PT conversion failed, storing plain paragraph:", err.message);
		const text = cleaned.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
		if (!text) return [];
		return [
			{
				_type: "block",
				style: "normal",
				_key: ulidLike().slice(0, 12),
				children: [{ _type: "span", text, _key: ulidLike().slice(0, 12) }],
			},
		];
	}
}

function loadPageDefs() {
	if (!existsSync(PAGES_DIR)) return [];
	return readdirSync(PAGES_DIR)
		.filter((f) => f.endsWith(".json"))
		.map((f) => JSON.parse(readFileSync(join(PAGES_DIR, f), "utf8")));
}

function upsertContent(db, table, row) {
	const existing = db.prepare(`SELECT id, live_revision_id FROM ${table} WHERE slug = ?`).get(row.slug);
	const now = new Date().toISOString().replace("T", " ").replace(/\.\d+Z$/, "");
	const contentJson = JSON.stringify(row.content);
	const dataPayload = JSON.stringify({
		title: row.title,
		excerpt: row.excerpt || "",
		content: row.content,
	});

	if (existing) {
		if (dryRun) {
			console.log(`update ${table} ${row.slug} (${contentJson.length} chars content json)`);
			return existing.id;
		}
		db.prepare(
			`UPDATE ${table}
       SET title = ?, excerpt = ?, content = ?, status = ?, updated_at = ?,
           published_at = COALESCE(published_at, ?)
       WHERE id = ?`,
		).run(row.title, row.excerpt || "", contentJson, row.status, now, row.publishedAt || now, existing.id);

		if (existing.live_revision_id) {
			db.prepare(`UPDATE revisions SET data = ? WHERE id = ?`).run(dataPayload, existing.live_revision_id);
		} else if (table === "ec_pages") {
			const revId = ulidLike();
			db.prepare(
				`INSERT INTO revisions (id, collection, entry_id, data, created_at) VALUES (?, ?, ?, ?, ?)`,
			).run(revId, table === "ec_pages" ? "pages" : "posts", existing.id, dataPayload, now);
			db.prepare(`UPDATE ${table} SET live_revision_id = ? WHERE id = ?`).run(revId, existing.id);
		}
		return existing.id;
	}

	const id = ulidLike();
	if (dryRun) {
		console.log(`insert ${table} ${row.slug}`);
		return id;
	}
	const revId = ulidLike();
	const collection = table === "ec_pages" ? "pages" : "posts";
	db.prepare(
		`INSERT INTO ${table}
      (id, slug, status, title, excerpt, content, created_at, updated_at, published_at, version, locale)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'en')`,
	).run(
		id,
		row.slug,
		row.status,
		row.title,
		row.excerpt || "",
		contentJson,
		now,
		now,
		row.status === "published" ? row.publishedAt || now : null,
	);
	db.prepare(
		`INSERT INTO revisions (id, collection, entry_id, data, created_at) VALUES (?, ?, ?, ?, ?)`,
	).run(revId, collection, id, dataPayload, now);
	db.prepare(`UPDATE ${table} SET live_revision_id = ? WHERE id = ?`).run(revId, id);
	return id;
}

function restorePosts(db) {
	const posts = fetchWpPosts();
	console.log(`Fetched ${posts.length} WordPress posts`);
	let updated = 0;
	for (const post of posts) {
		let slug = post.slug;
		if (!slug) {
			slug = String(post.title)
				.toLowerCase()
				.replace(/[^a-z0-9]+/g, "-")
				.replace(/^-|-$/g, "")
				.slice(0, 80);
		}
		const content = htmlToPt(post.html);
		const nonEmpty = content.some(
			(b) =>
				b?._type === "block" &&
				Array.isArray(b.children) &&
				b.children.some((c) => String(c?.text || "").trim()),
		);
		if (!nonEmpty) {
			console.warn(`skip empty conversion: ${slug}`);
			continue;
		}
		upsertContent(db, "ec_posts", {
			slug,
			title: post.title,
			excerpt: post.excerpt || "",
			content,
			status: post.status,
			publishedAt: post.date ? post.date.replace("T", " ").replace(/\.\d+Z$/, "").replace(/\+.*$/, "") : null,
		});
		updated += 1;
		console.log(`post ${slug}: ${content.length} blocks`);
	}
	console.log(`Posts restored: ${updated}`);
}

function restorePages(db) {
	const pages = loadPageDefs();
	console.log(`Loaded ${pages.length} CMS page definitions`);
	for (const page of pages) {
		const content = htmlToPt(page.html);
		upsertContent(db, "ec_pages", {
			slug: page.slug,
			title: page.title,
			excerpt: page.excerpt || "",
			content,
			status: "published",
			publishedAt: null,
		});
		console.log(`page ${page.slug}: ${content.length} blocks`);
	}
}

function main() {
	if (!existsSync(DB_PATH)) throw new Error(`Database not found: ${DB_PATH}`);
	const db = new Database(DB_PATH);
	db.pragma("journal_mode = WAL");
	try {
		if (!pagesOnly) restorePosts(db);
		if (!postsOnly) restorePages(db);
		console.log(dryRun ? "Dry run complete." : "CMS content restore complete.");
	} finally {
		db.close();
	}
}

main();
