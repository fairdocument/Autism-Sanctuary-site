import { getEmDashCollection, getEmDashEntry } from "emdash";

export const pageEyebrows: Record<string, string> = {
	about: "About Autism Sanctuary",
	people: "People",
	programs: "Programs & licensed services",
	"our-farm": "Pea Ridge Road · Western Albemarle",
	admissions: "Admissions",
	careers: "Join our community",
	fellowship: "Workforce & leadership formation",
	resources: "Resources & guidance",
	privacy: "Legal",
	terms: "Legal",
	contact: "Contact",
	donate: "Philanthropy",
};

export async function getCmsPage(slug: string) {
	const { entry, cacheHint, error } = await getEmDashEntry("pages", slug);
	return { page: entry ?? null, cacheHint, error };
}

export async function getPublishedPosts(limit = 50) {
	const { entries, cacheHint, error } = await getEmDashCollection("posts", {
		status: "published",
		limit,
		orderBy: { published_at: "desc" },
	});
	return { posts: entries ?? [], cacheHint, error };
}

export async function getCmsPost(slug: string) {
	const { entry, cacheHint, error } = await getEmDashEntry("posts", slug);
	return { post: entry ?? null, cacheHint, error };
}

export function formatPostDate(value: Date | string | null | undefined) {
	if (!value) return "";
	const date = value instanceof Date ? value : new Date(value);
	if (Number.isNaN(date.getTime())) return "";
	return date.toLocaleDateString("en-US", {
		year: "numeric",
		month: "long",
		day: "numeric",
	});
}
