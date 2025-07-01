(function() {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFileUploadButton);
    } else {
        initFileUploadButton();
    }

    function initFileUploadButton() {
        if (typeof fileUploadActive === 'undefined' || !fileUploadActive) {
            return;
        }
        observeButtonsContainerUpload();
    }

    function observeButtonsContainerUpload() {
        const buttonsContainer = document.getElementById('buttons');
        if (buttonsContainer) {
            const hasButtons = buttonsContainer.querySelector('.button');
            if (hasButtons && !buttonsContainer.querySelector('.upload-file-btn-dyn')) {
                const currentDirectory = typeof Sparent !== 'undefined' ? Sparent : '/';
                addUploadButton(currentDirectory);
            }

            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        const hasButtons = buttonsContainer.querySelector('.button');
                        if (hasButtons && !buttonsContainer.querySelector('.upload-file-btn-dyn')) {
                            const currentDirectory = typeof Sparent !== 'undefined' ? Sparent : '/';
                            addUploadButton(currentDirectory);
                        }
                    }
                });
            });
            observer.observe(buttonsContainer, { childList: true });
        }
    }

    function addUploadButton(directory) {
        try {
            const fileList = document.getElementById('buttons');
            if (!fileList) return;
            const existingButton = fileList.querySelector('.upload-file-btn-dyn');
            if (existingButton) return;
            const uploadBtn = document.createElement('button');
            uploadBtn.textContent = '📁 Téléverser un document';
            uploadBtn.classList.add('upload-file-btn-dyn', 'upload-file-button', 'button');
            uploadBtn.addEventListener('click', function () {
                window.location.href = 'modules/FileUpload/view.php';
            });
            fileList.appendChild(uploadBtn);
        } catch (error) {
            console.error('Erreur lors de l\'ajout du bouton Téléverser un document:', error);
        }
    }
})(); 