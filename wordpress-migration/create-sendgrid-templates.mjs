/**
 * Create / refresh Autism Sanctuary SendGrid dynamic templates (Alert + Newsletter).
 *
 * Usage:
 *   SENDGRID_API_KEY=SG.... node wordpress-migration/create-sendgrid-templates.mjs
 *
 * Does not print or store the API key. Writes template IDs to
 * wordpress-migration/sendgrid-templates.json
 */

import { writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const API = "https://api.sendgrid.com/v3";
const KEY = process.env.SENDGRID_API_KEY || "";
if (!KEY.startsWith("SG.")) {
	console.error("Set SENDGRID_API_KEY to a SendGrid API key (SG....)");
	process.exit(1);
}

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT = join(__dirname, "sendgrid-templates.json");

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
	email: "info@autismsanctuary.org",
	newsEmail: "newsletters@autismsanctuary.org",
	/** Hosted on the live WP media library for reliable email embedding */
	logoUrl:
		"https://www.autismsanctuary.org/wp-content/uploads/2026/08/autism-sanctuary-logo-300x284.png",
};

/** Shared email-safe CSS + table shell. */
function document(title, bodyRows) {
	return `<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>${title}</title>
<!--[if mso]>
<style type="text/css">
  body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
</style>
<![endif]-->
<style type="text/css">
  body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
  table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
  img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
  body { margin: 0 !important; padding: 0 !important; width: 100% !important; background: ${C.cream}; }
  a { color: ${C.green}; }
  .body-copy p { margin: 0 0 14px 0; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.65; color: ${C.ink}; }
  .body-copy p:last-child { margin-bottom: 0; }
  .body-copy a { color: ${C.green}; text-decoration: underline; }
  .body-copy ul, .body-copy ol { margin: 0 0 14px 0; padding-left: 22px; font-family: Arial, Helvetica, sans-serif; font-size: 16px; line-height: 1.65; color: ${C.ink}; }
  @media only screen and (max-width: 620px) {
    .email-container { width: 100% !important; }
    .fluid { width: 100% !important; height: auto !important; }
    .stack-pad { padding-left: 22px !important; padding-right: 22px !important; }
    .headline { font-size: 26px !important; line-height: 1.25 !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background-color:${C.cream};">
<div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden; mso-hide:all;">
  {{preheader}}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:${C.cream};">
  <tr>
    <td align="center" style="padding:28px 12px;">
      <table role="presentation" class="email-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background-color:${C.white};">
${bodyRows}
      </table>
    </td>
  </tr>
</table>
</body>
</html>`;
}

function topAccent(color = C.green) {
	return `        <tr>
          <td style="height:6px; line-height:6px; font-size:0; background-color:${color};">&nbsp;</td>
        </tr>`;
}

function brandHeader({ eyebrow, subline }) {
	return `        <tr>
          <td class="stack-pad" style="padding:24px 40px 8px; background-color:${C.white};">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="84" valign="middle" style="width:84px; padding:0 18px 0 0;">
                  <a href="${C.site}" style="text-decoration:none;">
                    <img src="${C.logoUrl}" width="72" height="68" alt="Autism Sanctuary" style="width:72px; height:auto; display:block; border:0;">
                  </a>
                </td>
                <td valign="middle" style="padding:0;">
                  <p style="margin:0 0 6px; font-family:Georgia, 'Times New Roman', serif; font-size:13px; letter-spacing:0.14em; text-transform:uppercase; color:${C.gold}; font-weight:700;">
                    ${eyebrow}
                  </p>
                  <p style="margin:0; font-family:Georgia, 'Times New Roman', serif; font-size:26px; line-height:1.15; color:${C.forest}; font-weight:700;">
                    <a href="${C.site}" style="color:${C.forest}; text-decoration:none;">Autism Sanctuary</a>
                  </p>
                  ${
										subline
											? `<p style="margin:8px 0 0; font-family:Arial, Helvetica, sans-serif; font-size:14px; line-height:1.45; color:${C.muted};">${subline}</p>`
											: ""
									}
                </td>
              </tr>
            </table>
          </td>
        </tr>`;
}

function rule() {
	return `        <tr>
          <td class="stack-pad" style="padding:16px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr><td style="border-top:1px solid ${C.rule}; height:1px; line-height:1px; font-size:0;">&nbsp;</td></tr>
            </table>
          </td>
        </tr>`;
}

function ctaButton() {
	return `{{#if cta_url}}
        <tr>
          <td class="stack-pad" style="padding:8px 40px 8px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background-color:${C.green}; border-radius:4px;">
                  <a href="{{cta_url}}" style="display:inline-block; padding:14px 26px; font-family:Arial, Helvetica, sans-serif; font-size:15px; font-weight:700; color:${C.white}; text-decoration:none; border-radius:4px;">
                    {{cta_text}}
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
{{/if}}`;
}

function footer({ contactEmail, showUnsubscribe = true }) {
	return `        <tr>
          <td style="background-color:${C.creamDark}; padding:0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td class="stack-pad" style="padding:24px 40px 28px; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:1.55; color:${C.muted};">
                  <a href="${C.site}" style="text-decoration:none;">
                    <img src="${C.logoUrl}" width="48" height="45" alt="Autism Sanctuary" style="width:48px; height:auto; display:block; border:0; margin:0 0 12px;">
                  </a>
                  <p style="margin:0 0 8px; font-family:Georgia, 'Times New Roman', serif; font-size:15px; color:${C.forest}; font-weight:700;">
                    Autism Sanctuary
                  </p>
                  <p style="margin:0 0 4px;">501(c)(3) nonprofit &amp; Virginia DBHDS-licensed care farm</p>
                  <p style="margin:0 0 4px;">${C.address}</p>
                  <p style="margin:0 0 12px;">
                    <a href="tel:+14342072118" style="color:${C.green}; text-decoration:none;">${C.phone}</a>
                    &nbsp;·&nbsp;
                    <a href="mailto:${contactEmail}" style="color:${C.green}; text-decoration:none;">${contactEmail}</a>
                  </p>
                  <p style="margin:0;">
                    <a href="${C.site}" style="color:${C.green}; text-decoration:underline;">autismsanctuary.org</a>${
											showUnsubscribe
												? `&nbsp;·&nbsp;<a href="<%asm_preferences_raw_url%>" style="color:${C.muted}; text-decoration:underline;">Manage email preferences</a>`
												: ""
										}
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>`;
}

const alertHtml = document(
	"Autism Sanctuary Alert",
	`
${topAccent(C.gold)}
${brandHeader({
	eyebrow: "Important notice",
	subline: "Nature's haven for autism — where growth knows no limits.",
})}
        <tr>
          <td class="stack-pad" style="padding:20px 40px 0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="background-color:${C.gold}; border-radius:3px; padding:6px 12px; font-family:Arial, Helvetica, sans-serif; font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:${C.forest};">
                  Alert
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td class="stack-pad" style="padding:16px 40px 8px;">
            <h1 class="headline" style="margin:0; font-family:Georgia, 'Times New Roman', serif; font-size:30px; line-height:1.22; color:${C.forest}; font-weight:700;">
              {{headline}}
            </h1>
          </td>
        </tr>
        <tr>
          <td class="stack-pad body-copy" style="padding:8px 40px 20px;">
            {{{body_html}}}
          </td>
        </tr>
${ctaButton()}
        <tr><td style="height:20px; line-height:20px; font-size:0;">&nbsp;</td></tr>
${footer({ contactEmail: C.email })}
`,
);

const newsletterHtml = document(
	"Autism Sanctuary Newsletter",
	`
${topAccent(C.green)}
${brandHeader({
	eyebrow: "{{issue_label}}",
	subline: "Trail Guide — happenings from the farm and programs",
})}
{{#if featured_image_url}}
        <tr>
          <td style="padding:20px 0 0;">
            <img class="fluid" src="{{featured_image_url}}" alt="{{featured_image_alt}}" width="600" style="width:100%; max-width:600px; height:auto; display:block;">
          </td>
        </tr>
{{/if}}
${rule()}
        <tr>
          <td class="stack-pad" style="padding:8px 40px 10px;">
            <h1 class="headline" style="margin:0; font-family:Georgia, 'Times New Roman', serif; font-size:32px; line-height:1.2; color:${C.forest}; font-weight:700;">
              {{headline}}
            </h1>
          </td>
        </tr>
{{#if intro}}
        <tr>
          <td class="stack-pad" style="padding:0 40px 16px;">
            <p style="margin:0; font-family:Georgia, 'Times New Roman', serif; font-size:18px; line-height:1.5; color:${C.muted}; font-style:italic;">
              {{intro}}
            </p>
          </td>
        </tr>
{{/if}}
        <tr>
          <td class="stack-pad body-copy" style="padding:4px 40px 20px;">
            {{{body_html}}}
          </td>
        </tr>
${ctaButton()}
        <tr><td style="height:24px; line-height:24px; font-size:0;">&nbsp;</td></tr>
${footer({ contactEmail: C.newsEmail })}
`,
);

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
		throw new Error(`${method} ${path} → ${res.status}: ${text.slice(0, 500)}`);
	}
	return data;
}

async function findExisting(name) {
	const data = await sg("GET", "/templates?generations=dynamic&page_size=200");
	const list = data?.result || [];
	return list.find((t) => t.name === name) || null;
}

async function ensureTemplate({ name, versionLabel, subject, html, testData }) {
	let tpl = await findExisting(name);
	if (!tpl) {
		tpl = await sg("POST", "/templates", { name, generation: "dynamic" });
		console.log(`Created template: ${name} (${tpl.id})`);
	} else {
		console.log(`Updating template: ${name} (${tpl.id})`);
	}

	const version = await sg("POST", `/templates/${tpl.id}/versions`, {
		active: 1,
		name: versionLabel,
		subject,
		html_content: html,
		generate_plain_content: true,
		editor: "code",
		test_data: JSON.stringify(testData),
	});
	console.log(`  Active version: ${version.id} (${versionLabel})`);
	return { id: tpl.id, name, version_id: version.id, subject };
}

const alert = await ensureTemplate({
	name: "AS Alert",
	versionLabel: "AS Alert preferences unsubscribe",
	subject: "{{subject}}",
	html: alertHtml,
	testData: {
		subject: "Program update — Autism Sanctuary",
		preheader: "Important update from Autism Sanctuary",
		headline: "Day program schedule change",
		body_html:
			"<p>Hello,</p><p>We are adjusting Monday programming this week due to weather. Please check with your support coordinator if you have questions.</p><p>Thank you for your flexibility.</p>",
		cta_url: "https://autismsanctuary.org/contact",
		cta_text: "Contact us",
		unsubscribe: "https://autismsanctuary.org",
	},
});

const newsletter = await ensureTemplate({
	name: "AS Newsletter",
	versionLabel: "AS Newsletter preferences unsubscribe",
	subject: "{{subject}}",
	html: newsletterHtml,
	testData: {
		subject: "Happening now at Autism Sanctuary",
		preheader: "Stories from the farm and programs",
		issue_label: "Farm & community update",
		headline: "Growth on the farm this season",
		intro:
			"A short look at what our farmers and staff have been building together.",
		body_html:
			"<p>Thank you for following Autism Sanctuary. This month we celebrated new skills, community outings, and quiet moments in the meadow.</p><p>We hope you'll visit soon — or share our work with a friend who might want to support the farm.</p>",
		featured_image_url:
			"https://www.autismsanctuary.org/wp-content/uploads/2026/09/IMG_2570.jpeg",
		featured_image_alt: "Autism Sanctuary farm",
		cta_url: "https://autismsanctuary.org/donate",
		cta_text: "Support the sanctuary",
		unsubscribe: "https://autismsanctuary.org",
	},
});

const payload = {
	updated_at: new Date().toISOString(),
	from: {
		alerts: C.email,
		newsletters: C.newsEmail,
	},
	templates: {
		alert,
		newsletter,
	},
	unsubscribe: {
		footer_tag: "<%asm_preferences_raw_url%>",
		label: "Manage email preferences",
		asm_group_id: 55041,
	},
	handlebars: {
		alert: [
			"subject",
			"preheader",
			"headline",
			"body_html",
			"cta_url",
			"cta_text",
		],
		newsletter: [
			"subject",
			"preheader",
			"issue_label",
			"headline",
			"intro",
			"body_html",
			"featured_image_url",
			"featured_image_alt",
			"cta_url",
			"cta_text",
		],
	},
	notes: [
		"Footer uses <%asm_preferences_raw_url%> (Manage email preferences) instead of one-click unsubscribe to reduce false unsubscribes from email security scanners.",
		"When sending via API, include asm.group_id (Trail Guide newsletter = 55041) so the preferences link resolves.",
	],
};

writeFileSync(OUT, JSON.stringify(payload, null, "\t") + "\n");
console.log(`Wrote ${OUT}`);
console.log(JSON.stringify(payload.templates, null, 2));
