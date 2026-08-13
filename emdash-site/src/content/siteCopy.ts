export const brand = {
	name: "Autism Sanctuary",
	tagline: "Nature's haven for autism: where growth knows no limits.",
	legal: "Autism Sanctuary is a 501(c)(3) nonprofit and Virginia DBHDS-licensed care farm.",
	address: "2860 Pea Ridge Road, Charlottesville, VA 22901",
	phone: "(434) 207-2118",
	phoneHref: "tel:+14342072118",
	email: "info@autismsanctuary.org",
	instagram: "https://www.instagram.com/autismsanctuary",
	facebook: "https://www.facebook.com/autismsanctuary",
};

/**
 * Fallback nav when EmDash `primary` menu is empty.
 * Live header reads Menus → Primary in /_emdash/admin.
 */
export const navItems = [
	{ label: "About", href: "/about" },
	{ label: "People", href: "/people" },
	{ label: "Programs", href: "/programs" },
	{ label: "Our farm", href: "/our-farm" },
	{ label: "Admissions", href: "/admissions" },
	{ label: "Careers", href: "/careers" },
	{ label: "Contact", href: "/contact" },
	{ label: "Donate", href: "/donate", cta: true },
] as const;

/**
 * Fallback footer columns when EmDash footer-* menus are empty.
 * Live footer reads Menus → Footer Explore / Engage / Legal in admin.
 */
export const footerLinks = {
	explore: [
		{ label: "About", href: "/about" },
		{ label: "People", href: "/people" },
		{ label: "Programs", href: "/programs" },
		{ label: "Our farm", href: "/our-farm" },
		{ label: "Resources", href: "/resources" },
		{ label: "News", href: "/news" },
	],
	engage: [
		{ label: "Admissions", href: "/admissions" },
		{ label: "Careers & volunteers", href: "/careers" },
		{ label: "Fellowship", href: "/fellowship" },
		{ label: "Donate", href: "/donate" },
		{ label: "Contact", href: "/contact" },
	],
	legal: [
		{ label: "Privacy", href: "/privacy" },
		{ label: "Terms", href: "/terms" },
	],
} as const;

export type Person = {
	name: string;
	role: string;
	/** Initials shown in the photo placeholder until a portrait is added */
	initials: string;
	bio: string;
};

export const boardMembers: readonly Person[] = [
	{
		name: "Jason Brewster",
		role: "President",
		initials: "JB",
		bio: "Biography coming soon.",
	},
	{
		name: "Robert Kreps",
		role: "Treasurer",
		initials: "RK",
		bio: "Biography coming soon.",
	},
	{
		name: "Matthew Osborne",
		role: "Secretary",
		initials: "MO",
		bio: "Biography coming soon.",
	},
	{
		name: "Rose Neville",
		role: "Board member",
		initials: "RN",
		bio: "Biography coming soon.",
	},
];

export const managementTeam: readonly Person[] = [
	{
		name: "Olivia Bruno",
		role: "Executive Director",
		initials: "OB",
		bio: "Biography coming soon.",
	},
	{
		name: "Isabelle (Izzy) Kueser",
		role: "Director of Adult Services",
		initials: "IK",
		bio: "Biography coming soon.",
	},
];

export const inquiryIntents = [
	{ value: "general", label: "General information" },
	{ value: "donate", label: "Donating" },
	{ value: "volunteer", label: "Volunteering" },
	{ value: "program-fit", label: "Exploring a program fit" },
	{ value: "jobs", label: "Job opportunities" },
] as const;

export type InquiryIntent = (typeof inquiryIntents)[number]["value"];

export const donateFunds = [
	{ value: "where-needed", label: "Where needed most" },
	{ value: "day-program", label: "Day program developments and needs" },
	{ value: "residential", label: "Residential initiatives" },
	{ value: "agriculture", label: "Agriculture and land management" },
] as const;

export const donatePresets = [25, 50, 100, 250] as const;
