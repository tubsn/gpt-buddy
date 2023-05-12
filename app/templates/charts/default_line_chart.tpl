<div id="Chart-<?=$id?>" class="mb"></div>

<script>
let ChartOptions<?=$id?> = {
	series: [
		<?php if (is_array($metric)): ?>
		<?php foreach ($metric as $index => $currentMetric): ?>
		{
			name: '<?php if (is_array($name)): ?><?=$name[$index]?><?php else: ?><?=$name?><?php endif; ?>',
			<?php if (is_array($color)): ?>color: '<?=$color[$index]?>',<?php endif; ?>
			data: <?=$currentMetric?>,
		},
		<?php endforeach; ?>
		<?php else: ?>
		{
			name: '<?=$name?>', color: '<?=$color?>',
			data: <?=$metric?>,
		},
		<?php endif; ?>
	],
	chart: {
		height: <?=$height ?? 300?>,
		<?php if (isset($area) && $area == false): ?>
		type: 'line',
		<?php else: ?>
		type: 'area',
		<?php endif; ?>
		toolbar: {show:false},
		zoom: {enabled:false},
		<?php if (isset($animation) && $animation == false): ?>
		animations: {enabled: false},
		<?php else: ?>
		animations: {enabled: true},
		<?php endif; ?>
		sparkline: {enabled: false},
		<?php if (isset($stacked) && $stacked == true): ?>
		stacked: true,
		<?php if (isset($stackedTo100) && $stackedTo100 == true): ?>
		stackType: '100%',
		<?php endif; ?>
		<?php else: ?>
		stacked: false,
		<?php endif; ?>
	},
	<?php if (is_array($metric) && !is_array($color)): ?>
	theme: {
		monochrome: {
			enabled: true,
			color: '<?=$color?>',
			shadeTo: 'light',
			shadeIntensity: 0.7
		}
	},
	<?php endif; ?>
	<?php if (isset($legend)): ?>
	legend: {
	show:true,
	position: '<?=$legend?>',
	horizontalAlign: 'center',
	onItemHover: {highlightDataSeries: true},
	floating: true,
	offsetY: 0,
	offsetX: 0,
	},
	<?php else: ?>
	legend: {show:false,},
	<?php endif; ?>

	stroke: {curve: 'smooth'},
	dataLabels: {enabled: false,},

	xaxis: {
		categories: <?=$dimension?>,
		crosshairs: {show: true},
		tooltip: {enabled: false},
		<?php if (isset($tickamount)): ?>tickAmount: <?=$tickamount?>,<?php endif; ?>
		labels: {
			style: {
				<?php if (isset($xfont)): ?>
				fontSize: '<?=$xfont?>',
				<?php else: ?>
				fontSize: '13px',
				<?php endif; ?>
				fontFamily: 'fira sans, sans-serif',
				fontWeight: 400,
			},
			<?php if (isset($prefix)): ?>
			formatter: function (value) {
				return '<?=$prefix?>' + value;
			},
			<?php endif; ?>
			<?php if (isset($suffix)): ?>
			formatter: function (value) {
				return value + '<?=$suffix?>';
			},
			<?php endif; ?>
		},


	},

	yaxis: {
		tickAmount: 4,
		labels: {rotate: 0},
	},

	grid: {row: {colors: ['#e5e5e5', 'transparent'], opacity: 0.2}},

	tooltip: {
		<?php if (isset($percent) && $percent == true): ?>
        y: {
          formatter: function(value) {
              return value + '&thinsp;%'
          },
        },
		<?php endif; ?>
		<?php if (isset($seconds) && $seconds == true): ?>
        y: {
          formatter: function(value) {
              return value + '&thinsp;s'
          },
        },
		<?php endif; ?>
	},
}

let Chart<?=$id?> = new ApexCharts(document.querySelector("#Chart-<?=$id?>"), ChartOptions<?=$id?>);
Chart<?=$id?>.render();
</script>
