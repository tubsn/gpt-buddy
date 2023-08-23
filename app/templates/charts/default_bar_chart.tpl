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
		type: 'bar',
		toolbar: {show:false},
		height: <?=$height ?? 300?>,
		<?php if (isset($animation) && $animation == false): ?>
		animations: {enabled: false},
		<?php else: ?>
		animations: {enabled: true},
		<?php endif; ?>
		<?php if (isset($stacked) && $stacked == true): ?>
		stacked: true,
		<?php if (isset($stackedTo100) && $stackedTo100 == true): ?>
		stackType: '100%',
		<?php endif; ?>
		<?php else: ?>
		stacked: false,
		<?php endif; ?>
	    animations: {
	        enabled: true,
	        easing: 'easeinout',
	        speed: 300,
	        animateGradually: {
	            enabled: true,
	            delay: 20
	        },

	    }
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
	tooltip: {
		<?php if (isset($showValues) && $showValues == true): ?>
		enabled: false,
		<?php else: ?>
		enabled: true,
		<?php endif; ?>
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

	grid: {
		show: true,
	},

	plotOptions: {
		bar: {
			borderRadius: 0,
			horizontal: false,
            //columnWidth: '25%',
		}
	},
	<?php if (is_array($metric)): ?>
    stroke: {
    	show: true,
    	width: 1,
    	colors: ['#fff']
    },
	<?php endif; ?>
	<?php if (isset($legend)): ?>
	legend: {position: '<?=$legend?>'},
	<?php else: ?>
	legend: {show:false,},
	<?php endif; ?>
	dataLabels: {
		textAnchor: 'middle',
		<?php if (isset($showValues) && $showValues == false): ?>
		enabled: false,
		<?php else: ?>
		enabled: true,
		<?php endif; ?>
  		offsetX: 0,
  		offsetY: 20,
		style: {
		  fontSize: '18px',
		  fontFamily: 'fira sans condensed, sans-serif',
		  colors: ['#fff']
		},
		background: {
			enabled: true,
			foreColor: '#444',
			padding: 4,
			borderRadius: 5,
			borderWidth: 0,
			opacity: 0.5,
		},

	},
	grid: {row: {colors: ['#e5e5e5', 'transparent'], opacity: 0.2}},
	xaxis: {
		categories: <?=$dimension?>,
		<?php if (isset($tickamount)): ?>tickAmount: <?=$tickamount?>,<?php endif; ?>
		labels: {
			rotate: -90,
			style: {
				<?php if (isset($xfont)): ?>
				fontSize: '<?=$xfont?>',
				<?php else: ?>
				fontSize: '12px',
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
		labels: {
			rotate: 0,
   		//formatter: function(val) {return val.toFixed(1);}
		},
	}

};

let Chart<?=$id?> = new ApexCharts(document.querySelector("#Chart-<?=$id?>"), ChartOptions<?=$id?>);
Chart<?=$id?>.render();
</script>
