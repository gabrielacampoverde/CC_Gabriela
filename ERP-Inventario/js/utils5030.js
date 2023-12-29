function getChartConfig3 (data, data1,labels){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labels,
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

function getChartConfig4 (data2022I,labels2022I){
    // console.log(data2022I);
    // console.log(labels2022I);
    const config1 = {
        type: 'line',
        data: {
            labels: labels2022I ,
            datasets: [{
              label: 'INCREMENTO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: data2022I,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(data2022I),
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

function getChartConfig5 (data2022Im,labels2022Im){
    console.log(data2022Im);
    // console.log(labels2022I);
    const config1 = {
        type: 'line',
        data: {
            labels: labels2022Im ,
            datasets: [{
              label: 'VENCIDO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: data2022Im,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(data2022Im),
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
    const totalDuration = 2000;
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

