const ctx = document.getElementById('voteChart').getContext('2d');
const voteChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Pour', 'Contre', 'Abstentions'],
        datasets: [{
            data: [60, 30, 10], // Remplacez par vos données dynamiques
            backgroundColor: ['#4caf50', '#f44336', '#2196f3'],
            borderColor: ['#fff', '#fff', '#fff'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
        }
    }
});
