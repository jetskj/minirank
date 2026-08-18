document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const trendFilter = document.getElementById('trend-filter');
    const rangeFilter = document.getElementById('range-filter');
    const tableBody = document.querySelector('tbody');

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function filterRows() {
        if (!tableBody) return;
        const term = searchInput ? searchInput.value.toLowerCase() : '';
        const trendValue = trendFilter ? trendFilter.value : 'All';
        const rangeValue = rangeFilter ? rangeFilter.value : 'All';
        const rows = tableBody.querySelectorAll('tr');

        rows.forEach(row => {
            const phraseCell = row.cells[0];
            const trendCell = row.querySelector('.kw-trend');
            const posCell = row.querySelector('.kw-pos');

            let matchesSearch = true;
            if (phraseCell) {
                const phrase = phraseCell.textContent.toLowerCase();
                matchesSearch = phrase.includes(term);
            }

            let matchesTrend = true;
            if (trendValue !== 'All') {
                const trendText = trendCell ? trendCell.textContent.trim() : '';
                matchesTrend = (trendText === trendValue);
            }

            let matchesRange = true;
            if (rangeValue !== 'All') {
                const posText = posCell ? posCell.textContent.trim() : '';
                const pos = (posText === '-' || posText === '') ? null : parseInt(posText, 10);
                if (pos === null) {
                    matchesRange = false;
                } else if (rangeValue === 'Top 3') {
                    matchesRange = (pos >= 1 && pos <= 3);
                } else if (rangeValue === 'Top 10') {
                    matchesRange = (pos >= 1 && pos <= 10);
                } else if (rangeValue === 'Top 50') {
                    matchesRange = (pos >= 1 && pos <= 50);
                } else if (rangeValue === '51+') {
                    matchesRange = (pos >= 51);
                }
            }

            row.style.display = (matchesSearch && matchesTrend && matchesRange) ? '' : 'none';
        });
    }

    // Live search filter
    if (searchInput) {
        searchInput.addEventListener('input', filterRows);
    }

    // Trend dropdown filter
    if (trendFilter) {
        trendFilter.addEventListener('change', filterRows);
    }

    // Range dropdown filter
    if (rangeFilter) {
        rangeFilter.addEventListener('change', filterRows);
    }

    // Project selector change
    const projectSelector = document.getElementById('project-selector');
    if (projectSelector) {
        projectSelector.addEventListener('change', (e) => {
            const projectId = e.target.value;
            window.location.href = `?project_id=${projectId}`;
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
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': getCsrfToken()
                        },
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
                            filterRows();
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
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': getCsrfToken()
                        },
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
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': getCsrfToken()
                        },
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

            const container = document.querySelector('.container');
            const projectId = container ? container.dataset.projectId : 1;

            try {
                const res = await fetch('api/keywords', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({ action: 'add', phrase, project_id: parseInt(projectId) })
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
                    filterRows();
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
