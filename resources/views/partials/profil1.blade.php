<div class="modal fade" id="profileImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:20px;">
            <div class="modal-header">
                <h5 class="modal-title">Photo de profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body text-center">
                <div class="d-flex justify-content-center mb-3">
                    <div id="profilePreviewWrap"
                         style="width:170px;height:170px;border-radius:50%;overflow:hidden;border:4px solid #dbeafe;background:#f8fafc;display:flex;align-items:center;justify-content:center;">
                        @if($profileImage)
                            <img id="profilePreview" src="{{ $profileImage }}" alt="Aperçu"
                                 style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div id="profilePreviewDefault">
                                <svg viewBox="0 0 40 40" fill="none" width="95" height="95">
                                    <circle cx="20" cy="20" r="20" fill="#dbeafe"/>
                                    <circle cx="20" cy="15" r="7" fill="#60a5fa"/>
                                    <ellipse cx="20" cy="33" rx="12" ry="7" fill="#60a5fa"/>
                                    <circle cx="20" cy="15" r="5.5" fill="#1d4ed8"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                </div>

                <form id="profileImageForm" enctype="multipart/form-data">
                    @csrf

                    <input type="file" name="profile_image" id="profile_image" accept="image/*" hidden>

                    <button type="button" class="btn btn-outline-primary mb-3" id="chooseProfileImage">
                        Choisir une image
                    </button>

                    <div class="small text-muted mb-3">
                        JPG, PNG, WEBP — max 2 Mo
                    </div>

                    <div id="profileImageError" class="text-danger mb-3" style="display:none;"></div>

                    <button type="submit" class="btn btn-primary w-100" id="saveProfileImageBtn">
                        Valider
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('openProfileModal');
    const modalEl = document.getElementById('profileImageModal');
    const modal = new bootstrap.Modal(modalEl);

    const chooseBtn = document.getElementById('chooseProfileImage');
    const fileInput = document.getElementById('profile_image');
    const form = document.getElementById('profileImageForm');
    const previewWrap = document.getElementById('profilePreviewWrap');
    const errorBox = document.getElementById('profileImageError');
    const saveBtn = document.getElementById('saveProfileImageBtn');

    const userId = {{ auth()->id() ?? 0 }};
    const localKey = 'profile_image_url_' + userId;

    const headerAvatar = document.querySelector('#headerProfileImage');
    const headerSvg = document.querySelector('#headerProfileSvg');

    let previewImg = document.getElementById('profilePreview');
    let defaultPreview = document.getElementById('profilePreviewDefault');

    const savedUrl = localStorage.getItem(localKey);
    if (savedUrl && headerAvatar) {
        headerAvatar.src = savedUrl;
    }

    openBtn.addEventListener('click', function () {
        modal.show();
    });

    chooseBtn.addEventListener('click', function () {
        fileInput.click();
    });

    fileInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            if (defaultPreview) {
                defaultPreview.remove();
                defaultPreview = null;
            }

            if (!previewImg) {
                previewImg = document.createElement('img');
                previewImg.id = 'profilePreview';
                previewImg.style.width = '100%';
                previewImg.style.height = '100%';
                previewImg.style.objectFit = 'cover';
                previewWrap.innerHTML = '';
                previewWrap.appendChild(previewImg);
            }

            previewImg.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        errorBox.style.display = 'none';
        errorBox.textContent = '';
        saveBtn.disabled = true;
        saveBtn.textContent = 'Enregistrement...';

        try {
            const formData = new FormData(form);

            const response = await fetch("{{ route('profile.image.update') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                let message = data.message || 'Erreur lors de l’enregistrement.';
                if (data.errors && data.errors.profile_image) {
                    message = data.errors.profile_image[0];
                }

                errorBox.textContent = message;
                errorBox.style.display = 'block';
                return;
            }

            if (headerAvatar) {
                headerAvatar.src = data.image_url;
            } else if (headerSvg) {
                headerSvg.outerHTML = `<img src="${data.image_url}" alt="Profil" id="headerProfileImage" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">`;
            }

            localStorage.setItem(localKey, data.image_url);

            modal.hide();

        } catch (error) {
            errorBox.textContent = 'Une erreur est survenue.';
            errorBox.style.display = 'block';
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Valider';
        }
    });
});
</script>
