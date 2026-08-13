import { definePlugin } from "emdash";
import { portableTextBlocks } from "./blocks";

export function createPlugin() {
	return definePlugin({
		id: "as-page-blocks",
		version: "0.1.1",
		admin: {
			portableTextBlocks,
		},
	});
}

export default createPlugin;
