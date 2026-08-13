import { definePlugin } from "emdash";

const text = (action_id: string, label: string, opts: { multiline?: boolean; placeholder?: string; initial?: string } = {}) => ({
	type: "text_input" as const,
	action_id,
	label,
	...(opts.multiline ? { multiline: true } : {}),
	...(opts.placeholder ? { placeholder: opts.placeholder } : {}),
	...(opts.initial ? { initial_value: opts.initial } : {}),
});

const toggle = (action_id: string, label: string) => ({
	type: "toggle" as const,
	action_id,
	label,
});

export function createPlugin() {
	return definePlugin({
		id: "as-page-blocks",
		version: "0.1.0",
		admin: {
			portableTextBlocks: [
				{
					type: "asHero",
					label: "Homepage Hero",
					icon: "video",
					description: "Full-bleed video hero with brand, headline, and CTAs",
					category: "Layout",
					fields: [
						text("eyebrow", "Eyebrow"),
						text("brand", "Brand name", { initial: "Autism Sanctuary" }),
						text("heading", "Headline", { multiline: true }),
						text("lede", "Lede", { multiline: true }),
						text("videoSrc", "Video URL", { placeholder: "/media/hero-farm.mp4" }),
						text("posterSrc", "Poster image URL", { placeholder: "/media/edgefield-aerial.jpg" }),
						text("primaryLabel", "Primary CTA label"),
						text("primaryHref", "Primary CTA href"),
						text("secondaryLabel", "Secondary CTA label"),
						text("secondaryHref", "Secondary CTA href"),
					],
				},
				{
					type: "asSplitFeature",
					label: "Split Feature",
					icon: "link",
					description: "Split copy + image section with optional checklist",
					category: "Layout",
					fields: [
						text("eyebrow", "Eyebrow"),
						text("heading", "Heading", { multiline: true }),
						text("body", "Body", { multiline: true }),
						text("checklist", "Checklist (JSON array or Title|Description lines)", {
							multiline: true,
							placeholder: '[{"title":"...","text":"..."}]',
						}),
						text("imageSrc", "Image URL"),
						text("imageAlt", "Image alt text"),
						toggle("reverse", "Image on left"),
						toggle("headingInStack", "Heading inside copy column"),
						text("sectionClass", "Section class", {
							placeholder: "section-cream",
							initial: "section-cream",
						}),
						text("primaryLabel", "Primary CTA label"),
						text("primaryHref", "Primary CTA href"),
						text("secondaryLabel", "Secondary CTA label"),
						text("secondaryHref", "Secondary CTA href"),
					],
				},
				{
					type: "asFeatureGrid",
					label: "Feature Grid",
					icon: "code",
					description: "Three-up feature cards with optional CTA",
					category: "Layout",
					fields: [
						text("eyebrow", "Eyebrow"),
						text("heading", "Heading", { multiline: true }),
						text("items", "Features (JSON array of {title,text})", {
							multiline: true,
							placeholder: '[{"title":"...","text":"..."}]',
						}),
						text("sectionClass", "Section class", {
							placeholder: "section-meadow",
							initial: "section-meadow",
						}),
						text("primaryLabel", "CTA label"),
						text("primaryHref", "CTA href"),
					],
				},
				{
					type: "asCtaBand",
					label: "CTA Band",
					icon: "link-external",
					description: "Centered call-to-action band",
					category: "Layout",
					fields: [
						text("title", "Title"),
						text("text", "Supporting text", { multiline: true }),
						text("primaryLabel", "Primary CTA label"),
						text("primaryHref", "Primary CTA href"),
						text("secondaryLabel", "Secondary CTA label"),
						text("secondaryHref", "Secondary CTA href"),
						text("variant", "Variant (forest|meadow)", { initial: "forest" }),
					],
				},
			],
		},
	});
}

export default createPlugin;
