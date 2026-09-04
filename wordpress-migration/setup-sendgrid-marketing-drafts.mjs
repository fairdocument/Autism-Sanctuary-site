/**
 * Push AS Alert + Trail Guide newsletter into SendGrid Design Library,
 * and create a Marketing Single Send draft (thank-you / UVA Today issue).
 *
 * Usage:
 *   SENDGRID_API_KEY=SG.... node wordpress-migration/setup-sendgrid-marketing-drafts.mjs
 *
 * Better long-term approach (used here):
 * - Design Library = reusable Marketing shells (edit in SendGrid UI)
 * - Single Sends = audience + schedule for the 572 Marketing Contacts
 * - Keep Dynamic Templates (AS Alert / AS Newsletter) for transactional/API only
 */

import { writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const API = "https://api.sendgrid.com/v3";
const KEY = process.env.SENDGRID_API_KEY || "";
if (!KEY.startsWith("SG.")) {
	console.error("Set SENDGRID_API_KEY");
	process.exit(1);
}

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT = join(__dirname, "sendgrid-marketing.json");

const C = {
	green: "#2f5d43",
	forest: "#1e3d2c",
	meadow: "#a7c4a0",
	cream: "#f7f4ec",
	creamDark: "#efe9dc",
	gold: "#c9a646",
	ink: "#1b1b1b",
	muted: "#4a534c",
	rule: "#d9d2c4",
	white: "#ffffff",
	site: "https://autismsanctuary.org",
	address: "2860 Pea Ridge Road, Charlottesville, VA 22901",
	phone: "(434) 207-2118",
	logoUrl:
		"https://www.autismsanctuary.org/wp-content/uploads/2026/08/autism-sanctuary-logo-300x284.png",
};

const SENDER_NEWSLETTER = 7495556; // newsletters@autismsanctuary.org
const ASM_GROUP = 55041; // Trail Guide newsletter
const WEB_SIGNUPS = "d6507184-f423-4780-917b-38ea8975f7a1";

async function sg(method, path, body) {
	const res = await fetch(`${API}${path}`, {
		method,
		headers: {
			Authorization: `Bearer ${KEY}`,
			"Content-Type": "application/json",
		},
		body: body ? JSON.stringify(body) : undefined,
	});
	const text = await res.text();
	let data = null;
	try {
		data = text ? JSON.parse(text) : null;
	} catch {
		data = { raw: text };
	}
	if (!res.ok) {
		throw new Error(`${method} ${path} → ${res.status}: ${text.slice(0, 800)}`);
	}
	return data;
}

function shell(accent, eyebrow, bodyInner) {
	return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Autism Sanctuary</title>
</head>
<body style="margin:0;padding:0;background:${C.cream};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:${C.cream};">
  <tr>
    <td align="center" style="padding:28px 12px;">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background:${C.white};">
        <tr><td style="height:6px;line-height:6px;font-size:0;background:${accent};">&nbsp;</td></tr>
        <tr>
          <td style="padding:24px 40px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="84" valign="middle" style="width:84px;padding:0 18px 0 0;">
                  <a href="${C.site}"><img src="${C.logoUrl}" width="72" alt="Autism Sanctuary" style="width:72px;height:auto;display:block;border:0;"></a>
                </td>
                <td valign="middle">
                  <p style="margin:0 0 6px;font-family:Georgia,serif;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:${C.gold};font-weight:700;">${eyebrow}</p>
                  <p style="margin:0;font-family:Georgia,serif;font-size:26px;line-height:1.15;color:${C.forest};font-weight:700;">Autism Sanctuary</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
${bodyInner}
        <tr>
          <td style="background:${C.creamDark};padding:24px 40px 28px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.55;color:${C.muted};">
            <img src="${C.logoUrl}" width="48" alt="" style="width:48px;height:auto;display:block;border:0;margin:0 0 12px;">
            <p style="margin:0 0 8px;font-family:Georgia,serif;font-size:15px;color:${C.forest};font-weight:700;">Autism Sanctuary</p>
            <p style="margin:0 0 4px;">501(c)(3) nonprofit &amp; Virginia DBHDS-licensed care farm</p>
            <p style="margin:0 0 4px;">${C.address}</p>
            <p style="margin:0 0 12px;"><a href="tel:+14342072118" style="color:${C.green};text-decoration:none;">${C.phone}</a> · <a href="mailto:newsletters@autismsanctuary.org" style="color:${C.green};text-decoration:none;">newsletters@autismsanctuary.org</a></p>
            <p style="margin:0;"><a href="${C.site}" style="color:${C.green};">autismsanctuary.org</a> · <a href="{{{unsubscribe_preferences}}}" style="color:${C.muted};">Manage email preferences</a></p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>`;
}

// Marketing designs don't use Handlebars {{#if}} — use editable static HTML.
const alertHtml = shell(
	C.gold,
	"Important notice",
	`
        <tr>
          <td style="padding:20px 40px 0;">
            <span style="display:inline-block;background:${C.gold};border-radius:3px;padding:6px 12px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:${C.forest};">Alert</span>
          </td>
        </tr>
        <tr>
          <td style="padding:16px 40px 8px;">
            <h1 style="margin:0;font-family:Georgia,serif;font-size:30px;line-height:1.22;color:${C.forest};font-weight:700;">Program update</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:8px 40px 20px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;color:${C.ink};">
            <p style="margin:0 0 14px;">Hello{{#if first_name}} {{first_name}}{{/if}},</p>
            <p style="margin:0 0 14px;">Replace this paragraph with your alert details (schedule change, weather, closure, or other timely notice).</p>
            <p style="margin:0;">Thank you for your flexibility.</p>
          </td>
        </tr>
        <tr>
          <td style="padding:8px 40px 24px;">
            <a href="${C.site}/contact/" style="display:inline-block;padding:14px 26px;background:${C.green};color:#fff;text-decoration:none;border-radius:4px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;">Contact us</a>
          </td>
        </tr>
`,
);

const newsletterHtml = shell(
	C.green,
	"Trail Guide · September 2026",
	`
        <tr>
          <td style="padding:20px 0 0;">
            <img src="https://www.autismsanctuary.org/wp-content/uploads/2026/09/5A42F5DA-06E7-4F20-BA2A-8E10739CAACA_1_105_c.jpeg" alt="Autism Sanctuary pavilion ribbon cutting celebration" width="600" style="width:100%;max-width:600px;height:auto;display:block;">
          </td>
        </tr>
        <tr>
          <td style="padding:24px 40px 10px;">
            <h1 style="margin:0;font-family:Georgia,serif;font-size:32px;line-height:1.2;color:${C.forest};font-weight:700;">Pavilion open, UVA Today, and our thanks</h1>
          </td>
        </tr>
        <tr>
          <td style="padding:0 40px 16px;">
            <p style="margin:0;font-family:Georgia,serif;font-size:18px;line-height:1.5;color:${C.muted};font-style:italic;">We cut the ribbon on our new outdoor pavilion, the University community spotlighted our farm, and Give Where You Live opens soon — none of it possible without you.</p>
          </td>
        </tr>
        <tr>
          <td style="padding:4px 40px 18px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.65;color:${C.ink};">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
              <tr>
                <td style="padding:16px 18px;background:${C.cream};border-left:4px solid ${C.green};">
                  <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:${C.gold};font-weight:700;">Farm milestone</p>
                  <p style="margin:0 0 10px;font-family:Georgia,serif;font-size:20px;line-height:1.3;color:${C.forest};font-weight:700;">Pavilion ribbon cutting</p>
                  <p style="margin:0 0 12px;font-size:15px;">On <strong>August 29</strong>, we celebrated the official opening of our new outdoor pavilion — a shaded home for agriculture programming, gatherings, and everyday farm life on the Edgefield property.</p>
                  <p style="margin:0 0 12px;font-size:15px;">The pavilion was made possible with generous support from the Virginia Outdoors Foundation’s <a href="https://www.vof.org/protect/grants/go/" style="color:${C.green};font-weight:700;">Get Outdoors Fund</a>. From the first posts and framing to the ribbon cutting, members, staff, volunteers, and partners poured hours into making this space real.</p>
                  <p style="margin:0 0 14px;font-size:15px;"><a href="https://www.autismsanctuary.org/pavilion-ribbon-cutting/" style="color:${C.green};font-weight:700;">Read the full announcement →</a></p>
                  <img src="https://www.autismsanctuary.org/wp-content/uploads/2026/09/7AA1036F-DEC7-4FAE-9C70-CE13EE28F508_1_105_c.jpeg" alt="Cutting the ribbon at the pavilion opening" width="520" style="width:100%;max-width:520px;height:auto;display:block;border:0;">
                </td>
              </tr>
            </table>
            <p style="margin:0 0 18px;">We're also glad to share two pieces of good news from the University of Virginia community this month — a full profile of our farm in <em>UVA Today</em>, and the launch of this fall's <strong>Give Where You Live</strong> campaign.</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
              <tr>
                <td style="padding:16px 18px;background:${C.cream};border-left:4px solid ${C.forest};">
                  <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:${C.gold};font-weight:700;">Featured story</p>
                  <p style="margin:0 0 10px;font-family:Georgia,serif;font-size:20px;line-height:1.3;color:${C.forest};font-weight:700;">Featured in UVA Today</p>
                  <p style="margin:0 0 12px;font-size:15px;">Bryan McKenzie's story, <em>“UVA community makes a place for everyone out on the farm,”</em> shares how Autism Sanctuary grew from a family need into a state-licensed day support program on more than 80 acres in Albemarle County.</p>
                  <p style="margin:0;font-size:15px;"><a href="https://news.virginia.edu/content/uva-community-makes-place-everyone-out-farm" style="color:${C.green};font-weight:700;">Read the full story on UVA Today →</a></p>
                </td>
              </tr>
            </table>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
              <tr>
                <td style="padding:16px 18px;background:${C.creamDark};border-left:4px solid ${C.meadow};">
                  <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:${C.green};font-weight:700;">With gratitude</p>
                  <p style="margin:0 0 10px;font-family:Georgia,serif;font-size:20px;line-height:1.3;color:${C.forest};font-weight:700;">Thank you for walking with us</p>
                  <p style="margin:0 0 12px;font-size:15px;">Moments like this remind us that Autism Sanctuary did not grow alone. We are deeply grateful to the community, families, friends, donors, volunteers, partners, and neighbors who have believed in this work from the start — and who continue to show up with time, encouragement, expertise, and generosity.</p>
                  <p style="margin:0;font-size:15px;">Your support has been instrumental to every step of our success, including the pavilion. Thank you for walking this path with us.</p>
                </td>
              </tr>
            </table>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
              <tr>
                <td style="padding:16px 18px;background:${C.cream};border-left:4px solid ${C.gold};">
                  <p style="margin:0 0 6px;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:${C.gold};font-weight:700;">Coming soon</p>
                  <p style="margin:0 0 10px;font-family:Georgia,serif;font-size:20px;line-height:1.3;color:${C.forest};font-weight:700;">Give Where You Live</p>
                  <p style="margin:0 0 12px;font-size:15px;">UVA's partnership with United Way of Greater Charlottesville runs <strong>September 16</strong> through <strong>December 18</strong>. Employees can pledge to local nonprofits — including Autism Sanctuary — through payroll deduction or by card.</p>
                  <p style="margin:0;font-size:15px;"><a href="https://news.virginia.edu/content/give-where-you-live-uva-employees-will-make-difference" style="color:${C.green};font-weight:700;">Learn about Give Where You Live →</a></p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:8px 40px 28px;">
            <a href="${C.site}/donate/" style="display:inline-block;padding:14px 26px;background:${C.green};color:#fff;text-decoration:none;border-radius:4px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;">Support Autism Sanctuary</a>
          </td>
        </tr>
`,
);

// Fix accidental Handlebars in alert - Marketing may not support {{#if first_name}}
const alertHtmlFixed = alertHtml.replace(
	"Hello{{#if first_name}} {{first_name}}{{/if}},",
	"Hello {{first_name}},",
);

async function upsertDesign(name, subject, html) {
	const listed = await sg("GET", "/designs?page_size=100");
	const existing = (listed.result || []).find((d) => d.name === name);
	if (existing) {
		const updated = await sg("PATCH", `/designs/${existing.id}`, {
			name,
			subject,
			html_content: html,
			generate_plain_content: true,
		});
		console.log(`Updated design: ${name} (${updated.id || existing.id})`);
		return { id: updated.id || existing.id, name, subject };
	}
	const created = await sg("POST", "/designs", {
		name,
		subject,
		html_content: html,
		generate_plain_content: true,
		editor: "code",
	});
	console.log(`Created design: ${name} (${created.id})`);
	return { id: created.id, name, subject };
}

const alertDesign = await upsertDesign(
	"AS Alert (Marketing)",
	"Autism Sanctuary alert",
	alertHtmlFixed,
);

const newsletterSubject =
	"Pavilion open — Featured in UVA Today, with gratitude & Give Where You Live";

const newsletterDesign = await upsertDesign(
	"AS Trail Guide — UVA Today & gratitude",
	newsletterSubject,
	newsletterHtml,
);

// Create / update Single Send draft using the newsletter design.
const ssName = "DRAFT: Trail Guide — Pavilion, UVA Today & thanks";
const ssNameLegacy = "DRAFT: Trail Guide — UVA Today thank you";
const singles = await sg("GET", "/marketing/singlesends?page_size=50");
let draft = (singles.result || []).find(
	(s) =>
		s.status === "draft" &&
		(s.name === ssName || s.name === ssNameLegacy),
);

const emailConfigCreate = {
	generate_plain_content: true,
	editor: "code",
	sender_id: SENDER_NEWSLETTER,
	suppression_group_id: ASM_GROUP,
	design_id: newsletterDesign.id,
};

// Updates cannot change design_id; push subject + html directly.
const emailConfigUpdate = {
	subject: newsletterSubject,
	html_content: newsletterHtml,
	generate_plain_content: true,
	sender_id: SENDER_NEWSLETTER,
	suppression_group_id: ASM_GROUP,
};

const sendTo = {
	// Prefer Web signups + overlap-safe: also offer all=false with key lists.
	// Using all contacts is clearer for a general Trail Guide; user can narrow in UI.
	all: true,
	list_ids: [],
	segment_ids: [],
};

if (draft && draft.status === "draft") {
	draft = await sg("PATCH", `/marketing/singlesends/${draft.id}`, {
		name: ssName,
		send_to: sendTo,
		email_config: emailConfigUpdate,
	});
	console.log(`Updated Single Send draft: ${draft.id}`);
} else if (!draft) {
	draft = await sg("POST", "/marketing/singlesends", {
		name: ssName,
		send_to: sendTo,
		email_config: emailConfigCreate,
	});
	console.log(`Created Single Send draft: ${draft.id}`);
} else {
	console.log(
		`Skipping Single Send update — status is "${draft.status}" (already sent). Design Library templates were still updated for future sends.`,
	);
}

const payload = {
	updated_at: new Date().toISOString(),
	asm_group_id: ASM_GROUP,
	sender_id: SENDER_NEWSLETTER,
	designs: {
		alert: alertDesign,
		newsletter: newsletterDesign,
	},
	single_send_draft: draft
		? {
				id: draft.id,
				name: draft.name || ssName,
				status: draft.status,
				edit_url: `https://mc.sendgrid.com/single-sends/${draft.id}/review`,
			}
		: null,
	notes: [
		"Design Library footers use {{{unsubscribe_preferences}}} (Manage email preferences) — not one-click {{{unsubscribe}}} — to reduce false unsubscribes from email security scanners.",
		"Gmail/Yahoo one-click still works via SendGrid List-Unsubscribe POST headers on Marketing sends.",
		"ASM group 55041 (Trail Guide newsletter) should remain is_default=false.",
		"Dynamic Templates (AS Alert / AS Newsletter) remain for transactional API sends.",
		"Hustle footer signups go to Marketing list: Web signups.",
	],
};

writeFileSync(OUT, JSON.stringify(payload, null, "\t") + "\n");
console.log(`Wrote ${OUT}`);
console.log(JSON.stringify(payload, null, 2));
