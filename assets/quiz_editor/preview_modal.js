function createPreviewModal(examId) {
    // Create modal container
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'settings-modal-overlay';
    modalOverlay.style.display = 'flex';

    // Create modal content
    const modalContent = document.createElement('div');
    modalContent.className = 'settings-modal-content';
    modalContent.style.width = '95%';
    modalContent.style.height = '90vh';
    modalContent.style.maxWidth = '1200px';
    modalContent.style.padding = '0';
    modalContent.style.display = 'flex';
    modalContent.style.flexDirection = 'column';

    // Create modal header
    const modalHeader = document.createElement('div');
    modalHeader.className = 'settings-modal-header';
    modalHeader.style.padding = '15px 20px';
    modalHeader.innerHTML = `
        <div class="settings-modal-title">
            <div class="settings-icon">
                <span class="material-symbols-rounded">visibility</span>
            </div>
            <div class="settings-text">
                <h2>Preview Exam</h2>
                <p>This is how students will see your exam</p>
            </div>
            <button class="close-modal">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
    `;

    // Create iframe for preview
    const previewFrame = document.createElement('iframe');
    previewFrame.src = `preview_exam.php?exam_id=${examId}`;
    previewFrame.style.flex = '1';
    previewFrame.style.width = '100%';
    previewFrame.style.border = 'none';

    // Assemble modal
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(previewFrame);
    modalOverlay.appendChild(modalContent);

    // Add to document
    document.body.appendChild(modalOverlay);

    // Handle close button
    const closeButton = modalHeader.querySelector('.close-modal');
    closeButton.addEventListener('click', () => {
        modalOverlay.remove();
    });

    // Handle click outside modal
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            modalOverlay.remove();
        }
    });
} 