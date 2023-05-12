<div id="Chart-<?=$id?>" class="mb"></div>

<script>
let ChartOptions<?=$id?> = {

        series: <?=$metric?>,
        chart: {
		  height:<?=$height?>,
          type: 'polarArea'
        },
        labels: <?=$dimension?>,
        fill: {
          opacity: 1
        },
        stroke: {
          width: 1,
          colors: undefined
        },
        yaxis: {
          show: false
        },
        legend: {
          position: 'bottom'
        },
        plotOptions: {
          polarArea: {
            rings: {
              strokeWidth: 0
            },
            spokes: {
              strokeWidth: 0
            },
          }
        },
        theme: {
          monochrome: {
            enabled: true,
			color: '<?=$color?>',
            shadeTo: 'light',
            shadeIntensity: 0.6
          }
        }
}

let Chart<?=$id?> = new ApexCharts(document.querySelector("#Chart-<?=$id?>"), ChartOptions<?=$id?>);
Chart<?=$id?>.render();
</script>
