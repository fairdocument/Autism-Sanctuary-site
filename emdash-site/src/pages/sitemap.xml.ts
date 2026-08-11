import type { APIRoute } from "astro";

const paths = [
	"/",
	"/about",
	"/people",
	"/programs",
	"/our-farm",
	"/admissions",
	"/resources",
	"/careers",
	"/fellowship",
	"/donate",
	"/contact",
	"/news",
	"/privacy",
	"/terms",
];

export const GET: APIRoute = ({ site, url }) => {
	const origin = (site?.toString() || url.origin).replace(/\/$/, "");
	const urls = paths
		.map(
			(path) => `  <url>
    <loc>${origin}${path === "/" ? "/" : path}</loc>
    <changefreq>weekly</changefreq>
  </url>`,
		)
		.join("\n");

	const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>`;

	return new Response(xml, {
		headers: {
			"Content-Type": "application/xml; charset=utf-8",
			"Cache-Control": "public, max-age=3600",
		},
	});
};
