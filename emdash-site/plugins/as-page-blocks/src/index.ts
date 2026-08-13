import type { PluginDescriptor } from "emdash";
import { portableTextBlocks } from "./blocks";

export function asPageBlocksPlugin(): PluginDescriptor {
	return {
		id: "as-page-blocks",
		version: "0.1.1",
		format: "native",
		entrypoint: "@autism-sanctuary/as-page-blocks/sandbox",
		componentsEntry: "@autism-sanctuary/as-page-blocks/astro",
		portableTextBlocks,
		options: {},
	};
}
