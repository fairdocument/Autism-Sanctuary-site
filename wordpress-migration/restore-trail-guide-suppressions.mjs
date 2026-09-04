/**
 * Restore false-positive ASM suppressions for Trail Guide newsletter (group 55041),
 * and keep the group non-default.
 *
 * Usage:
 *   SENDGRID_API_KEY=SG.... node wordpress-migration/restore-trail-guide-suppressions.mjs
 *   SENDGRID_API_KEY=SG.... node wordpress-migration/restore-trail-guide-suppressions.mjs a@b.com c@d.com
 */

const KEY = process.env.SENDGRID_API_KEY || "";
const GROUP = 55041;
if (!KEY.startsWith("SG.")) {
	console.error("Set SENDGRID_API_KEY");
	process.exit(1);
}

async function sg(method, path, body) {
	const res = await fetch(`https://api.sendgrid.com/v3${path}`, {
		method,
		headers: {
			Authorization: `Bearer ${KEY}`,
			...(body ? { "Content-Type": "application/json" } : {}),
		},
		body: body ? JSON.stringify(body) : undefined,
	});
	const text = await res.text();
	let data = null;
	try {
		data = text ? JSON.parse(text) : null;
	} catch {
		data = text;
	}
	if (!res.ok) {
		throw new Error(`${method} ${path} → ${res.status}: ${text.slice(0, 400)}`);
	}
	return data;
}

const fromArgs = process.argv.slice(2).filter((e) => e.includes("@"));
const emails =
	fromArgs.length > 0
		? fromArgs
		: (await sg("GET", `/asm/groups/${GROUP}/suppressions`)) || [];

console.log(`Restoring ${emails.length} suppression(s) from ASM group ${GROUP}`);
for (const email of emails) {
	await sg(
		"DELETE",
		`/asm/groups/${GROUP}/suppressions/${encodeURIComponent(email)}`,
	);
	console.log(`  restored ${email}`);
}

const group = await sg("PATCH", `/asm/groups/${GROUP}`, {
	name: "Trail Guide newsletter",
	description:
		"Autism Sanctuary newsletters and farm updates — group unsubscribe (not account-wide)",
	is_default: false,
});
console.log(
	`Group ${group.id}: is_default=${group.is_default}, unsubscribes=${group.unsubscribes}`,
);

const verify = await sg("GET", `/asm/groups/${GROUP}/suppressions`);
console.log("Remaining suppressions:", verify);
