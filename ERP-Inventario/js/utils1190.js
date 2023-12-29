function getChartConfig (data, labels, cData){
    console.log(cData);
    const config = {
        type: 'line',
        data: {
            labels ,
            datasets: [{
              label: cData.CSUBTI2,
              borderColor: "#3e95cd",
              data: data,
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
                    text: cData.CTITULO
                },
                legend: {
                    position:'top',
                    display: true
                  },
            },
            scales: {
              x: {
                type: 'category'
              }
            }
        }
    };
    return config;
}

function getChartConfig1 (data1, labels, cData){
    const config = {
        type: 'line',
        data: {
            labels ,
            datasets: [{
              label: cData.CSUBTI3,
              borderColor: "#d92d21",
              data: data1,
            }]
        },
        options: {
            responsive: true,
            animation: getChartAnimation(data1),
            interaction: {
                intersect: false
            },
            plugins: {
                title: {
                    display: true,
                    text: cData.CTITULO
                },
                legend: {
                    position:'top',
                    display: true
                  },
            },
            scales: {
              x: {
                type: 'category'
              }
            }
        }
    };
    return config;
}

function getChartAnimation(data){
    const totalDuration = 1500;
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