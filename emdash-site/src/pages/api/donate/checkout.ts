import type { APIRoute } from "astro";
import Stripe from "stripe";
import { donateFunds } from "../../../content/siteCopy";

export const prerender = false;

const FUND_LABELS = Object.fromEntries(donateFunds.map((f) => [f.value, f.label]));

const NOTICE_LABELS: Record<string, string> = {
	donor_email: "Donor email",
	donor_mail: "Donor mailing address",
	honoree_mail: "Honoree address",
	other_mail: "Another address",
};

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
			donor_name?: string;
			donor_email?: string;
			in_honor?: string;
			honoree_name?: string;
			notice_to?: string;
			notice_email?: string;
			notice_recipient?: string;
			notice_street?: string;
			notice_city?: string;
			notice_state?: string;
			notice_zip?: string;
		};

		const amount = Number(body.amount);
		const frequency = body.frequency === "monthly" ? "monthly" : "one_time";
		const fund = body.fund || "where-needed";
		const donorName = String(body.donor_name || "").trim();
		const donorEmail = String(body.donor_email || "").trim();
		const inHonor = body.in_honor === "yes";

		if (!Number.isFinite(amount) || amount < 1 || amount > 100000) {
			return json({ ok: false, message: "Please enter a valid gift amount." }, 400);
		}
		if (!donorName || !donorEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(donorEmail)) {
			return json({ ok: false, message: "Please provide a valid name and email." }, 400);
		}

		const honoreeName = String(body.honoree_name || "").trim();
		const noticeTo = String(body.notice_to || "donor_email");
		if (inHonor && !honoreeName) {
			return json({ ok: false, message: "Please enter the honoree’s name." }, 400);
		}

		const stripe = new Stripe(secret);
		const unitAmount = Math.round(amount * 100);
		const fundLabel = FUND_LABELS[fund] || fund;
		const origin = url.origin;

		const metadata: Record<string, string> = {
			fund,
			fund_label: fundLabel,
			organization: "Autism Sanctuary",
			frequency,
			donor_name: donorName.slice(0, 400),
			in_honor: inHonor ? "yes" : "no",
		};

		if (inHonor) {
			metadata.honoree_name = honoreeName.slice(0, 400);
			metadata.notice_to = noticeTo;
			metadata.notice_to_label = NOTICE_LABELS[noticeTo] || noticeTo;

			if (noticeTo === "donor_email") {
				const noticeEmail = String(body.notice_email || donorEmail).trim();
				metadata.notice_email = noticeEmail.slice(0, 400);
			} else {
				const recipient = String(body.notice_recipient || donorName).trim();
				const street = String(body.notice_street || "").trim();
				const city = String(body.notice_city || "").trim();
				const state = String(body.notice_state || "").trim();
				const zip = String(body.notice_zip || "").trim();
				if (!street || !city || !state || !zip) {
					return json(
						{ ok: false, message: "Please complete the mailing address for the acknowledgment notice." },
						400,
					);
				}
				metadata.notice_recipient = recipient.slice(0, 200);
				metadata.notice_address = `${street}, ${city}, ${state} ${zip}`.slice(0, 500);
			}
		}

		const productName =
			frequency === "monthly"
				? inHonor
					? `Monthly gift in honor of ${honoreeName}`
					: "Monthly gift to Autism Sanctuary"
				: inHonor
					? `Donation in honor of ${honoreeName}`
					: "Donation to Autism Sanctuary";

		const session = await stripe.checkout.sessions.create({
			mode: frequency === "monthly" ? "subscription" : "payment",
			success_url: `${origin}/donate/thanks?session_id={CHECKOUT_SESSION_ID}`,
			cancel_url: `${origin}/donate?canceled=1`,
			submit_type: frequency === "one_time" ? "donate" : undefined,
			billing_address_collection: "required",
			customer_email: donorEmail,
			metadata,
			payment_intent_data:
				frequency === "one_time"
					? {
							metadata,
						}
					: undefined,
			subscription_data:
				frequency === "monthly"
					? {
							metadata,
						}
					: undefined,
			line_items: [
				{
					quantity: 1,
					price_data: {
						currency: "usd",
						unit_amount: unitAmount,
						product_data: {
							name: productName.slice(0, 120),
							description: inHonor
								? `Designated: ${fundLabel}. In honor of ${honoreeName}.`
								: `Designated: ${fundLabel}`,
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
