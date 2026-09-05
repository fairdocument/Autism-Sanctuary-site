/**
 * SendGrid mailing-list hygiene.
 *
 * Removes Marketing Contacts that are hard-bounced, invalid, blocked,
 * spam-reported, or globally unsubscribed — so they stop inflating
 * audience counts and harming deliverability.
 *
 * Does NOT clear suppression lists (bounces/spam/unsubs stay suppressed).
 * Does NOT remove Trail Guide ASM group suppressions (intentional opt-outs).
 *
 * Usage:
 *   SENDGRID_API_KEY=SG.... node wordpress-migration/sendgrid-list-hygiene.mjs
 *   SENDGRID_API_KEY=SG.... DRY_RUN=1 node wordpress-migration/sendgrid-list-hygiene.mjs
 */

import { writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const KEY = process.env.SENDGRID_API_KEY || "";
const DRY = process.env.DRY_RUN === "1" || process.env.DRY_RUN === "true";
const API = "https://api.sendgrid.com/v3";

if (!KEY.startsWith("SG.")) {
	console.error("Set SENDGRID_API_KEY");
	process.exit(1);
}

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT = join(__dirname, "sendgrid-hygiene-report.json");

async function sg(method, path, body) {
	const res = await fetch(`${API}${path}`, {
		method,
		headers: {
			Authorization: `Bearer ${KEY}`,
			...(body !== undefined ? { "Content-Type": "application/json" } : {}),
		},
		body: body !== undefined ? JSON.stringify(body) : undefined,
	});
	const text = await res.text();
	let data = null;
	try {
		data = text ? JSON.parse(text) : null;
	} catch {
		data = { raw: text };
	}
	if (!res.ok) {
		throw new Error(`${method} ${path} → ${res.status}: ${text.slice(0, 600)}`);
	}
	return data;
}

async function fetchAllSuppressions(path) {
	// Suppression endpoints return arrays; paginate with offset if needed.
	const all = [];
	let offset = 0;
	const limit = 500;
	for (;;) {
		const page = await sg(
			"GET",
			`/${path}?limit=${limit}&offset=${offset}`,
		);
		const items = Array.isArray(page) ? page : [];
		all.push(...items);
		if (items.length < limit) break;
		offset += limit;
	}
	return all;
}

function norm(email) {
	return String(email || "")
		.trim()
		.toLowerCase();
}

const bounces = await fetchAllSuppressions("suppression/bounces");
const blocks = await fetchAllSuppressions("suppression/blocks");
const spam = await fetchAllSuppressions("suppression/spam_reports");
const invalid = await fetchAllSuppressions("suppression/invalid_emails");
const globalUnsubs = await fetchAllSuppressions("suppression/unsubscribes");

const buckets = {
	bounce: new Set(bounces.map((x) => norm(x.email)).filter(Boolean)),
	block: new Set(blocks.map((x) => norm(x.email)).filter(Boolean)),
	spam: new Set(spam.map((x) => norm(x.email)).filter(Boolean)),
	invalid: new Set(invalid.map((x) => norm(x.email)).filter(Boolean)),
	global_unsubscribe: new Set(
		globalUnsubs.map((x) => norm(x.email)).filter(Boolean),
	),
};

// Priority: bounce > invalid > spam > block > global_unsubscribe
const reasonByEmail = new Map();
for (const [reason, set] of Object.entries(buckets)) {
	for (const email of set) {
		if (!reasonByEmail.has(email)) reasonByEmail.set(email, reason);
	}
}

const candidates = [...reasonByEmail.keys()].sort();
console.log(`Suppression-derived candidates: ${candidates.length}`);
console.log(
	`  bounces=${buckets.bounce.size} blocks=${buckets.block.size} spam=${buckets.spam.size} invalid=${buckets.invalid.size} global_unsubs=${buckets.global_unsubscribe.size}`,
);

const found = [];
const notInContacts = [];

// Search contacts in chunks (SendGrid search query length limits).
const chunkSize = 25;
for (let i = 0; i < candidates.length; i += chunkSize) {
	const chunk = candidates.slice(i, i + chunkSize);
	const clauses = chunk.map((e) => `email = '${e.replace(/'/g, "''")}'`);
	const query = clauses.join(" OR ");
	const result = await sg("POST", "/marketing/contacts/search", { query });
	const hits = new Set(
		(result.result || []).map((c) => norm(c.email)).filter(Boolean),
	);
	for (const email of chunk) {
		if (hits.has(email)) {
			const contact = (result.result || []).find(
				(c) => norm(c.email) === email,
			);
			found.push({
				email,
				reason: reasonByEmail.get(email),
				id: contact?.id || null,
				list_ids: contact?.list_ids || [],
			});
		} else {
			notInContacts.push({
				email,
				reason: reasonByEmail.get(email),
			});
		}
	}
}

console.log(`In Marketing Contacts: ${found.length}`);
console.log(`Not in contacts (suppression only): ${notInContacts.length}`);

const listsBefore = await sg("GET", "/marketing/lists?page_size=50");
const listSnapshot = (listsBefore.result || []).map((L) => ({
	id: L.id,
	name: L.name,
	contact_count: L.contact_count,
}));

let deleteJob = null;
if (found.length === 0) {
	console.log("Nothing to delete from Marketing Contacts.");
} else if (DRY) {
	console.log("DRY_RUN=1 — would delete:");
	for (const f of found) console.log(`  ${f.reason}\t${f.email}`);
} else {
	const ids = found.map((f) => f.id).filter(Boolean);
	if (ids.length === 0) {
		console.log("No contact ids resolved; aborting delete.");
	} else {
		// API expects comma-separated ids as a query param (not JSON body).
		const chunkSize = 50;
		const jobs = [];
		for (let i = 0; i < ids.length; i += chunkSize) {
			const chunk = ids.slice(i, i + chunkSize);
			const qs = new URLSearchParams({ ids: chunk.join(",") });
			const job = await sg("DELETE", `/marketing/contacts?${qs.toString()}`);
			jobs.push(job);
			console.log(
				`Delete job ${jobs.length}: ${chunk.length} contacts → ${job?.job_id || JSON.stringify(job)}`,
			);
		}
		deleteJob = { jobs };
		for (const job of jobs) {
			if (!job?.job_id) continue;
			for (let i = 0; i < 20; i++) {
				await new Promise((r) => setTimeout(r, 3000));
				let status = "pending";
				try {
					const st = await sg(
						"GET",
						`/marketing/contacts/imports/${job.job_id}`,
					);
					status = st?.status || JSON.stringify(st);
				} catch (err) {
					status = String(err.message || err).slice(0, 120);
				}
				console.log(`  poll ${job.job_id.slice(0, 8)}… ${status}`);
				if (
					/complete|finished|completed|success/i.test(String(status)) ||
					(/deleted_count/i.test(String(status)) &&
						/"pending"/i.test(String(status)) === false)
				) {
					break;
				}
				// Treat delete jobs with matching deleted_count as done even if status lags.
				try {
					const st = await sg(
						"GET",
						`/marketing/contacts/imports/${job.job_id}`,
					);
					const req = st?.results?.requested_count;
					const del = st?.results?.deleted_count;
					if (
						typeof req === "number" &&
						typeof del === "number" &&
						req > 0 &&
						del >= req
					) {
						console.log(
							`  poll ${job.job_id.slice(0, 8)}… done (deleted ${del}/${req})`,
						);
						break;
					}
				} catch {
					/* ignore */
				}
			}
		}
	}
}

const listsAfter = DRY
	? listsBefore
	: await sg("GET", "/marketing/lists?page_size=50");
const listAfterSnap = (listsAfter.result || []).map((L) => ({
	id: L.id,
	name: L.name,
	contact_count: L.contact_count,
}));

const asm = await sg("GET", "/asm/groups");

const report = {
	updated_at: new Date().toISOString(),
	dry_run: DRY,
	suppression_counts: {
		bounces: buckets.bounce.size,
		blocks: buckets.block.size,
		spam_reports: buckets.spam.size,
		invalid_emails: buckets.invalid.size,
		global_unsubscribes: buckets.global_unsubscribe.size,
	},
	candidates: candidates.length,
	removed_from_contacts: found,
	suppression_only_not_in_contacts: notInContacts,
	delete_job: deleteJob,
	lists_before: listSnapshot,
	lists_after: listAfterSnap,
	asm_groups: asm,
	notes: [
		"Hard bounces, invalids, blocks, and spam reporters removed from Marketing Contacts when present.",
		"Global unsubscribes also removed from Marketing Contacts so they are not included in 'all contacts' sends.",
		"Suppression databases were left intact (SendGrid will still block sends to them).",
		"Trail Guide ASM suppressions left alone (intentional group opt-outs).",
	],
};

writeFileSync(OUT, JSON.stringify(report, null, "\t") + "\n");
console.log(`Wrote ${OUT}`);

const byReason = {};
for (const f of found) {
	byReason[f.reason] = (byReason[f.reason] || 0) + 1;
}
console.log("Removed by reason:", byReason);
console.log("List counts after:");
for (const L of listAfterSnap) {
	const before = listSnapshot.find((b) => b.id === L.id);
	const delta =
		before && typeof before.contact_count === "number"
			? L.contact_count - before.contact_count
			: "?";
	console.log(
		`  ${L.contact_count} (${delta >= 0 ? "+" : ""}${delta})  ${L.name}`,
	);
}
