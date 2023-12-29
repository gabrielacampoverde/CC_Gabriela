function getChartConfig (data, data1,labels){
    //console.log(data);
    const config = {
        type: 'line',
        data: {
            labels ,
            datasets: [{
              label: 'EMITIDO',
              borderColor: "#cd3e4e",
              backgroundColor:"#cd3e4e",
              borderWidth: 3,
              borderRadius: 20,
              data: data,
            },
            {
                label: 'PAGADO',
                borderColor: "#3E95CD",
                backgroundColor:"#3E95CD",
                borderWidth: 3,
                borderRadius: 20,
                data: data1,
              }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(data),
            interaction: {
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                },
                legend: {
                    position:'top',
                    display: true,
                    labels: {  //size legend
                        font: { size: 18}
                    }
                },
            },
            scales: {
                x: {
                  type: 'category',
                  ticks: {
                      font: {
                          size: 18 ,//this change the font size
                          weight: 'bold'
                      },
                  }
                },
                y: {
                  ticks: {
                      font: {
                          size: 18, //this change the font size
                          weight: 'bold'
                      }
                  }
                }
            }
        }
    };
    return config;
}

function getChartConfig2 (data2,labels1){
    //console.log(data2);
    const config1 = {
        type: 'line',
        data: {
            labels: labels1 ,
            datasets: [{
              label: 'INCREMENTO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: data2,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(data2),
            interaction: {
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                },
                legend: {
                    position:'top',
                    display: true,
                    labels: {  //size legend
                        font: { size: 18}
                    }
                },
            },
            scales: {
                x: {
                  type: 'category',
                  ticks: {
                      font: {
                          size: 18 ,//this change the font size
                          weight: 'bold'
                      },
                  }
                },
                y: {
                  ticks: {
                      font: {
                          size: 18, //this change the font size
                          weight: 'bold'
                      }
                  }
                }
            }
        }
    };
    return config1;
}

function getChartAnimation(data){
    const totalDuration = 800;
    const delayBetweenPoints = totalDuration / data.length;
    const previousY = (ctx) => ctx.index === 0 ? ctx.chart.scales.y.getPixelForValue(100) : ctx.chart.getDatasetMeta(ctx.datasetIndex).data[ctx.index - 1].getProps(['y'], true).y;
    const animation = {
        x: {
            type: 'number',
            easing: 'linear',
            duration: delayBetweenPoints,
            from: NaN, // the point is initially skipped
            delay(ctx) {
                if (ctx.type !== 'data' || ctx.xStarted) {
                    return 0;
                }
                ctx.xStarted = true;
                return ctx.index * delayBetweenPoints;
            }
        },
        y: {
            type: 'number',
            easing: 'linear',
            duration: delayBetweenPoints,
            data:data,
            from: previousY,
            delay(ctx) {
                if (ctx.type !== 'data' || ctx.yStarted) {
                    return 0;
                }
                ctx.yStarted = true;
                return ctx.index * delayBetweenPoints;
            }
        }
    };
    return animation;
}

