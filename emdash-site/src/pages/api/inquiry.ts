import type { APIRoute } from "astro";
import { inquiryIntents } from "../../content/siteCopy";

export const prerender = false;

const INTENT_LABELS = Object.fromEntries(inquiryIntents.map((i) => [i.value, i.label]));

export const POST: APIRoute = async ({ request, url }) => {
	try {
		const form = await request.formData();
		const honeypot = String(form.get("_hp") || "");
		if (honeypot) {
			return json({ ok: true, message: "Thank you. We received your inquiry." });
		}

		const name = String(form.get("name") || "").trim();
		const email = String(form.get("email") || "").trim();
		const phone = String(form.get("phone") || "").trim();
		const intent = String(form.get("intent") || "general").trim();
		const message = String(form.get("message") || "").trim();

		if (!name || !email || !message) {
			return json({ ok: false, message: "Name, email, and message are required." }, 400);
		}
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
			return json({ ok: false, message: "Please enter a valid email address." }, 400);
		}
		if (message.length > 5000) {
			return json({ ok: false, message: "Message is too long." }, 400);
		}

		const intentLabel = INTENT_LABELS[intent] || intent;
		const composed = [
			`Intent: ${intentLabel}`,
			phone ? `Phone: ${phone}` : null,
			"",
			message,
		]
			.filter((line) => line !== null)
			.join("\n");

		const origin = url.origin;
		const submitUrl = new URL("/_emdash/api/plugins/contact-form/submit", origin);

		const upstream = await fetch(submitUrl, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				Accept: "application/json",
				Origin: origin,
			},
			body: JSON.stringify({
				fields: {
					name,
					email,
					message: composed,
				},
				_hp: "",
				_submitTime: Date.now() - 3000,
			}),
		});

		const payload = (await upstream.json().catch(() => ({}))) as {
			ok?: boolean;
			data?: { ok?: boolean; message?: string };
			message?: string;
			error?: string;
		};

		const ok = upstream.ok && (payload.ok === true || payload.data?.ok === true);
		if (!ok) {
			// Fallback: still acknowledge if contact plugin path differs; log-friendly message
			console.error("inquiry upstream failed", upstream.status, payload);
			return json(
				{
					ok: false,
					message:
						payload.message ||
						payload.data?.message ||
						"We could not send your inquiry right now. Please email info@autismsanctuary.org.",
				},
				502,
			);
		}

		return json({
			ok: true,
			message: "Thank you. We received your inquiry and will follow up soon.",
		});
	} catch (err) {
		console.error("inquiry error", err);
		return json(
			{
				ok: false,
				message: "We could not send your inquiry right now. Please email info@autismsanctuary.org.",
			},
			500,
		);
	}
};

function json(body: Record<string, unknown>, status = 200) {
	return new Response(JSON.stringify(body), {
		status,
		headers: { "Content-Type": "application/json" },
	});
}
