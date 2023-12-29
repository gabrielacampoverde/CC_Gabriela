function getChartConfigPredic(dataP2023,dataP12023,labelsP2023,dataP22023){
    console.log(dataP22023);
    // console.log(dataP2023);
    // console.log('HOLAAAAAAAAAAAAAAAAAAAAAAAAAA');
    // console.log(dataP22023);
    // console.log('CHAUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUUU');
    const config = {
        type: 'line',
        data: {
            labels:labelsP2023,
            datasets: [{
              label: 'PROYECTADO RN',
              borderColor: "#cd3e4e",
              backgroundColor:"#cd3e4e",
              borderWidth: 3,
              borderRadius: 20,
              data: dataP2023,
            //   data: [12, 15.5, 14, 14.1, 15,0,0],
            },
            {
                label: 'PAGADO',
                borderColor: "#3E95CD",
                backgroundColor:"#3E95CD",
                borderWidth: 3,
                borderRadius: 20,
                data: dataP12023,
              },
              {
                label: 'PROYECTADO MC',
                borderColor: "#4ECD3E",
                backgroundColor:"#4ECD3E",
                borderWidth: 3,
                borderRadius: 20,
                data: dataP22023,
              }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(dataP2023),
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

