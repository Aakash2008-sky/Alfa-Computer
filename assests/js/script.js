// Delete confirmation modal handler
function confirmDelete(studentId, studentName) {
    const modal = document.getElementById('deleteModal');
    const deleteIdInput = document.getElementById('deleteStudentId');
    const deleteNameSpan = document.getElementById('deleteStudentName');

    if (modal && deleteIdInput && deleteNameSpan) {
        deleteIdInput.value = studentId;
        deleteNameSpan.innerText = studentName;
        modal.classList.add('active');
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

// Filter students dynamic search
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('studentSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.student-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
});
