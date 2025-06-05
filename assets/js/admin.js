document.addEventListener('DOMContentLoaded', function() {
    console.log("Admin JavaScript initialisé.");

    // General confirmation for delete buttons with class 'confirm-delete'
    const deleteButtons = document.querySelectorAll('.confirm-delete, a.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            const message = this.dataset.confirmMessage || 'Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Slug generator (example for a 'title' input and a 'slug' input)
    const titleInput = document.getElementById('courseTitle'); // Assuming an input with id="courseTitle"
    const slugInput = document.getElementById('courseSlug');   // Assuming an input with id="courseSlug"

    if (titleInput && slugInput) {
        titleInput.addEventListener('keyup', function() {
            slugInput.value = generateSlug(this.value);
        });
    }

    function generateSlug(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }

    // Simple WYSIWYG editor initialization (if you add one like TinyMCE)
    // Example: if (typeof tinymce !== 'undefined') {
    //     tinymce.init({
    //         selector: 'textarea.wysiwyg',
    //         plugins: 'lists link image code help wordcount',
    //         toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | help'
    //     });
    // }

    // Preview image before upload
    const imageUploadInput = document.getElementById('courseThumbnailUpload'); // e.g. <input type="file" id="courseThumbnailUpload">
    const imagePreview = document.getElementById('thumbnailPreview'); // e.g. <img id="thumbnailPreview" src="#" alt="Preview">

    if (imageUploadInput && imagePreview) {
        imageUploadInput.addEventListener('change', function(event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block'; // Show preview
                }
                reader.readAsDataURL(event.target.files[0]);
            } else {
                imagePreview.src = '#'; // Reset or default image
                imagePreview.style.display = 'none';
            }
        });
    }

});