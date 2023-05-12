<div id="Chart-<?=$id?>" class="mb"></div>

<script>
let ChartOptions<?=$id?> = {

        series: <?=$metric?>,
        chart: {
		  height:<?=$height?>,
          type: 'donut'
        },
        labels: <?=$dimension?>,
        fill: {
          opacity: 1
        },
        stroke: {
          width: 1,
          //colors: undefined
        },
        yaxis: {
          show: false
        },
		<?php if (isset($legend)): ?>
		legend: {position: '<?=$legend?>'},
		<?php else: ?>
		legend: {show:false,},
		<?php endif; ?>
		plotOptions: {
		    pie: {
		      donut: {
		        size: '50%'
		      }
		    }
		},
        theme: {
          monochrome: {
            enabled: true,
			color: '<?=$color?>',
            shadeTo: 'light',
            shadeIntensity: 0.7
          }
        }
}

let Chart<?=$id?> = new ApexCharts(document.querySelector("#Chart-<?=$id?>"), ChartOptions<?=$id?>);
Chart<?=$id?>.render();
</script>
