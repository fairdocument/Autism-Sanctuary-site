import type { APIRoute } from "astro";

export const GET: APIRoute = ({ site, url }) => {
	const origin = (site?.toString() || url.origin).replace(/\/$/, "");
	const body = `User-agent: *
Allow: /
Disallow: /_emdash/

Sitemap: ${origin}/sitemap.xml
`;
	return new Response(body, {
		headers: { "Content-Type": "text/plain; charset=utf-8" },
	});
};
