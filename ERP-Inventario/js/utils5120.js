function getChartConfig26 (dataSeg2023,labelsSeg2023){
    //console.log(dataSeg2023);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsSeg2023 ,
            datasets: [{
              label: 'CANTIDAD ESTUDIANTES',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataSeg2023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataSeg2023),
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

function getChartConfig27 (dataSegInc2023,labelsSegInc2023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsSegInc2023 ,
            datasets: [{
              label: 'INCREMENTO CANTIDAD ESTUDIANTES',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataSegInc2023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataSegInc2023),
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

function getChartConfig28 (dataSegMont2023,labelsSegMont2023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsSegMont2023 ,
            datasets: [{
              label: 'MONTO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataSegMont2023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataSegMont2023),
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

function getChartConfig29 (dataSegInc22023,labelsSegInc22023){
    // console.log(data);
    // console.log(data1);
    // console.log(labels);
    const config = {
        type: 'line',
        data: {
            labels: labelsSegInc22023 ,
            datasets: [{
              label: 'INCREMENTO MONTO',
              borderColor: "#3E95CD",
              backgroundColor:"#3E95CD",
              borderWidth: 3,
              borderRadius: 20,
              data: dataSegInc22023,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataSegInc22023),
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

