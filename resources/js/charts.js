import {
	Chart,
	ArcElement,
	BarController,
	BarElement,
	DoughnutController,
	Legend,
	LineController,
	LineElement,
	CategoryScale,
	LinearScale,
	PointElement,
	Tooltip,
} from 'chart.js';

Chart.register(
	ArcElement,
	BarController,
	BarElement,
	DoughnutController,
	LineController,
	LineElement,
	CategoryScale,
	LinearScale,
	PointElement,
	Legend,
	Tooltip,
);

/**
 * Portal chart palette. Kept in sync with the CSS custom properties in
 * resources/css/app.css so charts share the institutional identity.
 */
const PALETTE = {
	pine: '#173d35',
	fern: '#3b7665',
	gold: '#e7bc58',
	coral: '#ce7253',
	blue: '#a8c7c6',
	muted: '#9aa8a1',
};

const SERIES_COLOURS = [PALETTE.pine, PALETTE.gold, PALETTE.fern, PALETTE.coral, PALETTE.blue];

const isPlainObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

const readNumberArray = (value) =>
	Array.isArray(value) ? value.map((entry) => (Number.isFinite(Number(entry)) ? Number(entry) : 0)) : [];

const buildDataset = (series, palette, index) => {
	const values = readNumberArray(series.values);

	if (palette.length === 1) {
		return {
			label: String(series.label ?? ''),
			data: values,
			backgroundColor: PALETTE[palette[0]] ?? palette[0] ?? SERIES_COLOURS[index % SERIES_COLOURS.length],
			borderRadius: 4,
			maxBarThickness: 42,
		};
	}

	return {
		label: String(series.label ?? ''),
		data: values,
		backgroundColor: values.map(
			(_, valueIndex) => PALETTE[palette[valueIndex]] ?? SERIES_COLOURS[valueIndex % SERIES_COLOURS.length],
		),
		borderColor: PALETTE.surface ?? '#fffefa',
		borderWidth: 2,
	};
};

const sharedOptions = () => ({
	responsive: true,
	maintainAspectRatio: false,
	animation: { duration: 450 },
	plugins: {
		legend: {
			display: true,
			position: 'bottom',
			labels: {
				boxWidth: 10,
				boxHeight: 10,
				usePointStyle: true,
				pointStyle: 'circle',
				color: PALETTE.pine,
				font: { size: 11, weight: '600' },
			},
		},
		tooltip: {
			backgroundColor: PALETTE.pine,
			titleColor: '#fffefa',
			bodyColor: '#e7ece4',
			padding: 10,
			cornerRadius: 6,
			displayColors: false,
		},
	},
	scales: {},
});

const buildOptions = (type) => {
	const options = sharedOptions();

	if (type === 'doughnut') {
		options.cutout = '62%';
		options.plugins.legend.position = 'right';

		return options;
	}

	options.scales = {
		x: {
			grid: { display: false },
			ticks: { color: PALETTE.muted, font: { size: 11 } },
			border: { color: '#d9ded7' },
		},
		y: {
			beginAtZero: true,
			grid: { color: '#eceee9' },
			ticks: { color: PALETTE.muted, precision: 0, font: { size: 11 } },
			border: { display: false },
		},
	};

	return options;
};

const renderChart = (canvas) => {
	const payload = canvas.dataset.chartPayload;

	if (typeof payload !== 'string' || payload === '') {
		return;
	}

	let config;

	try {
		config = JSON.parse(payload);
	} catch {
		return;
	}

	if (!isPlainObject(config) || !Array.isArray(config.series)) {
		return;
	}

	const type = typeof config.type === 'string' ? config.type : 'bar';
	const palette = Array.isArray(config.palette) && config.palette.length > 0 ? config.palette : ['pine'];

	const datasets = config.series
		.filter(isPlainObject)
		.map((series, index) => buildDataset(series, palette, index));

	if (datasets.length === 0) {
		return;
	}

	// Chart.js replaces the canvas; keep the accessible fallback text in the DOM
	// so screen readers still receive the figures if a chart fails to render.
	Chart.defaults.font.family = "'DM Sans', 'Segoe UI', sans-serif";
	Chart.defaults.color = PALETTE.muted;

	new Chart(canvas, {
		type,
		data: {
			labels: Array.isArray(config.labels) ? config.labels.map(String) : [],
			datasets,
		},
		options: buildOptions(type),
	});
};

const initCharts = () => {
	document.querySelectorAll('canvas[data-chart-payload]').forEach(renderChart);
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initCharts, { once: true });
} else {
	initCharts();
}
