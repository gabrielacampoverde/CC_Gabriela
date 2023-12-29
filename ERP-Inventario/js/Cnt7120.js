// ------------------------------------------------------------------------------
// FONDOS FIJOS
// Creacion  2021-05-10 GCH
// ------------------------------------------------------------------------------
$(document).ready(function() {
    let x = document.getElementById('datos');
    let y = document.getElementById('data');
    let cFonRec = JSON.parse(x.textContent);
    let cData = JSON.parse(y.textContent);
    console.log(cFonRec);
    console.log(cData);

    let cCtaCnt = [];
    let nSaldo1 = [];
    let nSaldo2 = [];
    let cSubTi1 = [];
    let cSubTi2 = [];

    for (let item of cFonRec) {
        item.CCTACNT ? cCtaCnt.push(item.CCTACNT) : null;
        item.NSALDO1 ? nSaldo1.push(item.NSALDO1) : null;
        item.NSALDO2 ? nSaldo2.push(item.NSALDO2) : null;
    }

    if (cCtaCnt.length > 0 && nSaldo1.length > 0 && nSaldo2.length > 0) {
        let chartData = {
            labels: cCtaCnt,
            datasets: [{
                    label: cData.CSUBTI1,
                    data: nSaldo1,
                    backgroundColor: "#3e95cd",
                    borderWidth: 2,
                    hoverBorderWidth: 0,
                },
                {
                    label: cData.CSUBTI2,
                    data: nSaldo2,
                     backgroundColor: "#8e5ea2",
                    borderWidth: 2,
                    hoverBorderWidth: 0,
                }
            ],
        };

        var mostrar = $("#miGrafico");
        var grafico = new Chart(mostrar, {
            type:"bar",
            data:chartData,
        });
    }
});


// // ------------------------------------------------------------------------------
// // FONDOS FIJOS
// // Creacion  2021-05-10 GCH
// // ------------------------------------------------------------------------------
// $(document).ready(function() {
//     const tableHead = document.getElementById('tableHead');
//     const tableBody = document.getElementById('tableBody');
//     console.log(tableBody);
//     tableBody.style.display = 'none';
//     tableHead.addEventListener('click',()=> {
//         tableBody.style.display = tableBody.style.display == 'none' ? 'contents' : 'none';
//     });

//     let x = document.getElementById('datos');
//     let y = document.getElementById('data');
//     let cFonRec = JSON.parse(x.textContent);
//     let cData = JSON.parse(y.textContent);
//     // console.log(cFonRec);
//     // console.log(cData);

//     let cEntFin = [];
//     let nSaldo1 = [];
//     let nSaldo2 = [];

//     for (let item of cFonRec) {
//         item.CENTFIN ? cEntFin.push(item.CENTFIN) : null;
//         item.NSALDO1 ? nSaldo1.push(item.NSALDO1) : null;
//         item.NSALDO2 ? nSaldo2.push(item.NSALDO2) : null;
//     }
//     // console.log(cEntFin);
//     // console.log(nSaldo1);
//     // console.log(nSaldo2);
//     if (cEntFin.length > 0 && nSaldo1.length > 0 && nSaldo2.length > 0) {
//         let chartData = {
//             labels: cEntFin,
//             datasets: [{
//                     label: 'MARZO',
//                     data: nSaldo1,
//                     backgroundColor: "#3e95cd",
//                     borderWidth: 2,
//                     hoverBorderWidth: 0,
//                 },
//                 {
//                     label: 'ABRIL',
//                     data: nSaldo2,
//                      backgroundColor: "#8e5ea2",
//                     borderWidth: 2,
//                     hoverBorderWidth: 0,
//                 }
//             ],
//         };

//         var mostrar = $("#miGrafico");
//         var grafico = new Chart(mostrar, {
//             type:"bar",
//             data:chartData,
//             options: {
//                 title: {
//                     display: true,
//                     text: cData.CDESCRI
//               }
//             }
//         });
//     }
// });

