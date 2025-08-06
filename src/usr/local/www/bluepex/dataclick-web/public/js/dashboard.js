// Top 10 Users/Consumption
var chartRT0001 = function(element, params) {
	var myChart1 = echarts.init(element);
	var graph01 = {
		grid : {
			y: 0,
			y2: "35px",
			x:"120px",
			x2: "50px",
		},
		tooltip : {
			trigger: 'axis',
			formatter: "{a} <br/> {c}  MB"
		},
		toolbox: {
			show : true,
			feature : {
				saveAsImage : {show: true, title: "Download"}
			}
		},
		calculable : false,
		xAxis : [
			{
				type : 'value'
			}
		],
		yAxis : [
			{
				type : 'category',
				data : ['']
			}
		],
		series : [
			{
				name: params.title,
				type:'bar',
				data:[0]
			}
		]
	};
	graph01.yAxis[0].data = params.data.yAxis;
	graph01.series[0].data = params.data.series;
	myChart1.setOption(graph01);
}

//Top 5 redes sociais
var chartRT0002 = function(element, params) {
	var myChart2 = echarts.init(element);
	var graph02 = {
		tooltip : {
			trigger: 'item',
			formatter: "{a} <br/>{b} : {c}%"
		},
		toolbox: {
			show : true,
			feature : {
				saveAsImage : {show: true, title: "Download"}
			}
		},
		calculable : false,
		series : [
			{
				name: params.title,
				type: 'funnel',
				left: '8%',
				width: '70%',
				sort : 'ascending',
				label: {
					emphasis: {
						position:'inside',
					}
				},
				labelLine: {
					normal: {
						show: false
					}
				},
				itemStyle: {
					normal: {
						opacity: 0.7,
						borderWidth: 0
					}
				},
				data: []
			},
			{
				name: params.title,
				type:'funnel',
				min: 0,
				max: 100,
				left: '8%',
				width: '70%',
				sort : 'ascending',
				label: {
					normal: {
						position: 'inside',
						formatter: '{c}%',
						textStyle: {
							color: '#fff'
						}
					},
					emphasis: {
						position:'inside',
					}
				},
				itemStyle: {
					normal: {
						label: {
							position: 'inside'
						},
						borderWidth: 0,
						borderColor: '#fff'
					}
				},
				data:[]
			}
		]
	};
	graph02.series[0].data = params.data.series[0];
	graph02.series[1].data = params.data.series[1];
	myChart2.setOption(graph02);
}

//Top 10 categorias - acessos
var chartRT0003 = function(element, params) {
	var myChart3 = echarts.init(element, theme);
	var graph03 = {
		grid: { y: "10px", y2: "35px" },
		tooltip : {
			trigger: 'axis'
		},
		toolbox: {
			show : true,
			feature : {
				saveAsImage : {show: true, title: "Download"}
			}
		},
		calculable : false,
		xAxis : [
			{
				type : 'category',
				rotate: true,
				data : ['']
			}
		],
		yAxis : [
			{
				type : 'value'
			}
		],
		series : [
			{
				name: params.title,
				type:'bar',
				data : [0],
				markPoint : {
					data : [
					]
				},
				markLine : {
					data : [
						{type : 'average', name : 'Média'}
					]
				}
			}
		]
	};
	graph03.xAxis[0].data = params.data.xAxis;
	graph03.series[0].data = params.data.series;
	myChart3.setOption(graph03);
}

//Top 10 domínios
var chartRT0004 = function(element, params) {
	var myChart4 = echarts.init(element);
	var graph04 = {
		tooltip : {
			trigger: 'item',
			formatter: "{a} <br/>{b} : {c} MB ({d}%)"
		},
		toolbox: {
			show : true,
			feature : {
				saveAsImage : {show: true, title: "Download"}
			}
		},
		calculable : false,
		series : [
			{
				name: params.title,
				type:'pie',
				radius : ['40%', '55%'],
				itemStyle : {
					normal : {
						label : {
							show : true
						},
						labelLine : {
							show : true
						}
					},
					emphasis : {
						label : {
							show : true,
							position : 'center',
							textStyle : {
								fontSize : '18',
								fontWeight : 'bold'
							}
						}
					}
				},
				data:['']
			}
		]
	};
	graph04.series[0].data = params.data.series;
	myChart4.setOption(graph04);
}
    
//Top 10 Sites/Acessos
var chartRT0005 = function(element, params) {
	var myChart5 = echarts.init(element);
	var graph05 = {
		tooltip : {
			trigger: 'item',
			formatter: "{a} <br/>{b} : {c} ({d}%)"
		},
		toolbox: {
			show : true,
			feature : {
				saveAsImage : {show: true, title: "Download"}
			}
		},
		calculable : false,
		series : [
			{
				name: params.title,
				type:'pie',
				radius : '65%',
				center: ['50%', '50%'],
				data:['']
			}
		]
	};
	graph05.series[0].data = params.data.series;
	myChart5.setOption(graph05);
}

//Top 10 VPN usuários (tempo conexão)
var chartRT0006 = function(element, params) {
	var myChart6 = echarts.init(element);
	var graph06 = {
		tooltip : {
			trigger: 'axis'
		},
		toolbox: {
			show : true,
			feature: {
				saveAsImage : {show: true, title: "Download"}
			}
		},
		calculable : false,
		xAxis : [
			{
				type : 'category',
				boundaryGap : false,
				data : [params.title_series1, params.title_series2]
			}
		],
		yAxis : [
			{
				type : 'value'
			}
		],
		series : [
			{
				name: params.title_series1,
				type:'line',
				smooth:true,
				itemStyle: {normal: {areaStyle: {type: 'default'}}},
				data:[0]
			},
			{
				name: params.title_series2,
				type:'line',
				smooth:true,
				itemStyle: {normal: {areaStyle: {type: 'default'}}},
				data:[0]
			}
		]
	};

	graph06.xAxis[0].data = params.data.xAxis;
	graph06.series[0].data = params.data.series1;
	graph06.series[1].data = params.data.series2;
	myChart6.setOption(graph06);

	window.onresize = function() {
		$(".widget-chart").each(function(){
			var id = $(this).find("div[id^=chart]").attr('_echarts_instance_');
			window.echarts.getInstanceById(id).resize();
		});
	};
}
