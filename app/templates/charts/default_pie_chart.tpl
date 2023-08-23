<div id="Chart-<?=$id?>" class="mb"></div>

<script>
let ChartOptions<?=$id?> = {

          series: [{
          name: '<?=$name?>',
          data: <?=$metric?>,
        }],
          chart: {
	      toolbar: {show:false},
          type: 'radar',
        },
        dataLabels: {
          enabled: true
        },
        plotOptions: {
          radar: {

            polygons: {
              strokeColors: '#e9e9e9',
              fill: {
                colors: ['#f8f8f8', '#fff']
              }
            }
          }
        },
        /*title: {
          text: 'Mediatime nach Ressort'
	  	},*/
        colors: ['<?=$color?>'],
        markers: {
          size: 4,
          colors: ['#fff'],
          strokeWidth: 2,
        },
        tooltip: {
          y: {
            formatter: function(val) {
              return val
            }
          }
        },
        xaxis: {
          categories: <?=$dimension?>,
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

      labels: {
        style: {
          <?php if (isset($xfont)): ?>
          fontSize: '<?=$xfont?>',
          <?php else: ?>
          fontSize: '10px',
          <?php endif; ?>
          fontFamily: 'fira sans, sans-serif',
          fontWeight: 200,
        },
      },

        },
        yaxis: {
          tickAmount: 7,
          labels: {
            formatter: function(val, i) {
              if (i % 2 === 0) {
                return val
              } else {
                return ''
              }
            }
          }
        }


}

let Chart<?=$id?> = new ApexCharts(document.querySelector("#Chart-<?=$id?>"), ChartOptions<?=$id?>);
Chart<?=$id?>.render();
</script>
