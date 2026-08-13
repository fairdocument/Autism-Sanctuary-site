export type ChecklistItem = { title: string; text: string };
export type FeatureItem = { title: string; text: string };

function tryJson<T>(raw: unknown): T | null {
	if (typeof raw !== "string") return null;
	const trimmed = raw.trim();
	if (!trimmed.startsWith("[") && !trimmed.startsWith("{")) return null;
	try {
		return JSON.parse(trimmed) as T;
	} catch {
		return null;
	}
}

/** Accept array, JSON string, or `Title|Description` lines. */
export function parseChecklist(raw: unknown): ChecklistItem[] {
	if (Array.isArray(raw)) {
		return raw
			.map((item) => {
				if (typeof item === "string") return { title: "", text: item };
				if (item && typeof item === "object") {
					const o = item as Record<string, unknown>;
					return { title: String(o.title ?? ""), text: String(o.text ?? o.body ?? "") };
				}
				return null;
			})
			.filter((x): x is ChecklistItem => Boolean(x && (x.title || x.text)));
	}

	const fromJson = tryJson<ChecklistItem[]>(raw);
	if (fromJson) return parseChecklist(fromJson);

	if (typeof raw !== "string" || !raw.trim()) return [];

	return raw
		.split("\n")
		.map((line) => line.trim())
		.filter(Boolean)
		.map((line) => {
			const pipe = line.indexOf("|");
			if (pipe >= 0) {
				return { title: line.slice(0, pipe).trim(), text: line.slice(pipe + 1).trim() };
			}
			const colon = line.indexOf(":");
			if (colon > 0 && colon < 80) {
				return { title: line.slice(0, colon).trim(), text: line.slice(colon + 1).trim() };
			}
			return { title: "", text: line };
		});
}

export function parseFeatures(raw: unknown): FeatureItem[] {
	if (Array.isArray(raw)) {
		return raw
			.map((item) => {
				if (typeof item === "string") return { title: item, text: "" };
				if (item && typeof item === "object") {
					const o = item as Record<string, unknown>;
					return { title: String(o.title ?? ""), text: String(o.text ?? o.body ?? "") };
				}
				return null;
			})
			.filter((x): x is FeatureItem => Boolean(x && x.title));
	}

	const fromJson = tryJson<FeatureItem[]>(raw);
	if (fromJson) return parseFeatures(fromJson);

	if (typeof raw !== "string" || !raw.trim()) return [];

	return raw
		.split(/\n---\n|\n\n+/)
		.map((chunk) => chunk.trim())
		.filter(Boolean)
		.map((chunk) => {
			const [first, ...rest] = chunk.split("\n");
			return { title: (first || "").trim(), text: rest.join(" ").trim() };
		});
}

export function asBool(raw: unknown): boolean {
	if (typeof raw === "boolean") return raw;
	if (typeof raw === "string") return raw === "true" || raw === "1" || raw === "on";
	return Boolean(raw);
}
