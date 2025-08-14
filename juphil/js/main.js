document.addEventListener('DOMContentLoaded', function() {
    const addPhotoButton = document.getElementById('add-photo-field');
    if(addPhotoButton){
        addPhotoButton.addEventListener('click', function() {
            const photoUploadFields = document.getElementById('photo-upload-fields');
            const newItem = document.createElement('div');
            newItem.classList.add('photo-upload-item');
            newItem.innerHTML = `
                <div class="form-group">
                    <label>Photo</label>
                    <input type="file" name="photos[]" class="form-control">
                </div>
                <div class="form-group">
                    <label>Caption</label>
                    <input type="text" name="captions[]" class="form-control">
                </div>
            `;
            photoUploadFields.appendChild(newItem);
        });
    }
});