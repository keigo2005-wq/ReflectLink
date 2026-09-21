const chartCanvas = document.getElementById("positionChart");
const chartData = window.positionChartData;

if (chartCanvas && chartData) {
    new Chart(chartCanvas, {
        type: "bar",

        data: {
            labels: chartData.labels,

            datasets: [
                {
                    label: "コメント件数",
                    data: chartData.counts,
                    backgroundColor: "#007aff",
                    borderRadius: 6
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

const statusCanvas = document.getElementById("statusChart");
const statusData = window.statusChartData;

if (statusCanvas && statusData) {
    new Chart(statusCanvas, {
        type: "doughnut",

        data: {
            labels: statusData.labels,
            datasets: [
                {
                    data: statusData.counts,
                    backgroundColor: ["#ff3b30", "#ff9500", "#34c759"]
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom" }
            }
        }
    });
}
