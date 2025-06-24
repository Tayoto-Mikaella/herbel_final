document.addEventListener('DOMContentLoaded', function() {
    const addModal = document.getElementById('addAdminModal');
    const editModal = document.getElementById('editAdminModal');
    const deleteModal = document.getElementById('deleteConfirmModal');

    const tableBody = document.querySelector('.user-table tbody');

    // --- Modal Control ---
    const openModal = (modal) => modal.style.display = 'flex';
    const closeModal = (modal) => modal.style.display = 'none';

    document.getElementById('addAdminBtn')?.addEventListener('click', () => openModal(addModal));
    addModal?.querySelector('.close-add-modal')?.addEventListener('click', () => closeModal(addModal));
    editModal?.querySelector('.close-edit-modal')?.addEventListener('click', () => closeModal(editModal));
    deleteModal?.querySelector('#cancelDelete')?.addEventListener('click', () => closeModal(deleteModal));
    
    window.addEventListener('click', (event) => {
        if (event.target == addModal) closeModal(addModal);
        if (event.target == editModal) closeModal(editModal);
        if (event.target == deleteModal) closeModal(deleteModal);
    });

    // --- Main Actions (Edit and Delete) ---
    tableBody.addEventListener('click', function(e) {
        const editButton = e.target.closest('.edit-btn');
        const deleteButton = e.target.closest('.delete-btn');

        if (editButton) {
            handleEdit(editButton);
        }

        if (deleteButton) {
            handleDelete(deleteButton);
        }
    });

    function handleEdit(button) {
        const userId = button.dataset.id;
        fetch(`get_user.php?id=${userId}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const user = result.data;
                    document.getElementById('edit-user-id').value = user.User_ID;
                    document.getElementById('edit-name').value = user.Name;
                    document.getElementById('edit-email').value = user.Email;
                    document.getElementById('edit-status').value = user.Status;
                    document.getElementById('edit-password').value = '';
                    openModal(editModal);
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => console.error('Fetch error:', error));
    }

    function handleDelete(button) {
        const userId = button.dataset.id;
        const userName = button.dataset.name;
        
        const confirmText = deleteModal.querySelector('#delete-confirm-text');
        confirmText.textContent = `Do you really want to delete the admin account for '${userName}'? This action cannot be undone.`;
        
        openModal(deleteModal);
        
        const confirmBtn = document.getElementById('confirmDelete');
        
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', () => {
            fetch('delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: userId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    document.getElementById(`user-row-${userId}`).remove();
                } else {
                    alert('Error: ' + result.message);
                }
                closeModal(deleteModal);
            })
            .catch(error => {
                console.error('Fetch error:', error);
                closeModal(deleteModal);
            });
        });
    }
});