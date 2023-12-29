function getChartConfig6 (dataJur2023,labelsJur2023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsJur2023 ,
            datasets: [{
              label: 'CANTIDAD',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataJur2023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataJur2023),
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

function getChartConfig7 (dataJurInc2023,labelsJurInc2023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsJurInc2023 ,
            datasets: [{
              label: 'CANTIDAD',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataJurInc2023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataJurInc2023),
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

function getChartConfig8 (dataJurMont2023,labelsJurMont2023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsJurMont2023 ,
            datasets: [{
              label: 'MONTO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataJurMont2023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataJurMont2023),
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

function getChartConfig9 (dataJurInc22023,labelsJurInc22023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsJurInc22023 ,
            datasets: [{
              label: ' MONTO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataJurInc22023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataJurInc22023),
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

