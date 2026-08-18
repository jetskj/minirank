document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const tableBody = document.querySelector('tbody');

    // Live search filter
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(row => {
                const phraseCell = row.cells[0];
                if (phraseCell) {
                    const phrase = phraseCell.textContent.toLowerCase();
                    row.style.display = phrase.includes(term) ? '' : 'none';
                }
            });
        });
    }

    // Event delegation for table action buttons (Refresh, Edit, Delete)
    if (tableBody) {
        tableBody.addEventListener('click', async (e) => {
            // Refresh button
            if (e.target.classList.contains('refresh-btn')) {
                e.preventDefault();
                const btn = e.target;
                const keywordId = btn.dataset.id;
                try {
                    const res = await fetch('api/positions', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ keyword_id: parseInt(keywordId) })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const row = document.querySelector(`tr[data-id="${data.keyword_id}"]`);
                        if (row) {
                            const posCell = row.querySelector('.kw-pos');
                            const trendCell = row.querySelector('.kw-trend');
                            if (posCell) posCell.innerText = data.position;
                            if (trendCell && data.trend) {
                                trendCell.innerText = data.trend.charAt(0).toUpperCase() + data.trend.slice(1);
                                trendCell.className = `kw-trend ${data.trend.toLowerCase()}`;
                                row.className = data.trend.toLowerCase();
                            }
                        }
                    }
                } catch (err) {
                    console.error('Refresh failed', err);
                }
            }

            // Edit button
            if (e.target.classList.contains('edit-btn')) {
                e.preventDefault();
                const btn = e.target;
                const keywordId = btn.dataset.id;
                const oldPhrase = btn.dataset.phrase;
                const newPhrase = prompt('Enter new keyword phrase:', oldPhrase);
                if (!newPhrase || newPhrase.trim() === '' || newPhrase.trim() === oldPhrase) return;

                try {
                    const res = await fetch('api/keywords', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'edit', id: parseInt(keywordId), old: oldPhrase, new: newPhrase.trim() })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const row = document.querySelector(`tr[data-id="${keywordId}"]`);
                        if (row) {
                            const link = row.querySelector('td a');
                            if (link) {
                                link.textContent = data.phrase;
                                link.href = `?keyword=${keywordId}`;
                            }
                            btn.dataset.phrase = data.phrase;
                        }
                    } else {
                        alert(data.error || 'Failed to edit keyword');
                    }
                } catch (err) {
                    console.error('Edit failed', err);
                }
            }

            // Delete button
            if (e.target.classList.contains('delete-btn')) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this keyword?')) return;
                const btn = e.target;
                const keywordId = btn.dataset.id;

                try {
                    const res = await fetch('api/keywords', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: parseInt(keywordId) })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const row = document.querySelector(`tr[data-id="${keywordId}"]`);
                        if (row) {
                            row.remove();
                        }
                    } else {
                        alert(data.error || 'Failed to delete keyword');
                    }
                } catch (err) {
                    console.error('Delete failed', err);
                }
            }
        });
    }

    // Add keyword form submission
    const addKeywordForm = document.getElementById('add-keyword-form');
    if (addKeywordForm) {
        addKeywordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('new-keyword');
            const phrase = input.value.trim();
            if (!phrase) return;

            try {
                const res = await fetch('api/keywords', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', phrase })
                });
                const data = await res.json();

                if (data.success) {
                    const newRow = document.createElement('tr');
                    newRow.className = 'stable';
                    newRow.setAttribute('data-id', data.id);
                    newRow.innerHTML = `
                        <td><a href="?keyword=${data.id}">${escapeHtml(data.phrase)}</a></td>
                        <td class="kw-pos">-</td>
                        <td class="kw-trend stable">Stable</td>
                        <td>
                            <button class="edit-btn" data-id="${data.id}" data-phrase="${escapeHtml(data.phrase)}">Edit</button>
                            <button class="delete-btn" data-id="${data.id}">Delete</button>
                            <button class="refresh-btn" data-id="${data.id}">Refresh</button>
                        </td>
                    `;
                    tableBody.prepend(newRow);
                    input.value = '';
                } else {
                    alert(data.error || 'Failed to add keyword');
                }
            } catch (err) {
                console.error('Add failed', err);
            }
        });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
