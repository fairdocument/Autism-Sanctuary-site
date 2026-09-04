/**
 * Create Autism Sanctuary SendGrid dynamic templates (Alert + Newsletter).
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

const BRAND = {
	green: "#2f5d43",
	forest: "#1e3d2c",
	meadow: "#a7c4a0",
	cream: "#f7f4ec",
	gold: "#c9a646",
	ink: "#1b1b1b",
	muted: "#4a534c",
	rule: "#d9d2c4",
	site: "https://autismsanctuary.org",
	address: "2860 Pea Ridge Road, Charlottesville, VA 22901",
	phone: "(434) 207-2118",
	email: "info@autismsanctuary.org",
};

function shell(inner) {
	return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Autism Sanctuary</title>
<style>
  body { margin:0; padding:0; background:${BRAND.cream}; color:${BRAND.ink};
    font-family: Georgia, "Times New Roman", serif; }
  .wrap { max-width:600px; margin:0 auto; background:#ffffff; }
  .bar { height:6px; background:${BRAND.green}; }
  .pad { padding:28px 32px; }
  .brand { font-size:22px; font-weight:700; color:${BRAND.forest}; letter-spacing:0.02em; margin:0 0 4px; }
  .tag { font-size:13px; color:${BRAND.muted}; margin:0 0 20px; font-family: Arial, Helvetica, sans-serif; }
  h1 { font-size:26px; line-height:1.25; color:${BRAND.forest}; margin:0 0 16px; font-weight:700; }
  p, li { font-size:16px; line-height:1.6; color:${BRAND.ink};
    font-family: Arial, Helvetica, sans-serif; }
  .body { font-family: Arial, Helvetica, sans-serif; }
  .cta { display:inline-block; margin:20px 0 8px; padding:12px 22px; background:${BRAND.green};
    color:#ffffff !important; text-decoration:none; border-radius:4px; font-family: Arial, Helvetica, sans-serif;
    font-size:15px; font-weight:600; }
  .footer { padding:20px 32px 28px; border-top:1px solid ${BRAND.rule};
    font-family: Arial, Helvetica, sans-serif; font-size:12px; color:${BRAND.muted}; line-height:1.5; }
  .footer a { color:${BRAND.green}; }
  .preheader { display:none !important; visibility:hidden; opacity:0; height:0; width:0; overflow:hidden; }
  .alert-badge { display:inline-block; background:${BRAND.gold}; color:${BRAND.forest};
    font-family: Arial, Helvetica, sans-serif; font-size:11px; font-weight:700;
    letter-spacing:0.08em; text-transform:uppercase; padding:5px 10px; border-radius:3px; margin:0 0 14px; }
  .hero-img { width:100%; height:auto; display:block; }
</style>
</head>
<body>
${inner}
</body>
</html>`;
}

const alertHtml = shell(`
  <div class="preheader">{{preheader}}</div>
  <div class="wrap">
    <div class="bar"></div>
    <div class="pad">
      <p class="brand">Autism Sanctuary</p>
      <p class="tag">Nature's haven for autism</p>
      <div class="alert-badge">Alert</div>
      <h1>{{headline}}</h1>
      <div class="body">{{{body_html}}}</div>
      {{#if cta_url}}
      <p><a class="cta" href="{{cta_url}}">{{cta_text}}</a></p>
      {{/if}}
    </div>
    <div class="footer">
      <p><strong>Autism Sanctuary</strong> · 501(c)(3) nonprofit<br>
      ${BRAND.address}<br>
      <a href="tel:+14342072118">${BRAND.phone}</a> ·
      <a href="mailto:${BRAND.email}">${BRAND.email}</a></p>
      <p style="margin-top:12px;"><a href="${BRAND.site}">autismsanctuary.org</a>
      {{#if unsubscribe}} · <a href="{{{unsubscribe}}}">Unsubscribe</a>{{/if}}</p>
    </div>
  </div>
`);

const newsletterHtml = shell(`
  <div class="preheader">{{preheader}}</div>
  <div class="wrap">
    <div class="bar"></div>
    {{#if featured_image_url}}
    <img class="hero-img" src="{{featured_image_url}}" alt="{{featured_image_alt}}">
    {{/if}}
    <div class="pad">
      <p class="brand">Autism Sanctuary</p>
      <p class="tag">{{issue_label}}</p>
      <h1>{{headline}}</h1>
      {{#if intro}}
      <p style="font-size:17px; color:${BRAND.muted}; margin:0 0 18px;">{{intro}}</p>
      {{/if}}
      <div class="body">{{{body_html}}}</div>
      {{#if cta_url}}
      <p><a class="cta" href="{{cta_url}}">{{cta_text}}</a></p>
      {{/if}}
    </div>
    <div class="footer">
      <p><strong>Autism Sanctuary</strong> · 501(c)(3) nonprofit &amp; Virginia DBHDS-licensed care farm<br>
      ${BRAND.address}<br>
      <a href="tel:+14342072118">${BRAND.phone}</a> ·
      <a href="mailto:newsletters@autismsanctuary.org">newsletters@autismsanctuary.org</a></p>
      <p style="margin-top:12px;">
        <a href="${BRAND.site}">autismsanctuary.org</a>
        {{#if unsubscribe}} · <a href="{{{unsubscribe}}}">Unsubscribe</a>{{/if}}
      </p>
    </div>
  </div>
`);

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
	const data = await sg(
		"GET",
		"/templates?generations=dynamic&page_size=200",
	);
	const list = data?.result || [];
	return list.find((t) => t.name === name) || null;
}

async function ensureTemplate({ name, subject, html, testData }) {
	let tpl = await findExisting(name);
	if (!tpl) {
		tpl = await sg("POST", "/templates", { name, generation: "dynamic" });
		console.log(`Created template: ${name} (${tpl.id})`);
	} else {
		console.log(`Reusing template: ${name} (${tpl.id})`);
	}

	const version = await sg("POST", `/templates/${tpl.id}/versions`, {
		active: 1,
		name: `${name} v1`,
		subject,
		html_content: html,
		generate_plain_content: true,
		editor: "code",
		test_data: JSON.stringify(testData),
	});
	console.log(`  Active version: ${version.id}`);
	return { id: tpl.id, name, version_id: version.id, subject };
}

const alert = await ensureTemplate({
	name: "AS Alert",
	subject: "{{subject}}",
	html: alertHtml,
	testData: {
		subject: "Program update — Autism Sanctuary",
		preheader: "Important update from Autism Sanctuary",
		headline: "Day program schedule change",
		body_html:
			"<p>Hello,</p><p>We are adjusting Monday programming this week due to weather. Please check with your support coordinator if you have questions.</p>",
		cta_url: "https://autismsanctuary.org/contact",
		cta_text: "Contact us",
		unsubscribe: "https://autismsanctuary.org",
	},
});

const newsletter = await ensureTemplate({
	name: "AS Newsletter",
	subject: "{{subject}}",
	html: newsletterHtml,
	testData: {
		subject: "Happening now at Autism Sanctuary",
		preheader: "Stories from the farm and programs",
		issue_label: "Farm & community update",
		headline: "Growth on the farm this season",
		intro: "A short look at what our farmers and staff have been building together.",
		body_html:
			"<p>Thank you for following Autism Sanctuary. This month we celebrated new skills, community outings, and quiet moments in the meadow.</p><p>We hope you'll visit soon — or share our work with a friend who might want to support the farm.</p>",
		featured_image_url: "https://autismsanctuary.org/wp-content/uploads/2024/01/placeholder.jpg",
		featured_image_alt: "Autism Sanctuary farm",
		cta_url: "https://autismsanctuary.org/donate",
		cta_text: "Support the sanctuary",
		unsubscribe: "https://autismsanctuary.org",
	},
});

const payload = {
	updated_at: new Date().toISOString(),
	from: {
		alerts: "info@autismsanctuary.org",
		newsletters: "newsletters@autismsanctuary.org",
	},
	templates: {
		alert,
		newsletter,
	},
	handlebars: {
		alert: [
			"subject",
			"preheader",
			"headline",
			"body_html",
			"cta_url",
			"cta_text",
			"unsubscribe",
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
			"unsubscribe",
		],
	},
};

writeFileSync(OUT, JSON.stringify(payload, null, "\t") + "\n");
console.log(`Wrote ${OUT}`);
console.log(JSON.stringify(payload.templates, null, 2));
