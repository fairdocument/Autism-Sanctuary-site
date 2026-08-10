import type { APIRoute } from "astro";
import Stripe from "stripe";
import { donateFunds } from "../../../content/siteCopy";

export const prerender = false;

const FUND_LABELS = Object.fromEntries(donateFunds.map((f) => [f.value, f.label]));

export const POST: APIRoute = async ({ request, url }) => {
	const secret = process.env.STRIPE_SECRET_KEY;
	if (!secret) {
		return json(
			{
				ok: false,
				message:
					"Online checkout is being connected. Please email info@autismsanctuary.org to give, or try again soon.",
			},
			503,
		);
	}

	try {
		const body = (await request.json()) as {
			amount?: number;
			frequency?: "one_time" | "monthly";
			fund?: string;
		};

		const amount = Number(body.amount);
		const frequency = body.frequency === "monthly" ? "monthly" : "one_time";
		const fund = body.fund || "where-needed";

		if (!Number.isFinite(amount) || amount < 1 || amount > 100000) {
			return json({ ok: false, message: "Please enter a valid gift amount." }, 400);
		}

		const stripe = new Stripe(secret);
		const unitAmount = Math.round(amount * 100);
		const fundLabel = FUND_LABELS[fund] || fund;
		const origin = url.origin;

		const session = await stripe.checkout.sessions.create({
			mode: frequency === "monthly" ? "subscription" : "payment",
			success_url: `${origin}/donate/thanks?session_id={CHECKOUT_SESSION_ID}`,
			cancel_url: `${origin}/donate?canceled=1`,
			submit_type: frequency === "one_time" ? "donate" : undefined,
			billing_address_collection: "required",
			customer_email: undefined,
			metadata: {
				fund,
				fund_label: fundLabel,
				organization: "Autism Sanctuary",
				frequency,
			},
			line_items: [
				{
					quantity: 1,
					price_data: {
						currency: "usd",
						unit_amount: unitAmount,
						product_data: {
							name:
								frequency === "monthly"
									? "Monthly gift to Autism Sanctuary"
									: "Donation to Autism Sanctuary",
							description: `Designated: ${fundLabel}`,
						},
						...(frequency === "monthly"
							? { recurring: { interval: "month" as const } }
							: {}),
					},
				},
			],
		});

		if (!session.url) {
			return json({ ok: false, message: "Stripe did not return a checkout URL." }, 502);
		}

		return json({ ok: true, url: session.url });
	} catch (err) {
		console.error("stripe checkout error", err);
		return json(
			{
				ok: false,
				message:
					err instanceof Error
						? err.message
						: "Unable to start Stripe Checkout. Please email info@autismsanctuary.org.",
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
