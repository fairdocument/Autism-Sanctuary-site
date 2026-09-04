/**
 * Send a draft Trail Guide newsletter highlighting UVA Today + Give Where You Live.
 *
 * Usage:
 *   SENDGRID_API_KEY=SG.... node wordpress-migration/send-newsletter-draft-uva-gwyl.mjs
 */

const KEY = process.env.SENDGRID_API_KEY || "";
if (!KEY.startsWith("SG.")) {
	console.error("Set SENDGRID_API_KEY");
	process.exit(1);
}

const to = process.env.NEWSLETTER_TO || "brewster.jason@gmail.com";
const cc = (process.env.NEWSLETTER_CC || "olivia@autismsanctuary.org")
	.split(",")
	.map((e) => e.trim())
	.filter(Boolean);

const bodyHtml = `
<p style="margin:0 0 18px; font-family:Arial, Helvetica, sans-serif; font-size:16px; line-height:1.65; color:#1b1b1b;">We're so glad to share two pieces of good news from the University of Virginia community this month — a full profile of our farm in <em>UVA Today</em>, and the launch of this fall's <strong>Give Where You Live</strong> campaign.</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
  <tr>
    <td style="padding:16px 18px; background-color:#f7f4ec; border-left:4px solid #2f5d43;">
      <p style="margin:0 0 6px; font-family:Arial, Helvetica, sans-serif; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#c9a646; font-weight:700;">Featured story</p>
      <p style="margin:0 0 10px; font-family:Georgia, 'Times New Roman', serif; font-size:20px; line-height:1.3; color:#1e3d2c; font-weight:700;">Featured in UVA Today</p>
      <p style="margin:0 0 12px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color:#1b1b1b;">Bryan McKenzie's story, <em>“UVA community makes a place for everyone out on the farm,”</em> shares how Autism Sanctuary grew from a family need into a state-licensed day support program on more than 80 acres in Albemarle County — and how deeply UVA runs through our work.</p>
      <p style="margin:0 0 12px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color:#1b1b1b;">The piece follows members and staff with our animals and gardens, highlights founders Jason and Jennifer Brewster, and introduces Executive Director Olivia Bruno and Director of Adult Services Izzy Kueser — both Education School alumni.</p>
      <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:15px;"><a href="https://news.virginia.edu/content/uva-community-makes-place-everyone-out-farm" style="color:#2f5d43; font-weight:700; text-decoration:underline;">Read the full story on UVA Today →</a></p>
    </td>
  </tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
  <tr>
    <td style="padding:16px 18px; background-color:#f7f4ec; border-left:4px solid #c9a646;">
      <p style="margin:0 0 6px; font-family:Arial, Helvetica, sans-serif; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; color:#c9a646; font-weight:700;">Coming soon</p>
      <p style="margin:0 0 10px; font-family:Georgia, 'Times New Roman', serif; font-size:20px; line-height:1.3; color:#1e3d2c; font-weight:700;">Give Where You Live</p>
      <p style="margin:0 0 12px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color:#1b1b1b;">UVA's partnership with United Way of Greater Charlottesville runs <strong>September 16</strong> (Laurence E. Richardson Day of Caring) through <strong>December 18</strong>. Employees can pledge to local nonprofits — including Autism Sanctuary — through payroll deduction or by card.</p>
      <p style="margin:0 0 12px; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color:#1b1b1b;">Your gift helps keep farm-based day support going for adults with autism and related developmental disabilities, and moves us closer to the activity barn that will let us welcome more people on our waitlist.</p>
      <p style="margin:0; font-family:Arial, Helvetica, sans-serif; font-size:15px;"><a href="https://news.virginia.edu/content/give-where-you-live-uva-employees-will-make-difference" style="color:#2f5d43; font-weight:700; text-decoration:underline;">Learn about Give Where You Live →</a></p>
    </td>
  </tr>
</table>

<p style="margin:18px 0 0; font-family:Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color:#4a534c;">If you're a Hoo, watch for pledging options starting September 16. Anyone can also support the farm directly anytime.</p>
`.trim();

const personalization = {
	to: [{ email: to }],
	dynamic_template_data: {
		subject:
			"[DRAFT] Featured in UVA Today — Give Where You Live starts Sept 16",
		preheader:
			"Our farm in UVA Today, plus how Hoos can support Autism Sanctuary this fall.",
		issue_label: "Trail Guide · September 2026",
		headline: "A UVA Today feature — and a chance to give where you live",
		intro:
			"This month the University community spotlighted our farm, and Give Where You Live opens September 16 for employees who want to support Autism Sanctuary.",
		body_html: bodyHtml,
		featured_image_url:
			"https://www.autismsanctuary.org/wp-content/uploads/2026/09/Header_AutismSanctuary_MR.jpg",
		featured_image_alt:
			"Ty Hopkins and Megan McGrath with Russet the cow at Autism Sanctuary. Photo by Matt Riley, University Communications / UVA Today.",
		cta_url: "https://autismsanctuary.org/donate/",
		cta_text: "Support Autism Sanctuary",
	},
};

if (cc.length) {
	personalization.cc = cc.map((email) => ({ email }));
}

const payload = {
	from: {
		email: "newsletters@autismsanctuary.org",
		name: "Autism Sanctuary Newsletters",
	},
	reply_to: {
		email: "info@autismsanctuary.org",
		name: "Autism Sanctuary",
	},
	personalizations: [personalization],
	template_id: "d-e75934b564a240cfa0abca50ce2bf5f5",
	categories: ["newsletter-draft", "trail-guide"],
};

const res = await fetch("https://api.sendgrid.com/v3/mail/send", {
	method: "POST",
	headers: {
		Authorization: `Bearer ${KEY}`,
		"Content-Type": "application/json",
	},
	body: JSON.stringify(payload),
});

const text = await res.text();
console.log(`http:${res.status}`);
if (text) console.log(text);
if (!res.ok) process.exit(1);
console.log(`Draft sent to ${to}${cc.length ? ` (cc ${cc.join(", ")})` : ""}`);
