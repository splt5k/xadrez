// JavaScript para rolar para o jogador correspondente na tabela
const searchInput = document.getElementById('search');
const table = document.querySelector('.chess-table');
const originalTableHTML = table.innerHTML;

searchInput.addEventListener('input', () => {
    const searchTerm = searchInput.value.trim().toLowerCase();
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const playerName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        if (playerName.includes(searchTerm)) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });

    // Rolar para o primeiro jogador correspondente
    const firstVisibleRow = table.querySelector('tbody tr[style="display: table-row;"]');
    if (firstVisibleRow) {
        firstVisibleRow.scrollIntoView({ behavior: 'smooth' });
    }
    
    if (searchTerm === "") {
        // Restaurar a tabela original quando a pesquisa for limpa
        table.innerHTML = originalTableHTML;
    }
});
