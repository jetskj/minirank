document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const tableBody = document.querySelector('tbody');
    const rows = document.querySelectorAll('tbody tr');

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        rows.forEach(row => {
            const phrase = row.cells[0].textContent.toLowerCase();
            row.style.display = phrase.includes(term) ? '' : 'none';
        });
    });

    const refreshBtns = document.querySelectorAll('.refresh-btn');
    refreshBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const keywordId = btn.dataset.id;
            const res = await fetch('api/positions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ keyword_id: parseInt(keywordId) })
            });
            const data = await res.json();

            if (Array.isArray(data)) {
                data.forEach(item => {
                    const row = document.querySelector(`tr[data-id="${item.keyword_id}"]`);
                    if (row) {
                        const posCell = row.querySelector('.kw-pos');
                        const trendCell = row.querySelector('.kw-trend');
                        if (posCell) posCell.innerText = item.position;
                        if (trendCell) {
                            trendCell.innerText = item.trend.charAt(0).toUpperCase() + item.trend.slice(1);
                            trendCell.className = `kw-trend ${item.trend.toLowerCase()}`;
                        }
                    }
                });
            } else if (data.success) {
                const row = document.querySelector(`tr[data-id="${data.keyword_id}"]`);
                if (row) {
                    const posCell = row.querySelector('.kw-pos');
                    const trendCell = row.querySelector('.kw-trend');
                    if (posCell) posCell.innerText = data.position;
                    if (trendCell) {
                        trendCell.innerText = data.trend.charAt(0).toUpperCase() + data.trend.slice(1);
                        trendCell.className = `kw-trend ${data.trend.toLowerCase()}`;
                    }
                }
            }
        });
    });
});