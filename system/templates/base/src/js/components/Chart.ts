/**
 * Initialise chart.js in any matching divs
 * 
 * Must be used with the corresponding BootstrapHtml5::chart method.
 * If you're using vue or want more control, use the npm package directly instead
 */

import ChartJS, { ChartConfiguration } from "chart.js/auto";
import merge from "deepmerge";

export class Chart {
	private static ATTRIBUTE_NAME = "data-chart"
	private static SELECT_TARGET = `div[${this.ATTRIBUTE_NAME}]`

	static bindInteractions() {
		// querySelectorAll returns NodeListOf which does not implement an iterator
		const elements = Array.from(document.querySelectorAll(Chart.SELECT_TARGET));

		for (const elem of elements) {
			const config = elem.getAttribute(Chart.ATTRIBUTE_NAME);
			if (!config) continue;

			const parsed = JSON.parse(config);

			const canvas = elem.querySelector("canvas");
			if (!canvas) {
				console.error("Tried to initialise a chart, but missing canvas", elem);
			}

			// Provide default options for enabling a legend
			// and setting colours based on the current theme colours
			// We deep merge the user's config into this
			const opts: Partial<ChartConfiguration> = {
				type: "line",
				plugins: {
					// @ts-ignore the types here seem to be wrong?
					// https://www.chartjs.org/docs/latest/configuration/legend.html
					legend: {
						display: true,
						position: "bottom",
					}
				},

			};

			const chart = new ChartJS(
				canvas,
				merge(opts, parsed),
			);

			// chart.js doesn't support using css variables itself
			// so we have to eval them when they change and update the chart ourselves
			// see https://github.com/chartjs/Chart.js/issues/9983
			const onThemeChange = () => {
				for (const scale in chart.options.scales) {
					// @ts-ignore the types are wrong?
					// https://www.chartjs.org/docs/latest/configuration/title.html
					chart.options.scales[scale].title.color = css("--bs-body-color")

					chart.options.scales[scale].ticks.color = css("--bs-body-color");
				}

				chart.options.plugins.legend.labels.color = css("--bs-body-color");
				chart.options.plugins.legend.title.color = css("--bs-body-color");
				chart.update();
			}

			onThemeChange();

			//@ts-ignore
			window.cmfiveEventBus.addEventListener("theme-change", (event) => onThemeChange());
		}
	}
}

// get a css variable value
const css = (name: string) => {
	return getComputedStyle(document.documentElement).getPropertyValue(name);
}
