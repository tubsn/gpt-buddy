<?php

namespace app\models;
use flundr\rendering\TemplateEngine;

class AphexChart
{
	public $data;
	public $metric;
	public $dimension;
	public $showValues = false;
	public $color;
	public $height = 300;
	public $template = 'charts/default_bar_chart';
	public $name;
	public $area;
	public $percent;
	public $seconds;
	public $stacked;
	public $xfont;
	public $prefix;
	public $suffix;
	public $legend;
	public $tickamount;

	public function create() {
		$this->process_metrics_and_dimensions();
		return $this->render($this->template, $this->chart_data());
	}

	private function chart_data() {return (array) $this;}

	public function render($template, $data) {
		$data['id'] = uniqid(); // add a Unique ChartID for css classes
		$templateEngine = new TemplateEngine($template, $data);
		return $templateEngine->burn();
	}

	private function multiple_metrics() {
		if (is_array($this->metric[0])) {return true;}
		return false;
	}

	public function process_metrics_and_dimensions() {

		if ($this->data) {
			$this->dimension = array_keys($this->data);
			$this->metric = array_values($this->data);

			$metricKeys = array_keys(call_user_func_array('array_merge', $this->metric));

			$out = [];
			foreach ($metricKeys as $key) {
				$out[$key] = array_column($this->metric, $key);
			}

			$metricKeys = array_map(function ($set) { return ucfirst($set); }, $metricKeys);

			$this->name = $metricKeys;
			$this->metric = array_values($out);

		}

		if ($this->multiple_metrics()) {
			foreach ($this->metric as $key => $metric) {
				$this->metric[$key] = json_encode($metric, JSON_NUMERIC_CHECK);
			}
		}

		else {$this->metric = json_encode($this->metric, JSON_NUMERIC_CHECK);}
		$this->dimension = json_encode($this->dimension, JSON_NUMERIC_CHECK);

	}

}
