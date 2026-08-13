import type { PluginDescriptor } from "emdash";

export function asPageBlocksPlugin(): PluginDescriptor {
	return {
		id: "as-page-blocks",
		version: "0.1.0",
		format: "native",
		entrypoint: "@autism-sanctuary/as-page-blocks/sandbox",
		componentsEntry: "@autism-sanctuary/as-page-blocks/astro",
		options: {},
	};
}
